<?php

declare(strict_types=1);

namespace Security;

final class Contrasena
{
    public static function error(string $valor): ?string
    {
        if (strlen($valor) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres';
        }
        if (in_array(strtolower($valor), [
            '12345678', '123456789', '1234567890', 'abcdefgh', 'password',
            'password1', 'password123', 'qwerty123', 'qwertyuiop', 'jeremytech',
        ], true)) {
            return 'Elige una contraseña menos común';
        }
        return null;
    }
}
