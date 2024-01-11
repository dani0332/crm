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
    value: 'payment_gateway',
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
    <template #item-commission_with_vat="{ commission_with_vat }">
      {{ commission_with_vat ?? 0 }}
    </template>
    <template #item-vat_on_commission="{ vat_on_commission }">
      {{ vat_on_commission ?? 0 }}
    </template>
    <template #item-commission_without_vat="{ commission_without_vat }">
      {{ commission_without_vat ?? 0 }}
    </template>
    <template #item-collected_amount="{ collected_amount }">
      {{ collected_amount ?? 0 }}
    </template>
    <template #item-payment_date="{ payment_date }">
      {{ payment_date ?? 'N/A' }}
    </template>
    <template #item-unpaid="{ unpaid }">
      {{ unpaid ?? 'N/A' }}
    </template>
    <template #item-collects="{ collects }">
      {{ collects ?? 'N/A' }}
    </template>
    <template #item-insurer="{ insurer }">
      {{ insurer ?? 'N/A' }}
    </template>
    <template #item-line_of_bussiness="{ line_of_bussiness }">
      {{ line_of_bussiness ?? 'N/A' }}
    </template>
    <template #item-sub_type="{ sub_type }">
      {{ sub_type ?? 'N/A' }}
    </template>
    <template #item-customer_name="{ customer_name }">
      {{ customer_name ?? 'N/A' }}
    </template>
    <template #item-advisor="{ advisor }">
      {{ advisor ?? 'N/A' }}
    </template>
    <template #item-policy_issuer="{ policy_issuer }">
      {{ policy_issuer ?? 'N/A' }}
    </template>
    <template #item-invoice_description="{ invoice_description }">
      {{ invoice_description ?? 'N/A' }}
    </template>
    <template #item-payment_method="{ payment_method }">
      {{ payment_method ?? 'N/A' }}
    </template>
    <template #item-payment_gateway="{ payment_gateway }">
      {{ payment_gateway ?? 'N/A' }}
    </template>
    <template #item-insurer_invoice_no="{ insurer_invoice_no }">
      {{ insurer_invoice_no ?? 'N/A' }}
    </template>
    <template #item-insurer_invoice_date="{ insurer_invoice_date }">
      {{ insurer_invoice_date ?? 'N/A' }}
    </template>
    <template #item-borker_invoice_no="{ borker_invoice_no }">
      {{ borker_invoice_no ?? 'N/A' }}
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