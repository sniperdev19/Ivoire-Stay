/** @type {import('tailwindcss').Config} */
module.exports = {
  // Fichiers scannés pour détecter les classes utilisées (JIT).
  // IMPORTANT : inclure aussi le JS, car des classes Tailwind apparaissent
  // dans les :class Alpine et certains composants de public/assets/js.
  content: [
    './src/templates/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
