<script setup>
defineProps({
  reportData: Object,
  filterOptions: Object,
});
const loader = reactive({
  table: false,
});

const page = usePage();

let availableFilters = {
  transaction_approved_dates: ref([new Date(), new Date()]),
  quote_type_id: '1',
  teams: [],
  userIds: [],
};
const { isRequired, isEmail } = useRules();
const filters = reactive(availableFilters);
const teamUsers = ref([]);

const quoteTypesOptions = computed(() => {
  return page.props.filterOptions.quoteTypes.map(method => ({
    value: `${method.id}`,
    label: method.text,
  }));
});

const teamOptions = ref([]);

const params = useUrlSearchParams('history');
const tableHeader = [
  {
    text: 'Quote Type',
    value: 'quote_type_name',
  },
  {
    text: 'Transaction Date',
    value: 'transaction_date',
  },
  {
    text: 'Total Premium',
    value: 'total_premium',
  },
];

function onSubmit(isValid) {
  if (isValid) {
    Object.keys(filters).forEach(
      key => filters[key] === '' && delete filters[key],
    );
    router.visit('/reports/total-premium', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      only: ['reportData'],
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}
function onReset() {
  router.visit('/reports/total-premium', {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.replace('[]', '')] = params[key];
    } else {
      filters[key] = params[key];
    }
  }

  if (filters.teams.length > 0) {
    fetchTeamUsers();
  }
}

const fetchTeamUsers = () => {
  axios
    .post('/get-users-by-team', { team_filter: filters.teams })
    .then(response => {
      teamUsers.value = response.data;
    });
};

const fetchTeamsAgainstQuoteType = () => {
  axios
    .post('/get-teams-by-product', { quote_type_id: filters.quote_type_id })
    .then(response => {
      teamOptions.value = response.data.teams;
    });
};

onMounted(() => {
  setQueryStringFilters();
  fetchTeamsAgainstQuoteType();
});
</script>

<template>
  <div>
    <Head title="Total Premium Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Total Premium Report
    </h1>

    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <DatePicker
          v-model="filters.transaction_approved_dates"
          label="Transaction Approved Date"
          :rules="[isRequired]"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
          max-range="30"
        />
        <x-select
          v-model="filters.quote_type_id"
          label="Quote Type"
          :rules="[isRequired]"
          placeholder="Search by Quote Type"
          :options="quoteTypesOptions"
          @update:modelValue="fetchTeamsAgainstQuoteType"
          disabled
        />
        <ComboBox
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="
            teamOptions.map(team => ({
              value: team.id,
              label: team.name,
            }))
          "
          @update:modelValue="fetchTeamUsers"
        />

        <ComboBox
          v-model="filters.userIds"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="
            teamUsers.map(user => ({
              value: user.id,
              label: user.name,
            }))
          "
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>
    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="reportData || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="999"
      hide-footer
    >
    </DataTable>
  </div>
</template>
