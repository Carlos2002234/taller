// Íconos de la app (manifest, favicon.ico para Google y navegadores) y logo del encabezado en tamaño liviano.
// Uso: npm run iconos   (no toca las fotos ni los favicons que ya genera "npm run imagenes")
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const raiz = path.join(__dirname, '..');
const img = path.join(raiz, 'public', 'img');

// Un .ico que contiene PNG (válido en todos los navegadores actuales): encabezado + una entrada por tamaño
function crearIco(pngs) {
  const encabezado = Buffer.alloc(6);
  encabezado.writeUInt16LE(0, 0);
  encabezado.writeUInt16LE(1, 2);
  encabezado.writeUInt16LE(pngs.length, 4);
  const entradas = [];
  let desplazamiento = 6 + 16 * pngs.length;
  for (const { tamano, datos } of pngs) {
    const entrada = Buffer.alloc(16);
    entrada.writeUInt8(tamano >= 256 ? 0 : tamano, 0);
    entrada.writeUInt8(tamano >= 256 ? 0 : tamano, 1);
    entrada.writeUInt16LE(1, 4); // planos
    entrada.writeUInt16LE(32, 6); // bits por pixel
    entrada.writeUInt32LE(datos.length, 8);
    entrada.writeUInt32LE(desplazamiento, 12);
    desplazamiento += datos.length;
    entradas.push(entrada);
  }
  return Buffer.concat([encabezado, ...entradas, ...pngs.map((p) => p.datos)]);
}

async function main() {
  // Símbolo del logo sin el fondo negro sobrante (igual que en optimizar-imagenes.js)
  const simbolo = await sharp(path.join(raiz, 'assets', 'logo.jpg')).trim({ threshold: 20 }).toBuffer();

  // Íconos del manifest: fondo negro de marca y margen para que Android pueda recortarlos en círculo
  for (const tamano of [192, 512]) {
    const interior = Math.round(tamano * 0.72);
    const centro = await sharp(simbolo).resize(interior, interior, { fit: 'contain', background: '#0B0B0B' }).toBuffer();
    await sharp({ create: { width: tamano, height: tamano, channels: 3, background: '#0B0B0B' } })
      .composite([{ input: centro, gravity: 'center' }])
      .png()
      .toFile(path.join(img, `icono-${tamano}.png`));
  }
  console.log('✔ icono-192.png e icono-512.png');

  // Favicon de 48 px (Google pide un múltiplo de 48) y favicon.ico en la raíz pública
  const favicons = [];
  for (const tamano of [16, 32, 48]) {
    const datos = await sharp(simbolo).resize(tamano, tamano, { fit: 'contain', background: '#0B0B0B' }).flatten({ background: '#0B0B0B' }).png().toBuffer();
    favicons.push({ tamano, datos });
  }
  fs.writeFileSync(path.join(img, 'favicon-48.png'), favicons[2].datos);
  fs.writeFileSync(path.join(raiz, 'public', 'favicon.ico'), crearIco(favicons));
  console.log('✔ favicon-48.png y favicon.ico');

  // Logo completo del encabezado/pie a 400 px de ancho (se ve a ~95–176 px; el de 816 px queda para pantallas grandes)
  await sharp(path.join(img, 'logo-completo.webp'))
    .resize({ width: 400 })
    .webp({ quality: 90, alphaQuality: 100 })
    .toFile(path.join(img, 'logo-completo-400.webp'));
  console.log('✔ logo-completo-400.webp');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
