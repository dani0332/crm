<script setup>
defineProps({
  reportsData: Array,
});
const loader = reactive({
  table: false,
});
let availableFilters = {
  created_at: '',
  page: 1,
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

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
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
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
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <DatePicker
        v-model="filters.created_at"
        name="created_at"
        label="Created Date"
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
      Revival Conversion Report
    </h1>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="reportsData.data.leadConversionReport || []"
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
      :loading="loader.table"
      :headers="emailConversionReportTableHeader"
      :items="reportsData.data.emailConversionReport || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>

    <Pagination
      :links="{
        next: reportsData.next_page_url,
        prev: reportsData.prev_page_url,
        current: reportsData.current_page,
        from: reportsData.from,
        to: reportsData.to,
      }"
    />
  </div>
</template>
