<script setup>
defineProps({
  leadConversionReport: Array,
  emailConversionReport: Array,
});
const loaders = reactive({
  table: false,
});

const params = useUrlSearchParams('history');
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
    value: 'eamil_sent_count',
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
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Revival Conversion Report
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="leadConversionReport || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>
  </div>
  <x-divider class="my-4" />
  <div>
    <Head title="Advisor Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Revival Response Rate
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="emailConversionReportTableHeader"
      :items="emailConversionReport || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>
  </div>
</template>
