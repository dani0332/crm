<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  tooltip: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  class: {
    type: String,
    default: 'w-2/6',
  },
});
</script>

<template>
  <div>
    <div :class="class">
      <span class="relative group text-sm">
        <span class="border-b-2 border-dotted border-black text-sm"
          >{{ title }} </span
        ><sup v-if="required" class="text-red-500 ml-1">*</sup>
        <div
          class="absolute text-left hidden group-hover:block transform transition-transform z-40 h-fit _popoverContent_1wc81_3 top-full bottom-0 _popoverBottom_1wc81_14 left-1/2 right-full -translate-x-1/2 max-w-xs"
        >
          <div class="dark">
            <div
              class="x-popover-container block w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-200 dark:border-gray-800 p-2 text-white text-sm w-max max-w-xs"
            >
              <span data-v-d0063695>
                {{ tooltip }}
              </span>
            </div>
          </div>
        </div>
      </span>
    </div>
  </div>
</template>
