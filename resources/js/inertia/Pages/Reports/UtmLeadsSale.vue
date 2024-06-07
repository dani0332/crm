<script setup>
defineProps({
  reportData: Object,
  quoteTypes: Array,
});
const loader = reactive({
  table: false,
});

const page = usePage();
const isQuoteTypeEmpty = ref(false);

let availableFilters = {
  date_range: [],
  quote_type_id: '',
  group_by_one: '',
  group_by_two: '',
};
const { isRequired } = useRules();
const filters = reactive(availableFilters);

const quoteTypesOptions = computed(() => {
  return page.props.quoteTypes.map(method => ({
    value: `${method.id}`,
    label: method.text,
  }));
});
const params = useUrlSearchParams('history');
const tableHeader = [
  {
    text: 'UTM Source',
    value: 'utm_source',
  },
  {
    text: 'UTM Medium',
    value: 'utm_medium',
  },
  {
    text: 'UTM Campaigns',
    value: 'utm_campaign',
  },
  {
    text: 'Leads',
    value: 'leads_count',
  },
  {
    text: 'Authorized',
    value: 'authorized',
  },
  {
    text: 'Captured',
    value: 'captured',
  },
  {
    text: 'Authorized (AED)',
    value: 'authorized_sum',
  },
  {
    text: 'Captured (AED)',
    value: 'captured_sum',
  },
];

function onSubmit(isValid) {
  isQuoteTypeEmpty.value = !filters.quote_type_id;

  if (isValid && filters.quote_type_id) {
    Object.keys(filters).forEach(
      key => filters[key] === '' && delete filters[key],
    );
    router.visit('/reports/utm-report', {
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
  router.visit('/reports/utm-report', {
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
}
onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Utm Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      UTM Report
    </h1>

    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <DatePicker
          v-model="filters.date_range"
          label="Date Range"
          :rules="[isRequired]"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
          max-range="30"
        />
        <ComboBox
          v-model="filters.quote_type_id"
          label="Quote Type"
          placeholder="Search by Quote Type"
          :options="quoteTypesOptions"
          :single="true"
          :hasError="isQuoteTypeEmpty"
        />
        <x-select
          v-model="filters.group_by_one"
          label="Group by One"
          placeholder="Group By One"
          :rules="[isRequired]"
          :options="[
            { value: 'utm_source', label: 'UTM Sources' },
            { value: 'utm_medium', label: 'UTM Medium' },
            { value: 'utm_campaign', label: 'UTM Campaign' },
          ]"
        />
        <x-select
          v-model="filters.group_by_two"
          label="Group by Two"
          placeholder="Group By Two"
          :options="[
            { value: 'utm_source', label: 'UTM Sources' },
            { value: 'utm_medium', label: 'UTM Medium' },
            { value: 'utm_campaign', label: 'UTM Campaign' },
          ]"
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
