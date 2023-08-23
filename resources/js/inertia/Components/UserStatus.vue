<script setup>
const page = usePage();
const status = computed(() => page.props.auth.user.status);

const currentStatus = ref(status.value || 1);

const statusText = id => {
  const statuses = {
    1: 'Available',
    2: 'Offline',
    3: 'Unavailable',
  };
  return statuses[id];
};

onMounted(() => {
  window.Echo.channel('activity.user').listen(
    '.user.status.changed',
    function (e) {
      if (e?.userId == page.props.auth.user.id) {
        currentStatus.value = e.status;
      }
    },
  );
});

onUnmounted(() => {
  window.Echo.channel('activity.user').stopListening('user.status.changed');
});
</script>

<template>
  <div>
    <x-badge
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
    </x-badge>
  </div>
</template>
