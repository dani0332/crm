<script setup>
const props = defineProps({
  filters: Object,
});

const emit = defineEmits(['open', 'selectedFilters']);

const selectedOptions = ref({
  date: 0,
  status: [],
});

function getAdjustedDate(date, { days = 0, months = 0, setDate = null }) {
  const newDate = new Date(date);
  if (days) newDate.setDate(date.getDate() - days);
  if (months) newDate.setMonth(date.getMonth() + months);
  if (setDate !== null) newDate.setDate(setDate);
  return newDate;
}

const today = new Date();
const last7Days = getAdjustedDate(today, { days: 7 });
const last30Days = getAdjustedDate(today, { days: 30 });
const lastMonthStart = getAdjustedDate(today, { months: -1, setDate: 1 });
const lastMonthEnd = getAdjustedDate(today, { setDate: 0 });
const thisMonthStart = getAdjustedDate(today, { setDate: 1 });
const thisMonthEnd = getAdjustedDate(today, { months: 1, setDate: 0 });

const dateOptions = ref([
  { text: 'Today', value: 1 },
  { text: 'Last 7 days', value: 2 },
  { text: 'Last 30 days', value: 3 },
  { text: 'Last month', value: 4 },
  { text: 'This month', value: 5 },
]);

const status = ref([
  {
    text: 'Sales Opportunity',
    value: 1,
    tooltip:
      'This will be the sum of all potential sales we can achieve by closing these leads',
  },
  {
    text: 'Paid Awaiting Documents',
    value: 2,
    tooltip:
      'This means that we have received a payment for this lead however we require additional documents from clients to proceed futher.',
  },
  {
    text: 'Secured Deal',
    value: 3,
    tooltip:
      'Shows leads that have successfully concluded deals with clients. This means that we acquired the complete payment and documents required.',
  },
  {
    text: 'Cold',
    value: 4,
    tooltip:
      'Typically, leads which are overdue on the follow ups will automatically change to Cold as no further action has taken place on them. You can still work on these leads.',
  },
  {
    text: 'Stale',
    value: 5,
    tooltip:
      'Typically, these are the leads where the status has not changed for the last 30 days.',
  },
]);

const selectedFiltersLength = computed(() => {
  // sum filters that are selected from both filters

  return (
    (selectedOptions.value.date !== 0 ? 1 : 0) +
    selectedOptions.value.status.length
  );
});

const handleDateFilter = dateId => {
  let range = [];
  switch (dateId) {
    case 1:
      range = [today, today];
      break;
    case 2:
      range = [last7Days, today];
      break;
    case 3:
      range = [last30Days, today];
      break;
    case 4:
      range = [lastMonthStart, lastMonthEnd];
      break;
    case 5:
      range = [thisMonthStart, thisMonthEnd];
      break;
  }

  selectedOptions.value.date = dateId;

  emit('selectedFilters', {
    created_at_start: range[0],
    created_at_end: range[1],
  });
};

const handleStatusFilter = status => {
  if (selectedOptions.value.status.includes(status)) {
    selectedOptions.value.status = selectedOptions.value.status.filter(
      item => item !== status,
    );
  } else {
    selectedOptions.value.status.push(status);
  }

  emit('selectedFilters', {
    status: selectedOptions.value.status,
  });
};

const openState = e => {};
</script>
<template>
  <div>
    <x-popover align="left" position="bottom" @toggle="openState">
      <x-badge
        color="orange"
        class="mx-2"
        position="top"
        align="top"
        offset-y="-25"
      >
        <template #content> {{ selectedFiltersLength }} </template>
      </x-badge>
      <div class="flex gap-px">
        <x-button size="sm" color="#38bdf8" class="rounded-none rounded-l-lg">
          Filters
        </x-button>
        <x-button
          size="sm"
          color="#38bdf8"
          icon-right="chevronDown"
          class="rounded-none rounded-r-lg"
        ></x-button>
      </div>

      <template #content>
        <div class="w-72 bg-white shadow-lg border z-20 rounded">
          <p class="bg-gray-200 w-full text-xs p-1 font-bold">CHOOSE FILTERS</p>
          <div class="p-2 overflow-x-auto max-h-80">
            <div class="text-gray-400 text-xs font-bold">FILTER BY DATE</div>
            <ul class="space-y-0.5">
              <li
                v-for="option in dateOptions"
                :key="option.text"
                :class="{
                  'bg-primary text-white':
                    selectedOptions.date === option.value,
                }"
                class="px-3 py-1 capitalize text-sm cursor-pointer rounded-sm transition hover:bg-primary hover:text-white"
                @click="handleDateFilter(option.value)"
              >
                {{ option.text }}
              </li>
            </ul>
          </div>
          <x-divider></x-divider>
          <div class="p-2 max-h-80">
            <div class="text-gray-400 text-xs font-bold">FILTER BY STATUS</div>

            <ul class="space-y-0.5">
              <li
                v-for="option in status"
                :key="option.text"
                :class="{
                  'bg-primary text-white': selectedOptions.status.includes(
                    option.value,
                  ),
                }"
                class="px-3 py-1 capitalize text-sm cursor-pointer rounded-sm transition hover:bg-primary hover:text-white"
                :title="option.tooltip"
                @click="handleStatusFilter(option.value)"
              >
                <span>{{ option.text }}</span>
              </li>
            </ul>
          </div>
        </div>
      </template>
    </x-popover>
  </div>
</template>
