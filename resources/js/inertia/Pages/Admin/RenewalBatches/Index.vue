<script setup>
defineProps({
  batches: Object,
});

const formatted = date => useDateFormat(date, 'DD-MM-YYYY').value;

const loader = reactive({
  table: false,
});

const tableHeader = [
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'START DATE', value: 'start_date' },
  { text: 'END DATE', value: 'end_date' },
  { text: 'Batch Type', value: 'quote_type_id' },
];
</script>
<template>
  <div>
    <Head title="Renewal Batches" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Renewal Batches</h2>
      <x-button
        size="sm"
        color="#ff5e00"
        :href="route('renewal-batches-create')"
      >
        Create Renewal Batch
      </x-button>
    </div>
    <x-divider class="my-4" />
    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="batches.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-id="item">
        <a
          :href="route('renewal-batches-edit', item)"
          class="text-primary-500 hover:underline"
        >
          {{ item.id }}
        </a>
      </template>
      <template #item-start_date="{ start_date }">
        {{ start_date ? formatted(start_date) : 'N/A' }}
      </template>
      <template #item-end_date="{ end_date }">
        {{ end_date ? formatted(end_date) : 'N/A' }}
      </template>
      <template #item-quote_type_id="{ quote_type_id }">
        {{ quote_type_id == 1 ?  'Motor' : 'Non-motor' }}
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: batches.next_page_url,
        prev: batches.prev_page_url,
        current: batches.current_page,
        from: batches.from,
        to: batches.to,
        total: batches.total,
      }"
    />
  </div>
</template>
