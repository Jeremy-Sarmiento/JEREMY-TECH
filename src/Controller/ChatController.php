<?php

declare(strict_types=1);

namespace Controller;

use Model\ChatbotFaq;
use Model\Cuenta;
use Model\Envio;
use Model\LimiteIntentos;
use Model\Pedido;
use Model\Producto;

/**
 * Chatbot de la tienda (reglas locales + IA opcional).
 *
 * Reglas de seguridad:
 *  - El endpoint de mensajes exige POST (el CSRF ya lo valida el router).
 *  - Los pedidos solo se consultan si el mensaje incluye un código #JT-00000.
 *  - La consulta de pedido se limita al cliente autenticado; el código es
 *    derivado del id y no debe permitir enumerar pedidos ajenos.
 *  - La clave de IA se lee del entorno y nunca se envía al navegador.
 */
class ChatController
{
    private const MAX_HISTORIAL = 10;
    private const WHATSAPP = '51997833866';
    private const CORREO = 'jeresarmientotarojo@gmail.com';

    /** Token CSRF para el widget. */
    public function token(): void
    {
        \Security\Proteccion::iniciarSesion();
        $this->json(['token' => \Security\Proteccion::token()]);
    }

    /** Punto de entrada del chat. */
    public function message(): void
    {
        \Security\Proteccion::exigirPost();

        // Cada mensaje puede acabar en una llamada de pago a la IA: 30 por
        // minuto y por IP evitan que un script agote la cuota.
        $llaveChat = 'ip:' . LimiteIntentos::ipActual();
        $restanteChat = LimiteIntentos::restante('chat', $llaveChat);
        if ($restanteChat > 0) {
            $this->json([
                'reply' => 'Demasiadas consultas seguidas. Espera un momento y vuelve a intentarlo.',
            ], 429);
        }
        LimiteIntentos::registrar('chat', $llaveChat, LimiteIntentos::ipActual(), 30, 60, 60);

        $entrada = $_POST['message'] ?? '';
        $texto = is_string($entrada) ? trim($entrada) : '';
        if ($texto === '' || mb_strlen($texto) > 1000) {
            $this->json(['reply' => 'Mensaje inválido. Escribe una consulta de hasta 1000 caracteres.'], 422);
        }

        try {
            // 1) Reglas críticas solo para pedidos con código explícito (#JT-NNNNN).
            //    Todo lo demás — productos, envíos, comparaciones, etc. — lo maneja
            //    la IA con function calling.
            $respuesta = $this->ruleCritical($texto);
            if ($respuesta !== null) {
                $this->registrarPregunta($texto, 'pedido', 0);
                $this->responder($texto, $respuesta);
            }

            // 2) Preguntas frecuentes gestionadas desde el panel (chatbot_faq).
            //    Se consultan antes de la IA para que las preguntas comunes se
            //    respondan con la base de conocimiento administrable.
            $faq = ChatbotFaq::buscar($texto);
            if ($faq !== null) {
                $respuesta = [
                    'reply' => (string) ($faq['respuesta'] ?? ''),
                    'links' => $this->defaultLinks(),
                    'quick' => $this->defaultQuickReplies(),
                ];
                $this->registrarPregunta($texto, 'faq', (int) ($faq['id'] ?? 0));
                $this->responder($texto, $respuesta);
            }

            // 3) IA con function calling: decide qué herramientas usar (buscar
            //    productos, obtener envíos, consultar pedido) y responde con datos reales.
            if (env('CHAT_API_KEY') !== '') {
                $respuestaIA = $this->askAI($texto);
                if ($respuestaIA !== null) {
                    $this->registrarPregunta($texto, 'ia', 0);
                    $this->responder($texto, array_merge(
                        ['quick' => $this->defaultQuickReplies()],
                        $respuestaIA
                    ));
                }
            }

            // 4) Fallback si la IA no está disponible o falla.
            $this->registrarPregunta($texto, 'fallback', 0);
            $this->responder($texto, $this->fallback());
        } catch (\Throwable $error) {
            error_log('Chat: ' . $error->getMessage());
            $this->responder($texto, $this->fallback());
        }
    }

    // ================= REGLAS CRÍTICAS (BD + sesión) =================

