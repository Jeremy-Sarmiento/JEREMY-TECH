<?php

declare(strict_types=1);

namespace Model;

/**
 * Preguntas frecuentes del chatbot (Jeremy Tech MVC).
 *
 * Vive en base de datos (tabla `chatbot_faq`) para que el admin gestione el
 * conocimiento del asistente de forma compartida y auditable, en lugar del
 * `localStorage` por navegador usado en la demo previa (Sesión 08 / proyecto
 * Grafiluz).
 */
final class ChatbotFaq
{
    /**
     * Preguntas activas, ordenadas por el orden administrativo.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activos(): array
    {
        $stmt = conexion()->prepare(
            'SELECT * FROM chatbot_faq WHERE activo = 1 ORDER BY orden ASC, id ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function todas(): array
    {
        $stmt = conexion()->prepare('SELECT * FROM chatbot_faq ORDER BY orden ASC, id ASC');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = conexion()->prepare('SELECT * FROM chatbot_faq WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    public static function crear(string $pregunta, string $palabrasClave, string $respuesta, int $orden, bool $activo): int
    {
        $stmt = conexion()->prepare(
            'INSERT INTO chatbot_faq (pregunta, palabras_clave, respuesta, activo, orden)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            mb_substr($pregunta, 0, 255),
            mb_substr($palabrasClave, 0, 255),
            $respuesta,
            $activo ? 1 : 0,
            max(0, $orden),
        ]);

        return (int) conexion()->lastInsertId();
    }

    public static function actualizar(int $id, string $pregunta, string $palabrasClave, string $respuesta, int $orden, bool $activo): bool
    {
        $stmt = conexion()->prepare(
            'UPDATE chatbot_faq
                SET pregunta = ?, palabras_clave = ?, respuesta = ?, activo = ?, orden = ?
              WHERE id = ?'
        );
        $stmt->execute([
            mb_substr($pregunta, 0, 255),
            mb_substr($palabrasClave, 0, 255),
            $respuesta,
            $activo ? 1 : 0,
            max(0, $orden),
            $id,
        ]);

        return (bool) $stmt->rowCount();
    }

    public static function eliminar(int $id): bool
    {
        $stmt = conexion()->prepare('DELETE FROM chatbot_faq WHERE id = ?');
        $stmt->execute([$id]);

        return (bool) $stmt->rowCount();
    }

    public static function setActivo(int $id, bool $activo): bool
    {
        $stmt = conexion()->prepare('UPDATE chatbot_faq SET activo = ? WHERE id = ?');
        $stmt->execute([$activo ? 1 : 0, $id]);

        return (bool) $stmt->rowCount();
    }

    /**
     * Busca la mejor FAQ activa por coincidencia de palabras clave.
     *
     * @return array<string, mixed>|null
     */
    public static function buscar(string $texto): ?array
    {
        $usuario = self::normalizar($texto);
        if ($usuario === []) {
            return null;
        }

        $mejor = null;
        $mejorPuntaje = 0;

        foreach (self::activos() as $faq) {
            $score = self::puntaje($usuario, (string) ($faq['palabras_clave'] ?? ''));
            if ($score > $mejorPuntaje) {
                $mejorPuntaje = $score;
                $mejor = $faq;
            }
        }

        if ($mejor === null || $mejorPuntaje <= 0) {
            return null;
        }

        $mejor['_puntaje'] = $mejorPuntaje;
        return $mejor;
    }

    private static function puntaje(array $palabrasUsuario, string $textoClave): int
    {
        $claves = self::normalizar($textoClave);
        $usuario = array_flip($palabrasUsuario);

        $coincidencias = 0;
        foreach ($claves as $clave) {
            if (isset($usuario[$clave])) {
                $coincidencias++;
            }
        }

        return $coincidencias;
    }

    private static function normalizar(string $texto): array
    {
        $texto = mb_strtolower(trim($texto));
        $texto = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $texto);
        $texto = (string) preg_replace('/[^a-z0-9\s]/', ' ', $texto);
        $texto = trim((string) preg_replace('/\s+/', ' ', $texto));

        $palabras = preg_split('/[\s,;]+/', $texto) ?: [];
        $resultado = [];
        foreach ($palabras as $palabra) {
            $palabra = trim((string) $palabra);
            if ($palabra !== '' && mb_strlen($palabra) > 2) {
                $resultado[] = $palabra;
            }
        }

        return $resultado;
    }
}
