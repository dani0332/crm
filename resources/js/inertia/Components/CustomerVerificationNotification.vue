<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const channelName = `public.${page.props.appEnv}.customer.verification`;
const eventName = 'customer.verification.updated';

let worker;

const listen = () => {
  worker = new SharedWorker(
    '/build/workers/pusher.worker.js?v=' + new Date().getTime(),
  );
  
  worker.port.addEventListener('message', e => {
    // Since the message contains our customer verification data directly,
    // we can dispatch it without checking event type (similar to OCR pattern)
    if (e.data.quoteUuid && e.data.message === 'Customer verification status updated') {
      // Dispatch to window event listener
      window.dispatchEvent(
        new CustomEvent('customer-verification-updated', {
          detail: e.data,
        }),
      );
    }
  });

  worker.onerror = function (error) {
    console.error('Customer verification notification worker error:', error);
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
    worker.port.close();
  }
});
</script>

<template>
  <!-- Empty template - this component only handles events -->
</template>
