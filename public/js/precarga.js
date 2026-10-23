// Se carga ANTES de dibujar la página (sin "defer"): marca que el JavaScript está activo,
// para que las animaciones al bajar solo oculten contenido cuando de verdad se puedan mostrar.
document.documentElement.classList.add('js');
