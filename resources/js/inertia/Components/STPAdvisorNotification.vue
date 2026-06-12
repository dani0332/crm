<script setup>
const page = usePage();
const showNotification = ref(false);
const notificationData = ref({});

const channelName = `public.${page.props.appEnv}.activity.user`;
const eventName = 'stp.advisor.notification';

let worker;

const listen = () => {
  if (!page.props.auth?.user?.id) {
    return;
  }

  if (!page.props.pusherKey || !page.props.pusherCluster) {
    return;
  }

  worker = new SharedWorker('/build/workers/pusher.worker.js');

  worker.port.addEventListener('message', e => {
    // Convert both to numbers for comparison to handle type mismatchesp
    const notificationAdvisorId = Number(e.data.advisorId);
    const currentUserId = Number(page.props.auth.user.id);

    if (notificationAdvisorId === currentUserId) {
      showNotification.value = true;
      notificationData.value = {
        imageUrl: '/image/alfred-theme.png',
        title: e.data.title || 'New STP Advisor Notification',
        message: e.data.message,
        leadUuid: e.data.lead_uuid,
        timestamp: e.data.timestamp,
        timeout: 10000,
      };
      hideNotificationTimeOut();
    }
  });

  worker.onerror = function (error) {
    worker.port.close();
  };

  worker.port.onmessageerror = function (error) {};

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
          <span v-if="notificationData.leadUuid" class="notification-lead-uuid">
            Lead:
            <a
              v-if="notificationData.leadUuid"
              :href="`/quotes/health/${notificationData.leadUuid}`"
              class="text-primary-500 hover:underline ml-1"
              target="_blank"
            >
              {{ notificationData.leadUuid }}
            </a>
            <span v-else>
              {{ notificationData.leadUuid }}
            </span>
          </span>
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
.notification-lead-uuid {
  color: #007bff;
  font-weight: bold;
}
</style>
