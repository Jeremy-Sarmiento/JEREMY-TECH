<?php
declare(strict_types=1);

namespace Controller;

/**
 * Páginas informativas de la tienda (Jeremy Tech MVC).
 *
 * Rutas:
 *   ?ruta=pagina&p=privacidad
 *   ?ruta=pagina&p=terminos
 *   ?ruta=pagina&p=envios
 *   ?ruta=pagina&p=faq
 *   ?ruta=pagina&p=contacto
 *
 * Son páginas GET sin formularios: no modifican datos ni necesitan CSRF.
 */
class PaginaController
{
    /**
     * Título e icono de cada página. La clave es el valor de `p` en la URL.
     *
     * @var array<string, array{titulo: string, icono: string, descripcion: string}>
     */
    private const PAGINAS = [
        'privacidad' => [
            'titulo'      => 'Política de Privacidad',
            'icono'       => '🔐',
            'descripcion' => 'Cómo cuidamos y usamos los datos de tu cuenta y de tus pedidos.',
        ],
        'terminos' => [
            'titulo'      => 'Términos y Condiciones',
            'icono'       => '📜',
            'descripcion' => 'Las condiciones de compra, pagos, envíos, devoluciones y garantías.',
        ],
        'envios' => [
            'titulo'      => 'Envíos y Devoluciones',
            'icono'       => '🚚',
            'descripcion' => 'Zonas, tarifas, plazos de entrega y cómo iniciar una devolución.',
        ],
        'faq' => [
            'titulo'      => 'Preguntas frecuentes',
            'icono'       => '❓',
            'descripcion' => 'Respuestas rápidas sobre búsqueda, pagos, pedidos y garantías.',
        ],
        'contacto' => [
            'titulo'      => 'Contacto',
            'icono'       => '📩',
            'descripcion' => 'Canales directos para hablar con el equipo de JEREMY TECH.',
        ],
    ];

    /**
     * Renderiza la página indicada. Un tema desconocido vuelve al catálogo.
     */
    public function ver(): void
    {
        $tema = isset($_GET['p']) && is_scalar($_GET['p'])
            ? strtolower(trim((string) $_GET['p']))
            : 'faq';

        if (!isset(self::PAGINAS[$tema])) {
            header('Location: ?ruta=catalogo');
            exit;
        }

        $pagina = self::PAGINAS[$tema] + ['slug' => $tema];
        $rutaVista = 'pagina';
        $tituloPagina = $pagina['titulo'] . ' - JEREMY TECH';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/pagina.php';
        require BASE_PATH . '/views/layout/footer.php';
    }
}
