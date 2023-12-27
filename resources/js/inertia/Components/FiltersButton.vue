<script setup>
const emit = defineEmits(['open']);
const dates = ref([
  { text: 'Today', value: '' },
  { text: 'Last 7 days', value: '' },
  { text: 'Last 30 days', value: '' },
  { text: 'Last month', value: '' },
  { text: 'This month', value: '' },
  { text: 'Next month', value: '' },
]);

const status = ref([
  {
    text: 'Sales oppertunity',
    value: '',
    tooltip:
      'This will be the sum of all potential sales we can achieve by closing these leads',
  },
  {
    text: 'Paid awaiting documents',
    value: '',
    tooltip:
      'This means that we have received a payment for this lead however we require additional documents from clients to proceed futher.',
  },
  {
    text: 'Secured deal',
    value: '',
    tooltip:
      'Shows leads that have successfully concluded deals with clients. This means that we acquired the complete payment and documents required.',
  },
  {
    text: 'Cold',
    value: '',
    tooltip:
      'Typically, leads which are overdue on the follow ups will automatically change to Cold as no further action has taken place on them. You can still work on these leads.',
  },
  {
    text: 'Stale',
    value: '',
    tooltip:
      'Typically, these are the leads where the status has not changed for the last 30 days.',
  },
]);

const selectedFiltersLength = computed(() => {
  // sum filters that are selected from both filters
  return 7;
});
</script>
<template>
  <div>
    <x-popover align="left">
      <x-badge
        color="orange"
        class="mx-2"
        position="top"
        align="top"
        offset-y="-25"
      >
        <template #content> {{ selectedFiltersLength }} </template>
      </x-badge>
      <div class="flex gap-[1px]">
        <x-button color="#38bdf8" class="rounded-none rounded-l-lg"
          >Filters
        </x-button>
        <x-button
          color="#38bdf8"
          icon-right="chevronDown"
          class="rounded-none rounded-r-lg"
        ></x-button>
      </div>

      <template #content>
        <div class="w-80 bg-white border z-40 rounded">
          <p class="bg-gray-200 w-full text-xs p-1 font-bold">CHOOSE FILTERS</p>
          <div class="p-2 overflow-x-auto max-h-80">
            <header class="uppercase text-gray-400 text-sm font-bold">
              FILTER BY DATE
            </header>
            <ul class="">
              <li
                class="px-3 capitalize my-1 text-sm cursor-pointer hover:bg-primary hover:text-white"
                v-for="column in dates"
                :key="column.text"
              >
                {{ column.text }}
              </li>
            </ul>
          </div>
          <x-divider></x-divider>
          <div class="p-2 max-h-80">
            <header class="uppercase text-gray-400 text-sm font-bold">
              FILTER BY STATUS
            </header>

            <ul class="">
              <li
                class="px-3 capitalize my-1 text-sm cursor-pointer hover:bg-primary hover:text-white"
                v-for="column in status"
                :key="column.text"
                :title="column.tooltip"
              >
                <span
                  @mouseenter="column.isHover = true"
                  @mouseleave="column.isHover = false"
                  >{{ column.text }}</span
                >
                <x-tooltip
                  class="hidden"
                  :class="{ 'hover:inline': column.isHover }"
                >
                  <template #tooltip>
                    <span>{{ column.tooltip }}</span>
                  </template>
                </x-tooltip>
              </li>
            </ul>
          </div>
        </div>
      </template>
    </x-popover>
  </div>
</template>