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
      v-if="props.links.current !== 1"
      size="sm"
      icon-left="prev"
      :loading="props.loading"
      @click="setPage(props.links.current - 1)"
    >
      Previous
    </x-button>

    <x-button v-else tag="div" size="sm" icon-left="prev" disabled>
      Previous
    </x-button>

    <div class="text-xs lining-nums font-medium text-center text-gray-700">
      Now displaying: {{ props.links.from }} ~
      {{ props.links.to }}
      <span v-if="props.links.total"> of {{ props.links.total }} </span>
    </div>

    <x-button
      v-if="props.links.next !== null"
      size="sm"
      icon-right="next"
      :loading="props.loading"
      :disabled="props.links.next === null"
      @click="setPage(props.links.current + 1)"
    >
      Next
    </x-button>

    <x-button v-else tag="div" size="sm" icon-right="next" disabled>
      Next
    </x-button>
  </div>
</template>
