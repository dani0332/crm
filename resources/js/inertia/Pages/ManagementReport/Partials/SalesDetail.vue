<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
  groupBy: {
    type: String || null,
  },
});

const priceFormat = (price, thousandSeparator = false) => {
  return thousandSeparator
    ? parseFloat(price).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    : parseFloat(price).toFixed(2);
};

const tableHeader = reactive([
  {
    text: 'Ref-ID',
    value: 'uuid',
  },
  {
    text: 'Policy No.',
    value: 'policy_number',
  },
  {
    text: 'Department',
    value: 'department',
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
    text: 'Source',
    value: 'source',
  },
  {
    text: 'Team',
    value: 'team',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_vat_applicable',
  },
  {
    text: 'T. VAT',
    value: 'vat',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'price_vat_not_applicable',
  },
  {
    text: 'Discount',
    value: 'discount',
  },
  {
    text: 'T. Price',
    value: 'total_price',
  },
  {
    text: 'Commission (VAT applicable)',
    value: 'commission_vat_applicable',
  },
  {
    text: 'VAT on Commission',
    value: 'commission_vat',
  },
  {
    text: 'Commission (VAT not applicable)',
    value: 'commission_vat_not_applicable',
  },
  {
    text: 'T. Commission',
    value: 'total_commission',
  },
  {
    text: 'Collects',
    value: 'collects',
  },
  {
    text: 'Insurer Tax Inv No.',
    value: 'insurer_tax_invoice_number',
  },
  {
    text: 'Tax Invoice Date',
    value: 'insurer_tax_invoice_date',
  },
  {
    text: 'Payment Status',
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
    value: 'sub_type_line_of_business',
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

const calculateTotalSum = useCalculateTotalSum;

const isIntegerColumn = key => {
  // Add logic to determine if the column contains an integer
  // For example, check if the key corresponds to an integer column
  return [
    // 'transactions',
    'price_vat_applicable',
    'vat',
    'price_vat_not_applicable',
    'discount',
    'total_price',
    'commission_vat_applicable',
    'commission_vat',
    'commission_vat_not_applicable',
    'total_commission',
    'collected_amount',
  ].includes(key);
};
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
    :rows-per-page="100"
  >
    <template #item-uuid="{ uuid, routeName, code }">
      <a :href="route(routeName, uuid)" class="text-primary-500 hover:underline" target="_blank">
          {{ code }}
      </a>
    </template>
    <template #item-policy_number="{ policy_number }">
      {{ policy_number }}
    </template>
    <template #item-transactions="{ transactions }">
      {{ transactions ? transactions : 'N/A' }}
    </template>
    <template #item-policy_start_date="{ policy_start_date }">
      {{ policy_start_date ?? 'N/A' }}
    </template>
    <template #item-payment_due_date="{ payment_due_date, due_date }">
      {{ payment_due_date ? payment_due_date : due_date ? due_date : 'N/A' }}
    </template>
    <template #item-source="{ source }">
      {{ source }}
    </template>
    <template #item-team="{ team }">
      {{ team }}
    </template>
    <template #item-price_vat_applicable="{ price_vat_applicable }">
      {{ price_vat_applicable ? price_vat_applicable : 0.0 }}
    </template>
    <template #item-vat="{ vat }">
      {{ vat ?? 0 }}
    </template>
    <template #item-price_vat_not_applicable="{ price_vat_not_applicable }">
      {{ price_vat_not_applicable ? price_vat_not_applicable : 0.0 }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ? discount : 0.0 }}
    </template>
    <template #item-total_price="{ total_price }">
      {{ total_price ? total_price : 0.0 }}
    </template>
    <template #item-commission_vat_applicable="{ commission_vat_applicable }">
      {{ commission_vat_applicable ? commission_vat_applicable : 0.0 }}
    </template>
    <template #item-commission_vat="{ commission_vat }">
      {{ commission_vat ? commission_vat : 0.0 }}
    </template>
    <template
      #item-commission_vat_not_applicable="{ commission_vat_not_applicable }"
    >
      {{ commission_vat_not_applicable ? commission_vat_not_applicable : 0.0 }}
    </template>
    <template #item-total_commission="{ total_commission }">
      {{ total_commission ? total_commission : 0.0 }}
    </template>
    <template #item-collects="{ collects }">
      {{ collects }}
    </template>
    <template #item-insurer_tax_invoice_number="{ insurer_tax_invoice_number }">
      {{ insurer_tax_invoice_number ?? 'N/A' }}
    </template>
    <template #item-insurer_tax_invoice_date="{ insurer_tax_invoice_date }">
      {{ insurer_tax_invoice_date ?? 'N/A' }}
    </template>
    <template #item-transaction_payment_status="{ transaction_payment_status }">
      {{ transaction_payment_status }}
    </template>
    <template #item-date_paid="{ date_paid }">
      {{ date_paid ?? 'N/A' }}
    </template>
    <template #item-collected_amount="{ collected_amount }">
      {{ collected_amount ?? 0 }}
    </template>
    <template #item-customer_name="{ customer_name }">
      {{ customer_name }}
    </template>
    <template #item-customer_type="{ customer_type }">
      {{ customer_type && customer_type.includes('IND') ? 'Individual' : '--' }}
    </template>
    <template #item-insurer="{ insurer }">
      {{ insurer }}
    </template>
    <template #item-line_of_business="{ line_of_business }">
      {{ line_of_business ?? 'N/A' }}
    </template>
    <template #item-sub_type_line_of_business="{ sub_type_line_of_business }">
      {{ sub_type_line_of_business ?? 'N/A' }}
    </template>
    <template #item-advisor="{ advisor }">
      {{ advisor }}
    </template>
    <template #item-policy_issuer="{ policy_issuer }">
      {{ policy_issuer ?? 'N/A' }}
    </template>
    <template #body-append>
      <tr v-if="reportData.data.length > 0" class="total-row">
        <td class="direction-left">Total</td>
        <td
          v-for="header in tableHeader.slice(1, tableHeader.length)"
          :key="header.value"
          class="direction-center"
        >
          {{
            isIntegerColumn(header.value)
              ? priceFormat(
                  calculateTotalSum(reportData.data, header.value),
                  true,
                )
              : 'N/A'
          }}
        </td>
      </tr>
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
