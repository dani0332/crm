const mix = require('laravel-mix');
const path = require('path');

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
  .js('resources/js/inertia/inertia.js', 'public/js')
  .vue({ runtimeOnly: (process.env.NODE_ENV || 'production') === 'production' })
  .webpackConfig({
    resolve: {
      alias: {
        '@': path.resolve('./resources/js'),
      },
      extensions: ['.js', '.vue', '.json'],
    },
    output: {
      chunkFilename: 'js/[name].js?id=[chunkhash]',
    },
  })
  .postCss('resources/css/inertia.css', 'public/css', [require('tailwindcss')])
  .extract()
  .version();

mix.css('resources/css/livewire.css', 'public/css');

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
