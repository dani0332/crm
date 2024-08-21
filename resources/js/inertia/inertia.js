import '../../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import UI, { BaseTheme } from '@indielayer/ui';
import MainLayout from '@/inertia/Layouts/MainLayout.vue';
import icons from './icons';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import { ZiggyVue } from '../../../vendor/tightenco/ziggy';

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
      .use(UI, {
        prefix: 'X',
        icons,
        theme: {
          colors: {
            primary: {
              50: 'rgb(242,245,249)',
              100: 'rgb(228,235,243)',
              200: 'rgb(198,214,231)',
              300: 'rgb(163,191,217)',
              400: 'rgb(85,148,196)',
              500: '#1c83bc',
              600: 'rgb(27,124,178)',
              700: 'rgb(24,113,163)',
              800: 'rgb(18,85,122)',
              900: 'rgb(15,72,103)',
              950: 'rgb(13,59,84)',
            },
            success: {
              50: '#ecfdf5',
              100: '#d1fae5',
              200: '#a7f3d0',
              300: '#6ee7b7',
              400: '#34d399',
              500: '#10b981',
              600: '#059669',
              700: '#047857',
              800: '#065f46',
              900: '#064e3b',
              950: '#022c22',
            },
          },
          ...BaseTheme,
        },
      })
      .mount(el);
  },
});
