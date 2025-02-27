<script setup>
defineProps({
  header: Object,
});
</script>

<template>
  <x-tooltip placement="top">
    <span class="underline decoration-dotted">{{ header.text }}</span>
    <template #tooltip>
      <span
        class="whitespace-break-spaces !normal-case"
        style="margin-top: 50px"
      >
        {{ header.tooltip }}
      </span>
    </template>
  </x-tooltip>
</template>
