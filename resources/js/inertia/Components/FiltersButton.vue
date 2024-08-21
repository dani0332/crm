<script setup>
const props = defineProps({
  isShown: {
    type: Boolean,
    default: true,
  },
  filters: Object,
  filtersCount: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['toggleFilters', 'selectedFilters']);

const page = usePage();

const toggle = ref(props.isShown);

const selectedOptions = ref({
  date: 0,
  status: [],
});

const [
  today,
  last7Days,
  last30Days,
  lastMonthStart,
  lastMonthEnd,
  thisMonthStart,
  thisMonthEnd,
] = useDateRange();

const dateOptions = ref([
  { text: 'Today', value: 1 },
  { text: 'Last 7 days', value: 2 },
  { text: 'Last 30 days', value: 3 },
  { text: 'Last month', value: 4 },
  { text: 'This month', value: 5 },
]);

const statuses = ref([
  {
    text: 'Sales Opportunity',
    value: 1,
    quoteCodes: ['Application Pending'],
    paymentIds: [
      page.props.paymentStatusEnum?.NEW,
      page.props.paymentStatusEnum?.CREDIT_APPROVED,
      page.props.paymentStatusEnum?.DECLINED,
    ],
    tooltip:
      'This will be the sum of all potential sales we can achieve by closing these leads',
  },
  {
    text: 'Paid Awaiting Documents',
    value: 2,
    quoteCodes: ['Missing Documents Requested'],
    paymentIds: [
      page.props.paymentStatusEnum?.PENDING,
      page.props.paymentStatusEnum?.PAID,
    ],
    tooltip:
      'This means that we have received a payment for this lead however we require additional documents from clients to proceed futher.',
  },
  {
    text: 'Secured Deal',
    value: 3,
    quoteCodes: [
      'Transaction Approved',
      'Policy Documents Pending',
      'Policy Issued',
      'Policy Booked',
      'Policy Sent to Customer',
    ],
    tooltip:
      'Shows leads that have successfully concluded deals with clients. This means that we acquired the complete payment and documents required.',
  },
  {
    text: 'Cold',
    value: 4,
    quoteCodes: ['Cold'],
    tooltip:
      'Typically, leads which are overdue on the follow ups will automatically change to Cold as no further action has taken place on them. You can still work on these leads.',
  },
  {
    text: 'Stale',
    value: 5,
    quoteCodes: ['Stale'],
    tooltip:
      'Typically, these are the leads where the status has not changed for the last 30 days.',
  },
]);

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

  const selectedQuoteStatusCodes = statuses.value
    .filter(item => selectedOptions.value.status.includes(item.value))
    .map(item => item.quoteCodes)
    .flat();

  const quoteStatusCodes = filterQuoteStatuesByCodes(selectedQuoteStatusCodes);

  const quoteStatusIds = quoteStatusCodes.map(item => item.id);
  const hasPaymentStatus = selectedOptions.value.status.some(
    item => statuses.value[item - 1]?.paymentIds?.length > 0,
  );

  emit('selectedFilters', {
    quote_status: quoteStatusIds,
    ...(hasPaymentStatus && {
      payment_status: statuses.value
        .filter(item => selectedOptions.value.status.includes(item.value))
        .map(item => item.paymentIds)
        .flat(),
    }),
    cold: selectedOptions.value.status.includes(4),
    stale: selectedOptions.value.status.includes(5),
  });
};

const toggleFilter = () => {
  toggle.value = !toggle.value;
  emit('toggleFilters', toggle.value);
};

const filterQuoteStatuesByCodes = codes => {
  if (!codes.length || !page.props.leadStatuses) {
    return [];
  }
  return page.props.leadStatuses.filter(status => codes.includes(status.text));
};

onMounted(() => {
  setTimeout(() => {
    if (props.filters) {
      Object.keys(props.filters).forEach(key => {
        if (key === 'is_cold' && props.filters[key]) {
          console.log('cold');
          selectedOptions.value.status.push(4);
        } else if (key === 'is_stale' && props.filters[key]) {
          selectedOptions.value.status.push(5);
        }
      });
    }
  }, 1000);
});
</script>
<template>
  <div class="flex gap-px">
    <x-popover align="left" position="bottom">
      <x-badge color="orange" align="left" size="sm">
        <x-button size="sm" color="sky" class="rounded-none rounded-l-lg">
          Filters
        </x-button>
        <template #content> {{ filtersCount }} </template>
      </x-badge>

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
                v-for="option in statuses"
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

    <x-tooltip>
      <x-button
        size="sm"
        color="sky"
        class="rounded-l-none rounded-r-lg"
        square
        @click="toggleFilter"
      >
        <x-icon
          icon="chevronDown"
          size="sm"
          class="transition transform duration-300"
          :class="isShown ? '' : 'rotate-180'"
        />
      </x-button>
      <template #tooltip> Show/Hide filter </template>
    </x-tooltip>
  </div>
</template>
