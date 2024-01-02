<script setup>
import { daysSinceStale } from '../Composables/utilities';
const props = defineProps({
  date: {
    type: [String, Date],
    require: true,
  },
});
</script>
<template>
  <x-tooltip>
    <p
      v-if="daysSinceStale(date)"
      class="bg-red-600 px-1 rounded text-[9px] text-white"
    >
      {{ daysSinceStale(date) }}
      days
    </p>
    <template #tooltip>
      <span>Days elapsed since the lead was flagged as stale</span>
    </template>
  </x-tooltip>
</template>