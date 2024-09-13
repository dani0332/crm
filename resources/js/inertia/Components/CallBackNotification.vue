<script setup>
import Pusher from 'pusher-js';
const page = usePage();
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
  channel.bind('callback.notification', function (e) {
    if (e.advisorId === page.props.auth.user.id) {
      showNotification.value = true;
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        url: e.url,
        quoteUuid: e.quoteUuid,
        title: e.title,
        message: e.message,
        timeout: 10000,
      };
        hideNotificationTimeOut();
    }
  });
};

const url = () => {
  window.location.href = notificationData.value.url;
};
const hideNotification = () => {
  showNotification.value = false;
};

const hideNotificationTimeOut = () => {
    if (notificationData.value.timeout) {
        setTimeout(() => {
            showNotification.value = false;
        }, notificationData.value.timeout);
    }
};

onMounted(() => {
  listen();
});
onUnmounted(() => {
  channel.unbind('callback.notification');
  channel.unsubscribe('public.' + page.props.appEnv + '.activity.user');
});
</script>
<template xmlns="http://www.w3.org/1999/html">
  <div
    v-if="showNotification"
    class="custom-notification"
    ref="customNotification"
  >
    <div class="notification-content">
      <span class="close-icon closeTag" @click="hideNotification">&times;</span>
      <img
        :src="notificationData.imageUrl"
        alt="Notification Image"
        class="notification-image"
      />
      <div>
        <h2 class="notification-title">{{ notificationData.title }}</h2>
        <p class="notification-message">
          {{ notificationData.message }}
          <a class="notification-button" @click="url">{{
            notificationData.quoteUuid
          }}</a>
          will expire in 1 hour.
        </p>
      </div>
    </div>
  </div>
</template>
<style scoped>
.custom-notification {
  background-color: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  max-width: 700px;
  margin: 0 auto;
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}
.closeTag {
  position: absolute;
  right: 0;
  top: -12px;
  font-size: 22px;
  cursor: pointer;
}
.notification-content {
  display: flex;
  align-items: center;
  position: relative;
}
.notification-image {
  width: 52px;
  height: auto;
  border-radius: 8px;
  max-width: 300px;
  margin-right: 20px;
}
.notification-title {
  color: #007bff;
  font-size: 1.2em;
}
.notification-message {
  color: #000;
  margin-bottom: 0px;
}
.notification-button {
  color: #007bff;
  cursor: pointer;
  text-decoration: underline;
}
</style>
