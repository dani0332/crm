<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  item: {
    type: Object,
    default: () => {},
  },
});

const elRef = ref();

const htmlTag = computed(() => {
  if (props.item.target == '_blank') return 'a';
  if (props.item.href) return Link;

  return 'div';
});
</script>

<template>
  <component
    :is="htmlTag"
    ref="elRef"
    :href="item.href"
    :target="item.target"
    :title="item.label"
    class="relative flex min-h-[2rem] cursor-pointer items-center whitespace-nowrap rounded px-3 py-1.5 font-medium text-white transition hover:bg-gray-600"
    :class="{ '!bg-gray-700': item.active }"
  >
    <span v-if="$slots.prefix" class="mr-2 shrink-0">
      <slot name="prefix"></slot>
    </span>
    <x-icon v-else-if="item.icon" :icon="item.icon" class="mr-2" />

    <span class="flex-1 truncate">
      <slot>{{ item.label }}</slot>
    </span>

    <span class="ml-1 shrink-0">
      <x-spinner v-if="item.loading" />
      <template v-else>
        <span v-if="$slots.suffix">
          <slot name="suffix"></slot>
        </span>
        <x-icon v-else-if="item.iconRight" :icon="item.iconRight" />
      </template>
    </span>
  </component>
</template>
