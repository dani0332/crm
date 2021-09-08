const mix = require('laravel-mix');

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

mix.js('resources/js/app.js', 'public/js')
    .react()
    .postCss("resources/css/app.css", "build/css", [
        require("tailwindcss"),
    ]);

// mix.js('resources/js/app.js', 'public/js')
//     .react()
//     .less('resources/less/app.less', 'public/build', {
//         lessOptions: {
//             strictMath: true
//         }
//     }
//     .postCss("resources/css/app.css", "public/build", [
//         require("tailwindcss"),
//        ])
//     );



// mix.js("resources/js/app.js", "public/js")
// .react()
// .postCss("resources/css/app.css", "public/css", [
//     require("tailwindcss"),
// ])
// .less('resources/less/app.less', 'public/build', {
//         lessOptions: {
//             strictMath: true
//         }
// });
