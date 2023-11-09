<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
});

const tableHeader = reactive([
  {
    text: 'Customer Name',
    value: 'uuid',
  },
  {
    text: 'Customer ID',
    value: 'first_name',
  },
  {
    text: 'Policy Number',
    value: 'uuid',
  },
  {
    text: 'Insurer',
    value: 'tier',
  },
  {
    text: 'Line Of Bussiness',
    value: 'uuid',
  },
  {
    text: 'Policy Start Date',
    value: 'source',
  },
  {
    text: 'Policy Expiry Date',
    value: 'source',
  },
  {
    text: 'Collected Amount',
    value: 'tier',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'advisor',
  },
  {
    text: 'Total VAT',
    value: 'created_at',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'updated_at',
  },
  {
    text: 'Discount',
    value: 'tier',
  },
  {
    text: 'Total Price',
    value: 'tier',
  },
  {
    text: 'Pending Balance',
    value: 'tier',
  },
  {
    text: 'Commission (VAT applicable)',
    value: 'tier',
  },
  {
    text: 'VAT on Commission',
    value: 'tier',
  },
  {
    text: 'Commission (VAT not applicable)',
    value: 'tier',
  },
  {
    text: 'Policy Issuer',
    value: 'first_name',
  },
  {
    text: 'Lead Source',
    value: 'payment_status_id',
  },
  {
    text: 'Advisor',
    value: 'tier',
  },
  {
    text: 'Notes ',
    value: 'tier',
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