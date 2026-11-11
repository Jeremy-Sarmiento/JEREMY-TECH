(() => {
  const contenedores = document.querySelectorAll('.diagrama-embebido, #vistaPreviaDiagrama');
  if (!contenedores.length || typeof HTMLDialogElement === 'undefined') return;

  const visor = document.createElement('dialog');
  visor.className = 'diagrama-visor';
  visor.setAttribute('aria-labelledby', 'diagrama-visor-titulo');
  visor.innerHTML = `
    <header>
      <h2 id="diagrama-visor-titulo">Diagrama ampliado</h2>
      <button type="button" data-accion="cerrar" autofocus>Cerrar</button>
    </header>
    <div class="diagrama-controles">
      <button type="button" data-accion="reducir">Reducir</button>
      <output aria-live="polite" aria-label="Nivel de ampliación"></output>
      <button type="button" data-accion="aumentar">Aumentar</button>
      <button type="button" data-accion="ajustar">Ajustar al ancho</button>
    </div>
    <p>Desplázate para recorrer el diagrama. Puedes usar las flechas del teclado. Escape cierra la vista.</p>
    <div class="diagrama-lienzo" tabindex="0" role="region" aria-label="Diagrama ampliado desplazable"></div>`;
  document.body.append(visor);

  const lienzo = visor.querySelector('.diagrama-lienzo');
  let svg, posicion, estiloOriginal, anchoBase, ancho;

  function redimensionar(nuevoAncho) {
    ancho = nuevoAncho;
    svg.style.width = `${ancho}px`;
    svg.style.maxWidth = 'none';
    visor.querySelector('output').textContent = `${Math.round(ancho / anchoBase * 100)}%`;
    visor.querySelector('[data-accion="reducir"]').disabled = ancho <= 200;
    visor.querySelector('[data-accion="aumentar"]').disabled = ancho >= 6000;
  }

  function restaurar() {
    if (!svg) return;
    if (estiloOriginal === null) svg.removeAttribute('style');
    else svg.setAttribute('style', estiloOriginal);
    posicion.replaceWith(svg);
    svg = null;
  }
  visor.addEventListener('close', restaurar);
  window.addEventListener('beforeprint', () => {
    if (visor.open) { restaurar(); visor.close(); }
  });
  visor.addEventListener('click', evento => {
    const accion = evento.target.dataset.accion;
    if (accion === 'cerrar') visor.close();
    if (accion === 'reducir') redimensionar(Math.max(200, ancho / 1.25));
    if (accion === 'aumentar') redimensionar(Math.min(6000, ancho * 1.25));
    if (accion === 'ajustar') redimensionar(lienzo.clientWidth);
  });

  contenedores.forEach(contenedor => {
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'diagrama-ampliar';
    boton.textContent = 'Ampliar diagrama';
    const titulo = contenedor.querySelector('p')?.textContent.trim() || 'Vista previa del DER';
    boton.setAttribute('aria-label', `Ampliar diagrama: ${titulo}`);
    contenedor.after(boton);
    const actualizar = () => { boton.disabled = !contenedor.querySelector('svg'); };
    actualizar();
    if (contenedor.id === 'vistaPreviaDiagrama') {
      new MutationObserver(actualizar).observe(contenedor, { childList: true, subtree: true });
    }
    boton.addEventListener('click', () => {
      svg = contenedor.querySelector('svg');
      if (!svg) return;
      anchoBase = svg.viewBox.baseVal.width || svg.getBoundingClientRect().width;
      const tamanos = [...svg.querySelectorAll('text')].map(texto => parseFloat(getComputedStyle(texto).fontSize));
      const minimo = Math.min(...tamanos.filter(tamano => tamano > 0), 14);
      estiloOriginal = svg.getAttribute('style');
      posicion = document.createComment('Posición original del diagrama');
      svg.replaceWith(posicion);
      lienzo.append(svg);
      visor.classList.toggle('diagrama-evidencia', contenedor.classList.contains('diagrama-evidencia'));
      visor.querySelector('h2').textContent = titulo;
      visor.showModal();
      redimensionar(Math.ceil(anchoBase * 14 / minimo));
      lienzo.scrollTo(0, 0);
    });
  });
})();
