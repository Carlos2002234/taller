// JavaScript del panel: confirmar acciones peligrosas y revisar fotos antes de subirlas.

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
})();
