<script setup>
import Pusher from 'pusher-js';
const page = usePage();
import CustomWhatsappReminderNotification from './CustomWhatsappReminderNotification.vue';
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
  channel.bind('whatsapp.reminder.notification', function (e) {
    if (e.advisorId === page.props.auth.user.id) {
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        url: e.url,
        quoteUuid: e.quoteUuid,
        timeout: 30000,
      };
      showNotification.value = true;
    }
  });
};
const hideNotification = () => {
  showNotification.value = false;
};
onMounted(() => {
  listen();
});
onUnmounted(() => {
  channel.unbind('whatsapp.reminder.notification');
  channel.unsubscribe('public.' + page.props.appEnv + '.activity.user');
});
</script>

<template>
  <div>
    <CustomWhatsappReminderNotification
      v-if="showNotification"
      :imageUrl="notificationData.imageUrl"
      :url="notificationData.url"
      :quoteUuid="notificationData.quoteUuid"
      :timeout="notificationData.timeout"
      :callHideFunction="hideNotification"
      @close="showNotification = false"
    />
  </div>
</template>
