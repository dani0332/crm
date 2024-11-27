import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { HeadlessUiResolver } from 'unplugin-vue-components/resolvers';

export default defineConfig({
  plugins: [
    laravel(['resources/js/inertia/inertia.js']),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
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
      dirs: ['resources/js/inertia/Components', 'resources/js/inertia/Layouts'],
      extensions: ['vue'],
      resolvers: [
        HeadlessUiResolver(),
        name => {
          if (name === 'Head') {
            return {
              importName: 'Head',
              path: '@inertiajs/vue3',
            };
          }

          if (name === 'Link') {
            return {
              importName: 'Link',
              path: '@inertiajs/vue3',
            };
          }
        },
      ],
      directoryAsNamespace: true,
    }),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
    },
    extensions: ['.js', '.vue', '.json'],
  },
  define: {
    'process.env': process.env, // Make Vite environment variables available
  },
  optimizeDeps: {
    include: ['@vueuse/core', 'md-editor-v3', '@headlessui/vue'],
  },
});
