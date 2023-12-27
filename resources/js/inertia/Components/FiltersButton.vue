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
  { text: 'Sales oppertunity', value: '' },
  { text: 'Paid awaiting documents', value: '' },
  { text: 'Secured deal', value: '' },
  { text: 'Cold', value: '' },
  { text: 'Stale', value: '' },
]);
</script>
<template>
  <div>
    <x-popover align="left">
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
          <div class="p-2 overflow-x-auto max-h-80">
            <header class="uppercase text-gray-400 text-sm font-bold">
              FILTER BY STATUS
            </header>
            <ul class="">
              <li
                class="px-3 capitalize my-1 text-sm cursor-pointer hover:bg-primary hover:text-white"
                v-for="column in status"
                :key="column.text"
              >
                {{ column.text }}
              </li>
            </ul>
          </div>
        </div>
      </template>
    </x-popover>
  </div>
</template>