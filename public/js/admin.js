// JavaScript del panel: confirmar acciones peligrosas, revisar fotos antes de subirlas
// y avisar (aviso en pantalla, sonido y notificación) cuando entra una consulta nueva.

(function () {
  'use strict';

  // Formularios con data-confirmar="¿Seguro…?" piden confirmación antes de enviarse
  document.addEventListener('submit', function (evento) {
    var mensaje = evento.target.getAttribute('data-confirmar');
    if (mensaje && !window.confirm(mensaje)) evento.preventDefault();
  });

  // Fotos: mostrar cuántas se eligieron y avisar si alguna pesa más de 10 MB o sobran
  document.querySelectorAll('[data-fotos]').forEach(function (bloque) {
    var campo = bloque.querySelector('input[type=file]');
    var texto = bloque.querySelector('[data-fotos-texto]');
    var espacio = parseInt(bloque.getAttribute('data-espacio'), 10) || 0;
    if (!campo) return;

    campo.addEventListener('change', function () {
      var archivos = Array.prototype.slice.call(campo.files);
      var pesadas = archivos.filter(function (a) { return a.size > 10 * 1024 * 1024; });
      if (pesadas.length) {
        alert('Estas fotos pesan más de 10 MB y no se pueden subir:\n' + pesadas.map(function (a) { return a.name; }).join('\n'));
        campo.value = '';
        if (texto) texto.textContent = 'Elegir fotos (puedes elegir varias)';
        return;
      }
      if (archivos.length > espacio) {
        alert('Solo caben ' + espacio + ' foto(s) más. Se guardarán las primeras ' + espacio + '.');
      }
      if (texto) texto.textContent = archivos.length === 1 ? '1 foto elegida' : archivos.length + ' fotos elegidas';
    });
  });

  // Evitar doble envío en formularios que suben archivos
  document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (formulario) {
    formulario.addEventListener('submit', function (evento) {
      if (evento.defaultPrevented) return;
      var boton = formulario.querySelector('button[type=submit]');
      if (boton) {
        boton.disabled = true;
        boton.textContent = 'Guardando…';
      }
    });
  });

  // ---------------------------------------------------------------------
  // Alertas de consultas nuevas: el panel pregunta cada 30 s si llegó un lead.
  // ---------------------------------------------------------------------
  var urlNovedades = document.body.getAttribute('data-novedades');
  var zonaAvisos = document.querySelector('[data-avisos-panel]');
  var CLAVE_VISTA = 'ultima_consulta_avisada';
  var tituloOriginal = document.title;
  var sonido = null;

  // Los navegadores solo dejan sonar audio después de que la persona hizo clic en la página
  document.addEventListener('click', function () {
    if (!sonido && (window.AudioContext || window.webkitAudioContext)) {
      sonido = new (window.AudioContext || window.webkitAudioContext)();
    }
  });

  function sonar() {
    if (!sonido) return;
    [880, 1320].forEach(function (frecuencia, i) {
      var tono = sonido.createOscillator();
      var volumen = sonido.createGain();
      var inicio = sonido.currentTime + i * 0.18;
      tono.frequency.value = frecuencia;
      volumen.gain.setValueAtTime(0.0001, inicio);
      volumen.gain.exponentialRampToValueAtTime(0.25, inicio + 0.02);
      volumen.gain.exponentialRampToValueAtTime(0.0001, inicio + 0.16);
      tono.connect(volumen).connect(sonido.destination);
      tono.start(inicio);
      tono.stop(inicio + 0.17);
    });
  }

  function leerVista() {
    try { return parseInt(localStorage.getItem(CLAVE_VISTA), 10); } catch (e) { return NaN; }
  }
  function guardarVista(id) {
    try { localStorage.setItem(CLAVE_VISTA, String(id)); } catch (e) { /* sin almacenamiento */ }
  }

  function actualizarContadores(nuevas) {
    document.querySelectorAll('[data-contador-consultas]').forEach(function (el) {
      el.textContent = nuevas;
      el.classList.toggle('hidden', nuevas === 0 && !el.closest('[data-aviso-consultas-movil]'));
    });
    var movil = document.querySelector('[data-aviso-consultas-movil]');
    if (movil) movil.classList.toggle('hidden', nuevas === 0);
    document.title = nuevas > 0 ? '(' + nuevas + ') ' + tituloOriginal : tituloOriginal;
  }

  function mostrarAviso(consulta) {
    if (!zonaAvisos) return;
    var aviso = document.createElement('div');
    aviso.className = 'pointer-events-auto flex items-start gap-3 rounded-lg border border-rivera-rojo/30 bg-white p-4 text-sm text-rivera-negro shadow-2xl';
    aviso.setAttribute('role', 'alert');
    var texto = document.createElement('div');
    texto.className = 'min-w-0 flex-1';
    var titulo = document.createElement('p');
    titulo.className = 'font-semibold';
    titulo.textContent = '🔔 Nueva consulta';
    var detalle = document.createElement('p');
    detalle.className = 'mt-0.5 truncate text-neutral-700';
    detalle.textContent = consulta.nombre + ' · ' + consulta.telefono;
    var enlace = document.createElement('a');
    enlace.className = 'mt-2 inline-block font-semibold text-rivera-rojo hover:underline';
    enlace.href = consulta.url;
    enlace.textContent = 'Ver consulta →';
    texto.append(titulo, detalle, enlace);
    var cerrar = document.createElement('button');
    cerrar.type = 'button';
    cerrar.className = 'rounded p-1 text-neutral-500 hover:text-rivera-negro';
    cerrar.setAttribute('aria-label', 'Cerrar aviso');
    cerrar.textContent = '✕';
    cerrar.addEventListener('click', function () { aviso.remove(); });
    aviso.append(texto, cerrar);
    zonaAvisos.append(aviso);
  }

  function notificarSistema(consulta) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    var n = new Notification('Nueva consulta en la página', {
      body: consulta.nombre + ' · ' + consulta.telefono,
      tag: 'consulta-' + consulta.id,
      icon: document.querySelector('link[rel=icon]') ? document.querySelector('link[rel=icon]').href : undefined,
    });
    n.onclick = function () { window.focus(); location.href = consulta.url; };
  }

  function revisarNovedades() {
    fetch(urlNovedades, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) {
        if (r.status === 401) { clearInterval(intervalo); return null; } // la sesión se cerró
        return r.ok ? r.json() : null;
      })
      .then(function (datos) {
        if (!datos) return;
        actualizarContadores(datos.nuevas);
        if (!datos.ultima) return;
        var vista = leerVista();
        // La primera vez solo se toma nota de la última (no se avisa de consultas viejas)
        if (isNaN(vista)) { guardarVista(datos.ultima.id); return; }
        if (datos.ultima.id > vista) {
          guardarVista(datos.ultima.id);
          mostrarAviso(datos.ultima);
          sonar();
          notificarSistema(datos.ultima);
        }
      })
      .catch(function () { /* sin conexión: se intenta en la próxima vuelta */ });
  }

  var intervalo = null;
  if (urlNovedades && window.fetch) {
    revisarNovedades();
    intervalo = setInterval(revisarNovedades, 30000);
  }

  // Botón "Activar avisos en este dispositivo" (notificaciones del sistema)
  document.querySelectorAll('[data-activar-notificaciones]').forEach(function (boton) {
    var estado = document.querySelector('[data-estado-notificaciones]');
    var mostrar = function () {
      if (!('Notification' in window)) {
        boton.classList.add('hidden');
        if (estado) estado.textContent = 'Este navegador no permite notificaciones; igual verás el aviso y el sonido con el panel abierto.';
        return;
      }
      var activas = Notification.permission === 'granted';
      boton.classList.toggle('hidden', activas || Notification.permission === 'denied');
      if (estado) {
        estado.textContent = activas ? 'Notificaciones activas en este dispositivo.'
          : Notification.permission === 'denied' ? 'Las notificaciones están bloqueadas: actívalas en la configuración del navegador (ícono del candado junto a la dirección).'
          : 'Actívalas para recibir un aviso aunque estés en otra pestaña.';
      }
    };
    mostrar();
    boton.addEventListener('click', function () {
      Notification.requestPermission().then(mostrar);
    });
  });
})();
