/**
 * Chatbot flotante de Jeremy Tech.
 *
 * El token CSRF se obtiene de ?ruta=chat/token y se envía como campo `_csrf`
 * y cabecera `X-CSRF-Token`. El backend valida el POST globalmente.
 */
(function () {
  'use strict';

  const root = document.getElementById('chatbot');
  if (!root) return;

  const panel    = document.getElementById('chatbot-panel');
  const toggle   = document.getElementById('chatbot-toggle');
  const closeBtn = document.getElementById('chatbot-close');
  const form     = document.getElementById('chatbot-form');
  const input    = document.getElementById('chatbot-input');
  const send     = document.getElementById('chatbot-send');
  const messages = document.getElementById('chatbot-messages');
  const quickBox = document.getElementById('chatbot-quick');

  if (!panel || !toggle || !form || !input || !messages || !quickBox) return;

  const tokenUrl   = root.dataset.tokenUrl   || '?ruta=chat/token';
  const messageUrl = root.dataset.messageUrl || '?ruta=chat/message';
  const metaToken  = document.querySelector('meta[name="csrf-token"]');
  const csrfMeta   = metaToken ? metaToken.getAttribute('content') || '' : '';

  /* Ajuste de altura en móvil cuando aparece el teclado virtual */
  if (window.visualViewport) {
    const updateVp = function () {
      root.style.setProperty('--chat-viewport', Math.round(window.visualViewport.height) + 'px');
    };
    updateVp();
    window.visualViewport.addEventListener('resize', updateVp);
    window.visualViewport.addEventListener('scroll', updateVp);
  }

  const QUICK_DEFAULT = [
    { label: '¿Dónde está mi pedido?', text: '¿Dónde está mi pedido?'  },
    { label: 'Envíos',                 text: 'Envíos'                   },
    { label: 'Pagos',                  text: 'Métodos de pago'          },
    { label: 'Devoluciones',           text: 'Devoluciones y garantías' }
  ];

  let csrfToken    = '';
  let enviando     = false;
  let quickInicial = false;
  let typing       = null;

  /* ── Persistencia ── */
  function guardarAbierto(v) {
    try { sessionStorage.setItem('jt_chat_abierto', v ? '1' : '0'); } catch (_) {}
  }
  function abiertoPreviamente() {
    try { return sessionStorage.getItem('jt_chat_abierto') === '1'; } catch (_) { return false; }
  }

  /* ── Abrir / cerrar ── */
  function setOpen(abierto, enfocar) {
    panel.hidden = !abierto;
    toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    root.classList.toggle('chatbot-abierto', abierto);
    guardarAbierto(abierto);

    if (abierto) {
      if (!quickInicial) {
        renderQuick(QUICK_DEFAULT);
        quickInicial = true;
      }
      scrollAbajo();
      if (enfocar === true) {
        window.setTimeout(function () { input.focus(); }, 80);
      }
    }
  }

  function scrollAbajo() {
    messages.scrollTop = messages.scrollHeight;
  }

  /* ── Burbuja — se inserta antes del quickBox para que los botones
        queden siempre al final del área de mensajes ── */
  function addBubble(texto, autor) {
    const bubble = document.createElement('div');
    bubble.className = 'chatbot-bubble ' + (autor === 'user' ? 'user' : 'bot');
    bubble.textContent = String(texto);
    messages.insertBefore(bubble, quickBox);
    scrollAbajo();
    return bubble;
  }

  /* ── Links ── */
  function urlSegura(url) {
    const v = String(url || '').trim();
    if (v.startsWith('?ruta=') || v.startsWith('/') || v.startsWith('#')) return v;
    if (/^https?:\/\//i.test(v) || /^mailto:/i.test(v)) return v;
    return '';
  }

  function addLinks(enlaces) {
    if (!Array.isArray(enlaces)) return;
    const validos = [];
    enlaces.forEach(function (e) {
      if (!e || typeof e !== 'object') return;
      const url   = urlSegura(e.url);
      const label = String(e.label || '').trim();
      if (url && label) validos.push({ url: url, label: label });
    });
    if (!validos.length) return;

    const box = document.createElement('div');
    box.className = 'chatbot-links';
    validos.forEach(function (enlace) {
      const a = document.createElement('a');
      a.href = enlace.url;
      a.textContent = enlace.label;
      if (/^https?:\/\//i.test(enlace.url)) {
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
      }
      box.appendChild(a);
    });
    messages.insertBefore(box, quickBox);
    scrollAbajo();
  }

  /* ── Botones rápidos ── */
  function renderQuick(items) {
    quickBox.replaceChildren();

    if (!Array.isArray(items) || items.length === 0) {
      quickBox.hidden = true;
      return;
    }

    quickBox.hidden = false;
    items.forEach(function (item) {
      if (!item || typeof item !== 'object') return;
      const label = String(item.label || '').trim();
      const texto = String(item.text  || label).trim();
      if (!label || !texto) return;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = label;
      if (item.primary) btn.classList.add('primary');
      btn.addEventListener('click', function () {
        input.value = texto;
        form.requestSubmit();
      });
      quickBox.appendChild(btn);
    });
    scrollAbajo();
  }

  /* ── Indicador "escribiendo" ── */
  function mostrarEscribiendo() {
    quitarEscribiendo();
    const el = document.createElement('div');
    el.className = 'chatbot-typing';
    el.id = 'chatbot-typing';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-label', 'Jeremy-Bot está escribiendo');
    el.innerHTML = '<span></span><span></span><span></span>';
    messages.insertBefore(el, quickBox);
    typing = el;
    scrollAbajo();
  }

  function quitarEscribiendo() {
    if (typing && typing.parentNode) typing.parentNode.removeChild(typing);
    const el = document.getElementById('chatbot-typing');
    if (el && el.parentNode) el.parentNode.removeChild(el);
    typing = null;
  }

  /* ── Estado de envío ── */
  function setEnviando(estado) {
    enviando = estado;
    if (send) {
      send.disabled = estado;
      send.setAttribute('aria-busy', estado ? 'true' : 'false');
    }
    input.disabled = estado;
    if (!estado) input.focus();
  }

  /* ── Token CSRF ── */
  async function obtenerToken() {
    if (csrfToken) return csrfToken;
    try {
      const res = await fetch(tokenUrl, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (res.ok) {
        const d = await res.json().catch(function () { return null; });
        if (d && typeof d.token === 'string' && d.token) {
          csrfToken = d.token;
          return csrfToken;
        }
      }
    } catch (_) {}
    csrfToken = csrfMeta;
    return csrfToken;
  }

  /* ── Enviar mensaje ── */
  async function enviarMensaje(texto) {
    if (enviando) return;
    const mensaje = String(texto || '').trim();
    if (!mensaje) return;

    addBubble(mensaje, 'user');
    input.value = '';
    renderQuick([]);
    setEnviando(true);
    mostrarEscribiendo();

    try {
      let token = await obtenerToken();
      if (!token) throw new Error('Sin token CSRF');

      let res = await enviarPeticion(mensaje, token);

      if (res.status === 403) {
        csrfToken = '';
        token = await obtenerToken();
        if (token) res = await enviarPeticion(mensaje, token);
      }

      const datos = await res.json().catch(function () { return null; });
      quitarEscribiendo();

      if (!res.ok) {
        addBubble(
          res.status === 403
            ? 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.'
            : (datos && datos.reply) || 'No pude procesar el mensaje. Inténtalo de nuevo.',
          'bot'
        );
        return;
      }

      if (!datos || typeof datos.reply !== 'string' || !datos.reply.trim()) {
        addBubble('No pude generar una respuesta. Inténtalo de nuevo.', 'bot');
        return;
      }

      addBubble(datos.reply, 'bot');
      addLinks(datos.links);
      addLinks(datos.extra_links);
      renderQuick(Array.isArray(datos.quick) ? datos.quick : QUICK_DEFAULT);

    } catch (_) {
      quitarEscribiendo();
      addBubble('No pude conectar con el chat. Revisa tu conexión e inténtalo de nuevo.', 'bot');
    } finally {
      quitarEscribiendo();
      setEnviando(false);
    }
  }

  function enviarPeticion(mensaje, token) {
    const body = new FormData();
    body.append('message', mensaje);
    body.append('_csrf', token);
    return fetch(messageUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': token
      }
    });
  }

  /* ── Eventos ── */
  toggle.addEventListener('click', function () {
    setOpen(panel.hidden, true);
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setOpen(false, false);
      toggle.focus();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    enviarMensaje(input.value);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !panel.hidden) {
      setOpen(false, false);
      toggle.focus();
    }
  });

  if (abiertoPreviamente()) {
    setOpen(true, false);
  }
})();
