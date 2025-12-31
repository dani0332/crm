<script setup>
import { onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const channelName = `public.${page.props.appEnv}.document.notification`;
const eventName = 'document.notification';

let worker;

const listen = () => {
  worker = new SharedWorker(
    '/build/workers/pusher.worker.js?v=' + new Date().getTime(),
  );

  worker.port.addEventListener('message', e => {
    window.dispatchEvent(
      new CustomEvent('document-notification', {
        detail: e.data,
      }),
    );
  });

  worker.onerror = error => {
    console.error('Document notification worker error:', error);
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

onMounted(() => listen());
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
