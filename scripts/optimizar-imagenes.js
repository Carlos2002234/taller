// Genera versiones livianas (WebP) del logo y las fotos de /assets en public/img.
// Uso: npm run imagenes
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const raiz = path.join(__dirname, '..');
const salida = path.join(raiz, 'public', 'img');
const fotos = path.join(salida, 'fotos');
fs.mkdirSync(fotos, { recursive: true });

// Fotos del taller: [archivo en assets/fotos, anchos a generar]
const listaFotos = [
  ['rivera-07.jpg', [960, 1920]], // imagen principal (hero)
  ['rivera-01.jpg', [800, 1600]], // rótulo
  ['rivera-14.jpg', [800, 1600]], // fachada
  ['rivera-18.jpg', [800, 1600]], // fachada con banners
  ['rivera-23.jpg', [800, 1600]], // recepción
  ['rivera-08.jpg', [800, 1600]], // zona de lavado
  ['rivera-25.jpg', [800, 1600]], // aceites
  ['rivera-06.jpg', [800, 1600]], // mural Chevron
];

async function main() {
  // Logo: se recorta el fondo negro sobrante para usarlo en el encabezado
  const logo = sharp(path.join(raiz, 'assets', 'logo.jpg')).trim({ threshold: 20 });
  const simbolo = await logo.toBuffer();
  await sharp(simbolo).resize(160).webp({ quality: 90 }).toFile(path.join(salida, 'logo.webp'));
  await sharp(simbolo).resize(160).png().toFile(path.join(salida, 'logo.png'));
  await sharp(simbolo).resize(32).png().toFile(path.join(salida, 'favicon-32.png'));
  await sharp(simbolo).resize(180).flatten({ background: '#0B0B0B' }).png().toFile(path.join(salida, 'apple-touch-icon.png'));
  console.log('✔ logo y favicons');

  for (const [archivo, anchos] of listaFotos) {
    const base = path.parse(archivo).name;
    for (const ancho of anchos) {
      await sharp(path.join(raiz, 'assets', 'fotos', archivo))
        .rotate()
        .resize({ width: ancho, withoutEnlargement: true })
        .webp({ quality: 74 })
        .toFile(path.join(fotos, `${base}-${ancho}.webp`));
    }
    console.log('✔', base);
  }

  // Imagen para compartir en redes (Open Graph, 1200x630)
  await sharp(path.join(raiz, 'assets', 'fotos', 'rivera-14.jpg'))
    .rotate()
    .resize(1200, 630, { fit: 'cover' })
    .jpeg({ quality: 80 })
    .toFile(path.join(salida, 'og-imagen.jpg'));
  console.log('✔ og-imagen');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
