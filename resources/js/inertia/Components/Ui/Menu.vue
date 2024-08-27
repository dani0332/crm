<script setup>
import UiMenu from './Menu.vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  collapsible: {
    type: Boolean,
    default: true,
  },
  collapseIcon: String,
  disabled: Boolean,
});

const expanded = items => {
  return items.some(item => item.active);
};
</script>

<template>
  <div v-if="items" class="select-none space-y-1">
    <template v-for="(item, index) in items" :key="index">
      <template v-if="item.items">
        <x-accordion-item
          v-if="item.collapsible !== false"
          :icon="item.collapseIcon || collapseIcon"
          :expanded="expanded(item.items)"
          :disabled="disabled || item.disabled"
          :show-icon="!!(item.collapseIcon || collapseIcon)"
          class="space-y-1 text-white"
        >
          <template #default="{}">
            <ui-menu-item
              :item="item"
              :disabled="disabled || item.disabled"
              class="font-medium"
            />
          </template>
          <template #content="{ expand }">
            <ui-menu
              class="ml-4 border-l border-secondary-700 pl-1"
              :items="item.items"
              :collapsible="collapsible"
              :collapse-icon="item.collapseIcon || collapseIcon"
              :disabled="disabled || item.disabled"
            />
          </template>
        </x-accordion-item>
        <template v-else>
          <ui-menu-item
            :item="item"
            :disabled="disabled || item.disabled"
            class="font-medium"
            inactive
          />
          <ui-menu
            class="ml-4 space-y-1 border-l border-secondary-600 pl-1"
            :items="item.items"
            :collapsible="collapsible"
            :collapse-icon="item.collapseIcon || collapseIcon"
            :disabled="disabled || item.disabled"
          />
        </template>
      </template>
      <template v-else>
        <x-divider v-if="item.divider" />
        <ui-menu-item
          v-else
          :item="item"
          :disabled="disabled || item.disabled"
          :class="{ 'my-2': item.divider }"
        />
      </template>
    </template>
  </div>
</template>
