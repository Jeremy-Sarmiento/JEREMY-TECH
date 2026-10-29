/**
 * @fileoverview Ficha de producto — Jeremy Tech (Etapa 7).
 * Recalcula el precio unitario en vivo según la variante elegida y la
 * cantidad seleccionada (tramos de `precios_volumen`), y envía la línea al
 * carrito del servidor con la variante incluida.
 *
 * La regla de precio vive en el servidor (Model\Producto::precioDe). Este
 * fichero solo replica el cálculo para dar respuesta inmediata en pantalla;
 * el precio que se factura es siempre el que devuelve `carrito/procesar`.
 */
document.addEventListener('DOMContentLoaded', function () {
  const P = window.JT_PRODUCTO;
  if (!P) return;

  // (Bloque 8) La ficha registra una vista de producto para el dashboard de
  // analítica (top de productos por interacción).
  if (window.JeremyTechAnalitica) {
    JeremyTechAnalitica.registrar('producto_visto', { producto_id: Number(P.id) }, 'producto').catch(function () {});
  }

  const elPrecio    = document.getElementById('prodPrecio');
  const elAhorro    = document.getElementById('prodAhorro');
  const elStock     = document.getElementById('prodStock');
  const elCantidad  = document.getElementById('prodCantidad');
  const elAgregar   = document.getElementById('prodAgregar');
  const elMenos     = document.getElementById('prodMenos');
  const elMas       = document.getElementById('prodMas');

  const fmt = (n) => 'PEN ' + n.toLocaleString(undefined, {
    minimumFractionDigits: 2, maximumFractionDigits: 2
  });

  /** Variante activa (null = el producto tal cual está publicado). */
  function varianteActual() {
    if (P.varianteId === null) return null;
    return P.variantes.find(function (v) { return v.id === P.varianteId; }) || null;
  }

  /** Stock disponible: el de la variante si la hay, si no el del producto. */
  function stockDisponible() {
    const v = varianteActual();
    // El servidor vende min(stock del producto, stock de la variante).
    return v ? Math.min(v.stock, P.stockBase) : P.stockBase;
  }

  /** Tramo de volumen que cubre una cantidad (espejo de PrecioVolumen). */
  function tramoPara(cantidad) {
    const ordenados = P.tramos.slice().sort(function (a, b) {
      return b.cantidadMinima - a.cantidadMinima;
    });
    for (let i = 0; i < ordenados.length; i++) {
      const t = ordenados[i];
      if (cantidad >= t.cantidadMinima &&
          (t.cantidadMaxima === null || cantidad <= t.cantidadMaxima)) {
        return t;
      }
    }
    return null;
  }

  /** Precio unitario efectivo: base o tramo, más el extra de la variante. */
  function precioUnitario(cantidad) {
    const v = varianteActual();
    const extra = v ? Number(v.precioExtra) : 0;
    const t = tramoPara(cantidad);
    const base = t ? Number(t.precioUnitario) : Number(P.precioBase);
    return Math.round((base + extra) * 100) / 100;
  }

  function recalcular() {
    const stock = stockDisponible();
    let cantidad = parseInt(elCantidad.value, 10);
    if (isNaN(cantidad) || cantidad < 1) cantidad = 1;
    if (stock > 0 && cantidad > stock) cantidad = stock;
    elCantidad.value = String(cantidad);
    elCantidad.max = String(Math.max(1, stock));

    const unitario = precioUnitario(cantidad);
    elPrecio.dataset.precio = String(unitario);
    elPrecio.textContent = fmt(unitario);

    // Ahorro frente al precio publicado (solo si el tramo de verdad baja).
    const t = tramoPara(cantidad);
    if (t && Number(t.precioUnitario) < Number(P.precioBase)) {
      const ahorro = Math.round((Number(P.precioBase) - Number(t.precioUnitario)) * 100) / 100;
      elAhorro.hidden = false;
      elAhorro.textContent = '¡Ahorrarás ' + fmt(ahorro) + ' por unidad!';
    } else {
      elAhorro.hidden = true;
      elAhorro.textContent = '';
    }

    // Tramo destacado en la tabla.
    document.querySelectorAll('#prodVolumen tbody tr').forEach(function (fila) {
      fila.classList.toggle('activa', !!(t && Number(fila.dataset.tramo) === t.id));
    });

    // Disponibilidad.
    if (stock <= 0) {
      elStock.textContent = 'Agotado';
      elStock.className = 'producto-stock agotado';
      elAgregar.disabled = true;
    } else {
      elStock.textContent = 'En stock · ' + stock + ' disponibles';
      elStock.className = 'producto-stock';
      elAgregar.disabled = false;
    }
  }

  // --- Variantes ---
  document.querySelectorAll('#prodVariantes .variante-opcion').forEach(function (btn) {
    btn.addEventListener('click', function () {
      P.varianteId = Number(btn.dataset.variante);
      document.querySelectorAll('#prodVariantes .variante-opcion').forEach(function (b) {
        b.classList.toggle('activa', b === btn);
      });
      recalcular();
    });
  });

  // --- Cantidad ---
  if (elMenos) {
    elMenos.addEventListener('click', function () {
      elCantidad.value = String(Math.max(1, (parseInt(elCantidad.value, 10) || 1) - 1));
      recalcular();
    });
  }
  if (elMas) {
    elMas.addEventListener('click', function () {
      elCantidad.value = String((parseInt(elCantidad.value, 10) || 1) + 1);
      recalcular();
    });
  }
  if (elCantidad) elCantidad.addEventListener('change', recalcular);

  // --- Agregar al carrito (con variante) ---
  if (elAgregar) {
    elAgregar.addEventListener('click', function () {
      const cantidad = parseInt(elCantidad.value, 10) || 1;
      if (typeof window.agregarAlCarrito === 'function') {
        window.agregarAlCarrito(String(P.id), cantidad, P.varianteId);
      }
    });
  }

  recalcular();
});
