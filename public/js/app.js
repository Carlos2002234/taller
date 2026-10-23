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

  // Animaciones al bajar: las secciones .aparecer y las grillas .escalonado se muestran al entrar en pantalla
  var animables = document.querySelectorAll('.aparecer, .escalonado');
  if ('IntersectionObserver' in window) {
    var observador = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (entrada) {
        if (entrada.isIntersecting) {
          entrada.target.classList.add('visible');
          observador.unobserve(entrada.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    animables.forEach(function (el) { observador.observe(el); });
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
