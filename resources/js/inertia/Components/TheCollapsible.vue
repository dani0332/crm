<script setup>
defineProps({
  expanded: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['update:expanded']);
</script>

<template>
  <div>
    <div @click="$emit('update:expanded', !expanded)" class="cursor-pointer">
      <div class="flex justify-between pl-1 px-3">
        <slot name="header" />
        <x-icon icon="carrot_up" class="mt-2" v-if="expanded" />
        <x-icon icon="carrot_down" class="mt-2" v-else />
      </div>
    </div>
    <transition name="fade">
      <div v-if="expanded">
        <slot name="body" />
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}
</style>
