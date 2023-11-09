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
    value: 'uuid',
  },
  {
    text: 'Policy Start Date',
    value: 'source',
  },

  {
    text: 'Payment Due Date',
    value: 'uuid',
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
    text: 'Collected Amount',
    value: 'tier',
  },
  {
    text: 'Payment Date',
    value: 'first_name',
  },
  {
    text: 'Unpaid',
    value: 'tier',
  },
  {
    text: 'Collects',
    value: 'uuid',
  },
  {
    text: 'Insurer',
    value: 'uuid',
  },
  {
    text: 'Line of Business',
    value: 'uuid',
  },
  {
    text: 'Sub-Type',
    value: 'uuid',
  },
  {
    text: 'Customer Name',
    value: 'uuid',
  },
  {
    text: 'Advisor',
    value: 'tier',
  },
  {
    text: 'Policy Issuer',
    value: 'first_name',
  },
  {
    text: 'Invoice Description',
    value: '',
  },
  {
    text: 'Payment Method',
    value: '',
  },
  {
    text: 'Payment Gateway',
    value: 'source',
  },

  {
    text: 'Insurer Invoice No.',
    value: 'tier',
  },
  {
    text: 'Insurer Invoice Date',
    value: 'payment_status_id',
  },
  {
    text: 'Broker Invoice No',
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