<script setup>
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
    type: [Boolean, Object],
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  minTime: {
    type: Object,
    default: null,
  },
  maxTime: {
    type: Object,
    default: null,
  },
});

const selectedData = computed({
  get() {
    return props.modelValue;
  },
  set(newValue) {
    emit('update:modelValue', newValue);
  },
});

const iconPosition = computed(() => {
  return props.label ? '2.75rem' : '54%';
});
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
    :clearable="!props.disabled"
    :range="range"
    :min-time="props.minTime"
    :max-time="props.maxTime"
    text-input
    :teleport-center="false"
    teleport="body"
  >
    <!-- auto-apply -->
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
        @update:modelValue="onInput"
        @keydown.enter.prevent="onEnter"
        @blur="onBlur"
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

.dp--clear-btn {
  top: 50% !important;
}
</style>
