<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
});

const tableHeader = reactive([
  {
    text: 'Insurer',
    value: 'uuid',
  },
  {
    text: 'Line of Business',
    value: 'uuid',
  },
  {
    text: 'Active Policy Count',
    value: 'uuid',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'advisor',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'updated_at',
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