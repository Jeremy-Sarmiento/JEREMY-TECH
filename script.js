/**
 * @fileoverview Lógica Principal de la Tienda Jeremy Tech
 * @description Controla la interactividad del catálogo, carruseles, filtrado y búsqueda en tiempo real,
 * gestión del carrito de compras en LocalStorage, pasarela de checkout multimétodo (Tarjeta, Yape/Plin,
 * Transferencia Bancaria, Contra Entrega), historial y seguimiento de pedidos, e integración con WhatsApp.
 * @author Jeremy Tech
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {

  // =========================================================================
  // 1. RENDERIZADO DINÁMICO DEL CATÁLOGO DE PRODUCTOS
  // =========================================================================

  /**
   * Mapeo entre los nombres de categorías de datos y los IDs de los contenedores grid en el DOM.
   * @type {Object<string, string>}
   */
  const gridMap = {
    'Celulares': 'grid-celulares',
    'Laptops': 'grid-laptops',
    'Audifonos': 'grid-audifonos',
    'Accesorios': 'grid-accesorios',
    'Smartwatches': 'grid-smartwatches',
    'Tablets': 'grid-tablets',
    'Gaming': 'grid-gaming',
  };

  /**
   * Agrupa una lista de variantes de productos bajo su nombre comercial base.
   * Permite que múltiples capacidades/modelos (ej. 256GB y 512GB) compartan una misma tarjeta con carrusel.
   * @param {Array<Object>} lista - Lista de productos a agrupar.
   * @returns {Object<string, Array<Object>>} Diccionario con los nombres de producto como clave y sus variantes como valor.
   */
  function agruparProductos(lista) {
    const mapa = {};
    lista.forEach(p => {
      if (!mapa[p.nombre]) {
        mapa[p.nombre] = [];
      }
      mapa[p.nombre].push(p);
    });
    return mapa;
  }

  /**
   * Genera el marcado HTML para los indicadores circulares de colores disponibles de una variante.
   * Aplica borde sutil para colores claros (blanco, beige, plata, rosa) garantizando contraste.
   * @param {string[]} colores - Arreglo de códigos hexadecimales de color (ej. ['#FFFFFF', '#000000']).
   * @returns {string} Cadena HTML con elementos `.color-dot`.
   */
  function generarColoresHTML(colores) {
    return colores.map(c => {
      const border = (c === '#FFFFFF' || c === '#F5F5DC' || c === '#C0C0C0' || c === '#E8D5C4' || c === '#E8D5B7' || c === '#FFB6C1')
        ? ' style="background:' + c + '; border: 2px solid #ddd"'
        : ' style="background:' + c + '"';
      return '<div class="color-dot"' + border + '></div>';
    }).join('');
  }

  /**
   * Genera la estructura HTML completa de una tarjeta de producto (Card).
   * Incluye carrusel de imágenes de variantes, especificaciones técnicas, precio con soporte de divisa y botones de acción.
   * @param {Array<Object>} grupo - Lista de variantes que pertenecen a la misma tarjeta.
   * @returns {string} Cadena HTML del componente `.card`.
   */
  function generarCard(grupo) {
    const variantes = grupo;
    const tieneMulti = variantes.length > 1;

    // Generar elementos del carrusel de imágenes
    const carouselItems = variantes.map(v =>
      '<div class="card-carousel-item">' +
        '<img alt="' + v.nombre + '" loading="lazy" src="' + v.imagen + '"/>' +
      '</div>'
    ).join('');

    // Generar indicadores de puntos (dots)
    const dots = variantes.map((_, i) =>
      '<span class="card-carousel-dot' + (i === 0 ? ' active' : '') + '"></span>'
    ).join('');

    // Generar paneles de información para cada variante
    const infoItems = variantes.map((v, i) =>
      '<div class="card-info-item' + (i === 0 ? ' active' : '') + '" data-variant-id="' + v.id + '">' +
        '<h3>' + v.nombre + '</h3>' +
        '<p>' + v.especificaciones + '</p>' +
        '<p class="price" data-precio="' + v.precioPEN + '">PEN ' + v.precioPEN.toLocaleString() + '</p>' +
        '<div class="colors">' + generarColoresHTML(v.colores) + '</div>' +
        '<div class="card-actions">' +
          '<button class="btn-cart-add" onclick="agregarAlCarrito(\'' + v.id + '\')">' +
            '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>' +
            'Agregar' +
          '</button>' +
          '<a class="btn-whatsapp" href="#" onclick="enviarWhatsApp(event, \'' + v.nombre + ' - ' + v.especificaciones + '\')">' +
            '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
              '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>' +
            '</svg>' +
            'Consultar' +
          '</a>' +
        '</div>' +
      '</div>'
    ).join('');

    const btnsHTML = tieneMulti
      ? '<button class="card-carousel-btn prev" onclick="changeCardSlide(this, -1)">‹</button>' +
        '<button class="card-carousel-btn next" onclick="changeCardSlide(this, 1)">›</button>'
      : '';
    const dotsClass = tieneMulti ? '' : ' style="display:none"';

    // Índice de búsqueda textual para filtrado rápido
    const searchTerms = grupo.map(p => (p.nombre + ' ' + p.especificaciones + ' ' + p.categoria).toLowerCase()).join(' ');

    return '' +
      '<div class="card" data-search="' + searchTerms.replace(/"/g, '&quot;') + '">' +
        '<div class="card-carousel">' +
          '<div class="card-carousel-inner">' + carouselItems + '</div>' +
          btnsHTML +
          '<div class="card-carousel-dots"' + dotsClass + '>' + dots + '</div>' +
        '</div>' +
        '<div class="info">' + infoItems + '</div>' +
      '</div>';
  }

  /**
   * Recorre las categorías registradas en `gridMap`, agrupa productos y los renderiza en sus contenedores del DOM.
   * @function renderizarProductos
   * @returns {void}
   */
  function renderizarProductos() {
    Object.keys(gridMap).forEach(cat => {
      const gridId = gridMap[cat];
      const grid = document.getElementById(gridId);
      if (!grid) return;

      const nombresVistos = new Set();
      let html = '';

      productos.forEach(p => {
        if (p.categoria === cat && !nombresVistos.has(p.nombre)) {
          nombresVistos.add(p.nombre);
          const grupo = productos.filter(
            item => item.categoria === cat && item.nombre === p.nombre
          );
          html += generarCard(grupo);
        }
      });

      grid.innerHTML = html;
    });
  }

  // Ejecución inicial del renderizado del catálogo
  renderizarProductos();

  // =========================================================================
  // 2. CARRUSEL PRINCIPAL DE BANNERS (HERO SECTION)
  // =========================================================================

  /** Índice del slide activo en el banner principal */
  let currentSlide = 0;
  const slides = document.querySelectorAll('.carousel-item');
  const indicators = document.querySelectorAll('.indicator');
  const totalSlides = slides.length;
  let autoSlideInterval;

  /**
   * Cambia el slide visible del banner hero según el índice especificado.
   * @param {number} index - Índice destino del slide.
   */
  function showSlide(index) {
    if (index >= totalSlides) currentSlide = 0;
    else if (index < 0) currentSlide = totalSlides - 1;
    else currentSlide = index;

    const offset = -currentSlide * 100;
    const inner = document.querySelector('.carousel-inner');
    if (inner) inner.style.transform = 'translateX(' + offset + '%)';

    indicators.forEach((ind, i) => {
      ind.classList.toggle('active', i === currentSlide);
    });
  }

  /** Avanza al siguiente slide del banner hero */
  function nextSlide() {
    showSlide(currentSlide + 1);
    resetAutoSlide();
  }

  /** Retrocede al slide anterior del banner hero */
  function previousSlide() {
    showSlide(currentSlide - 1);
    resetAutoSlide();
  }

  /** Navega directamente a un slide específico por su indicador */
  function goToSlide(index) {
    showSlide(index);
    resetAutoSlide();
  }

  /** Inicia el temporizador de reproducción automática del banner principal (5 segundos) */
  function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
      nextSlide();
    }, 5000);
  }

  /** Reinicia el temporizador de rotación automática tras una interacción del usuario */
  function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
  }

  startAutoSlide();

  // =========================================================================
  // 3. NAVEGACIÓN, MENÚ MÓVIL Y BOTÓN FLOTANTE
  // =========================================================================

  const btnInicio = document.getElementById('btnInicio');

  // Visibilidad del botón flotante "Ir al Inicio" según la posición de scroll
  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
      if (btnInicio) btnInicio.classList.add('visible');
    } else {
      if (btnInicio) btnInicio.classList.remove('visible');
    }
  });

  /**
   * Realiza un desplazamiento suave hacia la sección de inicio compensando la barra de navegación fija.
   */
  window.irAlInicio = function() {
    const target = document.querySelector('#inicio');
    if (target) {
      const navHeight = document.querySelector('nav').offsetHeight;
      const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
      window.scrollTo({ top: targetPosition, behavior: 'smooth' });
    }
  };

  // Desplazamiento suave para enlaces ancla de la barra de navegación
  document.querySelectorAll('nav a').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId && targetId.startsWith('#')) {
        e.preventDefault();
        const target = document.querySelector(targetId);
        if (target) {
          const navHeight = document.querySelector('nav').offsetHeight;
          const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
          window.scrollTo({ top: targetPosition, behavior: 'smooth' });
        }
        const navLinks = document.getElementById('navMainLinks');
        const hamburger = document.getElementById('hamburgerBtn');
        if (navLinks && navLinks.classList.contains('open')) {
          navLinks.classList.remove('open');
          hamburger.classList.remove('active');
        }
      }
    });
  });

  /**
   * Alterna la visibilidad del menú desplegable móvil (hamburguesa).
   */
  window.toggleMenu = function() {
    const navLinks = document.getElementById('navMainLinks');
    const hamburger = document.getElementById('hamburgerBtn');
    navLinks.classList.toggle('open');
    hamburger.classList.toggle('active');
  };

  // Cierre automático del menú móvil al hacer clic fuera del panel
  document.addEventListener('click', function(e) {
    const navLinks = document.getElementById('navMainLinks');
    const hamburger = document.getElementById('hamburgerBtn');
    if (navLinks && hamburger && navLinks.classList.contains('open')) {
      if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) {
        navLinks.classList.remove('open');
        hamburger.classList.remove('active');
      }
    }
  });

  // =========================================================================
  // 4. CARRUSELES INDEPENDIENTES DE VARIANTES EN CARDS
  // =========================================================================

  /** Mapa de temporizadores de auto-rotación para tarjetas de productos */
  const cardCarousels = new Map();

  /**
   * Controlador de evento invocado al hacer clic en las flechas prev/next de una tarjeta.
   * @param {HTMLElement} button - Botón presionado dentro de la card.
   * @param {number} direction - -1 para anterior, 1 para siguiente.
   */
  function changeCardSlide(button, direction) {
    const card = button.closest('.card');
    changeCardSlideLogic(card, direction);
    resetCardAutoPlay(card);
  }

  /**
   * Aplica la transición y sincroniza la variante activa (imagen, dots y datos de texto/precio).
   * @param {HTMLElement} card - Elemento contenedor de la tarjeta.
   * @param {number} direction - Desplazamiento relativo del índice.
   */
  function changeCardSlideLogic(card, direction) {
    const carousel = card.querySelector('.card-carousel');
    const inner = carousel.querySelector('.card-carousel-inner');
    const items = carousel.querySelectorAll('.card-carousel-item');
    const dots = carousel.querySelectorAll('.card-carousel-dot');
    const infoItems = card.querySelectorAll('.card-info-item');
    const totalItems = items.length;

    let currentIndex = 0;
    dots.forEach((dot, index) => {
      if (dot.classList.contains('active')) currentIndex = index;
    });

    let newIndex = currentIndex + direction;
    if (newIndex >= totalItems) newIndex = 0;
    if (newIndex < 0) newIndex = totalItems - 1;

    inner.style.transform = 'translateX(' + (-newIndex * 100) + '%)';

    dots.forEach((dot, index) => {
      dot.classList.toggle('active', index === newIndex);
    });

    items.forEach((item, index) => {
      item.classList.toggle('active', index === newIndex);
    });

    infoItems.forEach((info, index) => {
      info.classList.toggle('active', index === newIndex);
    });
  }

  /**
   * Inicia la rotación automática de variantes para una tarjeta (cada 4 segundos).
   * @param {HTMLElement} card - Elemento contenedor de la tarjeta.
   */
  function startCardAutoPlay(card) {
    const items = card.querySelectorAll('.card-carousel-item');
    if (items.length > 1) {
      const interval = setInterval(() => {
        changeCardSlideLogic(card, 1);
      }, 4000);
      cardCarousels.set(card, interval);
    }
  }

  /**
   * Detiene la rotación automática de una tarjeta (ej. al posar el cursor).
   * @param {HTMLElement} card - Elemento contenedor de la tarjeta.
   */
  function stopCardAutoPlay(card) {
    if (cardCarousels.has(card)) {
      clearInterval(cardCarousels.get(card));
      cardCarousels.delete(card);
    }
  }

  /**
   * Reinicia el temporizador de auto-rotación de una tarjeta tras la interacción del usuario.
   * @param {HTMLElement} card - Elemento contenedor de la tarjeta.
   */
  function resetCardAutoPlay(card) {
    stopCardAutoPlay(card);
    startCardAutoPlay(card);
  }

  // Inicializar observadores y eventos para todas las cards
  document.querySelectorAll('.card').forEach(card => {
    const firstItem = card.querySelector('.card-carousel-item');
    if (firstItem) firstItem.classList.add('active');

    startCardAutoPlay(card);

    card.addEventListener('mouseenter', () => stopCardAutoPlay(card));
    card.addEventListener('mouseleave', () => startCardAutoPlay(card));
  });

  // Delegación de eventos para clics directos sobre los puntos (dots) de las cards
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('card-carousel-dot')) {
      const card = e.target.closest('.card');
      const dots = card.querySelectorAll('.card-carousel-dot');
      const items = card.querySelectorAll('.card-carousel-item');
      const clickedIndex = Array.from(dots).indexOf(e.target);
      const currentIndex = Array.from(dots).findIndex(dot => dot.classList.contains('active'));
      const direction = clickedIndex - currentIndex;

      items.forEach((item, index) => {
        item.classList.toggle('active', index === clickedIndex);
      });

      changeCardSlideLogic(card, direction);
      resetCardAutoPlay(card);
    }
  });

  // =========================================================================
  // 5. CONSULTAS DIRECTAS VÍA WHATSAPP
  // =========================================================================

  /**
   * Redirige al usuario al chat oficial de WhatsApp con un mensaje pre-cargado sobre el producto seleccionado.
   * @param {Event} event - Evento del clic.
   * @param {string} nombreProducto - Nombre y especificaciones del producto a consultar.
   */
  window.enviarWhatsApp = function(event, nombreProducto) {
    event.preventDefault();
    const numeroWhatsApp = '+51997833866';
    const mensaje = 'Hola, estoy interesado en: *' + nombreProducto + '*. ¿Podrías darme más información?';
    const mensajeCodificado = encodeURIComponent(mensaje);
    const urlWhatsApp = 'https://wa.me/' + numeroWhatsApp + '?text=' + mensajeCodificado;
    window.open(urlWhatsApp, '_blank');
  };

  // =========================================================================
  // 6. MOTOR DE BÚSQUEDA Y FILTRADO EN TIEMPO REAL
  // =========================================================================

  /**
   * Elimina diacríticos (tildes/acentos), convierte a minúsculas y recorta espacios en blanco.
   * Permite búsquedas tolerantes a variaciones ortográficas (ej. "teléfono" vs "telefono").
   * @param {string} txt - Texto a normalizar.
   * @returns {string} Texto limpio y normalizado.
   */
  function normalizarTexto(txt) {
    return (txt || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  }

  const busquedaInput = document.getElementById('busquedaInput');
  const btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
  const noSearchResults = document.getElementById('noSearchResults');
  const searchQueryText = document.getElementById('searchQueryText');

  /**
   * Ejecuta el filtrado en vivo en base al texto ingresado en la barra de búsqueda.
   * Oculta tarjetas no coincidentes y secciones de categorías que queden vacías.
   */
  function realizarBusqueda() {
    const query = normalizarTexto(busquedaInput.value);
    if (btnLimpiarBusqueda) {
      btnLimpiarBusqueda.style.display = query ? 'block' : 'none';
    }

    let totalCardsVisibles = 0;
    const allCards = document.querySelectorAll('.card');

    allCards.forEach(card => {
      const cardText = normalizarTexto(card.getAttribute('data-search') || card.textContent);
      const match = !query || cardText.includes(query);
      card.style.display = match ? '' : 'none';
      if (match) totalCardsVisibles++;
    });

    // Ocultar o mostrar secciones de categoría según la disponibilidad de tarjetas visibles
    Object.values(gridMap).forEach(gridId => {
      const grid = document.getElementById(gridId);
      if (!grid) return;
      const section = grid.closest('section');
      if (!section) return;
      const visibleInGrid = grid.querySelectorAll('.card:not([style*="display: none"])').length;
      section.style.display = visibleInGrid > 0 ? '' : 'none';
    });

    // Mostrar estado "Sin resultados" si ninguna tarjeta coincide
    if (query && totalCardsVisibles === 0) {
      if (noSearchResults) {
        noSearchResults.style.display = 'block';
        if (searchQueryText) searchQueryText.textContent = busquedaInput.value;
      }
    } else {
      if (noSearchResults) noSearchResults.style.display = 'none';
    }
  }

  if (busquedaInput) {
    busquedaInput.addEventListener('input', realizarBusqueda);
  }

  /**
   * Limpia el campo de búsqueda y restablece la visibilidad completa del catálogo.
   */
  window.limpiarBusqueda = function() {
    if (busquedaInput) {
      busquedaInput.value = '';
      realizarBusqueda();
      busquedaInput.focus();
    }
  };

  // =========================================================================
  // 7. GESTIÓN DE CUPONES, DESCUENTOS Y OPCIONES DE COMPRA
  // =========================================================================

  /**
   * Base de datos de cupones promocionales válidos en la tienda.
   * @type {Object<string, {nombre: string, desc: string, pct?: number, fijoPEN?: number}>}
   */
  const CUPONES = {
    'JEREMYTECH10': { nombre: 'JEREMYTECH10', desc: '10% de Descuento', pct: 0.10 },
    'PRIMERACOMPRA': { nombre: 'PRIMERACOMPRA', desc: '15% de Descuento', pct: 0.15 },
    'JEREMY50': { nombre: 'JEREMY50', desc: 'S/ 50.00 OFF', fijoPEN: 50 },
    'OFERTA2026': { nombre: 'OFERTA2026', desc: '12% de Descuento', pct: 0.12 }
  };

  /** Cupón actualmente aplicado a la compra */
  let cuponActivo = null;
  /** Modalidad de entrega seleccionada ('domicilio' | 'tienda') */
  let tipoEnvioActual = 'domicilio';
  /** Tipo de comprobante fiscal ('boleta' | 'factura') */
  let tipoComprobanteActual = 'boleta';
  /** Método de pago seleccionado ('tarjeta' | 'yape' | 'transferencia' | 'contraentrega') */
  let metodoPagoActual = 'tarjeta';
  /** Referencia a la última orden procesada exitosamente */
  let ultimoPedido = null;

  /**
   * Valida el código de cupón ingresado y actualiza los cálculos de descuento del carrito.
   */
  window.aplicarCupon = function() {
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

    if (CUPONES[codigo]) {
      cuponActivo = CUPONES[codigo];
      if (msgEl) {
        msgEl.textContent = '✓ Cupón ' + codigo + ' aplicado: ' + cuponActivo.desc;
        msgEl.className = 'coupon-tag-msg';
        msgEl.style.display = 'flex';
      }
      mostrarToast('¡Cupón ' + codigo + ' aplicado con éxito!');
      renderizarCarrito();
    } else {
      cuponActivo = null;
      if (msgEl) {
        msgEl.textContent = 'Código de cupón no válido o vencido';
        msgEl.className = 'coupon-tag-msg error';
        msgEl.style.display = 'block';
      }
      renderizarCarrito();
    }
  };

  /**
   * Alterna entre envío a domicilio y recojo en sede central física.
   * @param {'domicilio'|'tienda'} tipo - Modalidad de entrega elegida.
   */
  window.cambiarOpcionEnvio = function(tipo) {
    tipoEnvioActual = tipo;
    const optDom = document.getElementById('delivOptDomicilio');
    const optTie = document.getElementById('delivOptTienda');
    const dirInput = document.getElementById('clienteDireccion');

    if (optDom) optDom.classList.toggle('active', tipo === 'domicilio');
    if (optTie) optTie.classList.toggle('active', tipo === 'tienda');

    if (dirInput) {
      if (tipo === 'tienda') {
        dirInput.value = 'Recojo en Sede Central: La Paz Soldán / La Joya, Arequipa';
      } else if (dirInput.value.includes('Recojo en Sede Central')) {
        dirInput.value = '';
      }
    }
  };

  /**
   * Conmuta los campos de facturación según se requiera Boleta (DNI) o Factura (RUC + Razón Social).
   * @param {'boleta'|'factura'} tipo - Tipo de comprobante requerido.
   */
  window.cambiarTipoComprobante = function(tipo) {
    tipoComprobanteActual = tipo;
    const optBol = document.getElementById('optBoleta');
    const optFac = document.getElementById('optFactura');
    const campoBol = document.getElementById('campoBoletaDni');
    const campoFac = document.getElementById('campoFacturaRuc');
    const dniInput = document.getElementById('clienteDni');
    const rucInput = document.getElementById('clienteRuc');
    const rzInput = document.getElementById('clienteRazonSocial');

    if (optBol) optBol.classList.toggle('active', tipo === 'boleta');
    if (optFac) optFac.classList.toggle('active', tipo === 'factura');

    if (tipo === 'boleta') {
      if (campoBol) campoBol.style.display = 'block';
      if (campoFac) campoFac.style.display = 'none';
      if (dniInput) dniInput.required = true;
      if (rucInput) rucInput.required = false;
      if (rzInput) rzInput.required = false;
    } else {
      if (campoBol) campoBol.style.display = 'none';
      if (campoFac) campoFac.style.display = 'grid';
      if (dniInput) dniInput.required = false;
      if (rucInput) rucInput.required = true;
      if (rzInput) rzInput.required = true;
    }
  };

  /**
   * Conmuta la pestaña de pasarela de pago seleccionada y ajusta la obligatoriedad de campos.
   * @param {'tarjeta'|'yape'|'transferencia'|'contraentrega'} metodo - Método de pago a activar.
   */
  window.cambiarMetodoPago = function(metodo) {
    metodoPagoActual = metodo;
    const tabs = ['tarjeta', 'yape', 'transferencia', 'contraentrega'];
    const tabMap = {
      'tarjeta': { btn: 'tabTarjeta', panel: 'panelMetodoTarjeta' },
      'yape': { btn: 'tabYape', panel: 'panelMetodoYape' },
      'transferencia': { btn: 'tabTransf', panel: 'panelMetodoTransf' },
      'contraentrega': { btn: 'tabContra', panel: 'panelMetodoContra' }
    };

    tabs.forEach(t => {
      const b = document.getElementById(tabMap[t].btn);
      const p = document.getElementById(tabMap[t].panel);
      if (b) b.classList.toggle('active', t === metodo);
      if (p) p.style.display = (t === metodo) ? 'block' : 'none';
    });

    // Validaciones condicionales de inputs según el método
    const numTarj = document.getElementById('tarjetaNumero');
    const titTarj = document.getElementById('tarjetaTitular');
    const expTarj = document.getElementById('tarjetaExpiracion');
    const cvvTarj = document.getElementById('tarjetaCvv');
    const yapeOp = document.getElementById('yapeCodigoOperacion');
    const transfOp = document.getElementById('transfCodigoOperacion');

    if (numTarj) numTarj.required = (metodo === 'tarjeta');
    if (titTarj) titTarj.required = (metodo === 'tarjeta');
    if (expTarj) expTarj.required = (metodo === 'tarjeta');
    if (cvvTarj) cvvTarj.required = (metodo === 'tarjeta');
    if (yapeOp) yapeOp.required = (metodo === 'yape');
    if (transfOp) transfOp.required = (metodo === 'transferencia');

    actualizarMontosPago();
  };

  /**
   * Copia texto al portapapeles utilizando la API moderna Clipboard con fallback para compatibilidad.
   * @param {string} texto - Texto que será copiado.
   * @param {string} [feedback] - Mensaje a mostrar en el Toast de notificación.
   */
  window.copiarTexto = function(texto, feedback) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(texto).then(() => {
        mostrarToast('📋 ' + (feedback || 'Copiado al portapapeles'));
      }).catch(() => {
        copiarFallback(texto, feedback);
      });
    } else {
      copiarFallback(texto, feedback);
    }
  };

  /**
   * Fallback de copia al portapapeles mediante elemento `<input>` temporal y `document.execCommand`.
   * @param {string} texto - Texto a copiar.
   * @param {string} [feedback] - Mensaje de notificación.
   */
  function copiarFallback(texto, feedback) {
    const tempInput = document.createElement('input');
    tempInput.value = texto;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    mostrarToast('📋 ' + (feedback || 'Copiado al portapapeles'));
  }

  // =========================================================================
  // 8. GESTIÓN DEL CARRITO DE COMPRAS (LOCALSTORAGE)
  // =========================================================================

  /**
   * Estado del carrito de compras cargado desde LocalStorage (`jt_cart`).
   * @type {Array<{id: string, nombre: string, especificaciones: string, precioPEN: number, imagen: string, cantidad: number}>}
   */
  let carrito = JSON.parse(localStorage.getItem('jt_cart')) || [];

  /**
   * Persiste el estado del carrito en `localStorage`, actualiza badges y redibuja la interfaz del carrito.
   */
  function guardarCarrito() {
    localStorage.setItem('jt_cart', JSON.stringify(carrito));
    renderizarCarrito();
    actualizarBadges();
  }

  /**
   * Añade una unidad (o cantidad específica) de un producto al carrito de compras.
   * @param {string} productoId - ID del producto o variante a añadir.
   * @param {number} [cantidad=1] - Cantidad a sumar.
   */
  window.agregarAlCarrito = function(productoId, cantidad = 1) {
    const prod = productos.find(p => p.id === productoId);
    if (!prod) return;

    const itemExistente = carrito.find(item => item.id === productoId);
    if (itemExistente) {
      itemExistente.cantidad += cantidad;
    } else {
      carrito.push({
        id: prod.id,
        nombre: prod.nombre,
        especificaciones: prod.especificaciones,
        precioPEN: prod.precioPEN,
        imagen: prod.imagen,
        cantidad: cantidad
      });
    }

    guardarCarrito();
    mostrarToast('¡' + prod.nombre + ' añadido al carrito!');
  };

  /**
   * Incrementa o decrementa la cantidad de un ítem en el carrito; lo remueve si llega a 0.
   * @param {string} productoId - ID del producto.
   * @param {number} delta - Variación (+1 o -1).
   */
  window.cambiarCantidad = function(productoId, delta) {
    const item = carrito.find(p => p.id === productoId);
    if (!item) return;
    item.cantidad += delta;
    if (item.cantidad <= 0) {
      carrito = carrito.filter(p => p.id !== productoId);
    }
    guardarCarrito();
  };

  /**
   * Elimina completamente un producto del carrito sin importar su cantidad.
   * @param {string} productoId - ID del producto a eliminar.
   */
  window.eliminarDelCarrito = function(productoId) {
    carrito = carrito.filter(p => p.id !== productoId);
    guardarCarrito();
  };

  /**
   * Vacía la totalidad de los artículos del carrito y reinicia cupones activos.
   */
  window.vaciarCarrito = function() {
    if (carrito.length === 0) return;
    carrito = [];
    cuponActivo = null;
    const msgEl = document.getElementById('cuponTagMsg');
    if (msgEl) msgEl.style.display = 'none';
    const cuponInp = document.getElementById('cuponInput');
    if (cuponInp) cuponInp.value = '';
    guardarCarrito();
    mostrarToast('Carrito vaciado');
  };

  /**
   * Calcula el subtotal, descuento aplicado (porcentaje o fijo) y total a pagar en Soles (PEN).
   * @returns {{subtotalPEN: number, descuentoPEN: number, totalPEN: number}}
   */
  function calcularTotales() {
    let subtotalPEN = carrito.reduce((acc, cur) => acc + (cur.precioPEN * cur.cantidad), 0);
    let descuentoPEN = 0;

    if (cuponActivo) {
      if (cuponActivo.pct) {
        descuentoPEN = subtotalPEN * cuponActivo.pct;
      } else if (cuponActivo.fijoPEN) {
        descuentoPEN = Math.min(cuponActivo.fijoPEN, subtotalPEN);
      }
    }

    let totalPEN = Math.max(0, subtotalPEN - descuentoPEN);
    return { subtotalPEN, descuentoPEN, totalPEN };
  }

  /**
   * Actualiza los montos dinámicos en los paneles de pago específicos (ej. monto QR de Yape).
   */
  function actualizarMontosPago() {
    const { totalPEN } = calcularTotales();
    const yapeMontoEl = document.getElementById('yapeMontoPEN');
    if (yapeMontoEl) {
      yapeMontoEl.textContent = 'S/ ' + totalPEN.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }

  /**
   * Renderiza los elementos del drawer del carrito con conversión de divisas en tiempo real.
   */
  function renderizarCarrito() {
    const emptyState = document.getElementById('cartEmptyState');
    const itemsList = document.getElementById('cartItemsList');
    const cartFooter = document.getElementById('cartFooter');
    const countPill = document.getElementById('cartCountPill');
    const subtotalEl = document.getElementById('cartSubtotal');
    const discountRow = document.getElementById('cartDiscountRow');
    const discountEl = document.getElementById('cartDiscount');
    const totalEl = document.getElementById('cartTotal');
    const checkoutTotalEl = document.getElementById('checkoutTotalMonto');
    const checkoutDiscNote = document.getElementById('checkoutDiscountNote');

    const totalItems = carrito.reduce((acc, cur) => acc + cur.cantidad, 0);
    if (countPill) countPill.textContent = totalItems + (totalItems === 1 ? ' item' : ' items');

    if (carrito.length === 0) {
      if (emptyState) emptyState.style.display = 'block';
      if (itemsList) itemsList.innerHTML = '';
      if (cartFooter) cartFooter.style.display = 'none';
      if (checkoutTotalEl) checkoutTotalEl.textContent = 'PEN 0.00';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (cartFooter) cartFooter.style.display = 'block';

    const currentMoneda = (typeof monedaActual !== 'undefined') ? monedaActual : 'PEN';
    const simbolo = (typeof MONEDAS !== 'undefined' && MONEDAS[currentMoneda]) ? MONEDAS[currentMoneda].simbolo : currentMoneda;

    const itemsHTML = carrito.map(item => {
      const precioUnitario = typeof convertirPrecio === 'function' ? convertirPrecio(item.precioPEN, currentMoneda) : item.precioPEN;
      const subtotalItem = precioUnitario * item.cantidad;

      return '' +
        '<div class="cart-item">' +
          '<img src="' + item.imagen + '" alt="' + item.nombre + '" />' +
          '<div class="cart-item-info">' +
            '<div>' +
              '<div class="cart-item-title">' + item.nombre + '</div>' +
              '<div class="cart-item-spec">' + item.especificaciones + '</div>' +
            '</div>' +
            '<div class="cart-item-row">' +
              '<div class="cart-item-price">' + simbolo + ' ' + subtotalItem.toLocaleString() + '</div>' +
              '<div class="cart-item-controls">' +
                '<button class="qty-btn" onclick="cambiarCantidad(\'' + item.id + '\', -1)">-</button>' +
                '<span class="qty-val">' + item.cantidad + '</span>' +
                '<button class="qty-btn" onclick="cambiarCantidad(\'' + item.id + '\', 1)">+</button>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<button class="btn-remove-item" onclick="eliminarDelCarrito(\'' + item.id + '\')" title="Eliminar">&times;</button>' +
        '</div>';
    }).join('');

    if (itemsList) itemsList.innerHTML = itemsHTML;

    const { subtotalPEN, descuentoPEN, totalPEN } = calcularTotales();

    const subtotalConv = typeof convertirPrecio === 'function' ? convertirPrecio(subtotalPEN, currentMoneda) : subtotalPEN;
    const totalConv = typeof convertirPrecio === 'function' ? convertirPrecio(totalPEN, currentMoneda) : totalPEN;
    const descConv = typeof convertirPrecio === 'function' ? convertirPrecio(descuentoPEN, currentMoneda) : descuentoPEN;

    if (subtotalEl) subtotalEl.textContent = simbolo + ' ' + subtotalConv.toLocaleString();

    if (descuentoPEN > 0) {
      if (discountRow) discountRow.style.display = 'flex';
      if (discountEl) discountEl.textContent = '-' + simbolo + ' ' + descConv.toLocaleString() + ' (' + (cuponActivo.desc || '') + ')';
      if (checkoutDiscNote) {
        checkoutDiscNote.style.display = 'block';
        checkoutDiscNote.textContent = 'Cupón ' + cuponActivo.nombre + ' (-' + simbolo + ' ' + descConv.toLocaleString() + ')';
      }
    } else {
      if (discountRow) discountRow.style.display = 'none';
      if (checkoutDiscNote) checkoutDiscNote.style.display = 'none';
    }

    const formattedTotal = simbolo + ' ' + totalConv.toLocaleString();
    if (totalEl) totalEl.textContent = formattedTotal;
    if (checkoutTotalEl) checkoutTotalEl.textContent = formattedTotal;

    actualizarMontosPago();
  }

  /**
   * Actualiza el contador numérico en el badge del botón de carrito en la barra de navegación.
   */
  function actualizarBadges() {
    const totalItems = carrito.reduce((acc, cur) => acc + cur.cantidad, 0);
    const badgeNav = document.getElementById('cartBadgeNav');
    if (badgeNav) badgeNav.textContent = totalItems;
  }

  /**
   * Hook global invocado por `puente-digital.js` cuando se cambia la divisa de la tienda.
   */
  window.actualizarPreciosEnCarrito = function() {
    renderizarCarrito();
  };

  /** Abre el panel lateral deslizante (drawer) del carrito de compras */
  window.abrirCarrito = function() {
    const overlay = document.getElementById('cartOverlay');
    const drawer = document.getElementById('cartDrawer');
    if (overlay) overlay.classList.add('open');
    if (drawer) drawer.classList.add('open');
  };

  /** Cierra el panel lateral del carrito de compras */
  window.cerrarCarrito = function() {
    const overlay = document.getElementById('cartOverlay');
    const drawer = document.getElementById('cartDrawer');
    if (overlay) overlay.classList.remove('open');
    if (drawer) drawer.classList.remove('open');
  };

  // Inicialización del carrito
  renderizarCarrito();
  actualizarBadges();

  // =========================================================================
  // 9. PASARELA DE CHECKOUT MULTIMÉTODO Y FORMULARIO VIRTUAL
  // =========================================================================

  /**
   * Abre la ventana modal del checkout si el carrito contiene productos.
   */
  window.abrirCheckout = function() {
    if (carrito.length === 0) {
      mostrarToast('Tu carrito está vacío');
      return;
    }
    cerrarCarrito();
    const modal = document.getElementById('checkoutModal');
    if (modal) modal.style.display = 'flex';
    cambiarMetodoPago(metodoPagoActual);
    cambiarTipoComprobante(tipoComprobanteActual);
    cambiarOpcionEnvio(tipoEnvioActual);
    renderizarCarrito();
  };

  /** Cierra la modal de checkout */
  window.cerrarCheckout = function() {
    const modal = document.getElementById('checkoutModal');
    if (modal) modal.style.display = 'none';
  };

  /**
   * Inicializa la vista previa interactiva y el formateo de la tarjeta de crédito virtual
   * (detección de franquicia Visa, MasterCard, Amex, formateo de 16 dígitos, expiración MM/AA).
   */
  function initTarjetaVirtual() {
    const numInput = document.getElementById('tarjetaNumero');
    const nameInput = document.getElementById('tarjetaTitular');
    const expInput = document.getElementById('tarjetaExpiracion');
    const cvvInput = document.getElementById('tarjetaCvv');

    const prevNumber = document.getElementById('cardPreviewNumber');
    const prevName = document.getElementById('cardPreviewName');
    const prevExp = document.getElementById('cardPreviewExpiry');
    const brandLogo = document.getElementById('cardBrandLogo');
    const brandIcon = document.getElementById('inputBrandIcon');

    if (numInput) {
      numInput.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 16);
        let matches = val.match(/.{1,4}/g);
        let formatted = matches ? matches.join(' ') : val;
        e.target.value = formatted;

        if (prevNumber) {
          prevNumber.textContent = formatted || '•••• •••• •••• ••••';
        }

        let brand = 'TARJETA';
        let icon = '💳';
        if (/^4/.test(val)) {
          brand = 'VISA';
          icon = '💳 Visa';
        } else if (/^(5[1-5]|2[2-7])/.test(val)) {
          brand = 'MASTERCARD';
          icon = '💳 MC';
        } else if (/^3[47]/.test(val)) {
          brand = 'AMEX';
          icon = '💳 Amex';
        }
        if (brandLogo) brandLogo.textContent = brand;
        if (brandIcon) brandIcon.textContent = icon;
      });
    }

    if (nameInput) {
      nameInput.addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        if (prevName) prevName.textContent = val || 'NOMBRE Y APELLIDO';
      });
    }

    if (expInput) {
      expInput.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 4);
        if (val.length >= 3) {
          val = val.substring(0, 2) + '/' + val.substring(2);
        }
        e.target.value = val;
        if (prevExp) prevExp.textContent = val || 'MM/AA';
      });
    }

    if (cvvInput) {
      cvvInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
      });
    }
  }

  initTarjetaVirtual();

  // =========================================================================
  // 10. HISTORIAL DE PEDIDOS Y SEGUIMIENTO EN TIEMPO REAL
  // =========================================================================

  /**
   * Obtiene la lista de órdenes registradas desde `localStorage` (`jt_orders_history`).
   * @returns {Array<Object>}
   */
  function obtenerHistorialPedidos() {
    return JSON.parse(localStorage.getItem('jt_orders_history')) || [];
  }

  /**
   * Guarda una nueva orden al inicio de la lista en `localStorage`.
   * @param {Object} pedido - Objeto con los datos consolidados de la compra.
   */
  function guardarEnHistorialPedidos(pedido) {
    const historial = obtenerHistorialPedidos();
    historial.unshift(pedido);
    localStorage.setItem('jt_orders_history', JSON.stringify(historial));
  }

  /**
   * Procesa la orden de compra, valida los datos del formulario, simula el pago seguro
   * y despliega el ticket digital con opción de envío a WhatsApp.
   * @param {Event} event - Evento del formulario de checkout.
   */
  window.procesarPago = function(event) {
    event.preventDefault();
    if (carrito.length === 0) return;

    const btn = document.getElementById('btnPagarAhora');
    const btnText = btn ? btn.querySelector('.btn-text') : null;
    const spinner = btn ? btn.querySelector('.btn-spinner') : null;

    if (btn) btn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';

    const nombre = document.getElementById('clienteNombre').value.trim();
    const telefono = document.getElementById('clienteTelefono').value.trim();
    const email = document.getElementById('clienteEmail').value.trim();
    const direccion = document.getElementById('clienteDireccion').value.trim();

    // Documento / Facturación
    let comprobanteTexto = '';
    let docTexto = '';
    if (tipoComprobanteActual === 'boleta') {
      const dni = (document.getElementById('clienteDni')?.value || '').trim();
      comprobanteTexto = 'Boleta de Venta Electrónica';
      docTexto = 'DNI: ' + (dni || 'No especificado');
    } else {
      const ruc = (document.getElementById('clienteRuc')?.value || '').trim();
      const rz = (document.getElementById('clienteRazonSocial')?.value || '').trim();
      comprobanteTexto = 'Factura Electrónica';
      docTexto = 'RUC: ' + (ruc || 'No especificado') + ' - ' + (rz || '');
    }

    // Método de Pago
    let metodoPagoTexto = '';
    let detallePago = '';
    if (metodoPagoActual === 'tarjeta') {
      const numTarj = (document.getElementById('tarjetaNumero')?.value || '').trim();
      const ultimos4 = numTarj.slice(-4) || 'XXXX';
      const brandLogo = document.getElementById('cardBrandLogo')?.textContent || 'TARJETA';
      metodoPagoTexto = 'Tarjeta ' + brandLogo + ' (•••• ' + ultimos4 + ')';
      detallePago = 'Cobro procesado vía SSL Seguro 256-bit';
    } else if (metodoPagoActual === 'yape') {
      const codOp = (document.getElementById('yapeCodigoOperacion')?.value || '').trim();
      metodoPagoTexto = 'Yape / Plin';
      detallePago = 'Código de Op: #' + (codOp || '849201');
    } else if (metodoPagoActual === 'transferencia') {
      const codOp = (document.getElementById('transfCodigoOperacion')?.value || '').trim();
      metodoPagoTexto = 'Transferencia Bancaria Directa';
      detallePago = 'Nro. de Operación: #' + (codOp || '1290348');
    } else {
      metodoPagoTexto = 'Pago Contra Entrega / En Tienda';
      detallePago = 'A pagar al momento del recojo o entrega';
    }

    // Modalidad de Entrega
    const entregaTexto = (tipoEnvioActual === 'tienda')
      ? '🏬 Recojo en Sede Central (La Joya, Arequipa)'
      : '🚚 Envío a Domicilio (' + direccion + ')';

    const currentMoneda = (typeof monedaActual !== 'undefined') ? monedaActual : 'PEN';
    const simbolo = (typeof MONEDAS !== 'undefined' && MONEDAS[currentMoneda]) ? MONEDAS[currentMoneda].simbolo : currentMoneda;
    const { subtotalPEN, descuentoPEN, totalPEN } = calcularTotales();

    const totalConv = typeof convertirPrecio === 'function' ? convertirPrecio(totalPEN, currentMoneda) : totalPEN;
    const totalFormateado = simbolo + ' ' + totalConv.toLocaleString();

    // Generar código único de orden
    const ordenId = '#JT-' + Math.floor(10000 + Math.random() * 90000);
    const fecha = new Date().toLocaleDateString('es-PE', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });

    ultimoPedido = {
      id: ordenId,
      cliente: nombre,
      telefono: telefono,
      email: email,
      direccion: direccion,
      documento: docTexto,
      comprobante: comprobanteTexto,
      metodoPago: metodoPagoTexto,
      detallePago: detallePago,
      tipoEntrega: entregaTexto,
      fecha: fecha,
      items: [...carrito],
      subtotalPEN: subtotalPEN,
      descuentoPEN: descuentoPEN,
      cupon: cuponActivo ? cuponActivo.nombre : null,
      total: totalFormateado,
      totalPEN: totalPEN,
      moneda: currentMoneda,
      estado: (metodoPagoActual === 'contraentrega') ? 'Listo para Retiro' : 'Pagado',
      timelineStep: (metodoPagoActual === 'contraentrega') ? 2 : 1
    };

    // Registrar en el historial de compras local
    guardarEnHistorialPedidos(ultimoPedido);

    // =========================================================================
    // SESIÓN 3: Sincronización atómica con el Modelo Relacional (db-modelo.js)
    // =========================================================================
    if (typeof crearCliente === 'function' && typeof crearPedido === 'function') {
      try {
        const esB2B = (tipoComprobanteActual === 'factura');
        const dniVal = (document.getElementById('clienteDni')?.value || '').trim();
        const rucVal = (document.getElementById('clienteRuc')?.value || '').trim();
        const rzVal = (document.getElementById('clienteRazonSocial')?.value || '').trim();

        const clienteRel = crearCliente({
          nombre: esB2B ? (rzVal || nombre) : nombre,
          tipo: esB2B ? 'B2B' : 'B2C',
          dni: esB2B ? '' : dniVal,
          ruc: esB2B ? rucVal : '',
          empresa: esB2B ? (rzVal || nombre) : '',
          telefono: telefono,
          email: email,
          direccion: direccion || 'Distrito de La Joya, Arequipa'
        });

        const itemsRel = carrito.map(item => ({
          productoId: item.id,
          cantidad: item.cantidad,
          precioUnitario: item.precioPEN
        }));

        crearPedido(clienteRel.id, itemsRel, typeof productos !== 'undefined' ? productos : [], {
          metodo: metodoPagoTexto,
          tipoComprobante: comprobanteTexto,
          tipoEntrega: entregaTexto,
          estadoPago: (metodoPagoActual === 'contraentrega') ? 'Pendiente' : 'Pagado',
          codigoOperacion: (metodoPagoActual === 'yape' || metodoPagoActual === 'transferencia') ? detallePago : null
        });
      } catch (errRel) {
        console.warn('Advertencia al sincronizar con db-modelo.js:', errRel);
      }
    }

    // Simulación de procesamiento de pasarela de pago (1.4s)
    setTimeout(() => {
      if (btn) btn.disabled = false;
      if (btnText) btnText.style.display = 'inline-block';
      if (spinner) spinner.style.display = 'none';

      // Rellenar ticket de compra en la modal de éxito
      const tOrder = document.getElementById('ticketOrderId');
      const tCli = document.getElementById('ticketCliente');
      const tDoc = document.getElementById('ticketDocumento');
      const tComp = document.getElementById('ticketComprobante');
      const tMet = document.getElementById('ticketMetodoPago');
      const tEnt = document.getElementById('ticketEntrega');
      const tFec = document.getElementById('ticketFecha');
      const tTot = document.getElementById('ticketTotal');

      if (tOrder) tOrder.textContent = ultimoPedido.id;
      if (tCli) tCli.textContent = ultimoPedido.cliente;
      if (tDoc) tDoc.textContent = ultimoPedido.documento;
      if (tComp) tComp.textContent = ultimoPedido.comprobante;
      if (tMet) tMet.textContent = ultimoPedido.metodoPago + ' (' + ultimoPedido.detallePago + ')';
      if (tEnt) tEnt.textContent = ultimoPedido.tipoEntrega;
      if (tFec) tFec.textContent = ultimoPedido.fecha;
      if (tTot) tTot.textContent = ultimoPedido.total;

      const itemsContainer = document.getElementById('ticketItems');
      if (itemsContainer) {
        itemsContainer.innerHTML = ultimoPedido.items.map(it =>
          '<div class="ticket-item-line"><span>' + it.cantidad + 'x ' + it.nombre + ' (' + it.especificaciones + ')</span></div>'
        ).join('');
      }

      // Vaciar carrito y resetear cupón
      carrito = [];
      cuponActivo = null;
      const msgEl = document.getElementById('cuponTagMsg');
      if (msgEl) msgEl.style.display = 'none';
      const cuponInp = document.getElementById('cuponInput');
      if (cuponInp) cuponInp.value = '';
      guardarCarrito();

      cerrarCheckout();
      const form = document.getElementById('checkoutForm');
      if (form) form.reset();

      const succModal = document.getElementById('successModal');
      if (succModal) succModal.style.display = 'flex';
    }, 1400);
  };

  /** Cierra la modal de éxito tras la compra */
  window.cerrarModalExito = function() {
    const modal = document.getElementById('successModal');
    if (modal) modal.style.display = 'none';
  };

  /**
   * Abre la modal de historial de pedidos y renderiza la lista actualizada.
   */
  window.abrirHistorialPedidos = function() {
    const modal = document.getElementById('ordersModal');
    if (!modal) return;
    renderizarHistorialPedidos();
    modal.style.display = 'flex';
  };

  /** Cierra la modal de historial de pedidos */
  window.cerrarHistorialPedidos = function() {
    const modal = document.getElementById('ordersModal');
    if (modal) modal.style.display = 'none';
  };

  /**
   * Renderiza el historial de pedidos y la línea de tiempo de seguimiento (timeline).
   */
  function renderizarHistorialPedidos() {
    const container = document.getElementById('ordersListContainer');
    if (!container) return;

    const historial = obtenerHistorialPedidos();

    if (historial.length === 0) {
      container.innerHTML = '' +
        '<div class="empty-orders-state">' +
          '<div class="empty-icon">📦</div>' +
          '<h4>Aún no tienes pedidos registrados</h4>' +
          '<p>Tus compras y estados de entrega aparecerán aquí en tiempo real.</p>' +
          '<button class="btn-explorar" onclick="cerrarHistorialPedidos()">Explorar Tienda</button>' +
        '</div>';
      return;
    }

    const stepsLabels = ['Pagado', 'En Preparación', 'En Camino', 'Entregado'];

    container.innerHTML = historial.map(order => {
      const activeStep = order.timelineStep || 1;

      const timelineHTML = stepsLabels.map((lbl, idx) => {
        const stepNum = idx + 1;
        let stepClass = 'timeline-step';
        if (stepNum < activeStep) stepClass += ' completed';
        else if (stepNum === activeStep) stepClass += ' current';

        return '' +
          '<div class="' + stepClass + '">' +
            '<div class="timeline-dot">' + (stepNum < activeStep ? '✓' : stepNum) + '</div>' +
            '<span class="timeline-label">' + lbl + '</span>' +
          '</div>';
      }).join('');

      const itemsListMini = order.items.map(it =>
        '<li>' + it.cantidad + 'x ' + it.nombre + ' (' + it.especificaciones + ')</li>'
      ).join('');

      return '' +
        '<div class="order-card">' +
          '<div class="order-card-header">' +
            '<span class="order-id-tag">' + order.id + '</span>' +
            '<span class="order-status-badge">' + (order.estado || 'Pagado') + '</span>' +
          '</div>' +
          '<div class="order-timeline">' + timelineHTML + '</div>' +
          '<div class="order-details-summary">' +
            '<div><strong>Cliente:</strong> ' + order.cliente + ' (' + order.documento + ')</div>' +
            '<div><strong>Comprobante:</strong> ' + order.comprobante + '</div>' +
            '<div><strong>Pago:</strong> ' + order.metodoPago + '</div>' +
            '<div><strong>Entrega:</strong> ' + order.tipoEntrega + '</div>' +
            '<div><strong>Fecha:</strong> ' + order.fecha + '</div>' +
            '<ul class="order-items-list-mini">' + itemsListMini + '</ul>' +
          '</div>' +
          '<div class="order-card-actions">' +
            '<div class="order-card-total">Total: <strong>' + order.total + '</strong></div>' +
            '<button type="button" class="btn-order-wa" onclick="contactarOrdenWhatsApp(\'' + order.id + '\')">' +
              '💬 Soporte WhatsApp' +
            '</button>' +
          '</div>' +
        '</div>';
    }).join('');
  }

  /**
   * Inicia una consulta en WhatsApp para una orden específica del historial.
   * @param {string} orderId - Identificador de la orden (ej. '#JT-49120').
   */
  window.contactarOrdenWhatsApp = function(orderId) {
    const historial = obtenerHistorialPedidos();
    const order = historial.find(o => o.id === orderId);
    if (!order) return;

    const numeroWhatsApp = '+51997833866';
    let msg = 'Hola Jeremy Tech, quisiera consultar sobre el estado de mi orden *' + order.id + '* a nombre de *' + order.cliente + '*.';
    window.open('https://wa.me/' + numeroWhatsApp + '?text=' + encodeURIComponent(msg), '_blank');
  };

  /**
   * Genera un comprobante de compra digital estructurado y lo envía vía enlace directo a WhatsApp.
   */
  window.enviarReciboWhatsApp = function() {
    if (!ultimoPedido) return;
    const numeroWhatsApp = '+51997833866';
    let mensaje = '*COMPROBANTE DE COMPRA — JEREMY TECH*\n';
    mensaje += '━━━━━━━━━━━━━━━━━━━━━━━\n';
    mensaje += '📌 *Orden:* ' + ultimoPedido.id + '\n';
    mensaje += '📅 *Fecha:* ' + ultimoPedido.fecha + '\n';
    mensaje += '👤 *Cliente:* ' + ultimoPedido.cliente + '\n';
    mensaje += '📄 *Documento:* ' + ultimoPedido.documento + '\n';
    mensaje += '🧾 *Comprobante:* ' + ultimoPedido.comprobante + '\n';
    mensaje += '💳 *Método de Pago:* ' + ultimoPedido.metodoPago + ' (' + ultimoPedido.detallePago + ')\n';
    mensaje += '📍 *Modalidad de Entrega:* ' + ultimoPedido.tipoEntrega + '\n';
    if (ultimoPedido.cupon) {
      mensaje += '🎟️ *Cupón Aplicado:* ' + ultimoPedido.cupon + '\n';
    }
    mensaje += '━━━━━━━━━━━━━━━━━━━━━━━\n';
    mensaje += '📦 *ITEMS COMPRADOS:*\n';
    ultimoPedido.items.forEach(it => {
      mensaje += '• ' + it.cantidad + 'x ' + it.nombre + ' [' + it.especificaciones + ']\n';
    });
    mensaje += '━━━━━━━━━━━━━━━━━━━━━━━\n';
    mensaje += '💰 *TOTAL PAGADO:* ' + ultimoPedido.total + '\n\n';
    mensaje += '¡Gracias por confiar en *Jeremy Tech*! Tu orden está siendo procesada.';

    const urlWhatsApp = 'https://wa.me/' + numeroWhatsApp + '?text=' + encodeURIComponent(mensaje);
    window.open(urlWhatsApp, '_blank');
  };

  // =========================================================================
  // 11. SISTEMA DE NOTIFICACIONES TOAST
  // =========================================================================

  let toastTimer;

  /**
   * Despliega una alerta emergente flotante (Toast) en la esquina inferior de la pantalla.
   * @param {string} mensaje - Mensaje a mostrar.
   */
  function mostrarToast(mensaje) {
    const toast = document.getElementById('cartToast');
    const msg = document.getElementById('toastMessage');
    if (!toast || !msg) return;
    msg.textContent = mensaje;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('show');
    }, 2800);
  }

  // =========================================================================
  // 12. EXPOSICIÓN AL ÁMBITO GLOBAL PARA HANDLERS HTML
  // =========================================================================
  window.nextSlide = nextSlide;
  window.previousSlide = previousSlide;
  window.goToSlide = goToSlide;
  window.changeCardSlide = changeCardSlide;
  window.mostrarToast = mostrarToast;

});
