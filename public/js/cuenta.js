/**
 * @fileoverview Módulo de la cuenta del cliente — Jeremy Tech (MVC).
 * @description Mejora progresiva de la Fase 1:
 *   - alterna favoritos por AJAX sin recargar (los formularios siguen
 *     funcionando con POST normal si este archivo no carga);
 *   - muestra un toast con el resultado;
 *   - quita la tarjeta de "Mis favoritos" al desmarcar el corazón;
 *   - muestra los campos de empresa (B2B) o de reventa (C2C) del perfil.
 * @author Jeremy Tech
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {

  // =========================================================================
  // 1. UTILIDADES
  // =========================================================================

  /**
   * Token CSRF de la página (el mismo que usan carrito.js y chat.js).
   * @returns {string}
   */
  function tokenCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }

  /**
   * Muestra un aviso flotante que desaparece solo.
   * @param {string} mensaje
   * @param {'ok'|'error'} tipo
   */
  function mostrarToast(mensaje, tipo) {
    if (!mensaje) {
      return;
    }

    let region = document.querySelector('.cuenta-toast-region');
    if (!region) {
      region = document.createElement('div');
      region.className = 'cuenta-toast-region';
      region.setAttribute('role', 'status');
      region.setAttribute('aria-live', 'polite');
      document.body.appendChild(region);
    }

    const toast = document.createElement('div');
    toast.className = 'cuenta-toast cuenta-toast-' + (tipo === 'error' ? 'error' : 'ok');
    toast.textContent = mensaje;
    region.appendChild(toast);

    window.setTimeout(() => {
      toast.classList.add('cuenta-toast-saliendo');
      window.setTimeout(() => toast.remove(), 220);
    }, 3200);
  }

  /**
   * Id de producto de un formulario de favoritos.
   * @param {HTMLFormElement} form
   * @returns {number}
   */
  function productoIdDe(form) {
    const campo = form.querySelector('input[name="producto_id"]');
    return campo ? Number.parseInt(campo.value, 10) || 0 : 0;
  }

  /**
   * Refleja el estado del favorito en todos los botones del mismo producto
   * (por ejemplo, la tarjeta y la ficha si coinciden en la página).
   * @param {number} productoId
   * @param {boolean} esFavorito
   */
  function sincronizarProducto(productoId, esFavorito) {
    document.querySelectorAll('.favorito-form').forEach((form) => {
      if (productoIdDe(form) !== productoId) {
        return;
      }

      const boton = form.querySelector('button[type="submit"]');
      if (!boton) {
        return;
      }

      const icono = boton.querySelector('.favorito-icon');
      const texto = boton.querySelector('.favorito-texto');

      boton.classList.toggle('activo', esFavorito);
      boton.setAttribute('aria-pressed', esFavorito ? 'true' : 'false');

      const etiqueta = esFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos';
      boton.setAttribute('aria-label', etiqueta);
      boton.setAttribute('title', etiqueta);

      if (icono) {
        icono.textContent = esFavorito ? '♥' : '♡';
      }
      if (texto) {
        texto.textContent = esFavorito ? 'En favoritos' : 'Guardar en favoritos';
      }

      if (icono) {
        icono.classList.remove('favorito-pop');
        // Reinicia la animación aunque se pulse varias veces seguidas.
        void icono.offsetWidth;
        icono.classList.add('favorito-pop');
      }
    });
  }

  /**
   * Sustituye la rejilla vacía por el estado "sin favoritos".
   * El marcado vive en un <template> de views/favoritos.php.
   */
  function mostrarFavoritosVacios() {
    const rejilla = document.querySelector('.favoritos-grid');
    if (!rejilla) {
      return;
    }

    const plantilla = document.getElementById('favoritosVacioTpl');
    if (plantilla instanceof HTMLTemplateElement) {
      rejilla.replaceWith(plantilla.content.cloneNode(true));
      return;
    }

    rejilla.remove();
  }

  // =========================================================================
  // 2. FAVORITOS POR AJAX
  // =========================================================================

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('favorito-form')) {
      return;
    }

    event.preventDefault();

    const productoId = productoIdDe(form);
    if (productoId <= 0 || form.dataset.enviando === '1') {
      return;
    }

    const boton = form.querySelector('button[type="submit"]');
    const cuerpo = new FormData(form);

    form.dataset.enviando = '1';
    if (boton) {
      boton.disabled = true;
    }

    try {
      const respuesta = await fetch(form.action, {
        method: 'POST',
        body: cuerpo,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'fetch',
          'X-CSRF-Token': tokenCsrf(),
          'Accept': 'application/json'
        }
      });

      // Una sesión vencida puede acabar en el HTML del login: volvemos allí.
      if (respuesta.redirected) {
        window.location.href = '?ruta=login';
        return;
      }

      let datos = null;
      try {
        datos = await respuesta.json();
      } catch (error) {
        datos = null;
      }

      if (respuesta.status === 401) {
        window.location.href = '?ruta=login';
        return;
      }

      if (!respuesta.ok || !datos || datos.ok !== true) {
        const mensaje = datos && datos.error
          ? datos.error
          : 'No se pudo actualizar el favorito. Inténtalo de nuevo.';
        throw new Error(mensaje);
      }

      const esFavorito = datos.favorito === true;
      sincronizarProducto(productoId, esFavorito);

      mostrarToast(
        esFavorito ? 'Producto añadido a favoritos.' : 'Producto eliminado de favoritos.',
        'ok'
      );

      const enFavoritos = typeof JT_SESSION !== 'undefined' && JT_SESSION.rutaVista === 'favoritos';
      if (!esFavorito && enFavoritos) {
        const tarjeta = Array.from(document.querySelectorAll('[data-favorito-card]'))
          .find((nodo) => nodo.dataset.favoritoCard === String(productoId));

        if (tarjeta) {
          tarjeta.remove();
          if (!document.querySelector('[data-favorito-card]')) {
            mostrarFavoritosVacios();
          }
        }
      }
    } catch (error) {
      mostrarToast(
        error instanceof Error && error.message
          ? error.message
          : 'No se pudo actualizar el favorito. Inténtalo de nuevo.',
        'error'
      );
    } finally {
      delete form.dataset.enviando;
      if (boton) {
        boton.disabled = false;
      }
    }
  });

  // =========================================================================
  // 3. PERFIL: CAMPOS B2B / C2C
  // =========================================================================

  const tipoCuenta = document.getElementById('perfilTipo');
  if (tipoCuenta instanceof HTMLSelectElement) {
    const formPerfil = tipoCuenta.closest('form');

    const aplicarTipo = () => {
      if (formPerfil) {
        formPerfil.dataset.tipo = ['B2B', 'C2C'].includes(tipoCuenta.value)
          ? tipoCuenta.value
          : 'B2C';
      }
    };

    tipoCuenta.addEventListener('change', aplicarTipo);
    aplicarTipo();
  }

  // =========================================================================
  // 4. RESEÑAS DE PRODUCTOS
  // =========================================================================

  /**
   * Estrellas llenas/vacías para un promedio de 1 a 5.
   * @param {number} promedio
   * @returns {string}
   */
  function estrellasTexto(promedio) {
    const llenas = Math.max(0, Math.min(5, Math.round(Number(promedio) || 0)));
    return '★'.repeat(llenas) + '☆'.repeat(5 - llenas);
  }

  /**
   * Refresca la valoración visible de la ficha tras guardar una reseña.
   * @param {{total:number, promedio:number, distribucion:Object<string,number>}} resumen
   */
  function pintarResumenResenas(resumen) {
    if (!resumen) {
      return;
    }

    const total = Number(resumen.total) || 0;
    const promedio = Number(resumen.promedio) || 0;

    document.querySelectorAll('.producto-rating').forEach((bloque) => {
      const enlace = bloque.querySelector('a');
      if (!enlace) {
        return;
      }
      if (total <= 0) {
        return;
      }

      let estrellas = enlace.querySelector('.producto-rating-estrellas');
      let numero = enlace.querySelector('strong');
      let cuenta = enlace.querySelector('.producto-rating-total');

      if (!estrellas || !numero || !cuenta) {
        enlace.textContent = '';
        estrellas = document.createElement('span');
        estrellas.className = 'producto-rating-estrellas';
        estrellas.setAttribute('aria-hidden', 'true');
        numero = document.createElement('strong');
        cuenta = document.createElement('span');
        cuenta.className = 'producto-rating-total';
        enlace.append(estrellas, numero, cuenta);
      }

      estrellas.textContent = estrellasTexto(promedio);
      numero.textContent = promedio.toFixed(1);
      cuenta.textContent = total + (total === 1 ? ' opinión' : ' opiniones');
    });

    const nota = document.querySelector('.resenas-nota');
    if (nota) {
      const num = nota.querySelector('.resenas-nota-num');
      const estrellas = nota.querySelector('.resenas-nota-estrellas');
      const cuenta = nota.querySelector('.resenas-nota-total');
      if (num) {
        num.textContent = promedio.toFixed(1);
      }
      if (estrellas) {
        estrellas.textContent = estrellasTexto(promedio);
      }
      if (cuenta) {
        cuenta.textContent = total + (total === 1 ? ' opinión' : ' opiniones');
      }
    }

    const barras = document.querySelectorAll('.resenas-barras li');
    if (barras.length > 0 && resumen.distribucion) {
      barras.forEach((fila, indice) => {
        const estrella = 5 - indice;
        const cantidad = Number(resumen.distribucion[String(estrella)]) || 0;
        const relleno = fila.querySelector('.resenas-barra > span');
        const cuenta = fila.querySelector('.resenas-barra-cuenta');
        if (relleno) {
          relleno.style.width = (total > 0 ? Math.round(cantidad * 100 / total) : 0) + '%';
        }
        if (cuenta) {
          cuenta.textContent = String(cantidad);
        }
      });
    }
  }

  /**
   * Añade la reseña recién guardada al listado de la ficha (si existe).
   * @param {{autor:string, resena:?Object}} datos
   */
  function insertarResena(datos) {
    if (!datos || !datos.resena) {
      return;
    }

    let lista = document.querySelector('.resenas-lista');
    if (!lista) {
      const vacio = document.querySelector('.resenas-vacio');
      if (!vacio) {
        return;
      }
      lista = document.createElement('ul');
      lista.className = 'resenas-lista';
      vacio.replaceWith(lista);
    }

    // Si el cliente ya tenía una reseña listada, se sustituye en lugar de
    // duplicarla (la UNIQUE de la tabla mantiene una sola por producto).
    const existente = lista.querySelector('[data-autor-propio="1"]');
    if (existente) {
      existente.remove();
    }

    const item = document.createElement('li');
    item.className = 'resena-item';
    item.dataset.autorPropio = '1';

    const cabecera = document.createElement('div');
    cabecera.className = 'resena-item-cabecera';

    const autor = document.createElement('strong');
    autor.className = 'resena-item-autor';
    autor.textContent = datos.autor || 'Tú';

    const estrellas = document.createElement('span');
    estrellas.className = 'resena-item-estrellas';
    estrellas.textContent = estrellasTexto(datos.resena.calificacion);

    const fecha = document.createElement('time');
    fecha.className = 'resena-item-fecha';
    fecha.textContent = 'Ahora';

    cabecera.append(autor, estrellas, fecha);
    item.appendChild(cabecera);

    const comentario = String(datos.resena.comentario || '').trim();
    if (comentario !== '') {
      const texto = document.createElement('p');
      texto.className = 'resena-item-texto';
      texto.textContent = comentario;
      item.appendChild(texto);
    }

    lista.prepend(item);
  }

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('resena-form')) {
      return;
    }

    // Las calificaciones al vendedor comparten el estilo `.resena-form`,
    // pero tienen su propio manejador más abajo.
    if (form.classList.contains('calificacion-vendedor-form')) {
      return;
    }

    event.preventDefault();

    if (form.dataset.enviando === '1') {
      return;
    }

    const estado = form.querySelector('.resena-estado');
    const boton = form.querySelector('button[type="submit"]');
    const seleccion = form.querySelector('input[name="calificacion"]:checked');

    if (!seleccion) {
      if (estado) {
        estado.textContent = 'Elige de 1 a 5 estrellas antes de guardar.';
        estado.className = 'resena-estado error';
      }
      return;
    }

    form.dataset.enviando = '1';
    if (boton) {
      boton.disabled = true;
    }
    if (estado) {
      estado.textContent = 'Guardando…';
      estado.className = 'resena-estado';
    }

    try {
      const respuesta = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'fetch',
          'X-CSRF-Token': tokenCsrf(),
          'Accept': 'application/json'
        }
      });

      if (respuesta.redirected) {
        window.location.href = '?ruta=login';
        return;
      }

      let datos = null;
      try {
        datos = await respuesta.json();
      } catch (error) {
        datos = null;
      }

      if (respuesta.status === 401) {
        window.location.href = '?ruta=login';
        return;
      }

      if (!respuesta.ok || !datos || datos.ok !== true) {
        const mensaje = datos && (datos.error || datos.mensaje)
          ? (datos.error || datos.mensaje)
          : 'No se pudo guardar tu opinión. Inténtalo de nuevo.';
        throw new Error(mensaje);
      }

      if (estado) {
        estado.textContent = datos.mensaje;
        estado.className = 'resena-estado ok';
      }
      mostrarToast(datos.mensaje, 'ok');

      pintarResumenResenas(datos.resumen);
      insertarResena(datos);

      const detalles = form.closest('details');
      const resumenWidget = detalles ? detalles.querySelector('summary') : null;
      if (resumenWidget && datos.resena) {
        resumenWidget.textContent = '✏️ Tu opinión: ' + estrellasTexto(datos.resena.calificacion);
      }

      if (boton) {
        boton.textContent = 'Actualizar opinión';
      }
      const titulo = form.querySelector('.resena-titulo');
      if (titulo) {
        titulo.textContent = 'Tu calificación (puedes cambiarla)';
      }
    } catch (error) {
      const mensaje = error instanceof Error && error.message
        ? error.message
        : 'No se pudo guardar tu opinión. Inténtalo de nuevo.';
      if (estado) {
        estado.textContent = mensaje;
        estado.className = 'resena-estado error';
      }
      mostrarToast(mensaje, 'error');
    } finally {
      delete form.dataset.enviando;
      if (boton) {
        boton.disabled = false;
      }
    }
  });

  // =========================================================================
  // 4b. CALIFICACIONES A VENDEDORES (BLOQUE 3)
  // =========================================================================

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('calificacion-vendedor-form')) {
      return;
    }

    event.preventDefault();

    if (form.dataset.enviando === '1') {
      return;
    }

    const estado = form.querySelector('.resena-estado');
    const boton = form.querySelector('button[type="submit"]');
    const seleccion = form.querySelector('input[name="calificacion"]:checked');

    if (!seleccion) {
      if (estado) {
        estado.textContent = 'Elige de 1 a 5 estrellas antes de guardar.';
        estado.className = 'resena-estado error';
      }
      return;
    }

    form.dataset.enviando = '1';
    if (boton) {
      boton.disabled = true;
    }
    if (estado) {
      estado.textContent = 'Guardando…';
      estado.className = 'resena-estado';
    }

    try {
      const respuesta = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'fetch',
          'X-CSRF-Token': tokenCsrf(),
          'Accept': 'application/json'
        }
      });

      if (respuesta.redirected) {
        window.location.href = '?ruta=login';
        return;
      }

      let datos = null;
      try {
        datos = await respuesta.json();
      } catch (error) {
        datos = null;
      }

      if (respuesta.status === 401) {
        window.location.href = '?ruta=login';
        return;
      }

      if (!respuesta.ok || !datos || datos.ok !== true) {
        const mensaje = datos && (datos.error || datos.mensaje)
          ? (datos.error || datos.mensaje)
          : 'No se pudo guardar tu calificación. Inténtalo de nuevo.';
        throw new Error(mensaje);
      }

      if (estado) {
        estado.textContent = datos.mensaje;
        estado.className = 'resena-estado ok';
      }
      mostrarToast(datos.mensaje, 'ok');

      const calificacion = Number(datos.calificacion) || 0;
      const nombre = form.dataset.vendedorNombre || 'este vendedor';
      const detalles = form.closest('details');
      const resumenWidget = detalles ? detalles.querySelector('summary') : null;
      if (resumenWidget) {
        resumenWidget.textContent = '✏️ Tu calificación de ' + nombre + ': ' + estrellasTexto(calificacion);
      }

      const titulo = form.querySelector('.resena-titulo');
      if (titulo) {
        titulo.textContent = 'Tu calificación del vendedor (puedes cambiarla)';
      }
      if (boton) {
        boton.textContent = 'Actualizar calificación';
      }
    } catch (error) {
      const mensaje = error instanceof Error && error.message
        ? error.message
        : 'No se pudo guardar tu calificación. Inténtalo de nuevo.';
      if (estado) {
        estado.textContent = mensaje;
        estado.className = 'resena-estado error';
      }
      mostrarToast(mensaje, 'error');
    } finally {
      delete form.dataset.enviando;
      if (boton) {
        boton.disabled = false;
      }
    }
  });

  // =========================================================================
  // 5. MIS PRODUCTOS (FASE 3): VISTA PREVIA DE IMAGEN Y COLORES
  // =========================================================================

  const formProducto = document.getElementById('productoForm');
  if (formProducto instanceof HTMLFormElement) {
    const inputImagen = document.getElementById('productoImagen');
    const previewImagen = document.getElementById('productoImagenPreview');
    const imagenDefecto = formProducto.dataset.imagenDefault || 'img/producto-sin-imagen.svg';

    if (inputImagen instanceof HTMLInputElement && previewImagen instanceof HTMLImageElement) {
      previewImagen.addEventListener('error', () => {
        if (previewImagen.getAttribute('src') !== imagenDefecto) {
          previewImagen.src = imagenDefecto;
        }
      });

      const actualizarImagen = () => {
        const valor = inputImagen.value.trim();
        previewImagen.src = valor === '' ? imagenDefecto : valor;
      };

      inputImagen.addEventListener('input', actualizarImagen);
      inputImagen.addEventListener('change', actualizarImagen);
    }

    const inputColores = document.getElementById('productoColores');
    const previewColores = document.getElementById('productoColoresPreview');

    if (inputColores instanceof HTMLInputElement && previewColores instanceof HTMLElement) {
      const pintarColores = () => {
        previewColores.textContent = '';

        let lista = [];
        const texto = inputColores.value.trim();
        if (texto !== '') {
          try {
            const json = JSON.parse(texto);
            lista = Array.isArray(json) ? json : [];
          } catch (error) {
            lista = texto.split(/[,;]+/);
          }
        }

        lista
          .map((color) => String(color).trim().toUpperCase())
          .filter((color) => /^#(?:[0-9A-F]{3}|[0-9A-F]{6})$/.test(color))
          .forEach((color) => {
            const muestra = document.createElement('span');
            muestra.className = 'pv-color-muestra';
            muestra.style.background = color;
            muestra.title = color;
            muestra.setAttribute('aria-label', color);
            previewColores.appendChild(muestra);
          });
      };

      inputColores.addEventListener('input', pintarColores);
      pintarColores();
    }

    // Evita dobles envíos mientras el navegador navega al guardado.
    formProducto.addEventListener('submit', () => {
      const boton = formProducto.querySelector('button[type="submit"]');
      if (boton instanceof HTMLButtonElement) {
        boton.disabled = true;
        boton.textContent = 'Guardando…';
      }
    });
  }

});
