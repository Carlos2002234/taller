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

  // Testimonios del inicio: un testimonio a la vez que cambia SOLO cada N segundos.
  // Por qué antes "no cambiaban" (revisado 2026-11-21):
  //   1) con "Reducir movimiento" (macOS/iOS) la rotación se apagaba por completo;
  //   2) se pausaba con el cursor en CUALQUIER parte de la tarjeta o de la lista;
  //   3) solo corría si se veía el 35% de toda la rejilla, que en el celular mide más
  //      que la pantalla: en teléfonos bajos nunca arrancaba.
  // Ahora: rota siempre (con "reducir movimiento" solo con fundido), pausa con el cursor
  // sobre la tarjeta activa, con el foco del teclado o con el botón Pausar/Reanudar, y
  // mira si se ve la tarjeta (no toda la rejilla). El tiempo lo lleva un temporizador
  // propio que recuerda cuánto faltaba al pausar; la barra roja solo lo muestra.
  // También: deslizar con el dedo y flechas ← → del teclado cuando el carrusel tiene foco.
  // Mientras cambia solo, la zona no se anuncia (aria-live="off"); al elegir a mano sí ("polite").
  // Sin JavaScript, el CSS muestra los 3 testimonios completos y oculta los controles.
  document.querySelectorAll('[data-testimonios]').forEach(function (raiz) {
    var paneles = raiz.querySelectorAll('[data-testimonio]');
    var opciones = raiz.querySelectorAll('[data-testimonio-opcion]');
    var zona = raiz.querySelector('[data-testimonios-paneles]');
    var botonPausa = raiz.querySelector('[data-testimonios-pausa]');
    if (paneles.length < 2 || paneles.length !== opciones.length) return;

    var segundos = parseInt(raiz.getAttribute('data-segundos'), 10) || 8;
    var duracion = segundos * 1000;
    var activo = 0;
    var pausa = { mouse: false, foco: false, usuario: false, fuera: true };
    var temporizador = null, inicio = 0, restante = duracion;

    raiz.style.setProperty('--testimonio-duracion', segundos + 's');

    var detenido = function () { return pausa.mouse || pausa.foco || pausa.usuario || pausa.fuera; };

    // Temporizador que se puede pausar y reanudar sin perder lo que ya avanzó
    var parar = function () {
      if (temporizador === null) return;
      clearTimeout(temporizador);
      temporizador = null;
      restante = Math.max(0, restante - (Date.now() - inicio));
    };
    var seguir = function () {
      if (temporizador !== null || detenido()) return;
      inicio = Date.now();
      temporizador = setTimeout(function () { temporizador = null; restante = duracion; ir(activo + 1, false, false); }, restante);
    };

    var mostrarEstado = function () {
      if (detenido()) parar(); else seguir();
      raiz.classList.toggle('testimonios-en-pausa', detenido());
      // Sin texto de estado visible (pedido del dueño): el botón dice qué hará y su
      // aria-label (que empieza con el texto visible) explica el estado a los lectores de pantalla.
      botonPausa.textContent = pausa.usuario ? 'Reanudar' : 'Pausar';
      botonPausa.setAttribute('aria-label', pausa.usuario
        ? 'Reanudar: los testimonios están en pausa'
        : 'Pausar: los testimonios cambian solos cada ' + segundos + ' segundos');
    };

    // Reinicia la barra del activo (quitar la clase, forzar el redibujo y volver a ponerla)
    var arrancarBarra = function () {
      opciones.forEach(function (opcion) { opcion.querySelector('[data-testimonio-barra]').classList.remove('corriendo'); });
      var barra = opciones[activo].querySelector('[data-testimonio-barra]');
      void barra.offsetWidth;
      barra.classList.add('corriendo');
    };

    var ir = function (indice, manual, atras) {
      var nuevo = (indice + paneles.length) % paneles.length;
      if (nuevo === activo) return;
      var anterior = paneles[activo];
      activo = nuevo;
      zona.setAttribute('aria-live', manual ? 'polite' : 'off');

      paneles.forEach(function (panel) { panel.classList.remove('saliendo', 'animar', 'atras'); });
      // El saliente se queda visible mientras se va (la clase se quita al terminar su animación)
      anterior.classList.remove('activo');
      anterior.classList.toggle('atras', !!atras);
      anterior.classList.add('saliendo');
      setTimeout(function () { anterior.classList.remove('saliendo', 'atras'); }, 600);

      var entrante = paneles[activo];
      entrante.classList.add('activo');
      entrante.classList.toggle('atras', !!atras);
      void entrante.offsetWidth;
      entrante.classList.add('animar');

      opciones.forEach(function (opcion, i) { opcion.setAttribute('aria-pressed', i === activo ? 'true' : 'false'); });
      parar();
      restante = duracion;
      arrancarBarra();
      mostrarEstado();
    };

    opciones.forEach(function (opcion, i) {
      opcion.addEventListener('click', function () { ir(i, true, i < activo); });
    });
    raiz.querySelector('[data-testimonio-anterior]').addEventListener('click', function () { ir(activo - 1, true, true); });
    raiz.querySelector('[data-testimonio-siguiente]').addEventListener('click', function () { ir(activo + 1, true, false); });
    botonPausa.addEventListener('click', function () { pausa.usuario = !pausa.usuario; mostrarEstado(); });

    // Pausa solo con el cursor sobre la tarjeta activa (no sobre la lista ni los botones)
    zona.addEventListener('pointerenter', function (evento) { if (evento.pointerType === 'mouse') { pausa.mouse = true; mostrarEstado(); } });
    zona.addEventListener('pointerleave', function (evento) { if (evento.pointerType === 'mouse') { pausa.mouse = false; mostrarEstado(); } });

    // Pausa con el foco del teclado (el foco que deja un clic no cuenta)
    raiz.addEventListener('focusin', function (evento) {
      var conTeclado = true;
      try { conTeclado = evento.target.matches(':focus-visible'); } catch (e) { /* navegador viejo: se asume teclado */ }
      pausa.foco = conTeclado;
      mostrarEstado();
    });
    raiz.addEventListener('focusout', function (evento) {
      if (!raiz.contains(evento.relatedTarget)) { pausa.foco = false; mostrarEstado(); }
    });

    // Flechas del teclado cuando el foco está dentro del carrusel
    raiz.addEventListener('keydown', function (evento) {
      if (evento.key === 'ArrowRight') { evento.preventDefault(); ir(activo + 1, true, false); }
      if (evento.key === 'ArrowLeft') { evento.preventDefault(); ir(activo - 1, true, true); }
    });

    // Deslizar con el dedo sobre la tarjeta (horizontal; el scroll vertical no se toca)
    var toque = null;
    zona.addEventListener('pointerdown', function (evento) {
      if (evento.pointerType !== 'mouse') toque = { x: evento.clientX, y: evento.clientY };
    });
    zona.addEventListener('pointerup', function (evento) {
      if (!toque) return;
      var dx = evento.clientX - toque.x, dy = evento.clientY - toque.y;
      toque = null;
      if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.3) ir(activo + (dx < 0 ? 1 : -1), true, dx > 0);
    });
    zona.addEventListener('pointercancel', function () { toque = null; });

    // Solo corre mientras la tarjeta se ve (no hace falta ver toda la rejilla)
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entradas) {
        pausa.fuera = !entradas[0].isIntersecting;
        mostrarEstado();
      }, { threshold: 0.25 }).observe(zona);
    } else {
      pausa.fuera = false;
    }
    // Pestaña oculta: se congela y al volver sigue donde iba
    document.addEventListener('visibilitychange', function () {
      pausa.fuera = document.hidden || pausa.fuera;
      if (!document.hidden && 'IntersectionObserver' in window) {
        var caja = zona.getBoundingClientRect();
        pausa.fuera = caja.bottom < 0 || caja.top > window.innerHeight;
      }
      mostrarEstado();
    });

    arrancarBarra();
    mostrarEstado();
  });

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
      var texto = boton.textContent;
      boton.textContent = '¡Copiado!';
      setTimeout(function () { boton.textContent = texto; }, 1500);
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
