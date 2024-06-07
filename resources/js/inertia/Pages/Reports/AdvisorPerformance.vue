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
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;

const params = useUrlSearchParams('history');
const tableHeader = [
  {
    text: 'Advisor Name',
    value: 'advisor_name',
  },
  {
    text: 'CREATED MANUALLY',
    value: 'manual_created',
  },
  {
    text: 'AUTO ASSIGNED',
    value: 'auto_assigned',
  },
  {
    text: 'MANUALLY ASSIGNED',
    value: 'manually_assigned',
  },
  {
    text: 'TOTAL LEADS',
    value: 'total_leads',
  },
  {
    text: 'VIEW COUNT',
    value: 'view_count',
  },
  {
    text: 'NI',
    value: 'not_interested',
  },
  {
    text: 'IN PROGRESS',
    value: 'in_progress',
  },
  {
    text: 'BAD LEAD',
    value: 'bad_leads',
  },
  {
    text: 'SALE',
    value: 'sale_leads',
  },
];

const filters = reactive({
  advisorAssignedDates: [],
  tiers: [],
  assignmentTypes: 'All',
  teams: [],
  segment_filter: 'all',
  isCommercial: 'All',
  page: 1,
});

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
};

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    router.visit('/reports/advisor-performance', {
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
  if (page.props.defaultFilters) {
    filters.advisorAssignedDates =
      page.props.defaultFilters.advisorAssignedDates;
  }
  router.visit('/reports/advisor-performance', {
    method: 'get',
    data: {
      advisorAssignedDates: filters.advisorAssignedDates,
      page: 1,
    },
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
    <Head title="Advisor Performance Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Performance Report
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
        <ComboBox
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="
            Object.keys(filterOptions.teams).map(key => ({
              value: key,
              label: filterOptions.teams[key],
            }))
          "
        />
        <ComboBox
          v-model="filters.leadSources"
          label="Lead Source"
          placeholder="Search by Lead Source"
          :options="
            Object.keys(filterOptions.leadSources).map(key => ({
              value: key,
              label: filterOptions.leadSources[key],
            }))
          "
          :max-limit="3"
        />
        <x-select
          v-model="filters.isCommercial"
          label="Commercial"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />
        <x-select
          v-model="filters.assignmentTypes"
          label="Assignment Type"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: 1, label: 'System Assigned' },
            { value: 2, label: 'System ReAssigned' },
            { value: 3, label: 'Manual Assigned' },
            { value: 4, label: 'Manual ReAssigned' },
          ]"
        />
        <ComboBox
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="quoteSegments"
          :single="true"
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
      <template #body-append>
        <tr v-if="reportData.data.length > 0" class="total-row">
          <td class="direction-left">Total</td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'manual_created') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'auto_assigned') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'manually_assigned') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'total_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'view_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'not_interested') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'in_progress') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'bad_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'sale_leads') }}
          </td>
        </tr>
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
