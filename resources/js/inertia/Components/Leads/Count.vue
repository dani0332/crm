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
const options = {
  cluster: 'ap1',
  forceTLS: false,
};

const pusher = new Pusher(page.props.pusherKey, options);
const channel = pusher.subscribe(
  'public.' + page.props.appEnv + '.total-leads-count',
);

const listen = () => {
  channel.bind('leads.count', function (e) {
    totalCount.value = e.totalLeadsCount;
  });
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  channel.unbind('leads.count');
  channel.unsubscribe('public.' + page.props.appEnv + '.total-leads-count');
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
