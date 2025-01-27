<script setup>
defineProps({
  label: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div>
    <h4 class="text-gray-700 text-sm font-medium mb-1" v-bind="$attrs">
      {{ label }} <sup v-if="required" class="text-red-500">*</sup>
    </h4>
    <slot />
  </div>
</template>
