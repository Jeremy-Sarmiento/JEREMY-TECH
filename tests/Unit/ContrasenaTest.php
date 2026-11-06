<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Security\Contrasena;

class ContrasenaTest extends TestCase
{
    public function testRechazaClavesCortasYComunes(): void
    {
        foreach (['', '1234567', '12345678', 'Password123', 'jeremytech'] as $clave) {
            $this->assertNotNull(Contrasena::error($clave));
        }
        $this->assertNull(Contrasena::error('Ejemplo-Pruebas-2026!'));
    }
}
