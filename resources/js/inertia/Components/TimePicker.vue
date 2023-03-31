<script setup>
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  label: {
    required: true,
    type: String,
  },
  modelValue: {
    type: [String, Date, Object],
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Select Time',
  },
  single: {
    type: Boolean,
    default: true,
  },
  hasError: {
    type: Boolean,
    default: false,
  },
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
  <VueDatePicker
    v-model="selectedData"
    auto-apply
    :teleport="true"
    :month-change-on-scroll="false"
    :clearable="false"
    :time-picker="true"
  >
    <template #dp-input="{ value, onClear }">
      <x-input
        type="text"
        :value="value"
        :label="props.label"
        :placeholder="placeholder"
        :error="props.hasError ? 'This field is required' : ''"
        class="w-full"
        readonly
      />
      <div class="absolute right-2.5 top-[34px] hover:text-secondary-500">
        <svg
          v-if="!value"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="w-4 h-4"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>

        <svg
          v-else
          @click="onClear"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="w-4 h-4"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </div>
    </template>
  </VueDatePicker>
</template>

<style>
.dp__clear_icon {
  top: 40%;
}
</style>
