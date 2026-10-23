// Genera la versión web del video del taller (sin audio, liviana) y su portada.
// Uso: npm run video
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const ffmpeg = require('ffmpeg-static');
const sharp = require('sharp');

const raiz = path.join(__dirname, '..');
const original = path.join(raiz, 'assets', 'video', 'taller-original.mp4');
const salida = path.join(raiz, 'public', 'video');
fs.mkdirSync(salida, { recursive: true });

// Segundo del video que se usa como portada (la recepción con el logo)
const SEGUNDO_PORTADA = '23.4';

async function main() {
  // MP4 H.264 sin audio, 540x960, "faststart" para que empiece a reproducirse antes de bajar completo
  execFileSync(ffmpeg, [
    '-y', '-loglevel', 'error', '-i', original,
    '-an', '-vf', 'scale=540:-2', '-c:v', 'libx264', '-preset', 'slow', '-crf', '31',
    '-profile:v', 'high', '-pix_fmt', 'yuv420p', '-movflags', '+faststart',
    path.join(salida, 'taller.mp4'),
  ]);
  console.log('✔ video', Math.round(fs.statSync(path.join(salida, 'taller.mp4')).size / 1024), 'KB');

  // Portada: se muestra mientras el video no ha cargado (o si no se reproduce)
  const cuadro = execFileSync(ffmpeg, [
    '-loglevel', 'error', '-ss', SEGUNDO_PORTADA, '-i', original,
    '-frames:v', '1', '-vf', 'scale=540:-2', '-f', 'image2pipe', '-c:v', 'png', '-',
  ]);
  await sharp(cuadro).webp({ quality: 72 }).toFile(path.join(salida, 'taller-portada.webp'));
  console.log('✔ portada', Math.round(fs.statSync(path.join(salida, 'taller-portada.webp')).size / 1024), 'KB');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
