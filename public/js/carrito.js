// Carrito de compras.
// En el navegador solo se guardan IDs y cantidades: [{ id, cantidad }].
// Precios y stock SIEMPRE vienen del servidor (/api/carrito) y se validan otra vez al crear el pedido.

(function () {
  'use strict';

  var CLAVE = 'carrito';

  // --- Almacenamiento -------------------------------------------------------
  function leer() {
    try {
      var carrito = JSON.parse(localStorage.getItem(CLAVE) || '[]');
      if (!Array.isArray(carrito)) return [];
      return carrito
        .filter(function (item) {
          return item && Number.isInteger(item.id) && item.id > 0 && Number.isInteger(item.cantidad) && item.cantidad > 0;
        })
        .map(function (item) {
          return { id: item.id, cantidad: item.cantidad }; // se descarta cualquier otro dato
        });
    } catch (e) {
      return [];
    }
  }

  function guardar(carrito) {
    try {
      localStorage.setItem(CLAVE, JSON.stringify(carrito));
    } catch (e) { /* modo privado o almacenamiento lleno: se ignora */ }
    if (window.actualizarContadorCarrito) window.actualizarContadorCarrito();
  }

  function colones(monto) {
    return '₡' + String(Math.round(monto)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // Funciones compartidas con checkout.js
  window.Carrito = { leer: leer, guardar: guardar, colones: colones };

  // Pedido confirmado: el carrito ya se convirtió en pedido
  if (document.querySelector('[data-vaciar-carrito]')) guardar([]);

  // --- Aviso flotante ---------------------------------------------------------
  var temporizador;
  function mostrarAviso(mensaje, conEnlace) {
    var aviso = document.querySelector('[data-aviso-flotante]');
    if (!aviso) {
      aviso = document.createElement('div');
      aviso.setAttribute('data-aviso-flotante', '');
      aviso.setAttribute('role', 'status');
      aviso.setAttribute('aria-live', 'polite');
      aviso.className = 'fixed bottom-20 left-1/2 z-50 flex w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 items-center justify-between gap-4 rounded-lg bg-white px-4 py-3 text-sm font-medium text-rivera-negro shadow-xl shadow-black/30 transition-opacity duration-300 lg:bottom-6';
      document.body.appendChild(aviso);
    }
    aviso.textContent = mensaje;
    if (conEnlace) {
      var enlace = document.createElement('a');
      enlace.href = '/carrito';
      enlace.className = 'shrink-0 font-semibold text-rivera-rojo hover:underline';
      enlace.textContent = 'Ver carrito';
      aviso.appendChild(enlace);
    }
    aviso.classList.remove('opacity-0', 'pointer-events-none');
    clearTimeout(temporizador);
    temporizador = setTimeout(function () {
      aviso.classList.add('opacity-0', 'pointer-events-none');
    }, 3500);
  }

  // --- Agregar al carrito -----------------------------------------------------
  function agregar(id, cantidad, stock) {
    var carrito = leer();
    var item = carrito.find(function (i) { return i.id === id; });
    var actual = item ? item.cantidad : 0;
    var nueva = Math.min(actual + cantidad, stock);

    if (nueva <= actual) {
      mostrarAviso('Ya tienes en el carrito todas las unidades disponibles.', true);
      return;
    }
    if (item) item.cantidad = nueva;
    else carrito.push({ id: id, cantidad: nueva });
    guardar(carrito);
    mostrarAviso(nueva < actual + cantidad ? 'Agregamos las unidades disponibles.' : 'Producto agregado al carrito', true);
  }

  document.addEventListener('click', function (evento) {
    var boton = evento.target.closest('[data-agregar-carrito]');
    if (!boton || boton.disabled) return;

    var id = parseInt(boton.getAttribute('data-agregar-carrito'), 10);
    var stock = parseInt(boton.getAttribute('data-stock'), 10) || 0;
    var campo = boton.closest('[data-producto]') && boton.closest('[data-producto]').querySelector('[data-cantidad-campo]');
    var cantidad = campo ? Math.max(1, parseInt(campo.value, 10) || 1) : 1;
    if (id > 0 && stock > 0) agregar(id, cantidad, stock);
  });

  // --- Selector de cantidad (− / +) ------------------------------------------
  function limitar(campo) {
    var max = parseInt(campo.max, 10) || 1;
    var valor = parseInt(campo.value, 10) || 1;
    campo.value = String(Math.min(Math.max(valor, 1), max));
  }

  document.addEventListener('click', function (evento) {
    var boton = evento.target.closest('[data-cantidad-menos], [data-cantidad-mas]');
    if (!boton) return;
    var campo = boton.closest('[data-cantidad]').querySelector('[data-cantidad-campo]');
    var paso = boton.hasAttribute('data-cantidad-mas') ? 1 : -1;
    campo.value = String((parseInt(campo.value, 10) || 1) + paso);
    limitar(campo);
    campo.dispatchEvent(new Event('change', { bubbles: true }));
  });

  document.addEventListener('change', function (evento) {
    if (evento.target.matches('[data-cantidad-campo]')) limitar(evento.target);
  });

  // --- Galería de producto ----------------------------------------------------
  document.addEventListener('click', function (evento) {
    var miniatura = evento.target.closest('[data-galeria-ir]');
    if (!miniatura) return;
    var galeria = miniatura.closest('[data-galeria]');
    var pista = galeria.querySelector('[data-galeria-pista]');
    var indice = parseInt(miniatura.getAttribute('data-galeria-ir'), 10);
    pista.scrollTo({ left: indice * pista.clientWidth, behavior: 'smooth' });
  });

  document.querySelectorAll('[data-galeria-pista]').forEach(function (pista) {
    var miniaturas = pista.closest('[data-galeria]').querySelectorAll('[data-galeria-ir]');
    pista.addEventListener('scroll', function () {
      var activa = Math.round(pista.scrollLeft / pista.clientWidth);
      miniaturas.forEach(function (m, i) {
        m.classList.toggle('border-rivera-rojo', i === activa);
        m.classList.toggle('border-transparent', i !== activa);
      });
    }, { passive: true });
  });

  // --- Página del carrito -----------------------------------------------------
  var pagina = document.querySelector('[data-carrito-pagina]');
  if (!pagina) return;

  var elementos = {
    cargando: pagina.querySelector('[data-carrito-cargando]'),
    avisos: pagina.querySelector('[data-carrito-avisos]'),
    vacio: pagina.querySelector('[data-carrito-vacio]'),
    error: pagina.querySelector('[data-carrito-error]'),
    contenido: pagina.querySelector('[data-carrito-contenido]'),
    lista: pagina.querySelector('[data-carrito-lista]'),
    subtotal: pagina.querySelector('[data-carrito-subtotal]'),
    plantilla: pagina.querySelector('[data-carrito-plantilla]'),
  };
  var productos = {}; // datos reales del servidor, por ID

  function mostrar(estado) {
    elementos.cargando.classList.add('hidden');
    elementos.vacio.classList.toggle('hidden', estado !== 'vacio');
    elementos.error.classList.toggle('hidden', estado !== 'error');
    elementos.contenido.classList.toggle('hidden', estado !== 'contenido');
  }

  function avisar(mensajes) {
    elementos.avisos.textContent = '';
    mensajes.forEach(function (mensaje) {
      var aviso = document.createElement('div');
      aviso.className = 'aviso-info';
      aviso.setAttribute('role', 'status');
      aviso.textContent = mensaje;
      elementos.avisos.appendChild(aviso);
    });
  }

  function dibujar() {
    var carrito = leer();
    if (!carrito.length) {
      mostrar('vacio');
      return;
    }

    elementos.lista.textContent = '';
    var subtotal = 0;

    carrito.forEach(function (item) {
      var p = productos[item.id];
      var fila = elementos.plantilla.content.firstElementChild.cloneNode(true);
      var campo = function (nombre) { return fila.querySelector('[data-campo="' + nombre + '"]'); };

      fila.setAttribute('data-id', String(item.id));
      campo('enlace').href = p.url;
      campo('nombre').href = p.url;
      campo('nombre').textContent = p.nombre;
      campo('marca').textContent = p.marca || '';
      campo('precio').textContent = colones(p.precio) + ' c/u';
      campo('total').textContent = colones(p.precio * item.cantidad);
      if (p.imagen) {
        campo('imagen').src = p.imagen;
        campo('imagen').classList.remove('hidden');
        campo('sin-imagen').remove();
      }
      var cantidad = fila.querySelector('[data-cantidad-campo]');
      cantidad.value = String(item.cantidad);
      cantidad.max = String(p.stock);

      subtotal += p.precio * item.cantidad;
      elementos.lista.appendChild(fila);
    });

    elementos.subtotal.textContent = colones(subtotal);
    mostrar('contenido');
  }

  function cargar() {
    var carrito = leer();
    if (!carrito.length) {
      mostrar('vacio');
      return;
    }

    var ids = carrito.map(function (i) { return i.id; }).join(',');
    fetch(pagina.getAttribute('data-api') + '?ids=' + encodeURIComponent(ids), { headers: { Accept: 'application/json' } })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (datos) {
        productos = {};
        datos.productos.forEach(function (p) { productos[p.id] = p; });

        // Quitar lo que ya no existe o se agotó, y ajustar cantidades al stock real
        var mensajes = [];
        var valido = [];
        carrito.forEach(function (item) {
          var p = productos[item.id];
          if (!p || p.stock <= 0) {
            mensajes.push((p ? p.nombre : 'Un producto') + ' ya no está disponible y lo quitamos de tu carrito.');
            return;
          }
          if (item.cantidad > p.stock) {
            item.cantidad = p.stock;
            mensajes.push('Ajustamos la cantidad de ' + p.nombre + ' a ' + p.stock + ' (unidades disponibles).');
          }
          valido.push(item);
        });

        guardar(valido);
        avisar(mensajes);
        dibujar();
      })
      .catch(function () {
        mostrar('error');
      });
  }

  // Cambiar cantidad
  elementos.lista.addEventListener('change', function (evento) {
    if (!evento.target.matches('[data-cantidad-campo]')) return;
    var id = parseInt(evento.target.closest('[data-carrito-item]').getAttribute('data-id'), 10);
    var carrito = leer();
    var item = carrito.find(function (i) { return i.id === id; });
    if (!item) return;
    item.cantidad = Math.min(Math.max(parseInt(evento.target.value, 10) || 1, 1), productos[id].stock);
    guardar(carrito);
    dibujar();
  });

  // Quitar producto
  elementos.lista.addEventListener('click', function (evento) {
    if (!evento.target.closest('[data-carrito-quitar]')) return;
    var id = parseInt(evento.target.closest('[data-carrito-item]').getAttribute('data-id'), 10);
    guardar(leer().filter(function (i) { return i.id !== id; }));
    avisar([]);
    dibujar();
  });

  cargar();
})();
