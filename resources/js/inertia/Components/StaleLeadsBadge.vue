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
  <div v-if="daysSinceStale(date) !== false" class="relative self-end -mt-2">
    <x-tooltip align="right" position="bottom">
      <p class="bg-red-600 px-1 rounded text-[10px] text-white">
        {{ daysSinceStale(date) }}
        days
      </p>
      <template #tooltip>
        <div class="max-w-[160px] text-xs">
          <span>Days elapsed since the lead was flagged as stale</span>
        </div>
      </template>
    </x-tooltip>
  </div>
</template>
