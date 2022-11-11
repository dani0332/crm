const mix = require('laravel-mix');
mix.options({
  terser: {
    extractComments: false,
  },
});
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
  .js('resources/js/alpine/alpine.js', 'public/js')
  .postCss('resources/css/livewire.css', 'public/css', []);

// this should be removed, react's resources are not used
mix
  .js('resources/js/app.js', 'public/js')
  .react()
  .version()
  .postCss('resources/css/app.css', 'build/css', [require('tailwindcss')]);

mix
  .scripts(
    [
      'resources/js/includes/customjs.js',
      'resources/js/includes/tm_js.js',
      'public/build/js/customer_additional_contact.js',
    ],
    'public/build/js/customjs.min.js',
  )
  .styles(
    ['resources/css/custom.css', 'resources/css/style.css'],
    'public/build/css/style.min.css',
  )
  .version();
