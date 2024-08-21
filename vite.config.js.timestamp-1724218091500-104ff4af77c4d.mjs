// vite.config.js
import { defineConfig } from "file:///D:/MyAlfred/Projects/blanka/node_modules/vite/dist/node/index.js";
import laravel from "file:///D:/MyAlfred/Projects/blanka/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///D:/MyAlfred/Projects/blanka/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import AutoImport from "file:///D:/MyAlfred/Projects/blanka/node_modules/unplugin-auto-import/dist/vite.js";
import Components from "file:///D:/MyAlfred/Projects/blanka/node_modules/unplugin-vue-components/dist/vite.js";
import { HeadlessUiResolver } from "file:///D:/MyAlfred/Projects/blanka/node_modules/unplugin-vue-components/dist/resolvers.js";
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
    include: ["@vueuse/core", "md-editor-v3", "@headlessui/vue"]
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJEOlxcXFxNeUFsZnJlZFxcXFxQcm9qZWN0c1xcXFxibGFua2FcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkQ6XFxcXE15QWxmcmVkXFxcXFByb2plY3RzXFxcXGJsYW5rYVxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vRDovTXlBbGZyZWQvUHJvamVjdHMvYmxhbmthL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XHJcbmltcG9ydCBsYXJhdmVsIGZyb20gJ2xhcmF2ZWwtdml0ZS1wbHVnaW4nO1xyXG5pbXBvcnQgdnVlIGZyb20gJ0B2aXRlanMvcGx1Z2luLXZ1ZSc7XHJcbmltcG9ydCBBdXRvSW1wb3J0IGZyb20gJ3VucGx1Z2luLWF1dG8taW1wb3J0L3ZpdGUnO1xyXG5pbXBvcnQgQ29tcG9uZW50cyBmcm9tICd1bnBsdWdpbi12dWUtY29tcG9uZW50cy92aXRlJztcclxuaW1wb3J0IHsgSGVhZGxlc3NVaVJlc29sdmVyIH0gZnJvbSAndW5wbHVnaW4tdnVlLWNvbXBvbmVudHMvcmVzb2x2ZXJzJztcclxuXHJcbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XHJcbiAgcGx1Z2luczogW1xyXG4gICAgbGFyYXZlbChbJ3Jlc291cmNlcy9qcy9pbmVydGlhL2luZXJ0aWEuanMnXSksXHJcbiAgICB2dWUoe1xyXG4gICAgICB0ZW1wbGF0ZToge1xyXG4gICAgICAgIHRyYW5zZm9ybUFzc2V0VXJsczoge1xyXG4gICAgICAgICAgYmFzZTogbnVsbCxcclxuICAgICAgICAgIGluY2x1ZGVBYnNvbHV0ZTogZmFsc2UsXHJcbiAgICAgICAgfSxcclxuICAgICAgfSxcclxuICAgIH0pLFxyXG4gICAgQXV0b0ltcG9ydCh7XHJcbiAgICAgIGltcG9ydHM6IFtcclxuICAgICAgICAndnVlJyxcclxuICAgICAgICAnQHZ1ZXVzZS9jb3JlJyxcclxuICAgICAgICB7XHJcbiAgICAgICAgICAnQGluZXJ0aWFqcy92dWUzJzogWydyb3V0ZXInLCAndXNlUGFnZScsICd1c2VGb3JtJ10sXHJcbiAgICAgICAgICAnQGluZGllbGF5ZXIvdWknOiBbJ3VzZU5vdGlmaWNhdGlvbnMnXSxcclxuICAgICAgICAgIGF4aW9zOiBbWydkZWZhdWx0JywgJ2F4aW9zJ11dLFxyXG4gICAgICAgIH0sXHJcbiAgICAgIF0sXHJcbiAgICAgIGRpcnM6IFsncmVzb3VyY2VzL2pzL2luZXJ0aWEvQ29tcG9zYWJsZXMnXSxcclxuICAgIH0pLFxyXG4gICAgQ29tcG9uZW50cyh7XHJcbiAgICAgIGRpcnM6IFsncmVzb3VyY2VzL2pzL2luZXJ0aWEvQ29tcG9uZW50cycsICdyZXNvdXJjZXMvanMvaW5lcnRpYS9MYXlvdXRzJ10sXHJcbiAgICAgIGV4dGVuc2lvbnM6IFsndnVlJ10sXHJcbiAgICAgIHJlc29sdmVyczogW1xyXG4gICAgICAgIEhlYWRsZXNzVWlSZXNvbHZlcigpLFxyXG4gICAgICAgIG5hbWUgPT4ge1xyXG4gICAgICAgICAgaWYgKG5hbWUgPT09ICdIZWFkJykge1xyXG4gICAgICAgICAgICByZXR1cm4ge1xyXG4gICAgICAgICAgICAgIGltcG9ydE5hbWU6ICdIZWFkJyxcclxuICAgICAgICAgICAgICBwYXRoOiAnQGluZXJ0aWFqcy92dWUzJyxcclxuICAgICAgICAgICAgfTtcclxuICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICBpZiAobmFtZSA9PT0gJ0xpbmsnKSB7XHJcbiAgICAgICAgICAgIHJldHVybiB7XHJcbiAgICAgICAgICAgICAgaW1wb3J0TmFtZTogJ0xpbmsnLFxyXG4gICAgICAgICAgICAgIHBhdGg6ICdAaW5lcnRpYWpzL3Z1ZTMnLFxyXG4gICAgICAgICAgICB9O1xyXG4gICAgICAgICAgfVxyXG4gICAgICAgIH0sXHJcbiAgICAgIF0sXHJcbiAgICAgIGRpcmVjdG9yeUFzTmFtZXNwYWNlOiB0cnVlLFxyXG4gICAgfSksXHJcbiAgXSxcclxuICByZXNvbHZlOiB7XHJcbiAgICBhbGlhczoge1xyXG4gICAgICAnQCc6ICcvcmVzb3VyY2VzL2pzJyxcclxuICAgIH0sXHJcbiAgICBleHRlbnNpb25zOiBbJy5qcycsICcudnVlJywgJy5qc29uJ10sXHJcbiAgfSxcclxuICBidWlsZDoge1xyXG4gICAgY2h1bmtTaXplV2FybmluZ0xpbWl0OiA0MjAwLFxyXG4gIH0sXHJcbiAgb3B0aW1pemVEZXBzOiB7XHJcbiAgICBpbmNsdWRlOiBbJ0B2dWV1c2UvY29yZScsICdtZC1lZGl0b3ItdjMnLCAnQGhlYWRsZXNzdWkvdnVlJ10sXHJcbiAgfSxcclxufSk7XHJcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBMlEsU0FBUyxvQkFBb0I7QUFDeFMsT0FBTyxhQUFhO0FBQ3BCLE9BQU8sU0FBUztBQUNoQixPQUFPLGdCQUFnQjtBQUN2QixPQUFPLGdCQUFnQjtBQUN2QixTQUFTLDBCQUEwQjtBQUVuQyxJQUFPLHNCQUFRLGFBQWE7QUFBQSxFQUMxQixTQUFTO0FBQUEsSUFDUCxRQUFRLENBQUMsaUNBQWlDLENBQUM7QUFBQSxJQUMzQyxJQUFJO0FBQUEsTUFDRixVQUFVO0FBQUEsUUFDUixvQkFBb0I7QUFBQSxVQUNsQixNQUFNO0FBQUEsVUFDTixpQkFBaUI7QUFBQSxRQUNuQjtBQUFBLE1BQ0Y7QUFBQSxJQUNGLENBQUM7QUFBQSxJQUNELFdBQVc7QUFBQSxNQUNULFNBQVM7QUFBQSxRQUNQO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxVQUNFLG1CQUFtQixDQUFDLFVBQVUsV0FBVyxTQUFTO0FBQUEsVUFDbEQsa0JBQWtCLENBQUMsa0JBQWtCO0FBQUEsVUFDckMsT0FBTyxDQUFDLENBQUMsV0FBVyxPQUFPLENBQUM7QUFBQSxRQUM5QjtBQUFBLE1BQ0Y7QUFBQSxNQUNBLE1BQU0sQ0FBQyxrQ0FBa0M7QUFBQSxJQUMzQyxDQUFDO0FBQUEsSUFDRCxXQUFXO0FBQUEsTUFDVCxNQUFNLENBQUMsbUNBQW1DLDhCQUE4QjtBQUFBLE1BQ3hFLFlBQVksQ0FBQyxLQUFLO0FBQUEsTUFDbEIsV0FBVztBQUFBLFFBQ1QsbUJBQW1CO0FBQUEsUUFDbkIsVUFBUTtBQUNOLGNBQUksU0FBUyxRQUFRO0FBQ25CLG1CQUFPO0FBQUEsY0FDTCxZQUFZO0FBQUEsY0FDWixNQUFNO0FBQUEsWUFDUjtBQUFBLFVBQ0Y7QUFFQSxjQUFJLFNBQVMsUUFBUTtBQUNuQixtQkFBTztBQUFBLGNBQ0wsWUFBWTtBQUFBLGNBQ1osTUFBTTtBQUFBLFlBQ1I7QUFBQSxVQUNGO0FBQUEsUUFDRjtBQUFBLE1BQ0Y7QUFBQSxNQUNBLHNCQUFzQjtBQUFBLElBQ3hCLENBQUM7QUFBQSxFQUNIO0FBQUEsRUFDQSxTQUFTO0FBQUEsSUFDUCxPQUFPO0FBQUEsTUFDTCxLQUFLO0FBQUEsSUFDUDtBQUFBLElBQ0EsWUFBWSxDQUFDLE9BQU8sUUFBUSxPQUFPO0FBQUEsRUFDckM7QUFBQSxFQUNBLE9BQU87QUFBQSxJQUNMLHVCQUF1QjtBQUFBLEVBQ3pCO0FBQUEsRUFDQSxjQUFjO0FBQUEsSUFDWixTQUFTLENBQUMsZ0JBQWdCLGdCQUFnQixpQkFBaUI7QUFBQSxFQUM3RDtBQUNGLENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==