    /**
     * Solo intercepta el código de pedido explícito (#JT-NNNNN) porque requiere
     * verificar sesión y consultar BD antes de que la IA pueda responder.
     * Todo lo demás lo maneja la IA con function calling.
     */
    private function ruleCritical(string $texto): ?array
    {
        if (preg_match('/#?\s*JT\s*-\s*\d{1,10}/iu', $texto, $coincidencia)) {
            $codigo = strtoupper((string) preg_replace('/\s+/', '', $coincidencia[0]));
            return $this->consultarPedido($codigo);
        }

        return null;
    }

    private function consultarPedido(string $codigo): array
    {
        $usuario = Cuenta::usuarioActual();
        if ($usuario === null) {
            return [
                'reply' => 'Para consultar el pedido ' . $codigo . ' necesito que inicies sesión. '
                    . 'El seguimiento está asociado a tu cuenta por seguridad. Después vuelve a enviarme el código.',
                'links' => [['label' => '👤 Iniciar sesión', 'url' => '?ruta=login']],
                'quick' => $this->defaultQuickReplies(),
            ];
        }

        $pedido = Pedido::porCodigoDeCliente($codigo, (int) ($usuario['id'] ?? 0));
        if ($pedido === null) {
            return [
                'reply' => 'No encontré el pedido ' . $codigo . ' en tu cuenta. Verifica el código o revisa tu historial.',
                'links' => [['label' => '📦 Ver mis pedidos', 'url' => '?ruta=carrito#historial']],
                'quick' => $this->defaultQuickReplies(),
            ];
        }

        $etiquetas = [
            'Pendiente' => '⏳ Pendiente de pago',
            'Pagado'    => '✅ Pago confirmado',
            'Enviado'   => '🚚 En camino',
            'Entregado' => '🎉 Entregado',
            'Cancelado' => '❌ Cancelado',
        ];

        $estado = (string) ($pedido['estado'] ?? 'Pendiente');
        $lineas = [
            '📦 Pedido ' . Pedido::codigo((int) $pedido['id']),
            'Estado actual: ' . ($etiquetas[$estado] ?? $estado),
            'Total: S/ ' . number_format((float) ($pedido['total_pen'] ?? 0), 2),
            'Fecha: ' . $this->fecha((string) ($pedido['creado_en'] ?? '')),
        ];

        $zona = trim((string) ($pedido['envio_zona'] ?? ''));
        if ($zona !== '') {
            $lineas[] = 'Entrega: ' . $zona;
        }

        $courier = trim((string) ($pedido['courier'] ?? ''));
        $guia = trim((string) ($pedido['guia_numero'] ?? ''));
        if ($courier !== '' || $guia !== '') {
            $lineas[] = 'Envío: ' . trim($courier . ($guia !== '' ? ' · Guía ' . $guia : ''));
        }

        return [
            'reply' => implode("\n", $lineas),
            'links' => [['label' => '🔎 Ver historial y seguimiento', 'url' => '?ruta=carrito#historial']],
            'quick' => $this->defaultQuickReplies(),
        ];
    }

    private function respuestaEnvios(): array
    {
        $lineas = ['🚚 Envíos y entregas:'];
        $zonas = Envio::zonas();

        foreach (array_slice($zonas, 0, 6) as $zona) {
            $costo = (float) ($zona['costo'] ?? 0);
            $dias = trim((string) ($zona['dias_estimados'] ?? ''));
            $lineas[] = '• ' . (string) $zona['zona'] . ': '
                . ($costo <= 0 ? 'sin costo' : 'S/ ' . number_format($costo, 2))
                . ($dias !== '' ? ' (' . $dias . ')' : '');
        }

        if ($zonas === []) {
            $lineas[] = 'Consulta las zonas disponibles al finalizar la compra.';
        }

        $lineas[] = 'El costo final se calcula según la zona elegida en el checkout.';

        return [
            'reply' => implode("\n", $lineas),
            'links' => [
                ['label' => '📄 Ver política completa', 'url' => '?ruta=pagina&p=envios'],
                ['label' => '🛒 Ir al checkout', 'url' => '?ruta=carrito'],
            ],
            'quick' => $this->defaultQuickReplies(),
        ];
    }

