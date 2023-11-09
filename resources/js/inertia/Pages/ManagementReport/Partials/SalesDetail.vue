<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
});

const tableHeader = reactive([
  {
    text: 'Policy Number',
    value: 'uuid',
  },
  {
    text: 'Transactions',
    value: 'first_name',
  },
  {
    text: 'Policy Start Date',
    value: 'source',
  },
  {
    text: 'Payment Due Date',
    value: 'quoteStatus',
  },
  {
    text: 'Lead Source',
    value: 'payment_status_id',
  },
  {
    text: 'Team',
    value: 'is_ecommerce',
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
    text: 'Total Commission',
    value: 'tier',
  },
  {
    text: 'Collects',
    value: 'tier',
  },
  {
    text: 'Insurer Tax Invoice Number',
    value: 'tier',
  },
  {
    text: 'Tax Invoice Date',
    value: 'tier',
  },
  {
    text: 'Transaction Payment Status',
    value: 'tier',
  },
  {
    text: 'Date Paid',
    value: 'tier',
  },
  {
    text: 'Collected Amount',
    value: 'tier',
  },
  {
    text: 'Customer Name',
    value: 'tier',
  },
  {
    text: 'Customer Id',
    value: 'tier',
  },
  {
    text: 'Customer Type',
    value: 'tier',
  },
  {
    text: 'Insurer',
    value: 'tier',
  },
  {
    text: 'Line of Business',
    value: 'tier',
  },
  {
    text: 'Sub-Type',
    value: 'tier',
  },
  {
    text: 'Advisor',
    value: 'tier',
  },
  {
    text: 'Policy Issuer ',
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