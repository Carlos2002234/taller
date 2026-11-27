// JavaScript ligero del sitio: menú del celular y contador del carrito.

(function () {
  'use strict';

  // Menú desplegable en celular
  var boton = document.querySelector('[data-menu-boton]');
  var menu = document.getElementById('menu-movil');
  if (boton && menu) {
    boton.addEventListener('click', function () {
      var abierto = boton.getAttribute('aria-expanded') === 'true';
      boton.setAttribute('aria-expanded', String(!abierto));
      boton.setAttribute('aria-label', abierto ? 'Abrir menú' : 'Cerrar menú');
      menu.classList.toggle('hidden', abierto);
      boton.querySelector('[data-menu-icono-abrir]').classList.toggle('hidden', !abierto);
      boton.querySelector('[data-menu-icono-cerrar]').classList.toggle('hidden', abierto);
    });
  }

  // Animaciones al bajar: las secciones .aparecer, las grillas .escalonado y el bloque racing del inicio se muestran UNA vez,
  // cuando el 15% entra en pantalla (los estilos están en "Movimiento", resources/css/app.css)
  var animables = document.querySelectorAll('.aparecer, .escalonado, .revelar, .portada-racing');
  if ('IntersectionObserver' in window) {
    // Un .revelar empieza recortado por completo (clip-path) y el navegador no lo cuenta como "visible":
    // se observa su contenedor y, al entrar, se destapa el elemento.
    var destinos = new Map();
    var observador = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (entrada) {
        if (entrada.isIntersecting) {
          (destinos.get(entrada.target) || []).forEach(function (el) { el.classList.add('visible'); });
          destinos.delete(entrada.target);
          observador.unobserve(entrada.target);
        }
      });
    }, { threshold: 0.15 });
    animables.forEach(function (el) {
      var mirar = el.classList.contains('revelar') ? el.parentElement : el;
      if (!destinos.has(mirar)) destinos.set(mirar, []);
      destinos.get(mirar).push(el);
      observador.observe(mirar);
    });
  } else {
    animables.forEach(function (el) { el.classList.add('visible'); });
  }

  // Preguntas frecuentes: <details> nativo (sin JS abre al instante). Aquí solo se anima:
  // al abrir, el navegador pone [open] y en el cuadro siguiente se agrega "abierto" (0fr → 1fr en CSS);
  // al cerrar, se quita "abierto" y [open] se retira cuando termina la transición.
  document.querySelectorAll('[data-faq]').forEach(function (seccion) {
    var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    seccion.classList.add('faq-js');
    seccion.querySelectorAll('[data-faq-item]').forEach(function (item) {
      var respuesta = item.querySelector('.faq-respuesta');
      var espera = null;
      if (item.open) item.classList.add('abierto');

      // Abre por clic, teclado o "buscar en la página" (Chrome abre el <details> solo): todo pasa por "toggle"
      item.addEventListener('toggle', function () {
        if (item.open && !item.classList.contains('abierto')) {
          requestAnimationFrame(function () { item.classList.add('abierto'); });
        }
      });

      item.querySelector('summary').addEventListener('click', function (evento) {
        if (!item.open) return; // abrir: lo hace el navegador
        evento.preventDefault();
        if (!item.classList.contains('abierto')) { item.classList.add('abierto'); return; } // se estaba cerrando: vuelve a abrir
        item.classList.remove('abierto');
        var terminar = function () {
          clearTimeout(espera);
          respuesta.removeEventListener('transitionend', alTerminar);
          if (!item.classList.contains('abierto')) item.open = false;
        };
        var alTerminar = function (e) { if (e.target === respuesta && e.propertyName === 'grid-template-rows') terminar(); };
        if (reducido) { terminar(); return; }
        respuesta.addEventListener('transitionend', alTerminar);
        espera = setTimeout(terminar, 600); // por si el navegador no anima grid-template-rows
      });
    });
  });

  // Video del taller: se descarga y reproduce solo cuando está en pantalla, y se pausa al salir.
  // Con "reducir movimiento" o "ahorro de datos" no arranca solo (queda la portada con el botón ▶).
  document.querySelectorAll('[data-video]').forEach(function (contenedor) {
    var video = contenedor.querySelector('[data-video-auto]');
    var control = contenedor.querySelector('[data-video-boton]');
    if (!video || !control) return;
    var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var ahorro = navigator.connection && navigator.connection.saveData;
    var detenidoPorUsuario = reducido || ahorro;
    var enPantalla = false;

    var reproducir = function () {
      var promesa = video.play();
      if (promesa && promesa.catch) promesa.catch(function () {}); // el navegador puede bloquear la reproducción automática
    };
    var mostrarEstado = function () {
      var pausado = video.paused;
      control.setAttribute('aria-label', pausado ? 'Reproducir video' : 'Pausar video');
      control.querySelector('[data-video-icono-reproducir]').classList.toggle('hidden', !pausado);
      control.querySelector('[data-video-icono-pausa]').classList.toggle('hidden', pausado);
    };
    video.addEventListener('play', mostrarEstado);
    video.addEventListener('pause', mostrarEstado);
    control.classList.remove('hidden');

    control.addEventListener('click', function () {
      if (video.paused) {
        detenidoPorUsuario = false;
        reproducir();
      } else {
        detenidoPorUsuario = true;
        video.pause();
      }
    });

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entradas) {
        enPantalla = entradas[0].isIntersecting;
        if (enPantalla && !detenidoPorUsuario) reproducir();
        if (!enPantalla && !video.paused) video.pause();
      }, { threshold: 0.35 }).observe(video);
    }
  });

  // Reseñas de Google del inicio (componente testimonios.php; estilos "Reseñas de Google" en app.css).
  // 1) Entrada al bajar, UNA vez: encabezado, bloque de confianza, tarjetas en cascada y llamada final.
  //    Solo se ocultan (clase "por-entrar") las partes que todavía están debajo de la pantalla:
  //    si este código falla o tarda, todo se ve normal.
  // 2) El "5.0" cuenta desde 0.0 en 1,2 s (ease-out) y luego las estrellas se llenan una por una.
  // 3) Carrusel con transform: flechas, puntos, ← → / Inicio / Fin, arrastre con el dedo y
  //    desplazamiento horizontal del trackpad. Autoplay cada N s que se detiene con el mouse encima,
  //    con el foco del teclado, al tocarlo (hasta que se pulse "Reanudar"), fuera de pantalla o con la
  //    pestaña oculta. Con "reducir movimiento": sin autoplay ni desplazamientos (cambio con fundido).
  document.querySelectorAll('[data-resenas]').forEach(function (raiz) {
    var carrusel = raiz.querySelector('[data-resenas-carrusel]');
    var ventana = raiz.querySelector('[data-resenas-ventana]');
    var pista = raiz.querySelector('[data-resenas-pista]');
    var tarjetas = Array.prototype.slice.call(raiz.querySelectorAll('[data-resena]'));
    var puntos = Array.prototype.slice.call(raiz.querySelectorAll('[data-resenas-punto]'));
    var botonPausa = raiz.querySelector('[data-resenas-pausa]');
    var confianza = raiz.querySelector('[data-resenas-confianza]');
    var numero = raiz.querySelector('[data-resenas-numero]');
    var estrellas = raiz.querySelector('.resenas-estrellas');
    if (!carrusel || !ventana || !pista || !tarjetas.length || !botonPausa) return;

    var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var segundos = parseInt(raiz.getAttribute('data-segundos'), 10) || 6;
    var duracion = segundos * 1000;

    // --- 1) y 2) Entrada y contador ---
    var contar = function () {
      if (!confianza || !confianza.classList.contains('por-contar')) return;
      var terminar = function () {
        if (numero) numero.textContent = numero.getAttribute('data-valor');
        if (estrellas) estrellas.classList.add('llenas');
        confianza.classList.remove('por-contar');
      };
      if (reducido || !numero || !window.requestAnimationFrame) { terminar(); return; }
      var meta = parseFloat(numero.getAttribute('data-valor')) || 0;
      var t0 = null;
      setTimeout(function () {
        requestAnimationFrame(function paso(t) {
          if (t0 === null) t0 = t;
          var avance = Math.min(1, (t - t0) / 1200);
          var suave = 1 - Math.pow(1 - avance, 3); // ease-out
          numero.textContent = (meta * suave).toFixed(1);
          if (avance < 1) requestAnimationFrame(paso); else terminar();
        });
      }, 200);
    };
    var entrar = function (parte) {
      parte.classList.remove('por-entrar');
      parte.classList.add('entrando');
      if (parte === confianza) contar();
    };
    var partes = raiz.querySelectorAll('[data-resenas-parte], [data-resenas-carrusel]');
    if ('IntersectionObserver' in window) {
      var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
          if (!entrada.isIntersecting) return;
          observador.unobserve(entrada.target);
          entrar(entrada.target);
        });
      }, { threshold: 0.15 });
      partes.forEach(function (parte) {
        if (parte.getBoundingClientRect().top < window.innerHeight * 0.9) return; // ya se ve: no se toca
        parte.classList.add('por-entrar');
        if (parte === confianza) {
          parte.classList.add('por-contar');
          if (numero && !reducido) numero.textContent = '0.0';
        }
        observador.observe(parte);
      });
    }

    // --- 3) Carrusel ---
    var activo = 0;
    var desplazamientos = [0];
    var pausa = { mouse: false, foco: false, usuario: reducido, toque: false, fuera: true };
    var temporizador = null, inicio = 0, restante = duracion;

    raiz.style.setProperty('--resenas-duracion', segundos + 's'); // CSSOM (permitido por la CSP)

    // Dónde empieza cada posición (la última deja la última tarjeta pegada a la derecha)
    var medir = function () {
      var ultima = tarjetas[tarjetas.length - 1];
      var maximo = Math.max(0, ultima.offsetLeft + ultima.offsetWidth - pista.clientWidth);
      desplazamientos = [];
      for (var i = 0; i < tarjetas.length; i++) {
        var x = Math.min(tarjetas[i].offsetLeft, maximo);
        desplazamientos.push(x);
        if (x >= maximo - 1) break;
      }
      puntos.forEach(function (punto, i) { punto.hidden = i >= desplazamientos.length; });
      if (activo >= desplazamientos.length) activo = desplazamientos.length - 1;
      ubicar();
    };
    var mover = function (x) { pista.style.transform = 'translate3d(' + (-x) + 'px, 0, 0)'; };
    var ubicar = function () {
      mover(desplazamientos[activo]);
      puntos.forEach(function (punto, i) { punto.setAttribute('aria-pressed', i === activo ? 'true' : 'false'); });
    };

    var detenido = function () { return pausa.mouse || pausa.foco || pausa.usuario || pausa.toque || pausa.fuera; };
    var parar = function () {
      if (temporizador === null) return;
      clearTimeout(temporizador);
      temporizador = null;
      restante = Math.max(0, restante - (Date.now() - inicio));
    };
    var seguir = function () {
      if (temporizador !== null || detenido() || desplazamientos.length < 2) return;
      inicio = Date.now();
      temporizador = setTimeout(function () { temporizador = null; ir(activo + 1, true); }, restante);
    };
    // Barra roja del punto activo: corre con el autoplay; sin autoplay (pausa del usuario) queda llena
    var barra = function (reiniciar) {
      var corre = !(pausa.usuario || pausa.toque) && desplazamientos.length > 1;
      puntos.forEach(function (punto, i) {
        var b = punto.querySelector('[data-resenas-barra]');
        if (i !== activo || !corre) { b.classList.remove('corriendo'); return; }
        if (reiniciar || !b.classList.contains('corriendo')) {
          b.classList.remove('corriendo');
          void b.offsetWidth; // reinicia la animación
          b.classList.add('corriendo');
        }
      });
    };
    var mostrarEstado = function () {
      if (detenido()) parar(); else seguir();
      raiz.classList.toggle('en-pausa', detenido());
      var enPausa = pausa.usuario || pausa.toque;
      botonPausa.textContent = enPausa ? 'Reanudar' : 'Pausar';
      botonPausa.setAttribute('aria-label', enPausa
        ? 'Reanudar: las reseñas están en pausa'
        : 'Pausar: las reseñas cambian solas cada ' + segundos + ' segundos');
      barra(false);
    };

    // dar_vuelta: del final vuelve al inicio (autoplay, flechas); deslizar con el dedo no da la vuelta
    var ir = function (indice, darVuelta) {
      var total = desplazamientos.length;
      var nuevo = darVuelta ? (indice + total) % total : Math.max(0, Math.min(total - 1, indice));
      if (nuevo !== activo && reducido) {
        pista.classList.remove('cambio-fundido');
        void pista.offsetWidth;
        pista.classList.add('cambio-fundido');
      }
      activo = nuevo;
      ubicar();
      parar();
      restante = duracion;
      barra(true);
      mostrarEstado();
    };

    puntos.forEach(function (punto, i) { punto.addEventListener('click', function () { ir(i, false); }); });
    raiz.querySelector('[data-resenas-anterior]').addEventListener('click', function () { ir(activo - 1, true); });
    raiz.querySelector('[data-resenas-siguiente]').addEventListener('click', function () { ir(activo + 1, true); });
    botonPausa.addEventListener('click', function () {
      if (pausa.usuario || pausa.toque) { pausa.usuario = false; pausa.toque = false; restante = duracion; }
      else pausa.usuario = true;
      mostrarEstado();
      barra(true);
    });

    // Pausa con el mouse encima de las tarjetas
    ventana.addEventListener('pointerenter', function (evento) { if (evento.pointerType === 'mouse') { pausa.mouse = true; mostrarEstado(); } });
    ventana.addEventListener('pointerleave', function (evento) { if (evento.pointerType === 'mouse') { pausa.mouse = false; mostrarEstado(); } });

    // Pausa con el foco del teclado (el foco que deja un clic no cuenta)
    carrusel.addEventListener('focusin', function (evento) {
      var conTeclado = true;
      try { conTeclado = evento.target.matches(':focus-visible'); } catch (e) { /* navegador viejo: se asume teclado */ }
      pausa.foco = conTeclado;
      mostrarEstado();
    });
    carrusel.addEventListener('focusout', function (evento) {
      if (!carrusel.contains(evento.relatedTarget)) { pausa.foco = false; mostrarEstado(); }
    });

    // Teclado sobre las tarjetas
    ventana.addEventListener('keydown', function (evento) {
      var teclas = { ArrowRight: activo + 1, ArrowLeft: activo - 1, Home: 0, End: desplazamientos.length - 1 };
      if (!(evento.key in teclas)) return;
      evento.preventDefault();
      ir(teclas[evento.key], false);
    });

    // Arrastre con el dedo: la pista sigue al dedo y al soltar se acomoda (el scroll vertical no se toca)
    var toque = null;
    ventana.addEventListener('pointerdown', function (evento) {
      if (evento.pointerType === 'mouse') return;
      toque = { x: evento.clientX, y: evento.clientY, t: Date.now(), dir: null, dx: 0 };
      pausa.toque = true; // al tocar, el autoplay se detiene hasta que se pulse "Reanudar"
      mostrarEstado();
    });
    ventana.addEventListener('pointermove', function (evento) {
      if (!toque) return;
      var dx = evento.clientX - toque.x, dy = evento.clientY - toque.y;
      if (!toque.dir && (Math.abs(dx) > 8 || Math.abs(dy) > 8)) toque.dir = Math.abs(dx) > Math.abs(dy) ? 'h' : 'v';
      if (toque.dir !== 'h') return;
      toque.dx = dx;
      var base = desplazamientos[activo], maximo = desplazamientos[desplazamientos.length - 1];
      var x = base - dx;
      if (x < 0) x = x * 0.3; else if (x > maximo) x = maximo + (x - maximo) * 0.3; // resistencia en los bordes
      pista.classList.add('arrastrando');
      mover(x);
    });
    var soltar = function () {
      if (!toque) return;
      var dx = toque.dx, rapido = Math.abs(dx) > 25 && Date.now() - toque.t < 250;
      var horizontal = toque.dir === 'h';
      toque = null;
      pista.classList.remove('arrastrando');
      if (horizontal && (Math.abs(dx) > 50 || rapido)) ir(activo + (dx < 0 ? 1 : -1), false);
      else ubicar();
    };
    ventana.addEventListener('pointerup', soltar);
    ventana.addEventListener('pointercancel', soltar);

    // Trackpad: un gesto horizontal pasa una tarjeta (con una pausa corta para no saltar varias)
    var ultimaRueda = 0;
    ventana.addEventListener('wheel', function (evento) {
      if (Math.abs(evento.deltaX) <= Math.abs(evento.deltaY) || Math.abs(evento.deltaX) < 20) return;
      evento.preventDefault();
      if (Date.now() - ultimaRueda < 600) return;
      ultimaRueda = Date.now();
      ir(activo + (evento.deltaX > 0 ? 1 : -1), false);
    }, { passive: false });

    // Solo corre mientras las tarjetas se ven; la pestaña oculta también lo congela
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entradas) {
        pausa.fuera = !entradas[0].isIntersecting;
        mostrarEstado();
      }, { threshold: 0.25 }).observe(ventana);
    } else {
      pausa.fuera = false;
    }
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { pausa.fuera = true; mostrarEstado(); return; }
      var caja = ventana.getBoundingClientRect();
      pausa.fuera = caja.bottom < 0 || caja.top > window.innerHeight;
      mostrarEstado();
    });

    // Al cambiar el ancho (girar el teléfono, cambiar la ventana) se vuelven a medir las posiciones
    var esperando = false;
    window.addEventListener('resize', function () {
      if (esperando) return;
      esperando = true;
      requestAnimationFrame(function () { esperando = false; medir(); });
    });

    raiz.classList.add('listo');
    ventana.scrollLeft = 0; // por si se deslizó de forma nativa antes de que cargara este código
    medir();
    mostrarEstado();
  });

  // Horario: "Abierto ahora" / "Cerrado · abre …" y el día de hoy, con la hora de Costa Rica.
  // Misma regla que horario_estado() en PHP (que ya dejó el texto correcto); aquí se actualiza cada minuto.
  (function () {
    var bloques = document.querySelectorAll('[data-horario]');
    if (!bloques.length || !window.Intl) return;
    var DIAS = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
    var NUM = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6, Sun: 7 };
    var hora12 = function (h) {
      var p = h.split(':'), hh = parseInt(p[0], 10);
      return (hh % 12 === 0 ? 12 : hh % 12) + ':' + p[1] + ' ' + (hh >= 12 && hh < 24 ? 'p.m.' : 'a.m.');
    };
    var minutos = function (h) { var p = h.split(':'); return parseInt(p[0], 10) * 60 + parseInt(p[1], 10); };
    var ahoraCR = function () {
      var partes = {};
      new Intl.DateTimeFormat('en-US', { timeZone: 'America/Costa_Rica', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
        .formatToParts(new Date()).forEach(function (p) { partes[p.type] = p.value; });
      return { dia: NUM[partes.weekday], minuto: (parseInt(partes.hour, 10) % 24) * 60 + parseInt(partes.minute, 10) };
    };
    var duracion = function (m) { var h = Math.floor(m / 60), r = m % 60; return ((h ? h + ' h ' : '') + (r || !h ? r + ' min' : '')).trim(); };
    var actualizar = function () {
      var ahora;
      try { ahora = ahoraCR(); } catch (e) { return; }
      bloques.forEach(function (bloque) {
        var semana;
        try { semana = JSON.parse(bloque.getAttribute('data-horario')); } catch (e) { return; }
        var hoy = semana[ahora.dia], abierto = false, texto = 'Cerrado', cuenta = '', progreso = 0, simple = 'Cerrado';
        if (hoy && ahora.minuto >= minutos(hoy[0]) && ahora.minuto < minutos(hoy[1])) {
          abierto = true;
          texto = 'Abierto ahora · cierra a las ' + hora12(hoy[1]);
          simple = 'Abierto hoy hasta las ' + hora12(hoy[1]);
          cuenta = 'Cierra en ' + duracion(minutos(hoy[1]) - ahora.minuto);
          progreso = Math.round((ahora.minuto - minutos(hoy[0])) * 100 / (minutos(hoy[1]) - minutos(hoy[0])));
        } else {
          for (var i = 0; i <= 7; i++) {
            var d = (ahora.dia - 1 + i) % 7 + 1, h = semana[d];
            if (!h || (i === 0 && ahora.minuto >= minutos(h[0]))) continue;
            var cuando = i === 0 ? 'hoy' : i === 1 ? 'mañana' : 'el ' + DIAS[d];
            var faltan = i * 1440 + minutos(h[0]) - ahora.minuto;
            texto = 'Cerrado · abre ' + cuando + ' a las ' + hora12(h[0]);
            simple = 'Cerrado · abrimos ' + cuando + ' a las ' + hora12(h[0]);
            cuenta = faltan < 1440 ? 'Abre en ' + duracion(faltan) : 'Abre ' + cuando + ' a las ' + hora12(h[0]);
            break;
          }
        }
        var poner = function (selector, valor) { var el = bloque.querySelector(selector); if (el) el.textContent = valor; };
        bloque.querySelectorAll('[data-horario-estado]').forEach(function (el) { el.classList.toggle('abierto', abierto); });
        poner('[data-horario-texto]', texto);
        poner('[data-horario-titulo]', abierto ? 'Abierto ahora' : 'Cerrado');
        poner('[data-horario-cuenta]', cuenta);
        poner('[data-horario-simple]', simple);
        var barra = bloque.querySelector('[data-horario-progreso]');
        if (barra) barra.style.width = progreso + '%'; // CSSOM (permitido por la CSP)
        bloque.querySelectorAll('[data-dia]').forEach(function (fila) {
          var esHoy = parseInt(fila.getAttribute('data-dia'), 10) === ahora.dia;
          fila.classList.toggle('hoy', esHoy);
          if (esHoy) fila.setAttribute('aria-current', 'date'); else fila.removeAttribute('aria-current');
        });
      });
    };
    actualizar();
    setInterval(actualizar, 60000);
  })();

  // Encabezado transparente sobre la portada: se vuelve sólido al bajar o al abrir el menú
  var cuerpo = document.querySelector('[data-encabezado]');
  if (cuerpo && cuerpo.classList.contains('encabezado-flotante')) {
    var actualizarEncabezado = function () {
      var menuAbierto = boton && boton.getAttribute('aria-expanded') === 'true';
      cuerpo.classList.toggle('encabezado-solido', window.scrollY > 40 || menuAbierto);
    };
    window.addEventListener('scroll', actualizarEncabezado, { passive: true });
    if (boton) boton.addEventListener('click', actualizarEncabezado);
    actualizarEncabezado();
  }

  // Encabezado: sombra suave en cuanto se baja un poco (se ve que queda "flotando" sobre el contenido)
  var encabezado = document.querySelector('header');
  if (encabezado) {
    var sombraEncabezado = function () { encabezado.classList.toggle('encabezado-sombra', window.scrollY > 8); };
    window.addEventListener('scroll', sombraEncabezado, { passive: true });
    sombraEncabezado();
  }

  // Selects que envían su formulario al cambiar (ej. "Ordenar por" en la tienda)
  document.querySelectorAll('[data-auto-enviar]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // Origen de la visita (anuncios): se guarda 30 días para asociarlo al pedido.
  // 1) Parámetros UTM del enlace del anuncio; 2) si no hay, se deduce de fbclid/gclid
  //    (los agregan Facebook/Instagram y Google) o del sitio desde el que llegó la persona.
  try {
    var parametros = new URLSearchParams(location.search);
    var origen = null;
    if (parametros.get('utm_source')) {
      origen = { utm_source: parametros.get('utm_source'), utm_medium: parametros.get('utm_medium') || '', utm_campaign: parametros.get('utm_campaign') || '' };
    } else if (parametros.get('fbclid')) {
      origen = { utm_source: 'facebook', utm_medium: 'anuncio', utm_campaign: '' };
    } else if (parametros.get('gclid')) {
      origen = { utm_source: 'google', utm_medium: 'anuncio', utm_campaign: '' };
    } else if (document.referrer) {
      var sitio = new URL(document.referrer).hostname;
      var conocidos = { instagram: 'instagram', facebook: 'facebook', google: 'google', tiktok: 'tiktok', whatsapp: 'whatsapp', bing: 'bing' };
      if (sitio !== location.hostname) {
        Object.keys(conocidos).some(function (clave) {
          if (sitio.indexOf(clave) !== -1) { origen = { utm_source: conocidos[clave], utm_medium: 'referido', utm_campaign: '' }; return true; }
          return false;
        });
      }
    }
    if (origen) {
      origen.guardado = Date.now();
      localStorage.setItem('utm', JSON.stringify(origen));
    }
  } catch (e) { /* sin almacenamiento disponible */ }

  // Formularios de consulta: se les agrega el origen guardado (máximo 30 días) para saber qué anuncio trajo el contacto
  try {
    var origenGuardado = JSON.parse(localStorage.getItem('utm') || 'null');
    if (origenGuardado && Date.now() - origenGuardado.guardado < 30 * 24 * 3600 * 1000) {
      document.querySelectorAll('[data-consulta] [data-utm]').forEach(function (campo) {
        campo.value = origenGuardado[campo.getAttribute('data-utm')] || '';
      });
    }
  } catch (e) { /* sin almacenamiento disponible */ }

  // Ventana "¿Tienes preguntas?": el botón la abre (sin JavaScript, el enlace lleva a Contacto)
  var ventanaConsulta = document.getElementById('ventana-consulta');
  if (ventanaConsulta && typeof ventanaConsulta.showModal === 'function') {
    document.querySelectorAll('[data-abrir-consulta]').forEach(function (enlace) {
      enlace.addEventListener('click', function (evento) {
        evento.preventDefault();
        ventanaConsulta.showModal();
        var primerCampo = ventanaConsulta.querySelector('input[name="nombre"]');
        if (primerCampo) primerCampo.focus();
      });
    });
    ventanaConsulta.querySelectorAll('[data-cerrar-consulta]').forEach(function (boton) {
      boton.addEventListener('click', function () { ventanaConsulta.close(); });
    });
    // Clic en el fondo oscuro (fuera del recuadro) también la cierra
    ventanaConsulta.addEventListener('click', function (evento) {
      if (evento.target === ventanaConsulta) ventanaConsulta.close();
    });

    // Se abre sola apenas carga la página: es lo primero que ve cada visitante (decisión del dueño).
    // Una vez por visita: si la cierra, no reaparece en cada página mientras navega; en la próxima visita sí.
    var POPUP_CLAVE = 'consulta_popup_visto';
    var yaSeMostro = false;
    try { yaSeMostro = sessionStorage.getItem(POPUP_CLAVE) === '1'; } catch (e) { /* sin almacenamiento: se muestra */ }
    // ?consulta=abrir (ej. en un anuncio o para probar) la abre siempre
    if (!yaSeMostro || /[?&]consulta=abrir/.test(location.search)) {
      ventanaConsulta.showModal();
      try { sessionStorage.setItem(POPUP_CLAVE, '1'); } catch (e) { /* sin almacenamiento */ }
    }
  }

  // Quien envió una consulta en esta visita no vuelve a ver la ventana al seguir navegando
  if (/[?&]consulta=enviada/.test(location.search)) {
    try { sessionStorage.setItem('consulta_popup_visto', '1'); } catch (e) { /* sin almacenamiento */ }
  }

  // Botones "Copiar" (número SINPE, IBAN, monto)
  document.addEventListener('click', function (evento) {
    var boton = evento.target.closest('[data-copiar]');
    if (!boton || !navigator.clipboard) return;
    navigator.clipboard.writeText(boton.getAttribute('data-copiar')).then(function () {
      var etiqueta = boton.querySelector('[data-copiar-etiqueta]') || boton;
      var texto = etiqueta.textContent;
      etiqueta.textContent = boton.getAttribute('data-copiar-texto') || '¡Copiado!';
      boton.classList.add('copiado');
      setTimeout(function () { etiqueta.textContent = texto; boton.classList.remove('copiado'); }, 1800);
    });
  });

  // Subida del comprobante: mostrar el archivo elegido y avisar si pesa más de 5 MB
  document.querySelectorAll('[data-subir-comprobante]').forEach(function (formulario) {
    var campo = formulario.querySelector('input[type=file]');
    var nombre = formulario.querySelector('[data-nombre-archivo]');
    var boton = formulario.querySelector('button[type=submit]');
    var textoOriginal = nombre.textContent;

    campo.addEventListener('change', function () {
      var archivo = campo.files[0];
      if (!archivo) {
        nombre.textContent = textoOriginal;
        return;
      }
      if (archivo.size > 5 * 1024 * 1024) {
        alert('El archivo pesa más de 5 MB. Sube una captura de pantalla o un PDF más liviano.');
        campo.value = '';
        nombre.textContent = textoOriginal;
        return;
      }
      nombre.textContent = 'Archivo elegido: ' + archivo.name;
    });

    formulario.addEventListener('submit', function (evento) {
      if (!campo.files.length) {
        evento.preventDefault();
        alert('Elige la foto o el PDF de tu comprobante.');
        return;
      }
      boton.disabled = true;
      boton.textContent = 'Enviando…';
    });
  });

  // Contador del carrito (el carrito se guarda en el navegador; ver carrito.js)
  function cantidadEnCarrito() {
    try {
      var carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
      return carrito.reduce(function (total, item) { return total + (parseInt(item.cantidad, 10) || 0); }, 0);
    } catch (e) {
      return 0;
    }
  }

  window.actualizarContadorCarrito = function () {
    var cantidad = cantidadEnCarrito();
    document.querySelectorAll('[data-carrito-contador]').forEach(function (el) {
      el.textContent = cantidad > 99 ? '99+' : String(cantidad);
      el.classList.toggle('hidden', cantidad === 0);
      el.classList.toggle('flex', cantidad > 0);
    });
  };

  window.actualizarContadorCarrito();
})();
