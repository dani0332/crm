<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
});

const tableHeader = reactive([
  {
    text: 'Customer Name',
    value: 'customer_name',
  },
  {
    text: 'Customer ID',
    value: 'customer_id',
  },
  {
    text: 'Policy Number',
    value: 'policy_number',
  },
  {
    text: 'Insurer',
    value: 'insurer',
  },
  {
    text: 'Line Of Bussiness',
    value: 'line_of_bussiness',
  },
  {
    text: 'Policy Start Date',
    value: 'policy_start_date',
  },
  {
    text: 'Policy Expiry Date',
    value: 'policy_expiry_date',
  },
  {
    text: 'Collected Amount',
    value: 'collected_amount',
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
    text: 'Pending Balance',
    value: 'pending_balance',
  },
  {
    text: 'Commission (VAT applicable)',
    value: 'commission_with_vat',
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
    text: 'Policy Issuer',
    value: 'policy_issuer',
  },
  {
    text: 'Lead Source',
    value: 'lead_source',
  },
  {
    text: 'Advisor',
    value: 'advisor',
  },
  {
    text: 'Notes ',
    value: 'notes',
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