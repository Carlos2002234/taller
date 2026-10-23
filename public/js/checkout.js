// Checkout: resumen del pedido, campos de envío y envío del carrito al servidor.
// El total que se muestra es orientativo; el servidor lo recalcula con precios reales.

(function () {
  'use strict';

  var Carrito = window.Carrito;
  var raiz = document.querySelector('[data-checkout]');
  if (!raiz) return;

  var formulario = raiz.querySelector('[data-checkout-formulario]');
  var lista = raiz.querySelector('[data-checkout-lista]');
  var zona = raiz.querySelector('[data-zona]');
  var camposEnvio = raiz.querySelector('[data-campos-envio]');
  var boton = raiz.querySelector('[data-checkout-enviar]');
  var subtotal = 0;

  function tipoEntrega() {
    var marcado = raiz.querySelector('[data-entrega]:checked');
    return marcado ? marcado.value : 'retiro';
  }

  function actualizarTotales() {
    var envio = tipoEntrega() === 'envio' ? parseInt(zona.selectedOptions[0].getAttribute('data-tarifa'), 10) || 0 : 0;
    raiz.querySelector('[data-checkout-subtotal]').textContent = Carrito.colones(subtotal);
    raiz.querySelector('[data-checkout-envio]').textContent =
      tipoEntrega() !== 'envio' ? 'Gratis' : (zona.value ? Carrito.colones(envio) : 'Elige tu zona');
    raiz.querySelector('[data-checkout-total]').textContent = Carrito.colones(subtotal + envio);
  }

  // Mostrar u ocultar zona y dirección según el tipo de entrega
  raiz.querySelectorAll('[data-entrega]').forEach(function (opcion) {
    opcion.addEventListener('change', function () {
      camposEnvio.classList.toggle('hidden', tipoEntrega() !== 'envio');
      actualizarTotales();
    });
  });
  zona.addEventListener('change', actualizarTotales);

  // Resumen con precios reales
  var carrito = Carrito.leer();
  if (!carrito.length) {
    formulario.classList.add('hidden');
    raiz.querySelector('[data-checkout-vacio]').classList.remove('hidden');
    return;
  }

  var ids = carrito.map(function (i) { return i.id; }).join(',');
  fetch(raiz.getAttribute('data-api') + '?ids=' + encodeURIComponent(ids), { headers: { Accept: 'application/json' } })
    .then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(function (datos) {
      var productos = {};
      datos.productos.forEach(function (p) { productos[p.id] = p; });

      // Mismas reglas que el carrito: quitar agotados y ajustar al stock
      var valido = carrito.filter(function (item) {
        var p = productos[item.id];
        if (!p || p.stock <= 0) return false;
        item.cantidad = Math.min(item.cantidad, p.stock);
        return true;
      });
      Carrito.guardar(valido);
      carrito = valido;

      if (!carrito.length) {
        formulario.classList.add('hidden');
        raiz.querySelector('[data-checkout-vacio]').classList.remove('hidden');
        return;
      }

      raiz.querySelector('[data-checkout-cargando]').remove();
      subtotal = 0;
      carrito.forEach(function (item) {
        var p = productos[item.id];
        var fila = document.createElement('li');
        fila.className = 'flex justify-between gap-3';
        var nombre = document.createElement('span');
        nombre.textContent = p.nombre + ' × ' + item.cantidad;
        var monto = document.createElement('span');
        monto.className = 'shrink-0 font-semibold';
        monto.textContent = Carrito.colones(p.precio * item.cantidad);
        fila.append(nombre, monto);
        lista.appendChild(fila);
        subtotal += p.precio * item.cantidad;
      });

      actualizarTotales();
      boton.disabled = false;
    })
    .catch(function () {
      raiz.querySelector('[data-checkout-cargando]').textContent = 'No pudimos cargar tu carrito. Recarga la página.';
    });

  // UTM guardados (máximo 30 días)
  try {
    var utm = JSON.parse(localStorage.getItem('utm') || 'null');
    if (utm && Date.now() - utm.guardado < 30 * 24 * 3600 * 1000) {
      raiz.querySelectorAll('[data-utm]').forEach(function (campo) {
        campo.value = utm[campo.getAttribute('data-utm')] || '';
      });
    }
  } catch (e) { /* sin almacenamiento disponible */ }

  // Al enviar: adjuntar el carrito (solo IDs y cantidades) y evitar doble envío
  formulario.addEventListener('submit', function (evento) {
    raiz.querySelector('[data-checkout-carrito]').value = JSON.stringify(Carrito.leer());
    if (boton.disabled) {
      evento.preventDefault();
      return;
    }
    boton.disabled = true;
    boton.textContent = 'Enviando pedido…';
  });
})();
