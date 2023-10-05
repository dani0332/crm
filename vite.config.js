import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers';
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'

// const Components = require('unplugin-vue-components/webpack');
// const AutoImport = require('unplugin-auto-import/webpack');
// import react from '@vitejs/plugin-react';
// import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel([
            'public/css/app.css',
            'resources/js/inertia/inertia.js',
            'public/css'
        ]),
        AutoImport({
            imports: [
                'vue',
                '@vueuse/core',
                {
                    '@inertiajs/vue3': ['router', 'usePage', 'useForm'],
                    '@indielayer/ui': ['useNotifications'],
                    axios: [['default', 'axios']],
                },
            ],
            dirs: ['resources/js/inertia/Composables'],
        }),
        Components({
            dirs: [
                'resources/js/inertia/Components',
                'resources/js/inertia/Layouts',
            ],
            extensions: ['vue'],
            resolvers: [
                HeadlessUiResolver(),
                name =>
                {
                    if (name === 'Head')
                    {
                        return {
                            importName: 'Head',
                            path: '@inertiajs/vue3',
                        };
                    }

                    if (name === 'Link')
                    {
                        return {
                            importName: 'Link',
                            path: '@inertiajs/vue3',
                        };
                    }
                },
            ],
            directoryAsNamespace: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve('./resources/js'),
            ziggy: path.resolve('./vendor/tightenco/ziggy/dist/vue.es.js'),
        },
        extensions: ['.js', '.vue', '.json'],
    },
});