<script setup>
const daysSinceStale = date => useDaysSinceStale(date);
const props = defineProps({
  date: {
    type: [String, Date],
    require: true,
  },
  align: {
    type: String,
    default: 'right',
  },
  position: {
    type: String,
    default: 'top',
  },
});
</script>
<template>
  <div v-if="daysSinceStale(date) !== false" class="relative self-end -mt-2">
    <x-tooltip :placement="align">
      <p class="bg-red-600 px-1 rounded text-[10px] text-white">
        {{ daysSinceStale(date) }}
      </p>
      <template #tooltip>
        <div class="max-w-[160px] text-xs">
          <span>Days elapsed since the <br />lead was flagged as stale</span>
        </div>
      </template>
    </x-tooltip>
  </div>
</template>
