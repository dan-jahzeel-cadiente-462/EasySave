/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.html.twig",
    "./assets/**/*.js",
  ],
  safelist: [
    {
      pattern: /from-(green|teal|emerald|cyan)-500/,
    },
    {
      pattern: /to-(green|teal|emerald|cyan)-600/,
    },
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
