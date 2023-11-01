<script setup>
const props = defineProps({
  isActive: {
    type: Number,
    default: 0,
  },
  id: {
    type: [String, Number],
    required: true,
  },
  size: {
    type: String,
    default: 'md',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  refresh: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle']);

const state = ref(false);

const onUpdate = () => {
  emit('toggle', {
    id: props.id,
    active: state.value,
  });
};

watch(
  () => props.isActive,
  () => {
    state.value = props.isActive == 1;
  },
  {
    immediate: true,
  },
);

watch(
  () => props.refresh,
  () => {
    state.value = props.isActive == 1;
  },
);
</script>

<template>
  <x-toggle
    v-model="state"
    color="emerald"
    :size="size"
    :loading="loading"
    @update:model-value="onUpdate"
  />
</template>
