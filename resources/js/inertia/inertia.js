import '../../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import UI from '@indielayer/ui';
import MainLayout from '@/inertia/Layouts/MainLayout.vue';
import icons from './icons';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import { ZiggyVue } from '../../../vendor/tightenco/ziggy/dist/vue.m';

const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'IMCRM';

createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: async name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    let page = pages[`./Pages/${name}.vue`];
    page.default.layout = page.default?.layout || MainLayout;
    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({
      name: 'IMCRM',
      mounted: () => {
        // Remove Data Page for Protection
        document.querySelector('[data-page]')?.removeAttribute('data-page');
      },
      render: () => h(App, props),
    })
      .component('DataTable', Vue3EasyDataTable)
      .use(ZiggyVue, Ziggy)
      .use(plugin)
      .use(UI, { icons })
      .mount(el);
  },
});
