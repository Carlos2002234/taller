/** Colores y fuentes de Rivera Grupo Automotriz (paleta extraída del logo). */
module.exports = {
  content: ['./app/**/*.php', './public/**/*.php', './public/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        rivera: {
          rojo: '#D11A21',
          'rojo-oscuro': '#A8141A',
          negro: '#0B0B0B',
          carbon: '#1A1A1A',
          borde: '#2A2A2A',
          gris: '#F2F2F2',
        },
      },
      fontFamily: {
        display: ['Montserrat', 'system-ui', 'sans-serif'],
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        sitio: '80rem',
      },
    },
  },
  plugins: [],
};
