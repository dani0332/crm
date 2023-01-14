const colors = require('tailwindcss/colors');
const indielayer = require('@indielayer/ui/tailwind.preset');

module.exports = {
  presets: [indielayer()],
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    'node_modules/@indielayer/ui/**/*',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          50: 'rgb(242,245,249)',
          100: 'rgb(228,235,243)',
          200: 'rgb(198,214,231)',
          300: 'rgb(163,191,217)',
          400: 'rgb(85,148,196)',
          500: '#1d83bc',
          600: 'rgb(28,124,178)',
          700: 'rgb(25,113,163)',
          800: 'rgb(19,85,122)',
          900: 'rgb(16,72,103)',
        },
        secondary: {
          50: 'rgb(255,244,242)',
          100: 'rgb(255,232,228)',
          200: 'rgb(255,206,198)',
          300: 'rgb(255,177,161)',
          400: 'rgb(255,120,81)',
          500: '#ff5e00',
          600: 'rgb(242,89,0)',
          700: 'rgb(221,81,0)',
          800: 'rgb(165,61,0)',
          900: 'rgb(140,51,0)',
        },
        success: colors.green,
        warning: colors.yellow,
        error: colors.red,
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
