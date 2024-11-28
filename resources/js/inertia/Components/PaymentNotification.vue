<script setup>
const page = usePage();
import CustomNotification from './CustomNotification.vue';

const showNotification = ref(false);
const notificationData = ref({});

const channelName = `public.${page.props.appEnv}.activity.user`;
const eventName = 'payment.notification';

const listen = () => {
  const worker = new SharedWorker('/build/workers/pusher.worker.js');

  worker.port.addEventListener('message', e => {
    if (e.data.advisorId === page.props.auth.user.id) {
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        title: 'Payment',
        message: e.data.message,
        url: e.data.url,
        uuid: e.data.uuid,
        quoteType: e.data.quoteType,
        timeout: 30000,
      };
      showNotification.value = true;
    }
  });

  worker.onerror = function (error) {
    console.log(error.message);
    worker.port.close();
  };

  worker.port.start();

  //Subscribe to channel/event
  worker.port.postMessage({
    action: 'subscribe',
    channel: channelName,
    event: eventName,
    pusherKey: page.props.pusherKey,
    pusherCluster: page.props.pusherCluster,
  });
};
const hideNotification = () => {
  console.log('Parent function called!');
  showNotification.value = false;
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  //Unsubscribe to channel/event
  worker.port.postMessage({
    action: 'unsubscribe',
    channel: channelName,
    event: eventName,
  });
});
</script>

<template>
  <div>
    <CustomNotification
      v-if="showNotification"
      :imageUrl="notificationData.imageUrl"
      :title="notificationData.title"
      :message="notificationData.message"
      :uuid="notificationData.uuid"
      :quoteType="notificationData.quoteType"
      :url="notificationData.url"
      :timeout="notificationData.timeout"
      :callHideFunction="hideNotification"
      @close="showNotification = false"
    />
  </div>
</template>
