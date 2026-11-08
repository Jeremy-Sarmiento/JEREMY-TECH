# Correcciones de integridad PostgreSQL

Se conserva la rama `postgresql`, las 33 tablas, el historial y los cambios previos.
No se modifican los entregables ni se conceden roles automáticamente.

1. Stock administrativo: reproducir formulario antiguo, rechazar sobrescrituras y registrar ajustes con Inventario.
2. Relaciones: FK de marca/categoría/solicitudes/tokens y unicidad de solicitud pendiente, en estructura y migración idempotente.
3. Catálogo: categorías de BD, salida escapada y productos comprables solo con vendedor vigente o de plataforma.
4. Actualización: respaldo, migración no destructiva, ocultar publicaciones de cuentas no habilitadas y documentar despliegue.
5. Verificación: regresiones, suite completa, análisis estático, importación limpia y revisión independiente.

Ampliación solicitada: SKU automático `JT-` seguido del ID con un mínimo de cinco dígitos;
lo asigna PostgreSQL, se completan vacíos y se conservan códigos existentes.

Ampliación visual: retirar degradados del CSS propio y agrupar las variantes en una
tarjeta por vendedor, categoría y nombre. La tarjeta muestra la opción activa
(producto y primera variante) y el resto se elige en la ficha, sin fusionar
publicaciones ni inventario histórico.

Las pruebas usan exclusivamente `jeremy_tech_test`; no se ejecutan suites en paralelo sobre esa misma base.
La migración debe detenerse ante referencias inválidas o pendientes duplicadas, sin borrar ni reasignar historial.
