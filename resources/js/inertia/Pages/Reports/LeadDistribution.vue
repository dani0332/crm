<script setup>
defineProps({
  reportData: Object,
  filterOptions: Object,
  defaultFilters: Object,
});
const loaders = reactive({
  table: false,
});
const page = usePage();

const params = useUrlSearchParams('history');
const tableHeader = [
  {
    text: 'Tier Name',
    value: 'tier_name',
  },
  {
    text: 'Received Leads',
    value: 'received_leads',
  },
  {
    text: 'LEADS CREATED',
    value: 'lead_created',
  },
  {
    text: 'TOTAL LEADS',
    value: 'total_leads',
  },
  {
    text: 'AUTO ASSIGNED',
    value: 'auto_assigned',
  },
  {
    text: 'MANUALLY ASSIGNED',
    value: 'manually_assigned',
  },
];

const filters = reactive({
  advisorAssignedDates: [],
  tiers: [],
  page: 1,
});

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    router.visit('/reports/lead-distribution', {
      method: 'get',
      data: cleanFilters(filters),
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loaders.table = true),
      onFinish: () => {
        loaders.table = false;
      },
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  router.visit('/reports/lead-distribution', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const cleanFilters = filters => {
  Object.keys(filters).forEach(
    key => (filters[key] === '' || filters[key] == null) && delete filters[key],
  );
  return filters;
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
  if (page.props.defaultFilters) {
    filters.advisorAssignedDates =
      page.props.defaultFilters.advisorAssignedDates;
  }
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Lead Distribution Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Lead Distribution Report
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <DatePicker
          v-model="filters.advisorAssignedDates"
          label="Advisor Assigned Date"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
        />
        <ComboBox
          v-model="filters.tiers"
          label="Tiers"
          placeholder="Search by Tiers"
          :options="
            Object.keys(filterOptions.tiers).map(key => ({
              value: key,
              label: filterOptions.tiers[key],
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
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-advisor_name="item">
        <span class="font-bold"> {{ item.advisor_name }} </span>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: reportData.next_page_url,
        prev: reportData.prev_page_url,
        current: reportData.current_page,
        from: reportData.from,
        to: reportData.to,
        total: reportData.total,
        last: reportData.last_page,
      }"
    />
  </div>
</template>
