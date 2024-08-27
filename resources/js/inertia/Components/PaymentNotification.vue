<script setup>
import Pusher from 'pusher-js';
const page = usePage();
import CustomNotification from './CustomNotification.vue';

const showNotification = ref(false);
const notificationData = ref({});

const options = {
  cluster: 'ap1',
  forceTLS: false,
};

const pusher = new Pusher(page.props.pusherKey, options);
const channel = pusher.subscribe(
  'public.' + page.props.appEnv + '.activity.user',
);
const listen = () => {
  channel.bind('payment.notification', function (e) {
    if (e.advisorId === page.props.auth.user.id) {
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        title: 'Payment',
        message: e.message,
        url: e.url,
        uuid: e.uuid,
        quoteType: e.quoteType,
        timeout: 30000,
      };
      showNotification.value = true;
    }
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
  channel.unbind('payment.notification');
  channel.unsubscribe('public.' + page.props.appEnv + '.activity.user');
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
