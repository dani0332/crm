<script setup>
const props = defineProps({
  links: {
    type: Object,
    required: true,
  },
});

const loading = ref(false);

router.on('start', event => {
  loading.value = true;
});

router.on('finish', event => {
  loading.value = false;
});
</script>

<template>
  <div class="flex justify-between items-center gap-2 py-6">
    <Link
      :href="props.links.prev ? props.links.prev : '#'"
      preserve-scroll
      preserve-state
    >
      <x-button
        tag="div"
        size="sm"
        icon-left="prev"
        :loading="loading"
        :disabled="links.current === 1"
      >
        Previous
      </x-button>
    </Link>
    <div class="text-xs lining-nums font-medium">
      Page {{ links.current }} ~ [{{ links.from }} - {{ links.to }}]
    </div>
    <Link
      :href="props.links.next ? props.links.next : '#'"
      preserve-scroll
      preserve-state
    >
      <x-button
        tag="div"
        size="sm"
        icon-right="next"
        :loading="loading"
        :disabled="props.links.next === null"
      >
        Next
      </x-button>
    </Link>
  </div>
</template>
