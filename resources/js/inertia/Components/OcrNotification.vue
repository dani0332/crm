<script setup>
const page = usePage();
import CustomNotification from './CustomNotification.vue';

const showNotification = ref(false);
const notificationData = ref({});
const channelName = `public.${page.props.appEnv}.ocr.user`;
const eventName = 'ocr.notification';

let worker;

const listen = () => {
  console.log('listen ocr notification');
  worker = new SharedWorker('/build/workers/pusher.worker.js');
  worker.port.addEventListener('message', e => {
    console.log('ocr notification message', e);
    console.log('policy detail uuid', page.props.quote);
    if (e.data.uuid === page.props.quote.uuid) {
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        title: 'OCR Notification',
        message: e.data.message,
        status: e.data.status,
        uuid: e.data.uuid,
        error: e.data.error,
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
</script>

<template>
  <div>
    <CustomNotification
      v-if="showNotification"
      :title="notificationData.title"
      :message="notificationData.message"
      :timeout="10000"
      @close="showNotification = false"
    />
  </div>
</template> 