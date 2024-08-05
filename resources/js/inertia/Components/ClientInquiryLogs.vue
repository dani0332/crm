<script setup>
const props = defineProps({
  logs: Array,
});

const clientInquiryLogs = reactive({
  data: props.logs,
  table: [
    { text: 'ID', value: 'id' },
    { text: 'LOGGED AT', value: 'created_at' },
  ],
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <x-tooltip placement="right">
        <h3 class="font-semibold text-primary-800 text-lg">
          Client Enquiry Logs
        </h3>
        <template #tooltip>
          This log records each client query with timestamps for reliable
          tracking. Note: Manually input plans don't carry over to ensure
          updated information. Refer here for recent interactions and add
          manually quoted plans to the newest inquiry as needed.
        </template>
      </x-tooltip>

      <x-divider class="mb-4 mt-1" />
    </div>
    <DataTable
      table-class-name="compact tablefixed"
      :headers="clientInquiryLogs.table"
      :items="clientInquiryLogs.data || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="15"
      :hide-footer="clientInquiryLogs.data?.length < 15"
    >
      <template #item-created_at="item">
        {{ item.created_at }}
      </template>
    </DataTable>
  </div>
</template>
