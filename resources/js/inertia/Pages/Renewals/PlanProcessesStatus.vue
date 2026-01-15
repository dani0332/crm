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
  { text: 'FETCH PLANS STATUS', value: 'fetch_plans_status' },
  { text: 'STEP ERRORS', value: 'step_errors' },
  { text: 'CREATED AT', value: 'created_at' },
];
</script>
<template>
  <Head title="Fetch Plan Failed Details" />
  <title>Fetch Plan Failed Details</title>
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Fetch Plan Failed Details</h2>
    <div class="space-x-3">
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
    <template #item-step_errors="{ step_errors }">
      <ul class="list-disc m-2 marker:text-red-600">
        <li v-for="error in step_errors" :key="error">
          <span>{{ error }}</span>
        </li>
      </ul>
    </template>
    <template #item-fetch_plans_status="{ fetch_plans_status }">
      {{ fetch_plans_status }}
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
