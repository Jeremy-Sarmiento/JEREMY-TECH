<?php
declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Direccion;
use Model\Envio;

/**
 * Controlador de direcciones de envío (Jeremy Tech MVC) — Etapa 5.
 * CRUD de las direcciones guardadas del cliente autenticado. La ZONA de cada
 * dirección (`tarifa_envio_id` -> `tarifas_envio`) es lo único que fija el
 * costo de envío del pedido.
 */
class DireccionController
{
    /**
     * Página "Mis direcciones" (listado + alta/edición).
     */
    public function ver(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $editando = null;
        $idEditar = (int) ($_GET['editar'] ?? 0);
        if ($idEditar > 0) {
            $editando = Direccion::porId($idEditar, $clienteId);
        }

        $direcciones = Direccion::delCliente($clienteId);
        $zonas       = Envio::zonas();
        $flash       = self::tomarFlash();
        $rutaVista   = 'direcciones';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/direcciones.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * POST: crea o actualiza una dirección.
     */
    public function guardar(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?ruta=direcciones');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $datos = [
            'etiqueta'        => (string) ($_POST['etiqueta'] ?? ''),
            'direccion'       => (string) ($_POST['direccion'] ?? ''),
            'referencia'      => (string) ($_POST['referencia'] ?? ''),
            'tarifa_envio_id' => (int) ($_POST['tarifa_envio_id'] ?? 0),
            'predeterminada'  => !empty($_POST['predeterminada']) ? 1 : 0,
        ];

        $error = $id > 0
            ? Direccion::actualizar($id, $clienteId, $datos)
            : Direccion::crear($clienteId, $datos);

        self::guardarFlash(
            $error ?? ($id > 0 ? 'Dirección actualizada.' : 'Dirección guardada.'),
            $error !== null
        );
        header('Location: ?ruta=direcciones');
        exit;
    }

    /**
     * GET: elimina una dirección.
     */
    public function eliminar(): void
    {
        \Security\Proteccion::exigirPost();
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $ok = $id > 0 && Direccion::eliminar($id, $clienteId);
        self::guardarFlash($ok ? 'Dirección eliminada.' : 'No se pudo eliminar esa dirección.', !$ok);
        header('Location: ?ruta=direcciones');
        exit;
    }

    /**
     * GET: marca una dirección como predeterminada.
     */
    public function predeterminada(): void
    {
        \Security\Proteccion::exigirPost();
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && Direccion::porId($id, $clienteId) !== null) {
            Direccion::marcarPredeterminada($id, $clienteId);
            self::guardarFlash('Dirección predeterminada actualizada.', false);
        } else {
            self::guardarFlash('No se pudo cambiar la dirección predeterminada.', true);
        }
        header('Location: ?ruta=direcciones');
        exit;
    }

    private static function guardarFlash(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION['flash_direcciones'] = [
            'tipo'    => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{tipo: string, mensaje: string}
     */
    private static function tomarFlash(): array
    {
        \Security\Proteccion::iniciarSesion();
        $flash = $_SESSION['flash_direcciones'] ?? null;
        unset($_SESSION['flash_direcciones']);

        if (is_array($flash)) {
            return [
                'tipo'    => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        return ['tipo' => '', 'mensaje' => ''];
    }
}
