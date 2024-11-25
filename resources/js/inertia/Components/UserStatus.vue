<script setup>
import Pusher from 'pusher-js';
const page = usePage();
const status = computed(() => page.props.auth.user.status);

const currentStatus = ref(status.value || 1);

const notification = useNotifications('toast');

const statusText = id => {
  const statuses = {
    1: 'Available',
    2: 'Offline',
    3: 'Unavailable',
  };
  return statuses[id];
};

const userStatus = useStorage('refresh-user-counts', 0);

const channelName = `public.${page.props.appEnv}.activity.user`;
const eventName = "user.status.changed";

const listen = () => {
  const pusherKey = page.props.pusherKey;
  const options = {
    cluster: 'ap1',
    forceTLS: false,
  };
  const pusher = getPusherInstance(pusherKey, options);

  subscribeToChannel(pusher, channelName, eventName, (e) => {
    currentStatus.value = e.status;
    if (e.status == 1) {
      notification.success({
        title: e.userName + e.message,
        position: 'top',
      });
    }
    if (e.status == 2) {
      notification.info({
        title: e.userName + e.message,
        position: 'top',
      });
    }
    if (e.status == 3) {
      notification.error({
        title: e.userName + e.message,
        position: 'top',
      });
    }
    userStatus.value = e.status;
  });
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  unbindEvent(channelName, eventName);
  unsubscribeChannel(channelName);
});
</script>

<template>
  <div>
    <!--  <x-badge
      :color="
        {
          1: 'success',
          2: 'gray',
          3: 'error',
        }[currentStatus]
      "
      outlined
    >
      <x-tag class="gap-2">
        <x-icon
          :icon="
            {
              1: 'online',
              2: 'offline',
              3: 'offline',
            }[currentStatus]
          "
          :class="
            {
              1: 'text-success-400',
              2: 'text-gray-400',
              3: 'text-red-500',
            }[currentStatus]
          "
        />
        <span class="text-sm font-medium">{{ statusText(currentStatus) }}</span>
      </x-tag>
    </x-badge> -->
  </div>
</template>
