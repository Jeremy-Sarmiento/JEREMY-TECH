/**
 * @fileoverview Módulo del Carrito y Checkout — Jeremy Tech (Jeremy Tech MVC)
 * @description El carrito vive en la sesión del servidor (src/Model/Carrito.php).
 * Este JS comunica las acciones (agregar, eliminar, cantidad, cupón y pago)
 * mediante endpoints JSON de public/index.php, actualiza badges y toasts,
 * y mantiene el ticket de éxito en el cliente. La persistencia (localStorage,
 * db-modelo.js) ya no se usa: la base de datos es MariaDB.
 * @author Jeremy Tech
 * @version 2.0.0
 */

document.addEventListener('DOMContentLoaded', () => {

  // =========================================================================
  // 1. UTILIDADES DE RED (ENDPOINTS DEL ENRUTADOR)
  // =========================================================================

  /**
   * Envía una petición POST a una ruta del enrutador y parsea el JSON.
   * @param {string} ruta  - Ruta (ej. "carrito/agregar").
   * @param {Object} datos - Cuerpo (FormData o pares clave-valor).
   * @returns {Promise<Object>}
   */
  function tokenCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  async function pedirJSON(ruta, datos) {
    const cuerpo = datos instanceof FormData ? datos : new URLSearchParams(datos);
    const respuesta = await fetch('?ruta=' + ruta, {
      method: 'POST',
      body: cuerpo,
      headers: {
        'X-Requested-With': 'fetch',
        'X-CSRF-Token': tokenCsrf()
      }
    });

    let payload = null;
    try {
      payload = await respuesta.json();
    } catch (e) {
      // Respuesta no JSON (por ejemplo, un error 419 en HTML).
    }

    if (!respuesta.ok) {
      throw new Error((payload && payload.error) || ('HTTP ' + respuesta.status));
    }

    return payload;
  }

  /**
   * Actualiza el badge del botón "Carrito" de la barra de navegación.
   */
  function actualizarBadge(totalItems) {
    const numero = typeof totalItems === 'number' ? totalItems : (window.JT_SESSION ? JT_SESSION.totalItems : 0);
    const badge = document.getElementById('cartBadgeNav');
    if (badge) badge.textContent = numero;
  }

  // =========================================================================
  // 2. TOAST
  // =========================================================================

  let toastTimer;
  function mostrarToast(mensaje) {
    const toast = document.getElementById('cartToast');
    const msg = document.getElementById('toastMessage');
    if (!toast || !msg) return;
    msg.textContent = mensaje;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
  }

  // =========================================================================
  // 3. ACCIONES DEL CARRITO (SERVER-SIDE)
  // =========================================================================

  /**
   * Añade una unidad (o cantidad) de un producto al carrito del servidor.
   * @param {string}   productoId - ID del producto publicado.
   * @param {number}   cantidad   - Unidades a añadir.
   * @param {number|null} varianteId - Variante elegida en la ficha (Etapa 7).
   */
  window.agregarAlCarrito = async function (productoId, cantidad = 1, varianteId = null) {
    try {
      const r = await pedirJSON('carrito/agregar', {
        id: productoId,
        cantidad: cantidad,
        variante: varianteId || 0
      });
      if (r.ok) {
        const prod = (typeof productos !== 'undefined' && productos.find)
          ? productos.find(p => String(p.id) === String(productoId))
          : null;
        // La ficha de producto no define el array `productos` global.
        const nombre = prod ? prod.nombre
          : (window.JT_PRODUCTO && window.JT_PRODUCTO.nombre) || 'Producto';
        actualizarBadge(r.totalItems);
        if (window.JeremyTechAnalitica) {
          JeremyTechAnalitica.registrar('agregar_carrito', { producto_id: Number(productoId) }, 'ficha').catch(function () {});
        }
        mostrarToast('¡' + nombre + ' añadido al carrito!');
      } else {
        mostrarToast(r.error || 'No se pudo agregar el producto');
      }
    } catch (e) {
      mostrarToast(e.message || 'Error de conexión al agregar');
    }
  };

  /**
   * Cambia la cantidad de un ítem (delta) y recarga la vista del servidor.
   * @param {string} productoId - ID del producto.
   * @param {number} delta - Variación (+1 o -1).
   */
  window.cambiarCantidad = async function (productoId, delta) {
    const fila = document.querySelector('[data-item="' + productoId + '"]');
    const actual = fila ? parseInt(fila.dataset.cantidad, 10) : 1;
    const nuevo = actual + delta;
    if (nuevo <= 0) {
      eliminarDelCarrito(productoId);
      return;
    }
    try {
      const r = await pedirJSON('carrito/cambiar-cantidad', { id: productoId, cantidad: nuevo });
      if (r.ok) location.reload();
    } catch (e) {
      mostrarToast(e.message || 'Error al actualizar cantidad');
    }
  };

  /**
   * Elimina completamente un producto del carrito del servidor.
   */
  window.eliminarDelCarrito = async function (productoId) {
    try {
      const r = await pedirJSON('carrito/eliminar', { id: productoId });
      if (r.ok) location.reload();
    } catch (e) {
      mostrarToast(e.message || 'Error al eliminar el producto');
    }
  };

  /**
   * Vacía el carrito: los ítems cambian a 0 (por sesión se limpian al pagar).
   * Como factor de higiene se permite solo desde la vista del carrito.
   */
  window.vaciarCarrito = async function () {
    const filas = document.querySelectorAll('[data-item]');
    for (const fila of filas) {
      await pedirJSON('carrito/eliminar', { id: fila.dataset.item });
    }
    location.reload();
  };

  /**
   * Aplica un cupón contra el servidor y recarga la vista.
   */
  window.aplicarCupon = async function () {
    const input = document.getElementById('cuponInput');
    const msgEl = document.getElementById('cuponTagMsg');
    if (!input) return;

    const codigo = input.value.trim().toUpperCase();
    if (!codigo) {
      if (msgEl) {
        msgEl.textContent = 'Ingresa un código de cupón';
        msgEl.className = 'coupon-tag-msg error';
        msgEl.style.display = 'block';
      }
      return;
    }

    try {
      const r = await pedirJSON('carrito/aplicar-cupon', { codigo: codigo });
      if (r.ok) {
        mostrarToast('¡Cupón ' + codigo + ' aplicado con éxito!');
      } else if (msgEl) {
        msgEl.textContent = r.error || 'Código de cupón no válido';
        msgEl.className = 'coupon-tag-msg error';
        msgEl.style.display = 'block';
        return;
      }
      location.reload();
    } catch (e) {
      mostrarToast(e.message || 'Error al aplicar el cupón');
    }
  };

  // =========================================================================
  // 4. CHECKOUT (SERVER-SIDE) Y TICKET DE ÉXITO
  // =========================================================================

  /** Última orden confirmada por el servidor (para el ticket y WhatsApp). */
  let ultimoPedido = null;

  /**
   * Procesa el formulario de compra contra el servidor y muestra el ticket.
   * @param {Event} event
   */
  window.procesarPago = async function (event) {
    event.preventDefault();
    const form = document.getElementById('checkoutForm');
    if (!form) return;

    const btn = document.getElementById('btnPagarAhora');
    const btnText = btn ? btn.querySelector('.btn-text') : null;
    const spinner = btn ? btn.querySelector('.btn-spinner') : null;
    if (btn) btn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';

    try {
      const datos = new FormData(form);
      const r = await pedirJSON('carrito/procesar', datos);

      if (!r.ok) {
        mostrarToast(r.error || 'No se pudo procesar el pago');
        if (btn) btn.disabled = false;
        if (btnText) btnText.style.display = 'inline-block';
        if (spinner) spinner.style.display = 'none';
        return;
      }

      // Construir ticket con los datos del formulario + id real del pedido
      const modalidad = (document.getElementById('delivOptTienda').classList.contains('active')) ? 'tienda' : 'domicilio';
      const tipoBoleta = document.getElementById('optBoleta').classList.contains('active');
      const nombre = (document.getElementById('clienteNombre').value || '').trim();
      const telefono = (document.getElementById('clienteTelefono').value || '').trim();
      const email = (document.getElementById('clienteEmail').value || '').trim();
      const direccion = direccionResumida();

      const comprobanteTexto = tipoBoleta ? 'Boleta de Venta Electrónica' : 'Factura Electrónica';
      const docTexto = tipoBoleta
        ? 'DNI: ' + ((document.getElementById('clienteDni').value || '').trim() || 'No especificado')
        : 'RUC: ' + ((document.getElementById('clienteRuc').value || '').trim() || 'No especificado');

      const metodoInput = form.querySelector('input[name="metodo"]');
      const metodo = metodoInput ? metodoInput.value : 'tarjeta';
      const metodos = {
        tarjeta: 'Tarjeta de crédito / débito',
        yape: 'Yape / Plin',
        transferencia: 'Transferencia bancaria',
        contraentrega: 'Pago contraentrega / en tienda'
      };
      const metodoPagoTexto = metodos[metodo] || 'Tarjeta';

      const entregaTexto = (modalidad === 'tienda')
        ? '🏬 Recojo en Sede Central (La Joya, Arequipa)'
        : '🚚 Envío a Domicilio · ' + (zonaTextoActual() || 'Sin zona') + ' (' + (direccion || '—') + ')';

      const totalSpan = document.getElementById('checkoutTotalMonto') || document.getElementById('cartTotal');
      const totalTexto = totalSpan ? totalSpan.textContent : 'PEN 0.00';
      const fecha = new Date().toLocaleDateString('es-PE', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
      });

      ultimoPedido = {
        id: '#JT-' + String(r.pedidoId).padStart(5, '0'),
        cliente: nombre,
        telefono: telefono,
        email: email,
        direccion: direccion,
        documento: docTexto,
        comprobante: comprobanteTexto,
        metodoPago: metodoPagoTexto,
        estadoPago: r.estadoPago || 'Pendiente',
        transaccion: r.transaccionId || '—',
        fecha: fecha,
        total: totalTexto
      };

      // El estado real lo decide el servidor: aprobado o pendiente.
      const pagado = ultimoPedido.estadoPago === 'Pagado';
      const tituloExito = document.getElementById('successTitle');
      const subtituloExito = document.getElementById('successSubtitle');
      if (tituloExito) {
        tituloExito.textContent = pagado ? '¡Pago simulado aprobado!' : '¡Pedido registrado!';
      }
      if (subtituloExito) {
        subtituloExito.textContent = r.mensaje || (pagado
          ? 'Pago simulado aprobado. Tu orden está en preparación.'
          : 'Tu orden quedó registrada. El pago se confirmará según el método elegido.');
      }

      // Despliega el ticket
      const tOrd = document.getElementById('ticketOrderId');
      const tCli = document.getElementById('ticketCliente');
      const tDoc = document.getElementById('ticketDocumento');
      const tComp = document.getElementById('ticketComprobante');
      const tMet = document.getElementById('ticketMetodoPago');
      const tPago = document.getElementById('ticketPagoEstado');
      const tRef = document.getElementById('ticketTransaccion');
      const tEnt = document.getElementById('ticketEntrega');
      const tFec = document.getElementById('ticketFecha');
      const tTot = document.getElementById('ticketTotal');
      const tTotLabel = document.getElementById('ticketTotalLabel');
      const tItems = document.getElementById('ticketItems');
      if (tOrd) tOrd.textContent = ultimoPedido.id;
      if (tCli) tCli.textContent = ultimoPedido.cliente;
      if (tDoc) tDoc.textContent = ultimoPedido.documento;
      if (tComp) tComp.textContent = ultimoPedido.comprobante;
      if (tMet) tMet.textContent = ultimoPedido.metodoPago;
      if (tPago) tPago.textContent = ultimoPedido.estadoPago;
      if (tRef) tRef.textContent = ultimoPedido.transaccion;
      if (tEnt) tEnt.textContent = entregaTexto;
      if (tFec) tFec.textContent = ultimoPedido.fecha;
      if (tTot) tTot.textContent = ultimoPedido.total;
      if (tTotLabel) tTotLabel.textContent = pagado ? 'Total Pagado:' : 'Total del Pedido:';
      if (tItems) {
        const nItems = (window.JT_CARRO && JT_CARRO.totalItems) ? JT_CARRO.totalItems : 0;
        tItems.textContent = '🛍️ ' + nItems + (nItems === 1 ? ' producto comprado' : ' productos comprados');
      }

      if (window.JeremyTechAnalitica) {
        JeremyTechAnalitica.registrar('compra', { pedido_id: Number(r.pedidoId) }, 'checkout').catch(function () {});
      }

      form.reset();
      cambiarMetodoPago('tarjeta');
      actualizarBadge(0);
      const succModal = document.getElementById('successModal');
      if (succModal) succModal.style.display = 'flex';
    } catch (e) {
      mostrarToast(e.message || 'Error de conexión al procesar el pago');
      if (btn) btn.disabled = false;
      if (btnText) btnText.style.display = 'inline-block';
      if (spinner) spinner.style.display = 'none';
    }
  };

  /** Cierra la modal de éxito tras la compra */
  window.cerrarModalExito = function () {
    const modal = document.getElementById('successModal');
    if (modal) modal.style.display = 'none';
  };

  /**
   * Envía un comprobante de compra digital vía enlace directo a WhatsApp.
   */
  window.enviarReciboWhatsApp = function () {
    if (!ultimoPedido) return;
    const numeroWhatsApp = '51997833866';
    let mensaje = '*COMPROBANTE DE COMPRA — JEREMY TECH*\n';
    mensaje += '━━━━━━━━━━━━━━━━━━━━━━━\n';
    mensaje += '📌 *Orden:* ' + ultimoPedido.id + '\n';
    mensaje += '📅 *Fecha:* ' + ultimoPedido.fecha + '\n';
    mensaje += '👤 *Cliente:* ' + ultimoPedido.cliente + '\n';
    mensaje += '📄 *Documento:* ' + ultimoPedido.documento + '\n';
    mensaje += '🧾 *Comprobante:* ' + ultimoPedido.comprobante + '\n';
    mensaje += '💳 *Método de Pago:* ' + ultimoPedido.metodoPago + '\n';
    mensaje += '📌 *Estado del Pago:* ' + ultimoPedido.estadoPago + '\n';
    mensaje += '🔖 *Referencia:* ' + ultimoPedido.transaccion + '\n';
    mensaje += '━━━━━━━━━━━━━━━━━━━━━━━\n';
    mensaje += '💰 *TOTAL DEL PEDIDO:* ' + ultimoPedido.total + '\n\n';
    mensaje += '¡Gracias por confiar en *Jeremy Tech*!';

    window.open('https://wa.me/' + numeroWhatsApp + '?text=' + encodeURIComponent(mensaje), '_blank');
  };

  // =========================================================================
  // 5. CONTROLES DE LA VISTA DEL CARRITO (DOM SERVER-RENDERED)
  // =========================================================================

  // ------------------------------------------------------------------
  // 5-bis. DIRECCIÓN DE ENTREGA POR ZONA (Etapa 5)
  // ------------------------------------------------------------------

  /** ¿Está elegida la opción "dirección nueva"? */
  function direccionNuevaSeleccionada() {
    const sel = document.querySelector('input[name="direccion_id"]:checked');
    return !sel || sel.value === '0';
  }

  /** Zona elegida en el desplegable de "dirección nueva" -> su costo. */
  function costoZonaNueva() {
    const sel = document.getElementById('dirZonaNueva');
    if (!sel || !window.JT_CARRO || !Array.isArray(JT_CARRO.zonas)) return 0;
    const id = parseInt(sel.value, 10) || 0;
    const z = JT_CARRO.zonas.find(function (x) { return x.id === id; });
    return z ? Number(z.costo) || 0 : 0;
  }

  /** Costo de envío vigente: recojo, dirección guardada o zona de la nueva. */
  function costoEnvioActual() {
    if (!window.JT_CARRO) return 0;
    const optTie = document.getElementById('delivOptTienda');
    if (optTie && optTie.classList.contains('active')) return Number(JT_CARRO.envioTienda) || 0;
    const sel = document.querySelector('input[name="direccion_id"]:checked');
    if (sel && sel.value !== '0') return Number(sel.dataset.costo) || 0;
    return costoZonaNueva();
  }

  /** Oculta el bloque de dirección en "recojo" y gestiona required. */
  function actualizarBloqueDireccion() {
    const bloque = document.getElementById('bloqueDireccionEntrega');
    if (!bloque) return;
    const optTie = document.getElementById('delivOptTienda');
    const recojo = !!(optTie && optTie.classList.contains('active'));
    bloque.style.display = recojo ? 'none' : '';

    const nueva = document.getElementById('bloqueNuevaDireccion');
    const mostrarNueva = !recojo && direccionNuevaSeleccionada();
    if (nueva) nueva.style.display = mostrarNueva ? '' : 'none';

    const inputDir = document.getElementById('clienteDireccion');
    const selectZona = document.getElementById('dirZonaNueva');
    if (inputDir) inputDir.required = mostrarNueva;
    if (selectZona) selectZona.required = mostrarNueva;
  }

  /** Zona vigente en texto, para el resumen del carrito. */
  function zonaTextoActual() {
    if (window.JT_CARRO === undefined) return '';
    const optTie = document.getElementById('delivOptTienda');
    if (optTie && optTie.classList.contains('active')) return 'Recojo en tienda (sin costo)';
    const sel = document.querySelector('input[name="direccion_id"]:checked');
    if (sel && sel.value !== '0') return sel.dataset.zona || '';
    const zSel = document.getElementById('dirZonaNueva');
    if (!zSel || !Array.isArray(JT_CARRO.zonas)) return '';
    const id = parseInt(zSel.value, 10) || 0;
    const z = JT_CARRO.zonas.find(function (x) { return x.id === id; });
    return z ? z.zona : '';
  }

  /** Texto del destino para el ticket de éxito. */
  function direccionResumida() {
    const sel = document.querySelector('input[name="direccion_id"]:checked');
    if (sel && sel.value !== '0') return sel.dataset.texto || sel.dataset.zona || '—';
    const dir = document.getElementById('clienteDireccion');
    return dir ? (dir.value || '').trim() : '';
  }

  window.cambiarDireccionEntrega = function () {
    document.querySelectorAll('.addr-opt').forEach(function (opt) {
      const input = opt.querySelector('input[name="direccion_id"]');
      opt.classList.toggle('active', !!(input && input.checked));
    });
    actualizarBloqueDireccion();
    recalcularTotales();
  };

  /**
   * Alterna entre envío a domicilio y recojo en tienda.
   */
  window.cambiarOpcionEnvio = function (tipo) {
    const optDom = document.getElementById('delivOptDomicilio');
    const optTie = document.getElementById('delivOptTienda');
    const dirInput = document.getElementById('clienteDireccion');

    if (optDom) optDom.classList.toggle('active', tipo === 'domicilio');
    if (optTie) optTie.classList.toggle('active', tipo === 'tienda');

    void dirInput; // la dirección ya no es un campo libre: la decide la zona

    const campoModalidad = document.querySelector('input[name="modalidad"]');
    if (campoModalidad) campoModalidad.value = tipo;
    actualizarBloqueDireccion();
    recalcularTotales();
  };

  /**
   * Conmuta Boleta (DNI) / Factura (RUC + Razón Social).
   */
  window.cambiarTipoComprobante = function (tipo) {
    const optBol = document.getElementById('optBoleta');
    const optFac = document.getElementById('optFactura');
    const campoBol = document.getElementById('campoBoletaDni');
    const campoFac = document.getElementById('campoFacturaRuc');
    const dniInput = document.getElementById('clienteDni');

    if (optBol) optBol.classList.toggle('active', tipo === 'boleta');
    if (optFac) optFac.classList.toggle('active', tipo === 'factura');

    if (tipo === 'boleta') {
      if (campoBol) campoBol.style.display = 'block';
      if (campoFac) campoFac.style.display = 'none';
      if (dniInput) dniInput.required = true;
    } else {
      if (campoBol) campoBol.style.display = 'none';
      if (campoFac) campoFac.style.display = 'grid';
      if (dniInput) dniInput.required = false;
    }

    const campoComprobante = document.querySelector('input[name="comprobante"]');
    if (campoComprobante) campoComprobante.value = tipo;
    // El comprobante es del pedido, no de la cuenta: no se cambia `users.tipo`.
  };

  /**
   * Conmuta la pestaña de la pasarela de pago.
   * @param {'tarjeta'|'yape'|'transferencia'|'contraentrega'} metodo
   */
  window.cambiarMetodoPago = function (metodo) {
    const ids = ['tabTarjeta', 'tabYape', 'tabTransf', 'tabContra'];
    const paneles = {
      'tarjeta': 'panelMetodoTarjeta',
      'yape': 'panelMetodoYape',
      'transferencia': 'panelMetodoTransf',
      'contraentrega': 'panelMetodoContra'
    };

    ids.forEach(id => {
      const b = document.getElementById(id);
      if (b) b.classList.toggle('active', id === ('tab' + metodo.charAt(0).toUpperCase() + metodo.slice(1)));
    });

    for (const key in paneles) {
      const p = document.getElementById(paneles[key]);
      if (p) p.style.display = (key === metodo) ? 'block' : 'none';
    }

    // Solo el método visible exige sus campos. Así el navegador no bloquea el
    // envío por culpa de un campo oculto de otra pestaña.
    const tarjeta = ['tarjetaNumero', 'tarjetaTitular', 'tarjetaExpiracion', 'tarjetaCvv'];
    tarjeta.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.required = (metodo === 'tarjeta');
    });
    const yapeTel = document.getElementById('yapeTelefono');
    if (yapeTel) yapeTel.required = (metodo === 'yape');
    const yapeOp = document.getElementById('yapeCodigoOperacion');
    if (yapeOp) yapeOp.required = (metodo === 'yape');
    const transfOp = document.getElementById('transfCodigoOperacion');
    if (transfOp) transfOp.required = (metodo === 'transferencia');

    const campoMetodo = document.querySelector('input[name="metodo"]');
    if (campoMetodo) campoMetodo.value = metodo;
  };

  /**
   * Formatea el número de tarjeta mientras se escribe y actualiza la tarjeta
   * virtual. Es solo presentación: el servidor vuelve a validar con Luhn.
   */
  function configurarTarjetaVisual() {
    const numero = document.getElementById('tarjetaNumero');
    const titular = document.getElementById('tarjetaTitular');
    const expira = document.getElementById('tarjetaExpiracion');
    const previewNumero = document.getElementById('cardPreviewNumber');
    const previewNombre = document.getElementById('cardPreviewName');
    const previewExpira = document.getElementById('cardPreviewExpiry');
    const marca = document.getElementById('cardBrandLogo');
    const icono = document.getElementById('inputBrandIcon');

    const detectarMarca = (digitos) => {
      if (/^4/.test(digitos)) return 'VISA';
      if (/^(5[1-5]|2[2-7])/.test(digitos)) return 'MASTERCARD';
      if (/^3[47]/.test(digitos)) return 'AMEX';
      if (/^6/.test(digitos)) return 'DINERS';
      return 'TARJETA';
    };

    if (numero) {
      numero.addEventListener('input', () => {
        const digitos = numero.value.replace(/\D/g, '').slice(0, 19);
        numero.value = digitos.replace(/(\d{4})(?=\d)/g, '$1 ');
        if (previewNumero) {
          const visibles = digitos.slice(-4).padStart(4, '•');
          previewNumero.textContent = '•••• •••• •••• ' + visibles;
        }
        const etiqueta = detectarMarca(digitos);
        if (marca) marca.textContent = etiqueta;
        if (icono) icono.textContent = '💳';
      });
    }

    if (titular) {
      titular.addEventListener('input', () => {
        const valor = titular.value.toUpperCase().slice(0, 80);
        titular.value = valor;
        if (previewNombre) previewNombre.textContent = valor || 'NOMBRE Y APELLIDO';
      });
    }

    if (expira) {
      expira.addEventListener('input', () => {
        const digitos = expira.value.replace(/\D/g, '').slice(0, 4);
        expira.value = digitos.length > 2 ? digitos.slice(0, 2) + '/' + digitos.slice(2) : digitos;
        if (previewExpira) previewExpira.textContent = expira.value || 'MM/AA';
      });
    }
  }

  /**
   * Recorre los elementos `.price[data-precio]` de la página de carrito
   * y aplica la conversión de moneda (hook llamado por puente-digital.js).
   */
  function convertirMontosPagina() {
    if (typeof convertirPrecio !== 'function' || typeof monedaActual === 'undefined') return;
    document.querySelectorAll('.price[data-precio]').forEach(el => {
      const pen = parseFloat(el.dataset.precio);
      if (isNaN(pen)) return;
      const simbolo = (typeof MONEDAS !== 'undefined' && MONEDAS[monedaActual])
        ? MONEDAS[monedaActual].simbolo : monedaActual;
      el.textContent = simbolo + ' ' + convertirPrecio(Math.abs(pen), monedaActual).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + (pen < 0 ? ' (' + simbolo + ' -)' : '') ;
    });
  }

  /**
   * Recalcula en vivo los montos de envío/total según modalidad y cupón.
   * Los valores base vienen de JT_CARRO (inyectado por la vista carrito.php).
   */
  function recalcularTotales() {
    if (window.JT_CARRO === undefined) return;

    const optTie = document.getElementById('delivOptTienda');
    const modalidad = optTie && optTie.classList.contains('active') ? 'tienda' : 'domicilio';
    const envio = costoEnvioActual();

    const subtotal = JT_CARRO.subtotalPEN;
    const descuento = JT_CARRO.descuentoPEN || 0;
    const total = subtotal - descuento + envio;

    const subtotalEl = document.getElementById('cartSubtotal');
    const envioEl = document.getElementById('cartEnvio');
    const totalEl = document.getElementById('cartTotal');
    const checkoutTotalEl = document.getElementById('checkoutTotalMonto');
    const yapeMontoEl = document.getElementById('yapeMontoPEN');

    const texto = (el, val) => {
      if (!el) return;
      el.dataset.precio = String(val);
      el.textContent = fmt(val);
    };
    const fmt = (val) => {
      const simbolo = (typeof MONEDAS !== 'undefined' && MONEDAS[monedaActual] && typeof convertirPrecio === 'function')
        ? MONEDAS[monedaActual].simbolo : 'PEN';
      const valor = (typeof convertirPrecio === 'function') ? convertirPrecio(val, monedaActual) : val;
      return simbolo + ' ' + valor.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    texto(subtotalEl, subtotal);
    texto(envioEl, envio);
    texto(totalEl, total);
    texto(checkoutTotalEl, total);
    if (yapeMontoEl) yapeMontoEl.textContent = fmt(total);

    const zonaEl = document.getElementById('cartEnvioZona');
    if (zonaEl) {
      const zona = zonaTextoActual();
      zonaEl.textContent = zona ? 'Zona: ' + zona : 'Elige una dirección de entrega para calcular la zona y el costo de envío';
    }
  }

  // La vista de carrito ya envía el bloque de dirección en su estado inicial.
  actualizarBloqueDireccion();

  // En la página del carrito, conectar recálculo al cambio de divisa del puente.
  window.actualizarPreciosEnCarrito = function () {
    recalcularTotales();
    convertirMontosPagina();
  };

  // =========================================================================
  // 6. NAVEGACIÓN: DRAWER (removido) -> enlaces a la página del carrito
  // =========================================================================

  window.abrirCarrito = function () {
    window.location.href = '?ruta=carrito';
  };

  window.cerrarCarrito = function () {
    history.back();
  };

  window.abrirCheckout = function () {
    window.location.href = '?ruta=carrito#checkout';
  };

  window.cerrarCheckout = function () {
    window.location.href = '?ruta=carrito';
  };

  window.abrirHistorialPedidos = function () {
    window.location.href = '?ruta=mis-pedidos';
  };

  window.cerrarHistorialPedidos = function () {
    window.location.href = '?ruta=carrito';
  };

  /**
   * Consulta en WhatsApp por una orden del historial (vista carrito).
   */
  window.contactarOrdenWhatsApp = function (pedidoId) {
    const numeroWhatsApp = '51997833866';
    const msg = 'Hola Jeremy Tech, quisiera consultar sobre el estado de mi orden *#' + pedidoId + '*.';
    window.open('https://wa.me/' + numeroWhatsApp + '?text=' + encodeURIComponent(msg), '_blank');
  };

  // =========================================================================
  // 7. INICIALIZACIÓN
  // =========================================================================

  actualizarBadge();
  recalcularTotales();
  configurarTarjetaVisual();
  cambiarMetodoPago('tarjeta');

  if (window.JT_CARRO) {
    const hash = window.location.hash;
    if (hash === '#checkout' && !JT_CARRO.estaVacio) {
      // Scroll suave al formulario de pago si se navegó con ese hash.
      const ref = document.getElementById('btnPagarAhora');
      if (ref) setTimeout(() => ref.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
    } else if (hash === '#historial') {
      const hist = document.getElementById('historial');
      if (hist) setTimeout(() => hist.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200);
    }
  }

  window.mostrarToast = mostrarToast;
});