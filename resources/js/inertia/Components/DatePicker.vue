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

const currentTz = computed(() => {
  return Intl.DateTimeFormat().resolvedOptions().timeZone;
});
</script>
<template>
  <x-datepicker
    v-model="selectedData"
    :label="props.label"
    :format="props.withTime ? `dd/MM/yyyy HH:mm` : `dd/MM/yyyy`"
    :teleport="!props.disabled"
    :enable-time-picker="props.withTime"
    :month-change-on-scroll="false"
    :is-24="false"
    :disabled="props.disabled"
    position="left"
    class="w-full"
    auto-apply
    :clearable="!props.disabled"
    text-input
  >
    <template v-if="props.withTime" #action-extra>
      <span class="pb-1 text-xs">Timezone: {{ currentTz }}</span>
    </template>
  </x-datepicker>
</template>

<style>
:root {
  --dp-font-family: 'Inter', sans-serif;
}

.dp__icon.dp__clear_icon {
  top: v-bind(iconPosition) !important;
  @apply !text-orange-500;
}

.dp__cell_disabled {
  @apply opacity-20;
}
</style>
