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

const iconPosition = computed(() => {
  return props.label ? '2.75rem' : '54%';
});
</script>
<template>
  <div class="relative">
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
      text-input
      :range="range"
    >
      <template #dp-input="{ value, onEnter, onTab, onBlur, onInput }">
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
          @keydown.tab="onTab"
          @update:modelValue="onInput"
          @blur="onBlur"
          @keydown.enter.prevent="onEnter"
          :error="props.error"
        />
      </template>
    </x-datepicker>
  </div>
</template>

<style>
:root {
  --dp-font-family: 'Inter', sans-serif;
}

.dp__icon.dp__clear_icon {
  @apply !text-orange-500;
  top: v-bind(iconPosition) !important;
}

.dp__cell_disabled {
  @apply opacity-20;
}

.fixed-datepicker .dp__arrow_bottom {
  @apply top-0;
  transform: translate(-50%, -50%) rotate(-45deg);
}

.fixed-datepicker .dp__outer_menu_wrap.dp--menu-wrapper {
  @apply top-12 !important;
}
</style>
