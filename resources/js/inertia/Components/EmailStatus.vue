<script setup>
const props = defineProps({
  emailStatuses: Array,
});

const emailStatusTable = reactive({
  columns: [
    { text: 'Id', value: 'id' },
    { text: 'Email Subject', value: 'email_subject' },
    { text: 'Email Address', value: 'email_address' },
    { text: 'Status', value: 'email_status' },
    { text: 'Reason', value: 'reason' },
    { text: 'Template Id', value: 'template_id' },
    { text: 'Customer Id', value: 'customer_id' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
  ],
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="emailStatusTable.columns"
        :items="emailStatuses || []"
        show-index
        border-cell
        hide-rows-per-page
        hide-footer
      />
    </div>
  </div>
</template>
