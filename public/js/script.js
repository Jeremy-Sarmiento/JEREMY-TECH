/**
 * @fileoverview Lógica del Catálogo y Navegación — Jeremy Tech
 * @description Controla el carrusel del banner hero, la navegación (scroll suave, menú hamburguesa),
 * los carruseles de variantes de las tarjetas de producto, la consulta por WhatsApp de productos
 * y la búsqueda con sugerencias AJAX del catálogo.
 * La gestión del carrito de compras y el checkout está separada en carrito.js.
 * @author Jeremy Tech
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const nav = document.querySelector('nav');

  /**
   * Mantiene sincronizada la altura real del nav con CSS. Se usa para
   * compensar el sticky al navegar por anclas y para el resumen del carrito.
   */
  function updateNavHeight() {
    if (!nav) return;
    document.documentElement.style.setProperty('--nav-height', nav.offsetHeight + 'px');
  }
  updateNavHeight();
  window.addEventListener('load', updateNavHeight);
  window.addEventListener('resize', updateNavHeight);
  if (nav && typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(updateNavHeight).observe(nav);
  }

  // =========================================================================
  // 1. CARRUSEL PRINCIPAL DE BANNERS (HERO SECTION)
  // =========================================================================

  /** Índice del slide activo en el banner principal */
  let currentSlide = 0;
  const slides = document.querySelectorAll('.carousel-item');
  const indicators = document.querySelectorAll('.indicator');
  const totalSlides = slides.length;
  let autoSlideInterval = null;

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
      ind.setAttribute('aria-current', i === currentSlide ? 'true' : 'false');
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

  /** Detiene el temporizador de reproducción automática del banner principal. */
  function stopAutoSlide() {
    if (autoSlideInterval !== null) {
      clearInterval(autoSlideInterval);
      autoSlideInterval = null;
    }
  }

  /** Inicia el temporizador de reproducción automática del banner principal (5 segundos). */
  function startAutoSlide() {
    stopAutoSlide();
    if (reduceMotion || totalSlides < 2 || document.hidden) return;
    autoSlideInterval = setInterval(() => {
      showSlide(currentSlide + 1);
    }, 5000);
  }

  /** Reinicia el temporizador de rotación automática tras una interacción del usuario. */
  function resetAutoSlide() {
    startAutoSlide();
  }

  startAutoSlide();

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stopAutoSlide();
    else startAutoSlide();
  });

  // El banner se detiene mientras el usuario lo observa o interactúa con él.
  const hero = document.querySelector('.carousel');
  if (hero) {
    if (finePointer) {
      hero.addEventListener('mouseenter', stopAutoSlide);
      hero.addEventListener('mouseleave', startAutoSlide);
    }
    hero.addEventListener('focusin', stopAutoSlide);
    hero.addEventListener('focusout', event => {
      if (!hero.contains(event.relatedTarget)) startAutoSlide();
    });

    let heroTouchX = 0;
    let heroTouchY = 0;
    let heroTouchActive = false;

    hero.addEventListener('touchstart', event => {
      if (event.touches.length !== 1 || event.target.closest('button, a')) {
        heroTouchActive = false;
        return;
      }
      heroTouchX = event.touches[0].clientX;
      heroTouchY = event.touches[0].clientY;
      heroTouchActive = true;
      stopAutoSlide();
    }, { passive: true });

    hero.addEventListener('touchend', event => {
      if (!heroTouchActive || !event.changedTouches.length) return;
      const dx = event.changedTouches[0].clientX - heroTouchX;
      const dy = event.changedTouches[0].clientY - heroTouchY;
      heroTouchActive = false;
      if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.2) {
        if (dx < 0) nextSlide();
        else previousSlide();
      }
      startAutoSlide();
    }, { passive: true });

    hero.addEventListener('touchcancel', () => {
      heroTouchActive = false;
      startAutoSlide();
    }, { passive: true });
  }

  // =========================================================================
  // 2. NAVEGACIÓN, MENÚ MÓVIL Y BOTÓN FLOTANTE
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
   * Desplaza la vista a un elemento compensando la altura real del nav sticky.
   * @param {HTMLElement} target - Elemento destino.
   */
  function scrollToTarget(target) {
    if (!target) return;
    const navHeight = nav ? nav.offsetHeight : 0;
    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight - 8;
    window.scrollTo({
      top: Math.max(0, targetPosition),
      behavior: reduceMotion ? 'auto' : 'smooth',
    });
  }

  /**
   * Realiza un desplazamiento suave hacia la sección de inicio compensando la barra de navegación.
   */
  window.irAlInicio = function() {
    const inicio = document.querySelector('#inicio');
    if (inicio) {
      scrollToTarget(inicio);
    } else {
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
  };

  const navLinks = document.getElementById('navMainLinks');
  const hamburger = document.getElementById('hamburgerBtn');

  /**
   * Abre o cierra el menú móvil y mantiene aria-expanded sincronizado.
   * @param {boolean|null} force - true/false para forzar; null para alternar.
   */
  function setMenuOpen(force) {
    if (!navLinks || !hamburger) return;
    const abrir = force === null ? !navLinks.classList.contains('open') : Boolean(force);
    navLinks.classList.toggle('open', abrir);
    hamburger.classList.toggle('active', abrir);
    hamburger.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    hamburger.setAttribute('aria-label', abrir ? 'Cerrar menú de categorías' : 'Abrir menú de categorías');
    if (abrir) setAccountOpen(false);
  }

  window.toggleMenu = function() {
    setMenuOpen(null);
  };

  // Desplazamiento suave para enlaces ancla de la barra de navegación.
  document.querySelectorAll('nav a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href');
      if (targetId && targetId.startsWith('#')) {
        e.preventDefault();
        scrollToTarget(document.querySelector(targetId));
        setMenuOpen(false);
      }
    });
  });

  // Cierre automático del menú móvil al hacer clic fuera del panel.
  document.addEventListener('click', function(e) {
    if (navLinks && hamburger && navLinks.classList.contains('open')) {
      if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) {
        setMenuOpen(false);
      }
    }
  });

  // Cerrar el menú al pasar de móvil/tablet a escritorio.
  window.addEventListener('resize', function() {
    if (window.innerWidth > 1280) setMenuOpen(false);
  });

  // -------------------------------------------------------------------------
  // Menú de cuenta: además del hover, se puede abrir/cerrar con teclado y táctil.
  // -------------------------------------------------------------------------
  const accountBox = document.getElementById('navAccountBox');
  const accountBtn = accountBox ? accountBox.querySelector('.nav-account-btn') : null;

  function setAccountOpen(open) {
    if (!accountBox || !accountBtn) return;
    accountBox.classList.toggle('open', open);
    accountBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (accountBtn) {
    accountBtn.addEventListener('click', function(event) {
      event.stopPropagation();
      setMenuOpen(false);
      setAccountOpen(!accountBox.classList.contains('open'));
    });

    accountBox.addEventListener('click', function(event) {
      if (event.target.closest('a, button[type="submit"]')) setAccountOpen(false);
    });
  }

  document.addEventListener('click', function(event) {
    if (accountBox && !accountBox.contains(event.target)) setAccountOpen(false);
  });

  // Escape cierra los menús abiertos y devuelve el foco al disparador.
  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    if (navLinks && navLinks.classList.contains('open')) {
      setMenuOpen(false);
      if (hamburger) hamburger.focus();
    }
    if (accountBox && accountBox.classList.contains('open')) {
      setAccountOpen(false);
      if (accountBtn) accountBtn.focus();
    }
  });

  // =========================================================================
  // 3. CARRUSELES INDEPENDIENTES DE VARIANTES EN CARDS
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
    if (!card) return;
    const carousel = card.querySelector('.card-carousel');
    if (!carousel) return;
    const inner = carousel.querySelector('.card-carousel-inner');
    if (!inner) return;
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
      dot.setAttribute('aria-current', index === newIndex ? 'true' : 'false');
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
    if (!card) return;
    const items = card.querySelectorAll('.card-carousel-item');
    if (!finePointer || reduceMotion || document.hidden || items.length < 2) return;
    if (card.contains(document.activeElement)) return;
    stopCardAutoPlay(card);
    const interval = setInterval(() => {
      changeCardSlideLogic(card, 1);
    }, 4000);
    cardCarousels.set(card, interval);
  }

  /**
   * Detiene la rotación automática de una tarjeta (ej. al posar el cursor).
   * @param {HTMLElement} card - Elemento contenedor de la tarjeta.
   */
  function stopCardAutoPlay(card) {
    if (!card) return;
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
    card.addEventListener('focusin', () => stopCardAutoPlay(card));
    card.addEventListener('focusout', event => {
      if (!card.contains(event.relatedTarget)) startCardAutoPlay(card);
    });
  });

  // Delegación de eventos para clics directos sobre los puntos (dots) de las cards.
  document.addEventListener('click', (e) => {
    const dot = e.target.closest('.card-carousel-dot');
    if (!dot) return;

    const card = dot.closest('.card');
    if (!card) return;
    const dots = card.querySelectorAll('.card-carousel-dot');
    const items = card.querySelectorAll('.card-carousel-item');
    const clickedIndex = Array.from(dots).indexOf(dot);
    const currentIndex = Array.from(dots).findIndex(item => item.classList.contains('active'));
    const direction = clickedIndex - currentIndex;

    items.forEach((item, index) => {
      item.classList.toggle('active', index === clickedIndex);
    });

    changeCardSlideLogic(card, direction);
    resetCardAutoPlay(card);
  });

  // Swipe horizontal en las tarjetas. Se ignora el gesto sobre flechas/dots.
  let cardSwipe = null;
  document.addEventListener('touchstart', (event) => {
    const carousel = event.target.closest('.card-carousel');
    const variantes = carousel ? carousel.querySelectorAll('.card-carousel-item') : [];
    if (!carousel || variantes.length < 2 || event.touches.length !== 1 || event.target.closest('.card-carousel-btn, .card-carousel-dot')) {
      cardSwipe = null;
      return;
    }
    cardSwipe = {
      card: carousel.closest('.card'),
      x: event.touches[0].clientX,
      y: event.touches[0].clientY,
    };
  }, { passive: true });

  document.addEventListener('touchend', (event) => {
    if (!cardSwipe || !event.changedTouches.length) {
      cardSwipe = null;
      return;
    }
    const dx = event.changedTouches[0].clientX - cardSwipe.x;
    const dy = event.changedTouches[0].clientY - cardSwipe.y;
    const card = cardSwipe.card;
    cardSwipe = null;

    if (!card || Math.abs(dx) < 40 || Math.abs(dx) <= Math.abs(dy) * 1.2) return;

    card.dataset.swiped = '1';
    window.setTimeout(() => {
      delete card.dataset.swiped;
    }, 350);
    changeCardSlideLogic(card, dx < 0 ? 1 : -1);
    resetCardAutoPlay(card);
  }, { passive: true });

  // Evita que un swipe termine abriendo la ficha del producto por accidente.
  document.addEventListener('click', (event) => {
    const card = event.target.closest('.card');
    if (card && card.dataset.swiped === '1') {
      event.preventDefault();
      event.stopPropagation();
    }
  }, true);

  // =========================================================================
  // 4. CONSULTAS DIRECTAS VÍA WHATSAPP
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
    if (window.JeremyTechAnalitica) {
      JeremyTechAnalitica.registrar('clic_whatsapp', { producto: nombreProducto }, 'tienda').catch(function () {});
    }
  };

  // =========================================================================
  // 5. BÚSQUEDA CON SUGERENCIAS AJAX
  // =========================================================================

  const busquedaInput = document.getElementById('busquedaInput');
  const btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
  const searchSuggest = document.getElementById('searchSuggest');
  const searchForm = busquedaInput ? busquedaInput.closest('form') : null;
  const suggestUrl = searchForm && searchForm.dataset.suggestUrl
    ? searchForm.dataset.suggestUrl
    : '?ruta=catalogo/sugerencias';
  let suggestTimer = null;
  let suggestAbort = null;

  /**
   * Escapa un valor para insertarlo de forma segura con innerHTML.
   * @param {*} valor - Valor a escapar.
   * @returns {string} Texto seguro.
   */
  function escaparHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    }[caracter]));
  }

  /**
   * Oculta y limpia el panel de sugerencias.
   */
  function cerrarSugerencias() {
    if (!searchSuggest) return;
    searchSuggest.hidden = true;
    searchSuggest.innerHTML = '';
    if (busquedaInput) busquedaInput.setAttribute('aria-expanded', 'false');
  }

  /**
   * Ajusta el panel de sugerencias al espacio visible de la pantalla.
   *
   * Se alinea con el buscador, pero se desplaza horizontalmente cuando el
   * ancho disponible no alcanza para mostrarlo completo. Así no se corta
   * en pantallas pequeñas ni cuando el buscador está cerca de un borde.
   */
  function posicionarSugerencias() {
    if (!searchSuggest || !searchForm) return;

    const margen = 12;
    const anchoViewport = document.documentElement.clientWidth || window.innerWidth;
    const rectFormulario = searchForm.getBoundingClientRect();
    const ancho = Math.min(420, Math.max(0, anchoViewport - margen * 2));
    const maxIzquierda = Math.max(margen, anchoViewport - margen - ancho);
    const izquierda = Math.min(Math.max(rectFormulario.left, margen), maxIzquierda);

    searchSuggest.style.width = ancho + 'px';
    searchSuggest.style.left = (izquierda - rectFormulario.left) + 'px';
    searchSuggest.style.right = 'auto';
  }

  /**
   * Pinta las sugerencias recibidas del endpoint JSON.
   * @param {Array<Object>} items - Productos sugeridos.
   * @param {string} consulta - Texto que originó la búsqueda.
   */
  function mostrarSugerencias(items, consulta) {
    if (!searchSuggest) return;

    if (!items.length) {
      searchSuggest.innerHTML = '<div class="search-suggest-empty">Sin coincidencias para "'
        + escaparHtml(consulta) + '"</div>';
      posicionarSugerencias();
      searchSuggest.hidden = false;
      if (busquedaInput) busquedaInput.setAttribute('aria-expanded', 'true');
      return;
    }

    const filas = items.map(item => {
      const meta = [item.modelo, item.codigoSKU, item.categoria]
        .filter(Boolean)
        .join(' · ');
      const precio = Number(item.precioPEN || 0).toFixed(2);
      return '<a href="?ruta=producto&amp;slug=' + encodeURIComponent(item.slug) + '">'
        + '<span class="suggest-info">'
        + '<span class="suggest-name">' + escaparHtml(item.nombre) + '</span>'
        + '<span class="suggest-meta">' + escaparHtml(meta) + '</span>'
        + '</span>'
        + '<span class="suggest-price">PEN ' + escaparHtml(precio) + '</span>'
        + '</a>';
    }).join('');

    const vendedorInput = searchForm
      ? searchForm.querySelector('input[name="vendedor"]')
      : null;
    const vendedor = vendedorInput ? vendedorInput.value : '';
    const verTodos = '?ruta=catalogo&amp;q=' + encodeURIComponent(consulta)
      + (vendedor ? '&amp;vendedor=' + encodeURIComponent(vendedor) : '');

    searchSuggest.innerHTML = filas
      + '<a class="search-suggest-all" href="' + verTodos + '">Ver todos los resultados</a>';
    posicionarSugerencias();
    searchSuggest.hidden = false;
    if (busquedaInput) busquedaInput.setAttribute('aria-expanded', 'true');
  }

  /**
   * Consulta el endpoint de sugerencias y actualiza el panel.
   * @param {string} consulta - Texto normalizado a consultar.
   */
  async function consultarSugerencias(consulta) {
    if (suggestAbort) suggestAbort.abort();
    const controlador = new AbortController();
    suggestAbort = controlador;

    try {
      const vendedorInput = searchForm
        ? searchForm.querySelector('input[name="vendedor"]')
        : null;
      const vendedor = vendedorInput ? vendedorInput.value : '';
      const url = suggestUrl + (suggestUrl.includes('?') ? '&' : '?')
        + 'q=' + encodeURIComponent(consulta)
        + (vendedor ? '&vendedor=' + encodeURIComponent(vendedor) : '');
      const respuesta = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        signal: controlador.signal,
        credentials: 'same-origin',
      });

      if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
      const datos = await respuesta.json();

      if (busquedaInput && busquedaInput.value.trim() === consulta) {
        if (window.JeremyTechAnalitica) {
          JeremyTechAnalitica.registrar('busqueda', { consulta: consulta }, 'catalogo').catch(function () {});
        }
        mostrarSugerencias(Array.isArray(datos) ? datos : [], consulta);
      }
    } catch (error) {
      if (error.name !== 'AbortError') cerrarSugerencias();
    }
  }

  /**
   * Sincroniza la visibilidad del botón para limpiar el buscador.
   */
  function actualizarBotonLimpiar() {
    if (btnLimpiarBusqueda && busquedaInput) {
      btnLimpiarBusqueda.style.display = busquedaInput.value.trim() ? 'block' : 'none';
    }
  }

  if (busquedaInput) {
    actualizarBotonLimpiar();

    busquedaInput.addEventListener('input', () => {
      actualizarBotonLimpiar();
      clearTimeout(suggestTimer);

      if (suggestAbort) {
        suggestAbort.abort();
        suggestAbort = null;
      }

      const consulta = busquedaInput.value.trim();
      if (!searchSuggest || consulta.length < 2) {
        cerrarSugerencias();
        return;
      }

      suggestTimer = setTimeout(() => consultarSugerencias(consulta), 250);
    });

    busquedaInput.addEventListener('focus', () => {
      const consulta = busquedaInput.value.trim();
      if (searchSuggest && consulta.length >= 2 && searchSuggest.hidden) {
        consultarSugerencias(consulta);
      }
    });

    busquedaInput.addEventListener('keydown', evento => {
      if (evento.key === 'Escape') {
        cerrarSugerencias();
        busquedaInput.blur();
        return;
      }
      if (evento.key === 'ArrowDown' && searchSuggest && !searchSuggest.hidden) {
        const primero = searchSuggest.querySelector('a');
        if (primero) {
          evento.preventDefault();
          primero.focus();
        }
      }
    });

    if (searchSuggest) {
      searchSuggest.addEventListener('keydown', evento => {
        const enlaces = Array.from(searchSuggest.querySelectorAll('a'));
        const actual = enlaces.indexOf(document.activeElement);

        if (evento.key === 'Escape') {
          evento.preventDefault();
          cerrarSugerencias();
          busquedaInput.focus();
          return;
        }
        if (!enlaces.length) return;

        if (evento.key === 'ArrowDown') {
          evento.preventDefault();
          enlaces[Math.min(actual + 1, enlaces.length - 1)].focus();
        } else if (evento.key === 'ArrowUp') {
          evento.preventDefault();
          if (actual <= 0) busquedaInput.focus();
          else enlaces[actual - 1].focus();
        }
      });
    }

    if (searchForm) {
      searchForm.addEventListener('submit', cerrarSugerencias);
    }
  }

  // Cerrar el panel al hacer clic fuera del buscador.
  document.addEventListener('click', evento => {
    if (searchForm && searchSuggest && !searchForm.contains(evento.target)) {
      cerrarSugerencias();
    }
  });

  // Recalcula la posición del panel mientras está abierto: al cambiar el
  // tamaño de la ventana, al hacer scroll o si el buscador cambia de ancho.
  if (searchForm && searchSuggest) {
    const reposicionarSugerencias = () => {
      if (!searchSuggest.hidden) posicionarSugerencias();
    };

    window.addEventListener('resize', reposicionarSugerencias);
    window.addEventListener('scroll', reposicionarSugerencias, { passive: true });

    if (typeof ResizeObserver !== 'undefined') {
      const observador = new ResizeObserver(reposicionarSugerencias);
      observador.observe(searchForm);
    } else if (busquedaInput) {
      busquedaInput.addEventListener('transitionend', reposicionarSugerencias);
    }
  }

  /**
   * Limpia el buscador. Si la URL actual es el catálogo y hay una búsqueda
   * activa, vuelve al catálogo completo conservando el filtro de vendedor.
   */
  window.limpiarBusqueda = function() {
    if (!busquedaInput) return;

    busquedaInput.value = '';
    actualizarBotonLimpiar();
    cerrarSugerencias();

    const params = new URLSearchParams(window.location.search);
    const ruta = params.get('ruta') || 'catalogo';
    const tieneBusqueda = params.has('q');

    if (ruta === 'catalogo' && tieneBusqueda) {
      // Solo se quita la búsqueda: categoría, marca, precio, orden y
      // vendedor se conservan (Bloque 4).
      params.delete('q');
      params.delete('pagina');
      params.set('ruta', 'catalogo');
      window.location.href = '?' + params.toString();
      return;
    }

    busquedaInput.focus();
  };

  // =========================================================================
  // 6. EXPOSICIÓN AL ÁMBITO GLOBAL PARA HANDLERS HTML
  // =========================================================================
  window.nextSlide = nextSlide;
  window.previousSlide = previousSlide;
  window.goToSlide = goToSlide;
  window.changeCardSlide = changeCardSlide;

});
