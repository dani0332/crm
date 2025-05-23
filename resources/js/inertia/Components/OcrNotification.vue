<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CustomNotification from './CustomNotification.vue';

const page = usePage();
const showNotification = ref(false);
const notificationData = ref({});
const channelName = `public.${page.props.appEnv}.ocr.user`;
const eventName = 'ocr.notification';

let worker;

const listen = () => {
  worker = new SharedWorker('/build/workers/pusher.worker.js?v=' + new Date().getTime());
  worker.port.addEventListener('message', e => {
    const currentUrl = page.props.location || '';
    if (
      e.data.uuid === page.props?.quote?.uuid &&
      currentUrl.includes('/quotes/car/') &&
      e.data.userId === page.props.auth.user.id
    ) {
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        title: 'OCR Notification',
        message: e.data.message,
        status: e.data.status,
        uuid: e.data.uuid,
        error: e.data.error,
        docType: e.data.docType,
      };
      showNotification.value = true;
      // Emit event for PolicyDetail.vue
      window.dispatchEvent(new CustomEvent('ocr-notification', { detail: notificationData.value }));
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

onMounted(listen);
onUnmounted(() => {
  if (worker) {
    worker.port.postMessage({
      action: 'unsubscribe',
      channel: channelName,
      event: eventName,
    });
  }
});
const hideNotification = () => {
  showNotification.value = false;
};
</script>

<template>
  <div>
    <CustomNotification
      v-if="showNotification"
      :imageUrl="notificationData.imageUrl"
      :title="notificationData.title"
      :message="notificationData.message"
      :timeout="10000"
      :callHideFunction="hideNotification"
      @close="showNotification = false"
    />
  </div>
</template>