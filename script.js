document.addEventListener('DOMContentLoaded', () => {

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
    document.querySelector('.carousel-inner').style.transform = `translateX(${offset}%)`;

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

    inner.style.transform = `translateX(${-newIndex * 100}%)`;

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
    const mensaje = `Hola, estoy interesado en: *${nombreProducto}*. ¿Podrías darme más información?`;
    const mensajeCodificado = encodeURIComponent(mensaje);
    const urlWhatsApp = `https://wa.me/${numeroWhatsApp}?text=${mensajeCodificado}`;
    window.open(urlWhatsApp, '_blank');
  };

  // Exponer funciones al scope global para onclick del HTML
  window.nextSlide = nextSlide;
  window.previousSlide = previousSlide;
  window.goToSlide = goToSlide;
  window.changeCardSlide = changeCardSlide;

});
