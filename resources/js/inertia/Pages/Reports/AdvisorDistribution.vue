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
    text: 'Advisor Name',
    value: 'advisor_name',
  },
  {
    text: 'Total Leads',
    value: 'total_leads',
  },
  {
    text: 'TIER 0',
    value: 'tier_0_lead_count',
  },
  {
    text: 'TIER 1',
    value: 'tier_1_lead_count',
  },
  {
    text: 'TIER 2',
    value: 'tier_2_lead_count',
  },
  {
    text: 'TIER 3',
    value: 'tier_3_lead_count',
  },
  {
    text: 'TIER 4',
    value: 'tier_4_lead_count',
  },
  {
    text: 'TIER 5',
    value: 'tier_5_lead_count',
  },
  {
    text: 'TIER L',
    value: 'tier_l_lead_count',
  },
  {
    text: 'TIER H',
    value: 'tier_h_lead_count',
  },
  {
    text: 'TIER R',
    value: 'tier_r_lead_count',
  },
  {
    text: 'TIER 6 NON-ECOM',
    value: 'tier_6_lead_count',
  },
  {
    text: 'TIER 6 ECOM',
    value: 'tier_6_lead_count_e',
  },
  {
    text: 'TIER TR ECOM',
    value: 'tier_tr_lead_count_e',
  },
  {
    text: 'TIER TR NON-ECOM',
    value: 'tier_tr_lead_count',
  },
  {
    text: 'TOTAL LEAD COST',
    value: 'total_lead_cost',
  },
];

const filters = reactive({
  advisorAssignedDates: [],
  tiers: [],
  teams: [],
  isCommercial: false,
  page: 1,
});

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    router.visit('/reports/advisor-distribution', {
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

  router.visit('/reports/advisor-distribution', {
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

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
};
</script>

<template>
  <div>
    <Head title="Advisor Distribution Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Distribution Report
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
        <x-select
          v-model="filters.isCommercial"
          label="Commercial"
          placeholder="Select any option"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
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
            {{ calculateTotalSum(reportData.data, 'total_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_0_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_1_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_2_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_3_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_4_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_5_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_l_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_h_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_r_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_6_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_6_lead_count_e') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_tr_lead_count_e') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'tier_tr_lead_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'total_lead_cost') }}
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
