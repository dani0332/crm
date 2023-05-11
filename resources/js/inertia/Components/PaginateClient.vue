<script setup>
const props = defineProps({
  links: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update']);

const page = ref(1);

const setPage = newPage => {
  page.value = newPage;
  emit('update', newPage);
};
</script>

<template>
  <div class="flex justify-between items-center gap-2 py-6">
    <x-button
      v-if="links.current !== 1"
      size="sm"
      icon-left="prev"
      :loading="loading"
      @click="setPage(links.current - 1)"
    >
      Previous
    </x-button>

    <x-button v-else tag="div" size="sm" icon-left="prev" disabled>
      Previous
    </x-button>

    <div class="text-xs lining-nums font-medium text-center">
      <span v-if="links.last">
        <p>Page {{ links.current }} of {{ links.last }}</p>
        <p v-if="links.total" class="mt-1 text-gray-500">
          Total Records {{ links.total }}
        </p>
      </span>
      <span v-else>
        Page {{ links.current }} ~ [{{ links.from }} - {{ links.to }}]
      </span>
    </div>

    <x-button
      v-if="props.links.next !== null"
      size="sm"
      icon-right="next"
      :loading="loading"
      :disabled="props.links.next === null"
      @click="setPage(links.current + 1)"
    >
      Next
    </x-button>

    <x-button v-else tag="div" size="sm" icon-right="next" disabled>
      Next
    </x-button>
  </div>
</template>
