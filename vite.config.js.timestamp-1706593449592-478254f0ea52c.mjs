// vite.config.js
import { defineConfig } from "file:///home/muhammad/blanka/node_modules/vite/dist/node/index.js";
import laravel from "file:///home/muhammad/blanka/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///home/muhammad/blanka/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import AutoImport from "file:///home/muhammad/blanka/node_modules/unplugin-auto-import/dist/vite.js";
import Components from "file:///home/muhammad/blanka/node_modules/unplugin-vue-components/dist/vite.js";
import { HeadlessUiResolver } from "file:///home/muhammad/blanka/node_modules/unplugin-vue-components/dist/resolvers.js";
var vite_config_default = defineConfig({
  plugins: [
    laravel(["resources/js/inertia/inertia.js"]),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false
        }
      }
    }),
    AutoImport({
      imports: [
        "vue",
        "@vueuse/core",
        {
          "@inertiajs/vue3": ["router", "usePage", "useForm"],
          "@indielayer/ui": ["useNotifications"],
          axios: [["default", "axios"]]
        }
      ],
      dirs: ["resources/js/inertia/Composables"]
    }),
    Components({
      dirs: ["resources/js/inertia/Components", "resources/js/inertia/Layouts"],
      extensions: ["vue"],
      resolvers: [
        HeadlessUiResolver(),
        (name) => {
          if (name === "Head") {
            return {
              importName: "Head",
              path: "@inertiajs/vue3"
            };
          }
          if (name === "Link") {
            return {
              importName: "Link",
              path: "@inertiajs/vue3"
            };
          }
        }
      ],
      directoryAsNamespace: true
    })
  ],
  resolve: {
    alias: {
      "@": "/resources/js"
    },
    extensions: [".js", ".vue", ".json"]
  },
  build: {
    chunkSizeWarningLimit: 4200
  },
  optimizeDeps: {
    include: [
      "@vueuse/core",
      "@vuepic/vue-datepicker",
      "md-editor-v3",
      "@headlessui/vue"
    ]
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCIvaG9tZS9tdWhhbW1hZC9ibGFua2FcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIi9ob21lL211aGFtbWFkL2JsYW5rYS92aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vaG9tZS9tdWhhbW1hZC9ibGFua2Evdml0ZS5jb25maWcuanNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tICd2aXRlJztcclxuaW1wb3J0IGxhcmF2ZWwgZnJvbSAnbGFyYXZlbC12aXRlLXBsdWdpbic7XHJcbmltcG9ydCB2dWUgZnJvbSAnQHZpdGVqcy9wbHVnaW4tdnVlJztcclxuaW1wb3J0IEF1dG9JbXBvcnQgZnJvbSAndW5wbHVnaW4tYXV0by1pbXBvcnQvdml0ZSc7XHJcbmltcG9ydCBDb21wb25lbnRzIGZyb20gJ3VucGx1Z2luLXZ1ZS1jb21wb25lbnRzL3ZpdGUnO1xyXG5pbXBvcnQgeyBIZWFkbGVzc1VpUmVzb2x2ZXIgfSBmcm9tICd1bnBsdWdpbi12dWUtY29tcG9uZW50cy9yZXNvbHZlcnMnO1xyXG5cclxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcclxuICBwbHVnaW5zOiBbXHJcbiAgICBsYXJhdmVsKFsncmVzb3VyY2VzL2pzL2luZXJ0aWEvaW5lcnRpYS5qcyddKSxcclxuICAgIHZ1ZSh7XHJcbiAgICAgIHRlbXBsYXRlOiB7XHJcbiAgICAgICAgdHJhbnNmb3JtQXNzZXRVcmxzOiB7XHJcbiAgICAgICAgICBiYXNlOiBudWxsLFxyXG4gICAgICAgICAgaW5jbHVkZUFic29sdXRlOiBmYWxzZSxcclxuICAgICAgICB9LFxyXG4gICAgICB9LFxyXG4gICAgfSksXHJcbiAgICBBdXRvSW1wb3J0KHtcclxuICAgICAgaW1wb3J0czogW1xyXG4gICAgICAgICd2dWUnLFxyXG4gICAgICAgICdAdnVldXNlL2NvcmUnLFxyXG4gICAgICAgIHtcclxuICAgICAgICAgICdAaW5lcnRpYWpzL3Z1ZTMnOiBbJ3JvdXRlcicsICd1c2VQYWdlJywgJ3VzZUZvcm0nXSxcclxuICAgICAgICAgICdAaW5kaWVsYXllci91aSc6IFsndXNlTm90aWZpY2F0aW9ucyddLFxyXG4gICAgICAgICAgYXhpb3M6IFtbJ2RlZmF1bHQnLCAnYXhpb3MnXV0sXHJcbiAgICAgICAgfSxcclxuICAgICAgXSxcclxuICAgICAgZGlyczogWydyZXNvdXJjZXMvanMvaW5lcnRpYS9Db21wb3NhYmxlcyddLFxyXG4gICAgfSksXHJcbiAgICBDb21wb25lbnRzKHtcclxuICAgICAgZGlyczogWydyZXNvdXJjZXMvanMvaW5lcnRpYS9Db21wb25lbnRzJywgJ3Jlc291cmNlcy9qcy9pbmVydGlhL0xheW91dHMnXSxcclxuICAgICAgZXh0ZW5zaW9uczogWyd2dWUnXSxcclxuICAgICAgcmVzb2x2ZXJzOiBbXHJcbiAgICAgICAgSGVhZGxlc3NVaVJlc29sdmVyKCksXHJcbiAgICAgICAgbmFtZSA9PiB7XHJcbiAgICAgICAgICBpZiAobmFtZSA9PT0gJ0hlYWQnKSB7XHJcbiAgICAgICAgICAgIHJldHVybiB7XHJcbiAgICAgICAgICAgICAgaW1wb3J0TmFtZTogJ0hlYWQnLFxyXG4gICAgICAgICAgICAgIHBhdGg6ICdAaW5lcnRpYWpzL3Z1ZTMnLFxyXG4gICAgICAgICAgICB9O1xyXG4gICAgICAgICAgfVxyXG5cclxuICAgICAgICAgIGlmIChuYW1lID09PSAnTGluaycpIHtcclxuICAgICAgICAgICAgcmV0dXJuIHtcclxuICAgICAgICAgICAgICBpbXBvcnROYW1lOiAnTGluaycsXHJcbiAgICAgICAgICAgICAgcGF0aDogJ0BpbmVydGlhanMvdnVlMycsXHJcbiAgICAgICAgICAgIH07XHJcbiAgICAgICAgICB9XHJcbiAgICAgICAgfSxcclxuICAgICAgXSxcclxuICAgICAgZGlyZWN0b3J5QXNOYW1lc3BhY2U6IHRydWUsXHJcbiAgICB9KSxcclxuICBdLFxyXG4gIHJlc29sdmU6IHtcclxuICAgIGFsaWFzOiB7XHJcbiAgICAgICdAJzogJy9yZXNvdXJjZXMvanMnLFxyXG4gICAgfSxcclxuICAgIGV4dGVuc2lvbnM6IFsnLmpzJywgJy52dWUnLCAnLmpzb24nXSxcclxuICB9LFxyXG4gIGJ1aWxkOiB7XHJcbiAgICBjaHVua1NpemVXYXJuaW5nTGltaXQ6IDQyMDAsXHJcbiAgfSxcclxuICBvcHRpbWl6ZURlcHM6IHtcclxuICAgIGluY2x1ZGU6IFtcclxuICAgICAgJ0B2dWV1c2UvY29yZScsXHJcbiAgICAgICdAdnVlcGljL3Z1ZS1kYXRlcGlja2VyJyxcclxuICAgICAgJ21kLWVkaXRvci12MycsXHJcbiAgICAgICdAaGVhZGxlc3N1aS92dWUnLFxyXG4gICAgXSxcclxuICB9LFxyXG59KTtcclxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUFpUCxTQUFTLG9CQUFvQjtBQUM5USxPQUFPLGFBQWE7QUFDcEIsT0FBTyxTQUFTO0FBQ2hCLE9BQU8sZ0JBQWdCO0FBQ3ZCLE9BQU8sZ0JBQWdCO0FBQ3ZCLFNBQVMsMEJBQTBCO0FBRW5DLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQzFCLFNBQVM7QUFBQSxJQUNQLFFBQVEsQ0FBQyxpQ0FBaUMsQ0FBQztBQUFBLElBQzNDLElBQUk7QUFBQSxNQUNGLFVBQVU7QUFBQSxRQUNSLG9CQUFvQjtBQUFBLFVBQ2xCLE1BQU07QUFBQSxVQUNOLGlCQUFpQjtBQUFBLFFBQ25CO0FBQUEsTUFDRjtBQUFBLElBQ0YsQ0FBQztBQUFBLElBQ0QsV0FBVztBQUFBLE1BQ1QsU0FBUztBQUFBLFFBQ1A7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFVBQ0UsbUJBQW1CLENBQUMsVUFBVSxXQUFXLFNBQVM7QUFBQSxVQUNsRCxrQkFBa0IsQ0FBQyxrQkFBa0I7QUFBQSxVQUNyQyxPQUFPLENBQUMsQ0FBQyxXQUFXLE9BQU8sQ0FBQztBQUFBLFFBQzlCO0FBQUEsTUFDRjtBQUFBLE1BQ0EsTUFBTSxDQUFDLGtDQUFrQztBQUFBLElBQzNDLENBQUM7QUFBQSxJQUNELFdBQVc7QUFBQSxNQUNULE1BQU0sQ0FBQyxtQ0FBbUMsOEJBQThCO0FBQUEsTUFDeEUsWUFBWSxDQUFDLEtBQUs7QUFBQSxNQUNsQixXQUFXO0FBQUEsUUFDVCxtQkFBbUI7QUFBQSxRQUNuQixVQUFRO0FBQ04sY0FBSSxTQUFTLFFBQVE7QUFDbkIsbUJBQU87QUFBQSxjQUNMLFlBQVk7QUFBQSxjQUNaLE1BQU07QUFBQSxZQUNSO0FBQUEsVUFDRjtBQUVBLGNBQUksU0FBUyxRQUFRO0FBQ25CLG1CQUFPO0FBQUEsY0FDTCxZQUFZO0FBQUEsY0FDWixNQUFNO0FBQUEsWUFDUjtBQUFBLFVBQ0Y7QUFBQSxRQUNGO0FBQUEsTUFDRjtBQUFBLE1BQ0Esc0JBQXNCO0FBQUEsSUFDeEIsQ0FBQztBQUFBLEVBQ0g7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNQLE9BQU87QUFBQSxNQUNMLEtBQUs7QUFBQSxJQUNQO0FBQUEsSUFDQSxZQUFZLENBQUMsT0FBTyxRQUFRLE9BQU87QUFBQSxFQUNyQztBQUFBLEVBQ0EsT0FBTztBQUFBLElBQ0wsdUJBQXVCO0FBQUEsRUFDekI7QUFBQSxFQUNBLGNBQWM7QUFBQSxJQUNaLFNBQVM7QUFBQSxNQUNQO0FBQUEsTUFDQTtBQUFBLE1BQ0E7QUFBQSxNQUNBO0FBQUEsSUFDRjtBQUFBLEVBQ0Y7QUFDRixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
