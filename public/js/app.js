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
