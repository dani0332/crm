<script setup>
const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

const advisorOptions = ref([]);

const params = useUrlSearchParams('history');
const cleanObj = obj => useCleanObj(obj);
const serverOptions = ref({
  page: 1,
  sortBy: 'team',
  sortType: 'asc',
});

const filters = reactive({
  date: null,
  lob: 'Health',
  teams: null,
  advisors: null,
  filter_by: null,
});

const healthHeaders = ref([
  {
    text: 'RENEWAL TERMS RECEIVED',
    value: 'renewal_terms_recevied',
    is_active: true,
    sortable: true,
  },
  {
    text: 'APPLICATION PENDING',
    value: 'application_pending',
    is_active: true,
    sortable: true,
  },

  {
    text: 'APPLICATION SUBMITTED',
    value: 'application_submitted',
    is_active: true,
    sortable: true,
  },
  {
    text: 'MISSING DOCUMENTS',
    value: 'missing_documents',
    is_active: true,
    sortable: true,
  },
]);

const corplineHeaders = ref([
  {
    text: 'RENEWAL TERMS RECEIVED',
    value: 'renewal_terms_recevied',
    is_active: true,
    sortable: true,
  },
  {
    text: 'APPLICATION PENDING',
    value: 'application_pending',
    is_active: true,
    sortable: true,
  },

  {
    text: 'APPLICATION SUBMITTED',
    value: 'application_submitted',
    is_active: true,
    sortable: true,
  },
  {
    text: 'MISSING DOCUMENTS',
    value: 'missing_documents',
    is_active: true,
    sortable: true,
  },
]);

const commonHeaders = ref([
  {
    text: 'TEAM',
    value: 'team',
    width: 160,
    is_active: true,
    sortable: true,
  },
  {
    text: 'NEW LEAD',
    value: 'new_lead',
    is_active: true,
    sortable: true,
  },
  {
    text: 'ALLOCATED',
    value: 'allocated',
    is_active: true,
    sortable: true,
  },
  {
    text: 'QUOTED',
    value: 'quoted',
    is_active: true,
    sortable: true,
  },
  {
    text: 'FOLLOWED UP',
    value: 'followed_up',
    is_active: true,
    sortable: true,
  },
  {
    text: 'IN NEGOTIATION',
    value: 'in_negotiation',
    is_active: true,
    sortable: true,
  },
  {
    text: 'PAYMENT PENDING',
    value: 'payment_pending',
    is_active: true,
    sortable: true,
  },
]);

const tableHeader = ref(commonHeaders.value);

const teams = ref([
  { value: 47, label: 'Renewal' },
  { value: 42, label: 'New Bussiness' },
  { value: 21, label: 'EBP' },
  { value: 19, label: 'Speed' },
]);

const filteredTeams = computed(() => {
  if (filters.lob != 'Health') return teams.value.slice(0, 2);
  else return teams.value.filter(team => team.value !== 'All');
});

const onSubmit = isValid => {
  if (!isValid) return;
  serverOptions.value.page = 1;

  const filtersCleaned = cleanObj(filters);

  router.visit(route('pipeline-report'), {
    method: 'get',
    data: {
      ...filtersCleaned,
      ...serverOptions.value,
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => changeLob(),
    onFinish: () => (loaders.table = false),
  });
};

function onReset() {
  router.visit(route('pipeline-report'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const onTeamChange = e => {
  if (e.length == 0) {
    filters.teams = [];
    filters.advisors = [];
    advisorOptions.value = [];

    return;
  }

  loaders.advisorOptions = true;

  axios
    .post(route('fetch-advisors-by-team'), {
      teamIds: Array.isArray(e) ? e : [e],
    })
    .then(res => {
      if (res.data.advisors.length > 0) {
        advisorOptions.value = Object.keys(res.data.advisors).map(key => ({
          value: res.data.advisors[key].id,
          label: res.data.advisors[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

const [
  today,
  last7Days,
  last30Days,
  lastMonthStart,
  lastMonthEnd,
  thisMonthStart,
  thisMonthEnd,
] = useDateRange();

const presetDates = [
  {
    label: 'Today',
    value: [today, today],
  },
  {
    label: 'Last 7 days',
    value: [last7Days, today],
  },
  {
    label: 'Last 30 days',
    value: [last30Days, today],
  },
  {
    label: 'Last month',
    value: [lastMonthStart, lastMonthEnd],
  },
  {
    label: 'This month',
    value: [thisMonthStart, thisMonthEnd],
  },
];

function changeLob() {
  let specificHeaders = [];
  if (filters.lob === 'Health') {
    specificHeaders = healthHeaders.value;
  } else if (filters.lob === 'Corpline') {
    specificHeaders = corplineHeaders.value;
  }
  tableHeader.value = [...commonHeaders.value, ...specificHeaders];
}

const getTotal = item => {
  let sum = 0;

  Object.values(item).forEach(value => {
    value = parseFloat(value);
    if (!isNaN(value) && value > 0) {
      sum += value;
    }
  });
  return sum;
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
  changeLob();
});

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) onSubmit(true);
  },
);
</script>
<template>
  <Head title="Pipeline Report" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Pipeline Report
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
      <x-field label="Date Range" required>
        <DatePicker
          v-model="filters.date"
          range
          :max-range="365"
          size="sm"
          placeholder="Select Date (default last 30 days)"
          model-type="yyyy-MM-dd"
          :preset-dates="presetDates"
        />
      </x-field>
      <x-field label="Line Of Bussiness">
        <x-select
          v-model="filters.lob"
          placeholder="Search by Bussiness"
          :options="[
            { value: 'Health', label: 'Health' },
            { value: 'Pet', label: 'Pet' },
            { value: 'Cycle', label: 'Cycle' },
            { value: 'Home', label: 'Home' },
            { value: 'Corpline', label: 'Corpline' },
          ]"
          class="w-full"
          @update:modelValue="filters.teams = null"
        />
      </x-field>
      <x-field label="Teams">
        <x-select
          v-model="filters.teams"
          placeholder="Select Team"
          :options="filteredTeams"
          :loading="loaders.advisorOptions"
          class="w-full"
          @update:modelValue="onTeamChange($event)"
        />
      </x-field>
      <x-field
        :label="
          !filters.teams || filters.teams.length == 0
            ? `Advisors (select teams first)`
            : `Advisors`
        "
      >
        <ComboBox
          v-model="filters.advisors"
          placeholder="Search by Advisor Name"
          :options="advisorOptions"
          :select-all="filters.advisors?.length > 0"
          :deselect-all="filters.advisors?.length > 0"
          class="w-full"
          :loading="loaders.advisorOptions"
        />
      </x-field>
      <x-field label="Filter By">
        <x-select
          v-model="filters.filter_by"
          placeholder="Filter By"
          :options="[
            { value: 'total_leads', label: 'Total Leads' },
            { value: 'total_opportunity', label: 'Total Opportunity' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field
        v-if="filters.lob == 'corpline'"
        label="Bussiness Insurance Type"
      >
        <ComboBox
          placeholder="Search by Bussiness Insurance Type"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="flex gap-3 justify-end items-center">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    v-model:server-options="serverOptions"
    table-class-name="lining-nums mt-4"
    :loading="loaders.table"
    :headers="[
      ...tableHeader,
      {
        text: 'TOTAL',
        value: 'total',
      },
    ]"
    :items="props.reportData.data || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
    <template #item-total="item">
      <strong>{{ getTotal(item) }}</strong>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  />
</template>
