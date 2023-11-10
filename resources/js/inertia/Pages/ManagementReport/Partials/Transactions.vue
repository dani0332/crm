<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
});

const tableHeader = reactive([
  {
    text: 'Policy Number',
    value: 'policy_number',
  },
  {
    text: 'Transactions',
    value: 'transactions',
  },
  {
    text: 'Policy Start Date',
    value: 'policy_start_date',
  },

  {
    text: 'Payment Due Date',
    value: 'payment_due_date',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_with_vat',
  },
  {
    text: 'Total VAT',
    value: 'total_vat',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'price_without_vat',
  },
  {
    text: 'Discount',
    value: 'discount',
  },
  {
    text: 'Total Price',
    value: 'total_price',
  },
  {
    text: 'Commission (VAT applicable)',
    value: 'commission',
  },
  {
    text: 'VAT on Commission',
    value: 'vat_on_commission',
  },
  {
    text: 'Commission (VAT not applicable)',
    value: 'commission_without_vat',
  },
  {
    text: 'Collected Amount',
    value: 'collected_amount',
  },
  {
    text: 'Payment Date',
    value: 'payment_date',
  },
  {
    text: 'Unpaid',
    value: 'unpaid',
  },
  {
    text: 'Collects',
    value: 'collects',
  },
  {
    text: 'Insurer',
    value: 'insurer',
  },
  {
    text: 'Line of Business',
    value: 'line_of_bussiness',
  },
  {
    text: 'Sub-Type',
    value: 'sub_type',
  },
  {
    text: 'Customer Name',
    value: 'customer_name',
  },
  {
    text: 'Advisor',
    value: 'advisor',
  },
  {
    text: 'Policy Issuer',
    value: 'policy_issuer',
  },
  {
    text: 'Invoice Description',
    value: 'invoice_description',
  },
  {
    text: 'Payment Method',
    value: 'payment_method',
  },
  {
    text: 'Payment Gateway',
    value: 'payment_dateway',
  },

  {
    text: 'Insurer Invoice No.',
    value: 'insurer_invoice_no',
  },
  {
    text: 'Insurer Invoice Date',
    value: 'insurer_invoice_date',
  },
  {
    text: 'Broker Invoice No',
    value: 'borker_invoice_no',
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
  <!-- <Pagination
    :links="{
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  /> -->
</template>