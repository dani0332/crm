<script setup>
const props = defineProps({
  emailStatuses: Array,
  expanded: {
    type: Boolean,
    default: true,
  },
});

const page = usePage();

const emailStatusTable = reactive({
  columns: [
    { text: 'Id', value: 'id' },
    { text: 'Email Subject', value: 'email_subject' },
    { text: 'Email Address', value: 'email_address' },
    { text: 'Status', value: 'email_status' },
    { text: 'Reason', value: 'reason' },
    { text: 'Template Id', value: 'template_id' },
    { text: 'Customer Id', value: 'customer_id' },
    { text: 'Client Replied', value: 'customer_replied' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
  ],
});

const emailStatusesTableColumns = computed(() => {
  return emailStatusTable.columns.filter(column => {
    if (!page.props.isAdmin) {
      return column.value !== 'customer_id' && column.value !== 'template_id';
    }
    return column;
  });
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <DataTable
          table-class-name="tablefixed compact"
          :headers="emailStatusesTableColumns"
          :items="emailStatuses || []"
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-customer_replied="item">
            <span class="text-sm">{{
              item.customer_replied ? 'Yes' : 'No'
            }}</span>
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
