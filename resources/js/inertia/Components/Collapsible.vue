<script setup>
const props = defineProps({
  expanded: {
    type: Boolean,
    default: true,
  },
});
const isExpanded = ref(props.expanded);
</script>

<template>
  <div>
    <div @click="isExpanded = !isExpanded" class="cursor-pointer">
      <div class="flex justify-between pl-1 px-3">
        <slot name="header" />
        <x-icon icon="carrot_up" class="mt-2" v-if="isExpanded" />
        <x-icon icon="carrot_down" class="mt-2" v-else />
      </div>
    </div>
    <transition name="fade">
      <div v-if="isExpanded">
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
