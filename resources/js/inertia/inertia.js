import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import UI from '@indielayer/ui';
import MainLayout from '@/inertia/Layouts/MainLayout.vue';
import icons from './icons';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import { ZiggyVue } from 'ziggy';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'IMCRM';

createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: async name =>
  {
    const page = await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));
    page.default.layout = page.default?.layout || MainLayout;
    // page.default.layout = MainLayout;
    return page;
  },
  setup({ el, App, props, plugin })
  {
    createApp({
      name: 'IMCRM',
      mounted: () =>
      {
        // Remove Data Page for Protection
        document.querySelector('[data-page]')?.removeAttribute('data-page');
      },
      render: () => h(App, props),
    })
      .component('DataTable', Vue3EasyDataTable)
      .use(ZiggyVue)
      .use(plugin)
      .use(UI, { icons })
      .mount(el);
  },
});
