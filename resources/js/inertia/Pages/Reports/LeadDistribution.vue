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
const carRegistrationTypeEnum = page.props.carRegistrationType;
const carVehicleUseEnum = page.props.carVehicleUse;


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
    text: 'UNASSIGNED LEADS',
    value: 'unassigned_leads',
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
  createdAtDates: [],
  tiers: [],
  assignmentTypes: 'All',
  isCommercial: 'All',
  segment_filter: 'all',
  sic_advisor_requested: 'All',
  registration_type: 'All',
  vehicle_use: 'All',
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
  if (page.props.defaultFilters) {
    filters.createdAtDates = page.props.defaultFilters.createdAtDates;
  }
  router.visit('/reports/lead-distribution', {
    method: 'get',
    data: {
      createdAtDates: filters.createdAtDates,
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
    filters.createdAtDates = page.props.defaultFilters.createdAtDates;
  }
  setQueryStringFilters();
});

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
};

const registrationTypeOptions = computed(() => [
    { value: 'All', label: 'All' },
    ...Object.values(carRegistrationTypeEnum).map(item => ({
        value: item,
        label: item,
    })),
]);

const vehicleUseOptions = computed(() => [
    { value: 'All', label: 'All' },
    ...Object.values(carVehicleUseEnum).map(item => ({
        value: item,
        label: item,
    })),
]);

const isVehicleUseDisabled = computed(() => {
  return filters.registration_type === carRegistrationTypeEnum.COMPANY;
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
          v-model="filters.createdAtDates"
          label="Created Date"
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
        <x-select
        v-model="filters.registration_type"
        label="Registration Type"
        placeholder="Select any option"
        :options="registrationTypeOptions"

          />

          <x-select
          v-if="isVehicleUseDisabled"
          v-model="filters.vehicle_use"
          label="Vehicle Use"
          placeholder="Select any option"
          :options="vehicleUseOptions"
        />
        <x-select
          v-model="filters.isCommercial"
          label="Commercial Rule"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />



        <ComboBox
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
          :single="true"
        />

        <ComboBox
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="quoteSegments"
          :single="true"
        />
        <ComboBox
          v-model="filters.sic_advisor_requested"
          label="Advisor Requested"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
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
            {{ calculateTotalSum(reportData.data, 'received_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'lead_created') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'total_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'unassigned_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'auto_assigned') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'manually_assigned') }}
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
