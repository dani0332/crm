import defaultTheme from 'tailwindcss/defaultTheme';
const colors = require('tailwindcss/colors');
const indielayer = require('@indielayer/ui/tailwind.preset');

export default {
  presets: [indielayer()],
  content: [
    './resources/**/*.blade.php',
    './resources/js/inertia/**/*.{vue,js}',
    'node_modules/@indielayer/ui/{lib,src}/**/*',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#1d83bc',
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
        secondary: colors.slate,
        success: colors.emerald,
        warning: colors.yellow,
        error: colors.red,
      },
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
      },
    },
  },
};
