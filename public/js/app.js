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

  // Selects que envían su formulario al cambiar (ej. "Ordenar por" en la tienda)
  document.querySelectorAll('[data-auto-enviar]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // Origen de la visita (anuncios): guarda los parámetros UTM por 30 días para asociarlos al pedido
  try {
    var parametros = new URLSearchParams(location.search);
    if (parametros.get('utm_source')) {
      localStorage.setItem('utm', JSON.stringify({
        utm_source: parametros.get('utm_source'),
        utm_medium: parametros.get('utm_medium') || '',
        utm_campaign: parametros.get('utm_campaign') || '',
        guardado: Date.now(),
      }));
    }
  } catch (e) { /* sin almacenamiento disponible */ }

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
