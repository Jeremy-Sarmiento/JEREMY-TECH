/**
 * Analítica del storefront (Jeremy Tech MVC).
 *
 * Envía eventos al endpoint `POST ?ruta=analitica/evento`. Se usa para:
 *  - visitas (`visita`);
 *  - búsquedas del catálogo (`busqueda`);
 *  - WhatsApp (`clic_whatsapp`);
 *  - acciones del carrito/pago (`agregar_carrito`, `iniciar_checkout`, `compra`).
 *
 * El envío es best effort: si falla, la página no debe romperse. El registro
 * server-side (compras, reembolsos, etc.) se hace desde PHP con el mismo modelo.
 */
(function () {
  'use strict';

  function token() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
  }

  async function enviar(tipoEvento, datos, pagina) {
    const url = '?ruta=analitica/evento';
    const respuesta = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': token(),
      },
      body: JSON.stringify({
        tipo_evento: tipoEvento,
        datos: datos || {},
        pagina: pagina || (window.location.pathname.split('/').pop() || 'index.php'),
      }),
    });

    if (!respuesta.ok) {
      throw new Error('HTTP ' + respuesta.status);
    }

    return respuesta.json();
  }

  window.JeremyTechAnalitica = {
    registrar: function (tipoEvento, datos, pagina) {
      return enviar(tipoEvento, datos, pagina);
    },
  };

  function paginaActual() {
    const params = new URLSearchParams(window.location.search);
    const ruta = params.get('ruta');
    if (ruta) return ruta;
    const nombre = (window.location.pathname.split('/').pop() || 'inicio').replace(/^index\.php$/i, 'inicio');
    return nombre;
  }

  function registrarVisita() {
    JeremyTechAnalitica.registrar('visita', {}, paginaActual()).catch(function () {});
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registrarVisita);
  } else {
    registrarVisita();
  }
})();
