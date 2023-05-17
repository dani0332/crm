<script setup>
defineProps({
  reportData: Array,
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
</script>

<template>
  <div>
    <Head title="Advisor Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Revival Conversion Report
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>
  </div>
</template>
