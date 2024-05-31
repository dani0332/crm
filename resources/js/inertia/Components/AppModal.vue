<script setup>
defineOptions({
  inheritAttrs: false,
});

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  showHeader: {
    type: Boolean,
    default: false,
  },
  showClose: {
    type: Boolean,
    default: false,
  },
  actions: {
    type: Boolean,
    default: false,
  },
  backdropClose: {
    type: Boolean,
    default: true,
  },
  width: {
    type: String,
    default: 'md:min-w-[35%] max-w-[70%]',
  },
  hideoverflow: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue']);

const closeModal = () => emit('update:modelValue', !props.backdropClose);
</script>
<template>
  <div
    class="fixed w-full h-full flex justify-center items-center left-0 top-0 z-40"
    v-if="modelValue"
  >
    <div
      @click="closeModal"
      class="fixed inset-0 bg-gray-500 dark:bg-black transition-opacity ease-out duration-200 opacity-30 dark:opacity-70"
    ></div>
    <div
      :class="width"
      class="relative flex flex-col z-10 bg-white dark:bg-gray-900 rounded-md shadow-lg transform transition-all max-h-[80%] ease-out duration-200 opacity-100 translate-y-0 sm:scale-100"
    >
      <header
        class="text-lg font-semibold px-6 py-4 border-b"
        v-if="showHeader"
      >
        <slot name="header"> </slot>
        <div
          v-if="showClose"
          @click.stop="$emit('update:modelValue', false)"
          class="flex absolute p-1 top-4 z-10 right-4 rounded-full bg-opacity-10 hover:bg-opacity-30 cursor-pointer bg-gray-500 text-gray-800 dark:text-gray-300"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="shrink-0 x-icon inline h-5 w-5 stroke-2"
            stroke-linejoin="round"
            stroke-linecap="round"
            stroke="currentColor"
            fill="none"
            viewBox="0 0 24 24"
          >
            <path d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
      </header>
      <main
        v-bind="$attrs"
        class="px-6 py-4"
        :class="{ 'rounded-full': !showHeader, 'overflow-auto': hideoverflow }"
      >
        <slot></slot>
      </main>
      <footer v-if="actions" class="bg-slate-50 dark:bg-gray-800 p-4">
        <slot name="actions" class="font-weight-bold"></slot>
      </footer>
    </div>
  </div>
</template>
