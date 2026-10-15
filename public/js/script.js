/**
 * @fileoverview Lógica del Catálogo y Navegación — Jeremy Tech
 * @description Controla el carrusel del banner hero, la navegación (scroll suave, menú hamburguesa),
 * los carruseles de variantes de las tarjetas de producto, la consulta por WhatsApp de productos
 * y la búsqueda/filtrado en vivo del catálogo.
 * La gestión del carrito de compras y el checkout está separada en carrito.js.
 * @author Jeremy Tech
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {

  // =========================================================================
  // 1. MAPEO DE CATEGORÍAS A CONTENEDORES GRID
  // (El catálogo se renderiza desde PHP en el servidor; aquí solo se requiere
  //  el mapeo para el filtrado de búsqueda y visibilidad de secciones)
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
  // 12. EXPOSICIÓN AL ÁMBITO GLOBAL PARA HANDLERS HTML
  // =========================================================================
  window.nextSlide = nextSlide;
  window.previousSlide = previousSlide;
  window.goToSlide = goToSlide;
  window.changeCardSlide = changeCardSlide;

});
