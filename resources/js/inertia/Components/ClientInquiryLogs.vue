<script setup>
const props = defineProps({
  logs: Array,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

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
      <h3 class="font-semibold text-primary-800 text-lg">Client Enquiry Logs</h3>
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
      <template #item-created_at="{ created_at }">
        {{ dateFormat(created_at).value }}
      </template>
    </DataTable>
  </div>
</template>
