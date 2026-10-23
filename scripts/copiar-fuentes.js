// Copia las fuentes (woff2, solo caracteres latinos) desde node_modules a public/fonts.
// Uso: npm run fuentes
const fs = require('fs');
const path = require('path');

const destino = path.join(__dirname, '..', 'public', 'fonts');
const fuentes = [
  'montserrat/files/montserrat-latin-700-normal.woff2',
  'montserrat/files/montserrat-latin-800-normal.woff2',
  'montserrat/files/montserrat-latin-800-italic.woff2',
  'inter/files/inter-latin-400-normal.woff2',
  'inter/files/inter-latin-500-normal.woff2',
  'inter/files/inter-latin-600-normal.woff2',
];

fs.mkdirSync(destino, { recursive: true });
for (const f of fuentes) {
  const origen = path.join(__dirname, '..', 'node_modules', '@fontsource', f);
  fs.copyFileSync(origen, path.join(destino, path.basename(f)));
  console.log('✔', path.basename(f));
}
