<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';

const page = usePage();
const notification = useNotifications('toast');
const channelName = `public.${page.props.appEnv}.lead.status.updated`;
const eventName = 'lead.status.updated';

let worker;

const publishLeadStatusUpdated = data => {
  window.dispatchEvent(
    new CustomEvent('lead-status-updated', {
      detail: {
        title: 'Lead Status Updated',
        quoteType: data.quoteType,
        uuid: data.uuid,
      },
    }),
  );
};

const listen = () => {
  worker = new SharedWorker(
    '/build/workers/pusher.worker.js?v=' + new Date().getTime(),
  );
  worker.port.addEventListener('message', e => {
    const currentUrl = page.props.location || '';

    if (currentUrl.includes(`/quotes/car/${e.data.uuid}`)) {
      publishLeadStatusUpdated(e.data);
    }
  });
  worker.onerror = function (error) {
    worker.port.close();
  };
  worker.port.start();
  worker.port.postMessage({
    action: 'subscribe',
    channel: channelName,
    event: eventName,
    pusherKey: page.props.pusherKey,
    pusherCluster: page.props.pusherCluster,
  });
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  if (worker) {
    worker.port.postMessage({
      action: 'unsubscribe',
      channel: channelName,
      event: eventName,
    });
  }
});
</script>

<template></template>
