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
      v-if="links.current !== 1"
      :href="props.links.prev"
      preserve-scroll
      preserve-state
    >
      <x-button tag="div" size="sm" icon-left="prev" :loading="loading">
        Previous
      </x-button>
    </Link>
    <x-button v-else tag="div" size="sm" icon-left="prev" disabled>
      Previous
    </x-button>

    <div class="text-xs lining-nums font-medium text-center">
      Page {{ links.current }} ~ [{{ links.from }} - {{ links.to }}]
    </div>

    <Link
      v-if="props.links.next !== null"
      :href="props.links.next ? props.links.next : '#'"
      preserve-scroll
      preserve-state
    >
      <x-button tag="div" size="sm" icon-right="next" :loading="loading">
        Next
      </x-button>
    </Link>
    <x-button v-else tag="div" size="sm" icon-right="next" disabled>
      Next
    </x-button>
  </div>
</template>