    private function consultaProducto(string $texto): ?string
    {
        // Si es una comparación o pregunta de consejo, ceder a la IA
        if (preg_match('/\b(mejor|versus|vs\.?|comparar|diferencia|cual es mejor|cuál es mejor|qué es mejor|que es mejor|me conviene|recomien|debo comprar|deberia|debería)\b/iu', $texto)) {
            return null;
        }

        $patrones = [
            '/(?:busco|buscar|quiero|quisiera|necesito|recomiendas?|recomi[eé]nda(?:me)?|tienes|hay|mu[eé]strame|precio de|cu[aá]nto cuesta)\s+(.{2,60})/iu',
            '/\b(laptop|smartphone|celular|tel[eé]fono|aud[ií]fono|smartwatch|reloj|consola|teclado|mouse|parlante|gamer|gaming|apple|samsung|xiaomi|iphone|ipad|airpods|macbook|sony|bose|jbl|logitech|playstation|xbox|hp|lenovo)\b/iu',
        ];

        foreach ($patrones as $indice => $patron) {
            if (!preg_match($patron, $texto, $coincidencia)) {
                continue;
            }

            $consulta = $indice === 0 ? trim($coincidencia[1]) : $texto;
            $consulta = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $consulta));

            return $consulta !== '' ? $consulta : null;
        }

        return null;
    }

    private function respuestaProductos(string $consulta): array
    {
        $resultados = Producto::buscar($consulta, 6);

        if ($resultados === []) {
            return [
                'reply' => 'No encontré productos que coincidan con "' . $consulta . '" 😕. '
                    . 'Prueba con otra palabra o revisa el catálogo completo.',
                'links' => [['label' => '🛍️ Ver catálogo', 'url' => '?ruta=catalogo']],
                'quick' => $this->defaultQuickReplies(),
            ];
        }

        return $this->listaProductos(
            $resultados,
            '🔍 Encontré ' . count($resultados) . ' producto' . (count($resultados) !== 1 ? 's' : '') . ' para ti:'
        );
    }

    /**
     * Detecta intenciones de listado: más baratos, más caros, por categoría,
     * rango de precio, novedades, etc. Devuelve null si no aplica.
     */
    private function consultaListado(string $texto): ?array
    {
        $t = mb_strtolower($texto);

        // ── Si es una comparación entre marcas/productos, ceder a la IA ──
        if (preg_match('/\b(mejor|versus|vs\.?|o el|o la|comparar|diferencia entre|cual es mejor|cuál es mejor|qué es mejor|que es mejor|me conviene)\b/iu', $t)) {
            return null;
        }

        // ── Mapa de categorías ──
        $categorias = [
            '/\b(celular|cel|smartphone|iphone|samsung|xiaomi|android)/iu' => 'Celulares',
            '/\b(laptop|computadora|pc|notebook|macbook|lenovo|hp\b)/iu'   => 'Laptops',
            '/\b(audifono|auricular|audifonos|auriculares|cascos|earbuds|airpods|bose|sony|jbl)/iu' => 'Audifonos',
            '/\b(smartwatch|reloj inteligente|apple watch|galaxy watch|xiaomi band)/iu' => 'Smartwatches',
            '/\b(tablet|ipad|galaxy tab)/iu'   => 'Tablets',
            '/\b(gaming|gamer|mando|control|joystick|playstation|xbox|dualsense)/iu' => 'Gaming',
            '/\b(accesorio|mouse|teclado|cargador|cable|funda|parlante|bocina|monitor)/iu' => 'Accesorios',
        ];

        $categoriaDetectada = null;
        foreach ($categorias as $patron => $cat) {
            if (preg_match($patron, $texto)) {
                $categoriaDetectada = $cat;
                break;
            }
        }

        // ── Rango de precio explícito: "menos de 500", "hasta 300", "entre 200 y 800" ──
        $precioMin = null;
        $precioMax = null;

        if (preg_match('/entre\s+(\d+(?:[.,]\d+)?)\s*(?:y|a)\s*(\d+(?:[.,]\d+)?)\s*(?:soles?|pen|s\/)?/iu', $t, $m)) {
            $precioMin = (float) str_replace(',', '.', $m[1]);
            $precioMax = (float) str_replace(',', '.', $m[2]);
        } elseif (preg_match('/(?:menos de|hasta|no m[aá]s de|por menos de|max(?:imo)?\.?)\s*(?:s\/\s*)?(\d+(?:[.,]\d+)?)/iu', $t, $m)) {
            $precioMax = (float) str_replace(',', '.', $m[1]);
        } elseif (preg_match('/(?:m[aá]s de|desde|a partir de|min(?:imo)?\.?)\s*(?:s\/\s*)?(\d+(?:[.,]\d+)?)/iu', $t, $m)) {
            $precioMin = (float) str_replace(',', '.', $m[1]);
        }

        // ── Intención de orden ──
        $esMasBarato = preg_match('/(m[aá]s\s+barato|m[aá]s\s+econ[oó]mico|m[aá]s\s+baratos|econ[oó]mico|barato|baratos|econ[oó]micos|precio\s+bajo|m[aá]s\s+accesible|accesible)/iu', $t);
        $esMasCaro   = preg_match('/(m[aá]s\s+caro|m[aá]s\s+caros|premium|alto\s+gama|mejor\s+calidad|el\s+mejor|los\s+mejores|m[aá]s\s+costoso)/iu', $t);

        // ── Solo actúa si hay alguna intención clara ──
        $tieneIntencion = $esMasBarato || $esMasCaro || $precioMin !== null || $precioMax !== null
            || preg_match('/(lista|listar|muestra|mu[eé]strame|ver todos|todos los|qu[eé] .+ tienes|qu[eé] .+ hay|todos?|todo|completo)/iu', $t);

        if (!$tieneIntencion && $categoriaDetectada === null) {
            return null;
        }

        // Si solo detectó categoría sin intención de listado, deja que consultaProducto lo maneje
        if (!$tieneIntencion && $categoriaDetectada !== null) {
            return null;
        }

        // ── Construir filtros ──
        $orden = 'relevancia';
        if ($esMasBarato || $precioMax !== null) {
            $orden = 'precio_asc';
        } elseif ($esMasCaro) {
            $orden = 'precio_desc';
        }

        $filtros = ['orden' => $orden, 'limite' => 6];
        if ($categoriaDetectada !== null) {
            $filtros['categoria'] = $categoriaDetectada;
        }
        if ($precioMin !== null) {
            $filtros['precio_min'] = $precioMin;
        }
        if ($precioMax !== null) {
            $filtros['precio_max'] = $precioMax;
        }

        $resultados = Producto::catalogoFiltrado($filtros);

        // Sin resultados → ceder a la IA
        if ($resultados === []) {
            return null;
        }

        // ── Armar título descriptivo ──
        $titulo = '🛍️ ';
        if ($esMasBarato) {
            $titulo .= 'Los más económicos';
        } elseif ($esMasCaro) {
            $titulo .= 'Los más premium';
        } else {
            $titulo .= 'Productos disponibles';
        }
        if ($categoriaDetectada !== null) {
            $titulo .= ' en ' . $categoriaDetectada;
        }
        if ($precioMin !== null && $precioMax !== null) {
            $titulo .= ' (S/ ' . number_format($precioMin, 0) . ' – S/ ' . number_format($precioMax, 0) . ')';
        } elseif ($precioMax !== null) {
            $titulo .= ' (hasta S/ ' . number_format($precioMax, 0) . ')';
        } elseif ($precioMin !== null) {
            $titulo .= ' (desde S/ ' . number_format($precioMin, 0) . ')';
        }
        $titulo .= ':';

        $urlCatalogo = '?ruta=catalogo';
        if ($categoriaDetectada !== null) {
            $urlCatalogo .= '&categoria=' . rawurlencode($categoriaDetectada);
        }

        return $this->listaProductos($resultados, $titulo, $urlCatalogo);
    }

    /**
     * Genera la respuesta JSON con una lista de productos hidratados.
     *
     * @param array<int, array<string, mixed>> $resultados
     */
    private function listaProductos(array $resultados, string $titulo, string $urlCatalogo = '?ruta=catalogo'): array
    {
        $enlaces = [];
        foreach ($resultados as $producto) {
            $enlaces[] = [
                'label' => '🛍️ ' . (string) $producto['nombre']
                    . ' — S/ ' . number_format((float) $producto['precioPEN'], 2),
                'url' => '?ruta=producto&slug=' . rawurlencode((string) $producto['slug']),
            ];
        }

        return [
            'reply' => $titulo,
            'links' => $enlaces,
            'extra_links' => [['label' => '🛍️ Ver catálogo completo', 'url' => $urlCatalogo]],
            'quick' => $this->defaultQuickReplies(),
        ];
    }

    private function fallback(): array
    {
        return [
            'reply' => "No tengo una respuesta exacta para eso 🤔, pero puedo ayudarte con:\n"
                . "• Seguimiento de pedidos (con tu código #JT-00000)\n"
                . "• Búsqueda de productos\n"
                . "• Envíos, pagos, garantías y contacto\n\n"
                . 'También puedes escribirnos por WhatsApp.',
            'links' => [
                ['label' => '💬 Escribir por WhatsApp', 'url' => $this->whatsapp('Hola, necesito ayuda con Jeremy Tech.')],
                ['label' => '🛍️ Ver catálogo', 'url' => '?ruta=catalogo'],
            ],
            'quick' => $this->defaultQuickReplies(),
        ];
    }

    private function defaultQuickReplies(): array
    {
        return [
            ['label' => '📦 ¿Dónde está mi pedido?', 'text' => '¿Dónde está mi pedido?'],
            ['label' => '🚚 Envíos', 'text' => 'Envíos'],
            ['label' => '💳 Pagos', 'text' => 'Métodos de pago'],
            ['label' => '🔄 Devoluciones', 'text' => 'Devoluciones'],
            ['label' => '🛍️ Ver productos', 'text' => 'Ver productos'],
        ];
    }

    private function defaultLinks(): array
    {
        return [
            ['label' => '💬 Escribir por WhatsApp', 'url' => $this->whatsapp('Hola, necesito ayuda con Jeremy Tech.')],
            ['label' => '🛍️ Ver catálogo', 'url' => '?ruta=catalogo'],
        ];
    }

    private function registrarPregunta(string $texto, string $origen, int $faqId): void
    {
        try {
            \Model\Analitica::registrar(
                'pregunta_chatbot',
                [
                    'faq_id' => $faqId,
                    'origen' => $origen,
                ],
                'chatbot',
                \Model\Cuenta::idSesion()
            );
        } catch (\Throwable $error) {
            error_log('Analítica chatbot: ' . $error->getMessage());
        }
    }

    // ================= HISTORIAL =================

    private function responder(string $textoUsuario, array $respuesta): void
    {
        $this->pushHistory('user', $textoUsuario);
        $this->pushHistory('bot', (string) ($respuesta['reply'] ?? ''), [
            'links' => $respuesta['links'] ?? [],
            'extra_links' => $respuesta['extra_links'] ?? [],
        ]);
        $this->json($respuesta);
    }

    private function pushHistory(string $role, string $content, array $meta = []): void
    {
        $historial = $_SESSION['chat_history'] ?? [];
        if (!is_array($historial)) {
            $historial = [];
        }

        $entrada = [
            'role' => $role === 'bot' ? 'assistant' : 'user',
            'content' => $content,
        ];

        foreach (['links', 'extra_links'] as $clave) {
            if (!empty($meta[$clave]) && is_array($meta[$clave])) {
                $entrada[$clave] = $meta[$clave];
            }
        }

        $historial[] = $entrada;
        $_SESSION['chat_history'] = array_slice($historial, -self::MAX_HISTORIAL);
    }

    // ================= IA CON FUNCTION CALLING =================

    /**
     * Llama a la IA (Gemini o OpenRouter/OpenAI-compat) con herramientas declaradas.
     * La IA decide cuándo llamar cada herramienta; PHP ejecuta la consulta real.
     * Soporta hasta 3 rondas de function calling antes de forzar respuesta final.
     *
     * @return array{reply: string, links?: array, extra_links?: array}|null
     */
    private function askAI(string $texto): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $proveedor = strtolower(trim(env('CHAT_AI_PROVIDER', 'gemini')));
        $clave     = env('CHAT_API_KEY');
        $modelo    = trim(env('CHAT_MODEL', ''));
        if ($modelo === '') {
            $modelo = ($proveedor === 'gemini') ? 'gemini-3.5-flash' : (($proveedor === 'openrouter') ? 'openai/gpt-4o' : 'gpt-4o-mini');
        }

        if ($clave === '') {
            return null;
        }

        $historial = $_SESSION['chat_history'] ?? [];
        if (!is_array($historial)) {
            $historial = [];
        }

        $sistema = 'Eres Jeremy-Bot, el asistente virtual de Jeremy Tech, tienda de tecnología en La Joya, Arequipa, Perú. '
            . 'Cuando el usuario pregunte por productos, precios, comparaciones o recomendaciones, USA la herramienta buscar_productos para obtener datos reales ANTES de responder. '
            . 'Cuando pregunten por envíos o tarifas, usa obtener_envios. '
            . 'Cuando den un código de pedido (#JT-NNNNN), usa consultar_pedido. '
            . 'Responde en español, de forma natural, amable y con algún emoji. '
            . 'Puedes opinar, comparar, recomendar y explicar tecnología con libertad. '
            . 'Texto plano: sin Markdown, sin asteriscos para negritas, sin #, sin HTML. Usa • para listas. '
            . 'Precios siempre en soles peruanos (S/). '
            . 'Pagos: tarjeta, Yape/Plin, transferencia, contra entrega. Checkout en modo demo. '
            . 'Garantía y devoluciones: WhatsApp 997833866. Correo: jeresarmientotarojo@gmail.com. '
            . 'Nunca inventes stock, precios exactos ni estados de pedidos.';

        // Herramientas en formato OpenAI (compatible con OpenRouter y Gemini vía OpenAI-compat)
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_productos',
                    'description' => 'Busca productos en la tienda. Úsala para preguntas sobre productos, comparaciones, recomendaciones o listados.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'consulta'   => ['type' => 'string',  'description' => 'Texto de búsqueda: nombre, marca, modelo o características'],
                            'orden'      => ['type' => 'string',  'description' => 'precio_asc (más baratos), precio_desc (más caros), relevancia'],
                            'categoria'  => ['type' => 'string',  'description' => 'Celulares, Laptops, Audifonos, Smartwatches, Tablets, Gaming, Accesorios'],
                            'precio_max' => ['type' => 'number',  'description' => 'Precio máximo en soles'],
                            'precio_min' => ['type' => 'number',  'description' => 'Precio mínimo en soles'],
                            'limite'     => ['type' => 'integer', 'description' => 'Cantidad de resultados (1-8)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'obtener_envios',
                    'description' => 'Obtiene las tarifas y zonas de envío disponibles.',
                    'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_pedido',
                    'description' => 'Consulta el estado de un pedido por su código #JT-NNNNN.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'codigo' => ['type' => 'string', 'description' => 'Código del pedido, ej: #JT-00001'],
                        ],
                        'required' => ['codigo'],
                    ],
                ],
            ],
        ];

        // Historial de conversación en formato OpenAI
        $mensajes = [['role' => 'system', 'content' => $sistema]];
        foreach ($historial as $entrada) {
            if (!is_array($entrada) || !isset($entrada['role'], $entrada['content'])) {
                continue;
            }
            $mensajes[] = [
                'role'    => $entrada['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $entrada['content'],
            ];
        }
        $mensajes[] = ['role' => 'user', 'content' => $texto];

        // Endpoint y cabeceras según proveedor
        if ($proveedor === 'openrouter') {
            $endpoint  = 'https://openrouter.ai/api/v1/chat/completions';
            $cabeceras = [
                'Authorization: Bearer ' . $clave,
                'HTTP-Referer: https://jeremytech.pe',
                'X-Title: Jeremy-Tech',
            ];
        } elseif ($proveedor === 'openai') {
            $endpoint  = 'https://api.openai.com/v1/chat/completions';
            $cabeceras = ['Authorization: Bearer ' . $clave];
        } elseif ($proveedor === 'deepseek') {
            $endpoint  = 'https://api.deepseek.com/v1/chat/completions';
            $cabeceras = ['Authorization: Bearer ' . $clave];
        } else {
            // Gemini vía endpoint OpenAI-compatible
            $endpoint  = 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions';
            $cabeceras = ['Authorization: Bearer ' . $clave];
        }

        $linksAcumulados      = [];
        $extraLinksAcumulados = [];

        // Hasta 3 rondas de function calling
        for ($ronda = 0; $ronda < 3; $ronda++) {
            $payload = [
                'model'       => $modelo,
                'messages'    => $mensajes,
                'tools'       => $tools,
                'tool_choice' => 'auto',
                'max_tokens'  => 1200,
                'temperature' => 0.7,
            ];

            $respuesta = $this->httpJson('POST', $endpoint, $payload, $cabeceras);
            if ($respuesta === null && $proveedor === 'gemini' && !str_contains($payload['model'], 'lite')) {
                $payload['model'] = 'gemini-3.5-flash-lite';
                $respuesta = $this->httpJson('POST', $endpoint, $payload, $cabeceras);
            }
            if ($respuesta === null) {
                return null;
            }

            $choice  = $respuesta['choices'][0] ?? null;
            if ($choice === null) {
                return null;
            }

            $message    = $choice['message'] ?? [];
            $toolCalls  = $message['tool_calls'] ?? [];
            $contenido  = $message['content'] ?? null;
            $finReason  = $choice['finish_reason'] ?? '';

            // ── Respuesta final de texto ──
            if (empty($toolCalls) || $finReason === 'stop') {
                if (!is_string($contenido) || trim($contenido) === '') {
                    return null;
                }
                return [
                    'reply'       => $this->limpiarRespuestaIA($contenido),
                    'links'       => $linksAcumulados,
                    'extra_links' => $extraLinksAcumulados,
                ];
            }

            // ── Ejecutar tool calls ──
            // Agregar el mensaje del asistente con los tool_calls al historial
            $mensajes[] = $message;

            foreach ($toolCalls as $tc) {
                $tcId     = $tc['id'] ?? ('call_' . $ronda);
                $nombre   = $tc['function']['name'] ?? '';
                $argsRaw  = $tc['function']['arguments'] ?? '{}';
                $args     = json_decode($argsRaw, true) ?: [];

                [$textoResultado, $linksH, $extraLinksH] = $this->ejecutarHerramienta($nombre, $args);

                $linksAcumulados      = array_merge($linksAcumulados, $linksH);
                $extraLinksAcumulados = array_merge($extraLinksAcumulados, $extraLinksH);

                // Respuesta de la herramienta al historial
                $mensajes[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $tcId,
                    'content'      => $textoResultado,
                ];
            }
        }

        return null;
    }

    /**
     * Ejecuta una herramienta solicitada por la IA y devuelve
     * [string $descripcionParaIA, array $links, array $extraLinks].
     *
     * @return array{0: string, 1: array, 2: array}
     */
    private function ejecutarHerramienta(string $nombre, array $args): array
    {
        switch ($nombre) {
            case 'buscar_productos':
                $consulta  = trim((string) ($args['consulta'] ?? ''));
                $orden     = trim((string) ($args['orden'] ?? 'relevancia'));
                $categoria = trim((string) ($args['categoria'] ?? ''));
                $precioMin = isset($args['precio_min']) && is_numeric($args['precio_min'])
                    ? (float) $args['precio_min'] : null;
                $precioMax = isset($args['precio_max']) && is_numeric($args['precio_max'])
                    ? (float) $args['precio_max'] : null;
                $limite = isset($args['limite']) && is_numeric($args['limite'])
                    ? max(1, min(8, (int) $args['limite'])) : 5;

                $filtros = ['orden' => $orden, 'limite' => $limite];
                if ($categoria !== '') {
                    $filtros['categoria'] = $categoria;
                }
                if ($precioMin !== null) {
                    $filtros['precio_min'] = $precioMin;
                }
                if ($precioMax !== null) {
                    $filtros['precio_max'] = $precioMax;
                }

                // Si hay consulta de texto, usar buscar(); si solo filtros, usar catalogoFiltrado()
                if ($consulta !== '') {
                    $resultados = Producto::buscar($consulta, $limite);
                    // Si buscar no encontró nada pero hay categoría, intentar con catalogoFiltrado
                    if ($resultados === [] && $categoria !== '') {
                        $resultados = Producto::catalogoFiltrado($filtros);
                    }
                } else {
                    $resultados = Producto::catalogoFiltrado($filtros);
                }

                if ($resultados === []) {
                    return ['No se encontraron productos con esos criterios en la tienda.', [], []];
                }

                $lineas = [];
                $links  = [];
                foreach ($resultados as $p) {
                    $nombre_p = (string) $p['nombre'];
                    $precio   = number_format((float) $p['precioPEN'], 2);
                    $stock    = (int) $p['stock'];
                    $lineas[] = $nombre_p . ' — S/ ' . $precio . ($stock > 0 ? ' (en stock)' : ' (sin stock)');
                    $links[]  = [
                        'label' => '🛍️ ' . $nombre_p . ' — S/ ' . $precio,
                        'url'   => '?ruta=producto&slug=' . rawurlencode((string) $p['slug']),
                    ];
                }

                $descripcion = 'Productos encontrados (' . count($resultados) . '): ' . implode(' | ', $lineas);
                $extraLinks  = [['label' => '🛍️ Ver catálogo completo', 'url' => '?ruta=catalogo']];
                return [$descripcion, $links, $extraLinks];

            case 'obtener_envios':
                $zonas  = Envio::zonas();
                $lineas = [];
                foreach ($zonas as $zona) {
                    $costo = (float) ($zona['costo'] ?? 0);
                    $dias  = trim((string) ($zona['dias_estimados'] ?? ''));
                    $lineas[] = (string) $zona['zona'] . ': '
                        . ($costo <= 0 ? 'sin costo' : 'S/ ' . number_format($costo, 2))
                        . ($dias !== '' ? ' (' . $dias . ')' : '');
                }
                if ($lineas === []) {
                    return ['No hay tarifas de envío configuradas.', [], []];
                }
                $links = [
                    ['label' => '📄 Ver política de envíos', 'url' => '?ruta=pagina&p=envios'],
                    ['label' => '🛒 Ir al checkout', 'url' => '?ruta=carrito'],
                ];
                return ['Zonas y tarifas de envío: ' . implode(' | ', $lineas), $links, []];

            case 'consultar_pedido':
                $codigo  = strtoupper(trim((string) ($args['codigo'] ?? '')));
                $usuario = Cuenta::usuarioActual();
                if ($usuario === null) {
                    $links = [['label' => '👤 Iniciar sesión', 'url' => '?ruta=login']];
                    return ['El cliente no ha iniciado sesión. Pídele que inicie sesión para consultar su pedido.', $links, []];
                }
                $pedido = Pedido::porCodigoDeCliente($codigo, (int) ($usuario['id'] ?? 0));
                if ($pedido === null) {
                    $links = [['label' => '📦 Ver mis pedidos', 'url' => '?ruta=carrito#historial']];
                    return ['No se encontró el pedido ' . $codigo . ' para este cliente.', $links, []];
                }
                $etiquetas = [
                    'Pendiente' => 'Pendiente de pago',
                    'Pagado'    => 'Pago confirmado',
                    'Enviado'   => 'En camino',
                    'Entregado' => 'Entregado',
                    'Cancelado' => 'Cancelado',
                ];
                $estado  = (string) ($pedido['estado'] ?? 'Pendiente');
                $lineas  = [
                    'Código: ' . Pedido::codigo((int) $pedido['id']),
                    'Estado: ' . ($etiquetas[$estado] ?? $estado),
                    'Total: S/ ' . number_format((float) ($pedido['total_pen'] ?? 0), 2),
                    'Fecha: ' . $this->fecha((string) ($pedido['creado_en'] ?? '')),
                ];
                $zona    = trim((string) ($pedido['envio_zona'] ?? ''));
                if ($zona !== '') {
                    $lineas[] = 'Zona de entrega: ' . $zona;
                }
                $courier = trim((string) ($pedido['courier'] ?? ''));
                $guia    = trim((string) ($pedido['guia_numero'] ?? ''));
                if ($courier !== '' || $guia !== '') {
                    $lineas[] = 'Envío: ' . trim($courier . ($guia !== '' ? ' · Guía ' . $guia : ''));
                }
                $links = [['label' => '🔎 Ver historial', 'url' => '?ruta=carrito#historial']];
                return [implode('. ', $lineas), $links, []];

            default:
                return ['Herramienta desconocida: ' . $nombre, [], []];
        }
    }

    /**
     * La IA a veces devuelve Markdown aunque se le pida texto plano. El widget
     * lo muestra con textContent, así que se limpian las marcas más comunes.
     */
    private function limpiarRespuestaIA(string $texto): string
    {
        $texto = (string) preg_replace('/\\*\\*(.*?)\\*\\*/su', '$1', $texto);
        $texto = (string) preg_replace('/__(.*?)__/su', '$1', $texto);
        $texto = (string) preg_replace('/^\\s*#{1,6}\\s*/mu', '', $texto);
        $texto = (string) preg_replace('/`([^`]*)`/su', '$1', $texto);
        return trim($texto);
    }

    private function httpJson(string $metodo, string $url, array $payload, array $cabeceras): ?array
    {
        $cuerpo = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($cuerpo === false) {
            return null;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $cabeceras),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'JeremyTech-Chat/1.0',
        ]);

        $bruto = curl_exec($ch);
        $codigoHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($bruto === false || $codigoHttp >= 400) {
            error_log('Chat IA HTTP ' . $codigoHttp . ($bruto ? ': ' . substr($bruto, 0, 300) : ''));
            return null;
        }

        $datos = $bruto !== '' ? json_decode((string) $bruto, true) : null;

        return is_array($datos) ? $datos : null;
    }

    // ================= UTILIDADES =================

    private function json(array $datos, int $estado = 200): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function fecha(string $fecha): string
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return '—';
        }

        $timestamp = strtotime($fecha);
        return $timestamp === false ? $fecha : date('d/m/Y', $timestamp);
    }

    private function whatsapp(string $mensaje): string
    {
        return 'https://wa.me/' . self::WHATSAPP . '?text=' . rawurlencode($mensaje);
    }
}
