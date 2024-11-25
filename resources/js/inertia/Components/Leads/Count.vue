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
const eventName = "leads.count";

const listen = () => {
  const pusherKey = page.props.pusherKey;
  const options = {
    cluster: 'ap1',
    forceTLS: false,
  };
  const pusher = getPusherInstance(pusherKey, options);

  subscribeToChannel(pusher, channelName, eventName, (e) => {
    totalCount.value = e.totalLeadsCount;
  });
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  unbindEvent(channelName, eventName);
  unsubscribeChannel(channelName);
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
