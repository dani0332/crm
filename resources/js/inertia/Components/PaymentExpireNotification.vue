<script setup>
const page = usePage();
import CustomExpireNotification from './CustomExpireNotification.vue';

const showNotification = ref(false);
const notificationData = ref({});

const channelName = `public.${page.props.appEnv}.activity.user`;
const eventName = 'expire.notification';

const listen = () => {
  const worker = new SharedWorker('/build/workers/pusher.worker.js');

  worker.port.addEventListener('message', e => {
    if (e.data.advisorId === page.props.auth.user.id) {
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        title: 'Payment',
        message: e.data.message,
        url: e.data.url,
        quoteUuid: e.data.quoteUuid,
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
    <CustomExpireNotification
      v-if="showNotification"
      :imageUrl="notificationData.imageUrl"
      :title="notificationData.title"
      :message="notificationData.message"
      :url="notificationData.url"
      :quoteUuid="notificationData.quoteUuid"
      :timeout="notificationData.timeout"
      :callHideFunction="hideNotification"
      @close="showNotification = false"
    />
  </div>
</template>
