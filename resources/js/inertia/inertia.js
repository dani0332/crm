import '../../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import UI, { colors } from '@indielayer/ui';
import MainLayout from '@/inertia/Layouts/MainLayout.vue';
import icons from './icons';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import { ZiggyVue } from '../../../vendor/tightenco/ziggy';

const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'IMCRM';

createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: async name =>
  {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    let page = pages[`./Pages/${name}.vue`];
    page.default.layout = page.default?.layout || MainLayout;
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
      .use(ZiggyVue, Ziggy)
      .use(plugin)
      .use(UI, {
        icons,
        theme: {
          colors: {
            primary: {
              50: 'rgb(242,245,249)',
              100: 'rgb(228,235,243)',
              200: 'rgb(198,214,231)',
              300: 'rgb(163,191,217)',
              400: 'rgb(85,148,196)',
              500: '#1d83bc',
              600: 'rgb(28,124,178)',
              700: 'rgb(25,113,163)',
              800: 'rgb(19,85,122)',
              900: 'rgb(16,72,103)',
            },
            secondary: colors.orange,
            success: colors.green,
            warning: colors.yellow,
            error: colors.red,
          },
        },
      })
      .mount(el);
  },
});
