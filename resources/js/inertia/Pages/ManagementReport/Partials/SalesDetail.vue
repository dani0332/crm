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
    text: 'Lead Source',
    value: 'lead_source',
  },
  {
    text: 'Team',
    value: 'team',
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
    text: 'Total Commission',
    value: 'total_commission',
  },
  {
    text: 'Collects',
    value: 'collects',
  },
  {
    text: 'Insurer Tax Invoice Number',
    value: 'inurer_tax_invoice_number',
  },
  {
    text: 'Tax Invoice Date',
    value: 'tax_invoice_date',
  },
  {
    text: 'Transaction Payment Status',
    value: 'transaction_payment_status',
  },
  {
    text: 'Date Paid',
    value: 'date_paid',
  },
  {
    text: 'Collected Amount',
    value: 'collected_amount',
  },
  {
    text: 'Customer Name',
    value: 'customer_name',
  },
  {
    text: 'Customer Id',
    value: 'customer_id',
  },
  {
    text: 'Customer Type',
    value: 'customer_type',
  },
  {
    text: 'Insurer',
    value: 'insurer',
  },
  {
    text: 'Line of Business',
    value: 'line_of_business',
  },
  {
    text: 'Sub-Type',
    value: 'sub_type',
  },
  {
    text: 'Advisor',
    value: 'advisor',
  },
  {
    text: 'Policy Issuer ',
    value: 'policy_issuer',
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
    <template #item-policy_number="{ policy_number }">
      {{ policy_number ?? 'N/A' }}
    </template>
    <template #item-transactions="{ transactions }">
      {{ transactions ?? 0 }}
    </template>
    <template #item-policy_start_date="{ policy_start_date }">
      {{ policy_start_date ?? 'N/A' }}
    </template>
    <template #item-payment_due_date="{ payment_due_date }">
      {{ payment_due_date ?? 'N/A' }}
    </template>
    <template #item-lead_source="{ lead_source }">
      {{ lead_source ?? 'N/A' }}
    </template>
    <template #item-team="{ team }">
      {{ team ?? 'N/A' }}
    </template>
    <template #item-price_with_vat="{ price_with_vat }">
      {{ price_with_vat ?? 0 }}
    </template>
    <template #item-total_vat="{ total_vat }">
      {{ total_vat ?? 0 }}
    </template>
    <template #item-price_without_vat="{ price_without_vat }">
      {{ price_without_vat ?? 0 }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ?? 0 }}
    </template>
    <template #item-total_price="{ total_price }">
      {{ total_price ?? 0 }}
    </template>
    <template #item-commission="{ commission }">
      {{ commission ?? 0 }}
    </template>
    <template #item-vat_on_commission="{ vat_on_commission }">
      {{ vat_on_commission ?? 0 }}
    </template>
    <template #item-commission_without_vat="{ commission_without_vat }">
      {{ commission_without_vat ?? 0 }}
    </template>
    <template #item-total_commission="{ total_commission }">
      {{ total_commission ?? 0 }}
    </template>
    <template #item-collects="{ collects }">
      {{ collects ?? 'N/A' }}
    </template>
    <template #item-inurer_tax_invoice_number="{ inurer_tax_invoice_number }">
      {{ inurer_tax_invoice_number ?? 0 }}
    </template>
    <template #item-tax_invoice_date="{ tax_invoice_date }">
      {{ tax_invoice_date ?? 'N/A' }}
    </template>
    <template #item-transaction_payment_status="{ transaction_payment_status }">
      {{ transaction_payment_status ?? 'N/A' }}
    </template>
    <template #item-date_paid="{ date_paid }">
      {{ date_paid ?? 'N/A' }}
    </template>
    <template #item-collected_amount="{ collected_amount }">
      {{ collected_amount ?? 0 }}
    </template>
    <template #item-customer_name="{ customer_name }">
      {{ customer_name ?? 'N/A' }}
    </template>
    <template #item-customer_id="{ customer_id }">
      {{ customer_id ?? 'N/A' }}
    </template>
    <template #item-customer_type="{ customer_type }">
      {{ customer_type ?? 'N/A' }}
    </template>
    <template #item-insurer="{ insurer }">
      {{ insurer ?? 'N/A' }}
    </template>
    <template #item-line_of_business="{ line_of_business }">
      {{ line_of_business ?? 'N/A' }}
    </template>
    <template #item-sub_type="{ sub_type }">
      {{ sub_type ?? 'N/A' }}
    </template>
    <template #item-advisor="{ advisor }">
      {{ advisor ?? 'N/A' }}
    </template>
    <template #item-policy_issuer="{ policy_issuer }">
      {{ policy_issuer ?? 'N/A' }}
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