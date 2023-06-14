<script setup>
defineProps({
  reportData: Object,
  quoteTypes: Array,
});
const loader = reactive({
  table: false,
});

const page = usePage();

let availableFilters = {
  date_range: [],
  quote_type_id: '',
  group_by_one: '',
  group_by_two: '',

  page: 1,
};
const { isRequired, isEmail } = useRules();
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
  if (isValid) {
    filters.page = 1;

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
    data: { page: 1 },
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
        <x-select
          v-model="filters.quote_type_id"
          label=" Quote Type"
          :rules="[isRequired]"
          placeholder="Search by Quote Type"
          :options="quoteTypesOptions"
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
      :items="reportData.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>

    <Pagination
      :links="{
        next: reportData.next_page_url,
        prev: reportData.prev_page_url,
        current: reportData.current_page,
        from: reportData.from,
        to: reportData.to,
      }"
    />
  </div>
</template>
