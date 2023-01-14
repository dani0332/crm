import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import UI from '@indielayer/ui';
import icons from './icons';

const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'IMCRM';

createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: name => require(`./Pages/${name}`),
  setup({ el, App, props, plugin }) {
    createApp({
      name: 'IMCRM',
      mounted: () => {
        // Remove Data Page for Protection
        document.querySelector('[data-page]')?.removeAttribute('data-page');
      },
      render: () => h(App, props),
    })
      .use(plugin)
      .use(UI, { icons })
      .mount(el);
  },
});
