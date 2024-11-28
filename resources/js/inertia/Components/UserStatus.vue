<script setup>
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
const eventName = 'user.status.changed';

const listen = () => {
  const worker = new SharedWorker('/build/workers/pusher.worker.js');

  worker.port.addEventListener('message', e => {
    currentStatus.value = e.data.status;
    if (e.data.status == 1) {
      notification.success({
        title: e.data.userName + e.data.message,
        position: 'top',
      });
    }
    if (e.data.status == 2) {
      notification.info({
        title: e.data.userName + e.data.message,
        position: 'top',
      });
    }
    if (e.data.status == 3) {
      notification.error({
        title: e.data.userName + e.data.message,
        position: 'top',
      });
    }
    userStatus.value = e.data.status;
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
