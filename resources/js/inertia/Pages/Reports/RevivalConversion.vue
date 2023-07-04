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
  <div>
    <Head title="Revival Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Conversion Rate
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="conversionRateTableHeader"
      :items="reportsData.conversionRate || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>
  </div>
  <x-divider class="my-4" />
  <div>
    <Head title="Revival Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Auth To Capture Rate
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="reportsData.leadConversionReport || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>
  </div>
  <x-divider class="my-4" />
  <div>
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Response Rate Of Customer
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="emailConversionReportTableHeader"
      :items="reportsData.emailConversionReport || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>
  </div>
</template>
