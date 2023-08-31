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
    state.value = props.isActive;
  },
  {
    immediate: true,
  },
);
</script>

<template>
  <x-toggle v-model="state" color="emerald" @update:model-value="onUpdate" />
</template>
