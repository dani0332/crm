<script setup>
defineProps({
  header: {
    type: Object,
    required: true,
  },
});
</script>
<template>
  <div class="customize-header underline">
    <x-tooltip position="bottom end" class="arrow-t underline">
      <span
        class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
      >
        {{ header.text }}
      </span>
      <template #tooltip>
        <div class="whitespace-normal normal-case">
          {{ header.tooltip }}
        </div>
      </template>
    </x-tooltip>
  </div>
</template>
