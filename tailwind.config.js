/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          green: '#3bad59',
          'green-dark': '#2e8b46',
        }
      }
    },
  },
  plugins: [],
}

