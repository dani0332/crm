<script setup>
defineProps({
  isExpanded: {
    type: Boolean,
    required: true,
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg
  },
});

defineEmits(['click']);
</script>

<template>
  <button
    type="button"
    class="text-gray-500 hover:text-gray-700 focus:outline-none"
    @click="$emit('click')"
  >
    <svg
      :class="[
        'transform transition-transform duration-200',
        {
          'h-4 w-4': size === 'sm',
          'h-5 w-5': size === 'md',
          'h-6 w-6': size === 'lg',
          'rotate-90': isExpanded,
        },
      ]"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke-width="2"
      stroke="currentColor"
    >
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
    </svg>
  </button>
</template>
