<script setup>
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  label: {
    type: String,
    default: '',
  },
  modelValue: {
    type: [String, Date, Array, Object],
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Select Date',
  },
  single: {
    type: Boolean,
    default: true,
  },
  withTime: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  rules: {
    type: Array,
    default: () => [],
  },
  customError: {
    type: String,
    default: '',
  },
  minDate: {
    type: Date,
    default: null,
  },
  size: {
    type: String,
    default: 'md',
  },
  onlySelect: {
    type: Boolean,
    default: false,
  },
  maxDate: {
    type: Date,
    default: null,
  },
  monthPicker: {
    type: Boolean,
    default: false,
  },
  disableYear: {
    type: Boolean,
    default: false,
  },
  noMargin: {
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

const monthPicker = computed(() => {
  return props.monthPicker;
});
const onlyCurentYear = () => {
  if (props.disableYear && props.monthPicker)
    return [new Date().getFullYear(), new Date().getFullYear()];
  else return [1900, 2100];
};
</script>
<template>
  <VueDatePicker
    v-model="selectedData"
    auto-apply
    :format="
      props.withTime
        ? 'dd-MM-yyyy HH:mm'
          ? props.monthPicker
          : 'MM/yyyy'
        : 'dd-MM-yyyy'
    "
    :teleport="true"
    :enable-time-picker="props.withTime"
    :month-change-on-scroll="false"
    :clearable="false"
    :disabled="props.disabled"
    :min-date="props.minDate"
    :max-date="props.maxDate"
    utc="preserve"
    :is-24="false"
    text-input
    :month-picker="monthPicker"
  >
    <template #dp-input="{ value, onClear, onInput, onBlur }">
      <x-input
        type="text"
        :value="value"
        :label="props.label"
        :placeholder="placeholder"
        :disabled="props.disabled"
        :size="props.size"
        :class="props.noMargin ? '!mb-0' : ''"
        class="w-full"
        :rules="value ? [] : props.rules"
        :error="props.customError ? props.customError : ''"
        @update:modelValue="onInput"
        @blur="onBlur"
        :readonly="props.onlySelect"
      />
      <div
        v-if="!props.disabled"
        :class="
          props.label == ''
            ? props.size == 'xs'
              ? 'top-1'
              : 'top-3'
            : 'top-[34px]'
        "
        class="absolute right-2.5 hover:text-secondary-500"
      >
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
            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
          />
        </svg>

        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="w-4 h-4"
          @click="onClear"
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
