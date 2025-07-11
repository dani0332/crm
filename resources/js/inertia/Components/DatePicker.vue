<script setup>
import _ from 'lodash';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  modelValue: {
    type: [String, Array, Date, Object, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  withTime: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
  },
  helper: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  rules: {
    type: Array,
    default: () => [],
  },
  tooltip: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Select date',
  },
  hideFooter: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  range: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
});

const selectedData = computed({
  get() { return props.modelValue; },
  set(newValue) { emit('update:modelValue', newValue); },
});

const iconPosition = computed(() => {
  return props.label ? '2.75rem' : '54%';
});

  function isValidDateFormat(input) {
    if (!_.isString(input) || _.isEmpty(input)) return false;

    // Regex for dd/MM/yyyy or dd/MM/yyyy HH:mm
    const dateRegex = props.withTime
      ? /^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/
      : /^(\d{2})\/(\d{2})\/(\d{4})$/;

    if (!dateRegex.test(input)) return false;

    const [, day, month, year, hours, minutes] = input.match(dateRegex) || [];
    const dateString = `${year}-${month}-${day}${props.withTime ? `T${hours}:${minutes}:00` : ''}`;
    const date = new Date(dateString);

    const isValid = !isNaN(date) &&
      date.getDate() === parseInt(day) &&
      date.getMonth() + 1 === parseInt(month) &&
      date.getFullYear() === parseInt(year);

    if (props.withTime) {
      return isValid &&
        date.getHours() === parseInt(hours) &&
        date.getMinutes() === parseInt(minutes) &&
        parseInt(hours) < 24 &&
        parseInt(minutes) < 60;
    }

    return isValid;
  }

  function isValidDateRangeFormat(input) {
    if (!_.isString(input) || _.isEmpty(input)) return false;

    // Regex for dd/MM/yyyy - dd/MM/yyyy or dd/MM/yyyy HH:mm - dd/MM/yyyy HH:mm
    const rangeDateRegex = props.withTime
      ? /^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})\s*-\s*(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/
      : /^(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2})\/(\d{2})\/(\d{4})$/;

    if (!rangeDateRegex.test(input)) return false;

    // Split into first and second dates
    const [firstDate, secondDate] = input.split(/\s*-\s*/);

    // Validate both dates using isValidDateFormat
    return isValidDateFormat(firstDate) && isValidDateFormat(secondDate);
  }
</script>
<template>
  <x-datepicker
    v-model="selectedData"
    :format="props.withTime ? `dd/MM/yyyy HH:mm` : `dd/MM/yyyy`"
    :enable-time-picker="props.withTime"
    :month-change-on-scroll="false"
    :is-24="false"
    utc="preserve"
    :disabled="props.disabled"
    position="left"
    class="w-full"
    auto-apply
    :clearable="!props.disabled"
    :range="range"
    text-input
  >
    <template #dp-input="{ value, onEnter, onTab, onBlur, onInput, onPaste }">
      <x-input
        :model-value="value"
        :label="props.label"
        :size="props.size"
        :disabled="props.disabled"
        :helper="props.helper"
        :icon-right="props.disabled ? null : value ? 'clear' : 'calendar'"
        :loading="props.loading"
        :rules="props.rules"
        :tooltip="props.tooltip"
        :placeholder="props.placeholder"
        :hide-footer="props.hideFooter"
        @update:modelValue="($event) => {
          console.log('onInput:', $event);
          console.log('range:', range);
          if (props.range ? isValidDateRangeFormat($event) : isValidDateFormat($event)) {
            onInput($event);
          }else{
            console.error('Invalid Date');
          }
        }"
        @keydown.enter.prevent="($event) => {
          console.log('onEnter:', value);
          if (props.range ? isValidDateRangeFormat(value) : isValidDateFormat(value)) {
            onEnter($event);
          }
        "
        @blur="
          () => {
            console.log('onBlur:', value);
            onBlur();
          }
        "
        @keydown.tab="onTab"
        @paste="onPaste"
        :error="props.error"
        :required="props.required"
      />
    </template>
  </x-datepicker>
</template>

<style>
:root {
  --dp-font-family: 'Inter', sans-serif;
}

.dp__icon.dp__clear_icon {
  @apply !text-orange-500;
}

.dp__cell_disabled {
  @apply opacity-20;
}
</style>
