/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './partials/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          primary: '#0C9DD5',
          dark:    '#7B3046',
          accent:  '#C3456B',
          bg:      '#F8FAFC',
          surface: '#FFFFFF',
        },
      },
      fontFamily: {
        serif: ['"Fraunces"', 'ui-serif', 'Georgia', 'serif'],
        sans:  ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        floaty: '0 30px 60px -25px rgba(123,48,70,0.35)',
      },
    },
  },
  plugins: [],
};
