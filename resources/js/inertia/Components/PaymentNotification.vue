<script setup>
const page = usePage();
import CustomNotification from './CustomNotification.vue';

const showNotification = ref(false);
const notificationData = ref({});

const channelName = `public.${page.props.appEnv}.activity.user`;
const eventName = 'payment.notification';

let worker = null;

const listen = () => {
  worker = new SharedWorker('/build/workers/pusher.worker.js');

  worker.port.addEventListener('message', e => {
    console.log('[PaymentNotification] Broadcast received:', {
      event: e.data,
      currentUserId: page.props.auth.user.id,
      matches: e.data.advisorId === page.props.auth.user.id,
    });

    if (e.data.advisorId === page.props.auth.user.id) {
      console.log('[PaymentNotification] Processing notification for current user');
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
    } else {
      console.log('[PaymentNotification] Broadcast ignored - not for current user', {
        broadcastAdvisorId: e.data.advisorId,
        currentUserId: page.props.auth.user.id,
      });
    }
  });

  worker.onerror = function (error) {
    console.log(error.message);
    worker.port.close();
  };

  worker.port.start();

  console.log('[PaymentNotification] Subscribing to channel:', {
    channel: channelName,
    event: eventName,
  });

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
