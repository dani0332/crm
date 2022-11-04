module.exports = {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php',
    // './vendor/laravel/jetstream/**/*.blade.php',
    // './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {},
  },

  plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
