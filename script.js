document.addEventListener('DOMContentLoaded', () => {

  // ========== GENERAR PRODUCTOS DESDE DATOS ==========
  const gridMap = {
    'Celulares': 'grid-celulares',
    'Laptops': 'grid-laptops',
    'Audifonos': 'grid-audifonos',
    'Accesorios': 'grid-accesorios',
    'Smartwatches': 'grid-smartwatches',
  };

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

  function generarColoresHTML(colores) {
    return colores.map(c => {
      const border = (c === '#FFFFFF' || c === '#F5F5DC' || c === '#C0C0C0' || c === '#E8D5C4' || c === '#E8D5B7' || c === '#FFB6C1')
        ? ' style="background:' + c + '; border: 2px solid #ddd"'
        : ' style="background:' + c + '"';
      return '<div class="color-dot"' + border + '></div>';
    }).join('');
  }

  function generarCard(grupo) {
    const variantes = grupo;
    const tieneMulti = variantes.length > 1;

    const carouselItems = variantes.map(v =>
      '<div class="card-carousel-item">' +
        '<img alt="' + v.nombre + '" loading="lazy" src="' + v.imagen + '"/>' +
      '</div>'
    ).join('');

    const dots = variantes.map((_, i) =>
      '<span class="card-carousel-dot' + (i === 0 ? ' active' : '') + '"></span>'
    ).join('');

    const infoItems = variantes.map((v, i) =>
      '<div class="card-info-item' + (i === 0 ? ' active' : '') + '">' +
        '<h3>' + v.nombre + '</h3>' +
        '<p>' + v.especificaciones + '</p>' +
        '<p class="price" data-precio="' + v.precioPEN + '">PEN ' + v.precioPEN.toLocaleString() + '</p>' +
        '<div class="colors">' + generarColoresHTML(v.colores) + '</div>' +
        '<a class="btn-whatsapp" href="#" onclick="enviarWhatsApp(event, \'' + v.nombre + ' - ' + v.especificaciones + '\')">' +
          '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>' +
          '</svg>' +
          'Consultar' +
        '</a>' +
      '</div>'
    ).join('');

    const btnsHTML = tieneMulti
      ? '<button class="card-carousel-btn prev" onclick="changeCardSlide(this, -1)">‹</button>' +
        '<button class="card-carousel-btn next" onclick="changeCardSlide(this, 1)">›</button>'
      : '';
    const dotsClass = tieneMulti ? '' : ' style="display:none"';

    return '' +
      '<div class="card">' +
        '<div class="card-carousel">' +
          '<div class="card-carousel-inner">' + carouselItems + '</div>' +
          btnsHTML +
          '<div class="card-carousel-dots"' + dotsClass + '>' + dots + '</div>' +
        '</div>' +
        '<div class="info">' + infoItems + '</div>' +
      '</div>';
  }

  function renderizarProductos() {
    const agrupados = agruparProductos(productos);

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

  renderizarProductos();

  // ========== CARRUSEL PRINCIPAL ==========
  let currentSlide = 0;
  const slides = document.querySelectorAll('.carousel-item');
  const indicators = document.querySelectorAll('.indicator');
  const totalSlides = slides.length;
  let autoSlideInterval;

  function showSlide(index) {
    if (index >= totalSlides) currentSlide = 0;
    else if (index < 0) currentSlide = totalSlides - 1;
    else currentSlide = index;

    const offset = -currentSlide * 100;
    document.querySelector('.carousel-inner').style.transform = 'translateX(' + offset + '%)';

    indicators.forEach((ind, i) => {
      ind.classList.toggle('active', i === currentSlide);
    });
  }

  function nextSlide() {
    showSlide(currentSlide + 1);
    resetAutoSlide();
  }

  function previousSlide() {
    showSlide(currentSlide - 1);
    resetAutoSlide();
  }

  function goToSlide(index) {
    showSlide(index);
    resetAutoSlide();
  }

  function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
      nextSlide();
    }, 5000);
  }

  function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
  }

  startAutoSlide();

  // ========== BOTÓN FLOTANTE PARA IR AL INICIO ==========
  const btnInicio = document.getElementById('btnInicio');

  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
      btnInicio.classList.add('visible');
    } else {
      btnInicio.classList.remove('visible');
    }
  });

  window.irAlInicio = function() {
    const target = document.querySelector('#inicio');
    if (target) {
      const navHeight = document.querySelector('nav').offsetHeight;
      const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
      window.scrollTo({ top: targetPosition, behavior: 'smooth' });
    }
  };

  // Smooth scroll para navegación
  document.querySelectorAll('nav a').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = this.getAttribute('href');
      const target = document.querySelector(targetId);
      if (target) {
        const navHeight = document.querySelector('nav').offsetHeight;
        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
        window.scrollTo({ top: targetPosition, behavior: 'smooth' });
      }
    });
  });

  // ========== CARRUSELES DE CARDS ==========
  const cardCarousels = new Map();

  function changeCardSlide(button, direction) {
    const card = button.closest('.card');
    changeCardSlideLogic(card, direction);
    resetCardAutoPlay(card);
  }

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

  function startCardAutoPlay(card) {
    const items = card.querySelectorAll('.card-carousel-item');
    if (items.length > 1) {
      const interval = setInterval(() => {
        changeCardSlideLogic(card, 1);
      }, 4000);
      cardCarousels.set(card, interval);
    }
  }

  function stopCardAutoPlay(card) {
    if (cardCarousels.has(card)) {
      clearInterval(cardCarousels.get(card));
      cardCarousels.delete(card);
    }
  }

  function resetCardAutoPlay(card) {
    stopCardAutoPlay(card);
    startCardAutoPlay(card);
  }

  // Inicializar todas las cards
  document.querySelectorAll('.card').forEach(card => {
    const firstItem = card.querySelector('.card-carousel-item');
    if (firstItem) firstItem.classList.add('active');

    startCardAutoPlay(card);

    card.addEventListener('mouseenter', () => stopCardAutoPlay(card));
    card.addEventListener('mouseleave', () => startCardAutoPlay(card));
  });

  // Dots clickeables
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

  // ========== WHATSAPP ==========
  window.enviarWhatsApp = function(event, nombreProducto) {
    event.preventDefault();
    const numeroWhatsApp = '+51997833866';
    const mensaje = 'Hola, estoy interesado en: *' + nombreProducto + '*. ¿Podrías darme más información?';
    const mensajeCodificado = encodeURIComponent(mensaje);
    const urlWhatsApp = 'https://wa.me/' + numeroWhatsApp + '?text=' + mensajeCodificado;
    window.open(urlWhatsApp, '_blank');
  };

  // Exponer funciones al scope global para onclick del HTML
  window.nextSlide = nextSlide;
  window.previousSlide = previousSlide;
  window.goToSlide = goToSlide;
  window.changeCardSlide = changeCardSlide;

});
