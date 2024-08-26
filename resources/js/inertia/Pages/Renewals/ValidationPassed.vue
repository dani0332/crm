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
  { text: 'QUOTE', value: 'id' },
  { text: 'CREATED AT', value: 'created_at' },
];
</script>
<template>
  <Head title="Vaidation Passed Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Validation Passed Details</h2>
    <div class="space-x-3">
      <x-button
        color="#ff5e00"
        size="sm"
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
    <template #item-id="{ id }">
      <x-button
        size="sm"
        color="#1d83bc"
        :href="route('viewQuoteRedirect', { id: batchId, leadId: id })"
        class="btn-2"
        >View Quote</x-button
      >
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
