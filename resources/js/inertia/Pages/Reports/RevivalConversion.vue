<script setup>
defineProps({
  reportsData: Array,
});
const loader = reactive({
  table: false,
});
let availableFilters = {
  date_assigned: '',
  car_type_insurance_id: '',
  lead_source: '',
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
  if (isValid) {
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/reports/revival-conversion', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

const tabs = ref([
  { index: 0, label: 'Conversion Rate' },
  { index: 1, label: 'Auth To Capture Rate' },
  { index: 2, label: 'Response Rate Of Customer' },
]);
function onReset() {
  router.visit('/reports/revival-conversion', {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
const conversionRateTableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
  },
  {
    text: 'Total number of payments captured',
    value: 'conversion_captured',
  },
  {
    text: 'Total number of Revived',
    value: 'total_revived',
  },
  {
    text: 'Ratio',
    value: 'ratio',
  },
];

const tableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
  },
  {
    text: 'Total number of payments captured',
    value: 'captured',
  },
  {
    text: 'Total number of payments authorized',
    value: 'authorized',
  },
  {
    text: 'Auth to Capture Rate',
    value: 'ratio',
  },
];
const emailConversionReportTableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
  },
  {
    text: 'Total Replied',
    value: 'reply_received_count',
  },
  {
    text: 'Total Sent',
    value: 'email_sent_count',
  },
  {
    text: 'Response rate of customer',
    value: 'ratio',
  },
];
</script>

<template>
  <div>
    <Head title="Revival Conversion Report" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-select
          v-model="filters.lead_source"
          label="Lead Source"
          placeholder="Lead Source"
          :options="[
            { value: 'REVIVAL', label: 'Revival' },
            { value: 'REVIVAL_REPLIED', label: 'Revival Replied' },
            { value: 'REVIVAL_PAID', label: 'Revival Paid' },
          ]"
          class="w-full"
        />
        <x-select
          v-model="filters.car_type_insurance_id"
          label="Type Of Car Insurance"
          placeholder="Type Of Car Insurance"
          :options="[
            { value: '1', label: 'Comprehensive' },
            { value: '2', label: 'TPL' },
          ]"
          class="w-full"
        />
        <DatePicker
          v-model="filters.date_assigned"
          name="created_at"
          label="Date Assigned"
          class="w-full"
        />
      </div>

      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <TabGroup>
      <TabList
        class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full justify-center"
      >
        <Tab
          v-for="{ index, label } in tabs"
          :key="index"
          v-slot="{ selected }"
        >
          <button
            :class="[
              'rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
              'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
              selected
                ? 'bg-white shadow text-primary-600'
                : 'hover:bg-white/50',
            ]"
          >
            {{ label }}
          </button>
        </Tab>
      </TabList>

      <TabPanels class="mt-2">
        <TabPanel>
          <DataTable
            table-class-name="tablefixed"
            :loading="loader.table"
            :headers="conversionRateTableHeader"
            :items="reportsData.conversionRate || []"
            border-cell
            hide-rows-per-page
          >
          </DataTable>
        </TabPanel>
        <TabPanel>
          <DataTable
            table-class-name="tablefixed"
            :loading="loader.table"
            :headers="tableHeader"
            :items="reportsData.leadConversionReport || []"
            border-cell
            hide-rows-per-page
          >
          </DataTable>
        </TabPanel>
        <TabPanel>
          <DataTable
            table-class-name="tablefixed"
            :loading="loader.table"
            :headers="emailConversionReportTableHeader"
            :items="reportsData.emailConversionReport || []"
            border-cell
            hide-rows-per-page
          >
          </DataTable
        ></TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>
