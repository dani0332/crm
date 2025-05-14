<script setup>
const emit = defineEmits(['select-all', 'clear']);

const onSelectAll = () => {
  emit('select-all');
};

const onClear = () => {
  emit('clear');
};
</script>

<template>
  <x-divider class="my-1" />
  <div class="flex justify-between gap-1 p-1">
    <x-button
      color="success"
      icon="true"
      size="xs"
      outlined
      @click="onSelectAll"
    >
      Select all
    </x-button>
    <x-button color="red" icon="false" size="xs" outlined @click="onClear">
      Clear
    </x-button>
  </div>
</template>
