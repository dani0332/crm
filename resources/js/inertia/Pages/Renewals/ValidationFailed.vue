<script setup>
const props = defineProps({
  renewalLeads: Array,
  batchId: String,
});

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Batch', value: 'batch' },
  { text: 'FILE Name', value: 'renewal_upload_lead' },
  { text: 'QUOTE TYPE', value: 'quote_type' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'STATUS', value: 'status' },
  { text: 'VALIDATION ERRORS', value: 'validation_errors' },
  { text: 'CREATED AT', value: 'created_at' },
];
</script>
<template>
  <Head title="Vaidation Failed Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Validation Failed Details</h2>
    <div class="space-x-3">
      <x-button
        size="sm"
        color="emerald"
        :href="route('validation-failed-download', { id: batchId })"
        class="btn-2"
        >Export</x-button
      >
      <x-button
        size="sm"
        color="#ff5e00"
        :href="route('renewals-uploaded-leads-list')"
        class="btn-2"
        >Batches List</x-button
      >
    </div>
  </div>
  <x-divider class="my-4" />
  <DataTable
    table-class-name="tablefixed"
    :loading="loader.table"
    :headers="tableHeader"
    :items="renewalLeads.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-renewal_upload_lead="{ renewal_upload_lead }">
      {{ renewal_upload_lead.file_name }}
    </template>
    <template #item-validation_errors="{ validation_errors }">
      <ul class="list-disc m-2 marker:text-red-600">
        <li v-for="error in validation_errors" :key="error">
          <span>{{ error }}</span>
        </li>
      </ul>
    </template>
    <template #item-status="{ status }">
      {{ status }}
    </template>
    <template #item-created_at="{ created_at }">
      {{ created_at.split('T')[0] }}
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: renewalLeads.next_page_url,
      prev: renewalLeads.prev_page_url,
      current: renewalLeads.current_page,
      from: renewalLeads.from,
      to: renewalLeads.to,
    }"
  />
  <p class="text-xs mt-12">
    © AFIA Insurance Brokerage Services LLC, registration no. 85, under UAE
    Insurance Authority
  </p>
</template>
