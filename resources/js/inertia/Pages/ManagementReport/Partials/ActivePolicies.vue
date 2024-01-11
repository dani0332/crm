<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
});

const tableHeader = reactive([
  {
    text: 'Insurer',
    value: 'insurer',
  },
  {
    text: 'Line of Business',
    value: 'line_of_business',
  },
  {
    text: 'Active Policy Count',
    value: 'active_policy_count',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_with_vat',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'price_without_vat',
  },
]);
</script>
<template>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loader"
    :headers="tableHeader"
    :items="props.reportData.data || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
    <template #item-insurer="{ insurer }">
      {{ insurer ?? 'N/A' }}
    </template>
    <template #item-line_of_business="{ line_of_business }">
      {{ line_of_business ?? 'N/A' }}
    </template>
    <template #item-active_policy_count="{ active_policy_count }">
      {{ active_policy_count ?? 0 }}
    </template>
    <template #item-price_with_vat="{ price_with_vat }">
      {{ price_with_vat ?? 0 }}
    </template>
    <template #item-price_without_vat="{ price_without_vat }">
      {{ price_without_vat ?? 0 }}
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  />
</template>