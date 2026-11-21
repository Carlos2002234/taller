// Movimiento e interacción del sitio público (pedido del dueño 2026-11-21: "una página que la gente disfrute").
// Todo es opcional: sin este archivo la página se ve y funciona igual.
// Reglas: solo se cambian transform/translate/opacity (nada que mueva el diseño), un solo
// requestAnimationFrame por cuadro, y con "reducir movimiento" no se hace nada de esto.
// Estilos en el bloque "Interacción y movimiento al bajar" de resources/css/app.css.

(function () {
  'use strict';

  var consulta = function (q) { return window.matchMedia ? window.matchMedia(q) : { matches: false }; };
  var reducido = consulta('(prefers-reduced-motion: reduce)').matches;
  // Mouse o trackpad (no pantallas táctiles): inclinación, imán y parallax
  var punteroFino = consulta('(hover: hover) and (pointer: fine)').matches;
  var escritorio = consulta('(min-width: 1024px)').matches;

  // ---------------------------------------------------------------------------
  // 1) Al bajar: barra de progreso de lectura, encabezado compacto, parallax de la fachada
  //    y la cinta de categorías que acelera con la velocidad del scroll.
  // ---------------------------------------------------------------------------
  var barra = document.querySelector('[data-barra-lectura]');
  var encabezado = document.querySelector('header');
  var fachada = escritorio && punteroFino && !reducido ? document.querySelector('main picture > img.foto-entrar') : null;
  var pista = reducido ? null : document.querySelector('.cinta-racing-pista');
  var animacionCinta = null;
  var velocidadCinta = 1;
  var ultimoY = window.scrollY;
  var ultimoT = performance.now();
  var pendiente = false;

  var cuadro = function () {
    pendiente = false;
    var y = window.scrollY;
    var ahora = performance.now();

    if (barra) {
      var total = document.documentElement.scrollHeight - window.innerHeight;
      barra.style.transform = 'scaleX(' + (total > 0 ? Math.min(1, y / total) : 0) + ')';
    }
    if (encabezado) encabezado.classList.toggle('encabezado-compacto', y > 60);

    // Parallax: la foto baja más lento que la página, como máximo un 8% de su alto.
    // Se usa la propiedad "translate" para no chocar con la animación de entrada (que usa "transform").
    if (fachada) {
      var caja = fachada.parentNode.getBoundingClientRect();
      if (caja.bottom > 0) {
        var desplazamiento = Math.min(y * 0.12, caja.height * 0.08);
        fachada.style.translate = '0 ' + desplazamiento.toFixed(1) + 'px';
      }
    }

    // Cinta: más rápida mientras se baja rápido; vuelve sola a su ritmo
    if (pista) {
      if (!animacionCinta && pista.getAnimations) animacionCinta = pista.getAnimations()[0] || null;
      if (animacionCinta) {
        var dt = Math.max(16, ahora - ultimoT);
        var objetivo = 1 + Math.min(Math.abs(y - ultimoY) / dt * 2, 2.5); // hasta 3,5 veces más rápida
        velocidadCinta += (objetivo - velocidadCinta) * 0.15;
        animacionCinta.playbackRate = velocidadCinta;
        if (velocidadCinta > 1.02) pedirCuadro();
        else if (velocidadCinta !== 1) { velocidadCinta = 1; animacionCinta.playbackRate = 1; }
      }
    }
    ultimoY = y;
    ultimoT = ahora;
  };
  var pedirCuadro = function () {
    if (pendiente) return;
    pendiente = true;
    requestAnimationFrame(cuadro);
  };
  window.addEventListener('scroll', pedirCuadro, { passive: true });
  window.addEventListener('resize', pedirCuadro, { passive: true });
  pedirCuadro();

  // ---------------------------------------------------------------------------
  // 2) Tarjetas de producto y categoría: inclinación 3D suave que sigue al cursor
  //    (el brillo y el zoom de la imagen son CSS puro)
  // ---------------------------------------------------------------------------
  if (punteroFino && !reducido) {
    var tarjeta = null, ex = 0, ey = 0, esperando = false;
    var inclinar = function () {
      esperando = false;
      if (!tarjeta) return;
      var caja = tarjeta.getBoundingClientRect();
      var px = (ex - caja.left) / caja.width - 0.5;   // -0.5 … 0.5
      var py = (ey - caja.top) / caja.height - 0.5;
      tarjeta.style.setProperty('--ry', (px * 8).toFixed(2) + 'deg');
      tarjeta.style.setProperty('--rx', (-py * 8).toFixed(2) + 'deg');
    };
    var soltar = function () {
      if (!tarjeta) return;
      tarjeta.classList.remove('inclinando');
      tarjeta.style.removeProperty('--rx');
      tarjeta.style.removeProperty('--ry');
      tarjeta = null;
    };
    document.addEventListener('pointermove', function (evento) {
      if (evento.pointerType !== 'mouse') return;
      var actual = evento.target.closest ? evento.target.closest('.tarjeta-interactiva') : null;
      if (actual !== tarjeta) { soltar(); if (actual) { tarjeta = actual; tarjeta.classList.add('inclinando'); } }
      if (!tarjeta) return;
      ex = evento.clientX; ey = evento.clientY;
      if (!esperando) { esperando = true; requestAnimationFrame(inclinar); }
    }, { passive: true });
    document.addEventListener('pointerleave', soltar);

    // -------------------------------------------------------------------------
    // 3) Botones principales con efecto magnético leve (se acercan un poco al cursor)
    // -------------------------------------------------------------------------
    if (escritorio) {
      document.querySelectorAll('.btn-sesgo, .btn-primario:not(.btn-pequeno)').forEach(function (boton) {
        boton.classList.add('magnetico');
        boton.addEventListener('pointermove', function (evento) {
          var caja = boton.getBoundingClientRect();
          var dx = evento.clientX - (caja.left + caja.width / 2);
          var dy = evento.clientY - (caja.top + caja.height / 2);
          var limitar = function (v) { return Math.max(-8, Math.min(8, v)); };
          boton.style.translate = limitar(dx * 0.2).toFixed(1) + 'px ' + limitar(dy * 0.3).toFixed(1) + 'px';
        });
        boton.addEventListener('pointerleave', function () { boton.style.translate = ''; });
      });
    }
  }

  // ---------------------------------------------------------------------------
  // 4) Agregar al carrito: la miniatura vuela al carrito, el ícono rebota y el contador hace "pop".
  //    carrito.js avisa con el evento "carrito:agregado".
  // ---------------------------------------------------------------------------
  var carritoVisible = function () {
    var enlaces = document.querySelectorAll('[data-carrito-icono]');
    for (var i = 0; i < enlaces.length; i++) {
      var caja = enlaces[i].getBoundingClientRect();
      if (caja.width > 0 && caja.bottom > 0 && caja.top < window.innerHeight) return enlaces[i];
    }
    return null;
  };
  var rebotar = function (enlace) {
    document.querySelectorAll('[data-carrito-icono]').forEach(function (el) {
      el.classList.remove('carrito-rebota');
      void el.offsetWidth;
      el.classList.add('carrito-rebota');
    });
    if (enlace) setTimeout(function () { enlace.classList.remove('carrito-rebota'); }, 800);
  };

  document.addEventListener('carrito:agregado', function (evento) {
    var boton = evento.detail && evento.detail.boton;
    var destino = carritoVisible();
    if (reducido || !boton || !destino || !Element.prototype.animate) { rebotar(destino); return; }

    var contenedor = boton.closest('article, [data-producto]');
    var foto = contenedor && contenedor.querySelector('img');
    if (!foto || !foto.getBoundingClientRect().width) { rebotar(destino); return; }

    var origen = foto.getBoundingClientRect();
    var lado = Math.min(origen.width, origen.height, 140);
    var meta = destino.getBoundingClientRect();
    var mini = document.createElement('img');
    mini.src = foto.currentSrc || foto.src;
    mini.alt = '';
    mini.className = 'miniatura-vuela';
    mini.style.left = (origen.left + origen.width / 2 - lado / 2) + 'px';
    mini.style.top = (origen.top + origen.height / 2 - lado / 2) + 'px';
    mini.style.width = lado + 'px';
    mini.style.height = lado + 'px';
    document.body.appendChild(mini);

    var dx = meta.left + meta.width / 2 - (origen.left + origen.width / 2);
    var dy = meta.top + meta.height / 2 - (origen.top + origen.height / 2);
    var vuelo = mini.animate([
      { transform: 'translate(0, 0) scale(1)', opacity: 1 },
      { transform: 'translate(' + dx * 0.55 + 'px, ' + (dy * 0.55 - 60) + 'px) scale(.6)', opacity: 1, offset: 0.55 },
      { transform: 'translate(' + dx + 'px, ' + dy + 'px) scale(.12)', opacity: 0.4 }
    ], { duration: 700, easing: 'cubic-bezier(.22, 1, .36, 1)' });
    vuelo.onfinish = vuelo.oncancel = function () { mini.remove(); rebotar(destino); };
  });
})();
