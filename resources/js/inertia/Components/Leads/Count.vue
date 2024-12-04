<script setup>
const props = defineProps({
  leadsCount: {
    type: Number,
    default: 0,
  },
});
const page = usePage();
const totalCount = ref(props.leadsCount);
const previousDate = getPreviousDate;

const channelName = `public.${page.props.appEnv}.total-leads-count`;
const eventName = 'leads.count';

const listen = () => {
  const worker = new SharedWorker('/build/workers/pusher.worker.js');

  worker.port.addEventListener('message', e => {
    totalCount.value = e.data.totalLeadsCount;
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

watch(
  () => props.leadsCount,
  () => {
    totalCount.value = props.leadsCount;
  },
);
</script>

<template>
  <div>
    <x-tooltip placement="right">
      <x-tag size="sm" color="#777" class="lining-nums font-semibold">
        {{ totalCount }}
      </x-tag>
      <template #tooltip>
        <span>Total Leads received since {{ previousDate() }}</span>
      </template>
    </x-tooltip>
  </div>
</template>
