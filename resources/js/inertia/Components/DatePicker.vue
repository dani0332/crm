<script setup>
import { computed, ref } from 'vue';
import VueTailwindDatepicker from 'vue-tailwind-datepicker';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  label: {
    required: true,
    type: String,
  },
  modelValue: {
    type: [String, Array, Object],
    default: '',
  },
  single: {
    type: Boolean,
    default: true,
  },
});

const formatter = ref({
  date: 'DD-MM-YYYY',
  month: 'MMM',
});

const selectedData = computed({
  get() {
    return props.modelValue;
  },
  set(newValue) {
    emit('update:modelValue', newValue);
    return;
  },
});
</script>
<template>
  <label
    class="relative x-input inline-block align-bottom text-left mb-3 w-full"
  >
    <p class="font-medium text-gray-800 dark:text-gray-200 mb-1">
      {{ props.label }}
    </p>
    <VueTailwindDatepicker
      v-model="selectedData"
      input-classes="appearance-none block w-full placeholder-gray-400 dark:placeholder-gray-500 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 dark:border-gray-700 border shadow-sm rounded-md hover:border-gray-400 dark:hover:border-gray-500 px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-sky-400"
      :as-single="single"
      :formatter="formatter"
    ></VueTailwindDatepicker>
  </label>
</template>
