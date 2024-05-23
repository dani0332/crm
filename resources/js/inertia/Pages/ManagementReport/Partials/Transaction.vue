<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
  groupBy: {
    type: String || null,
  },
});

const calculateTotalSum = useCalculateTotalSum;

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
    value: 'price_vat_applicable',
  },
  {
    text: 'Total VAT',
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
    text: 'Total Price',
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
    text: 'Collected Amount',
    value: 'collected_amount',
  },
  {
    text: 'Payment Date',
    value: 'payment_date',
  },
  {
    text: 'Unpaid',
    value: 'pending_balance',
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
    value: 'line_of_business',
  },
  {
    text: 'Sub-Type',
    value: 'sub_type_line_of_business',
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
    value: 'insurer_invoice_number',
  },
  {
    text: 'Insurer Invoice Date',
    value: 'insurer_tax_invoice_date',
  },
  {
    text: 'Broker Invoice No',
    value: 'broker_invoice_number',
  },
]);
const isIntegerColumn = key => {
  // Add logic to determine if the column contains an integer
  // For example, check if the key corresponds to an integer column
  return [
    'price_vat_applicable',
    'vat',
    'price_vat_not_applicable',
    'discount',
    'total_price',
    'commission_vat_applicable',
    'commission_vat',
    'commission_vat_not_applicable',
    'collected_amount',
    'pending_balance',
    'collects',
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
    <template #item-payment_due_date="{payment_due_date, due_date}">
      {{ (payment_due_date ? payment_due_date : (due_date ? due_date : 'N/A')) }}
    </template>
    <template #item-price_vat_applicable="{ price_vat_applicable }">
      {{ price_vat_applicable ? priceFormat(price_vat_applicable) : 0.0 }}
    </template>
    <template #item-vat="{ vat }">
      {{ vat ? priceFormat(vat) : 0.0 }}
    </template>
    <template #item-price_vat_not_applicable="{ price_vat_not_applicable }">
      {{
        price_vat_not_applicable ? priceFormat(price_vat_not_applicable) : 0.0
      }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ? priceFormat(discount) : 0.0 }}
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
    <template #item-collected_amount="{ collected_amount }">
      {{ collected_amount ? priceFormat(collected_amount) : 0 }}
    </template>
    <template #item-payment_date="{ payment_date }">
      {{ payment_date ?? 'N/A' }}
    </template>
    <template #item-pending_balance="{ pending_balance }">
      {{ pending_balance ?? 'N/A' }}
    </template>
    <template #item-collects="{ collects }">
      {{ collects ?? 'N/A' }}
    </template>
    <template #item-insurer="{ insurer }">
      {{ insurer ?? 'N/A' }}
    </template>
    <template #item-line_of_business="{ line_of_business }">
      {{ line_of_business ?? 'N/A' }}
    </template>
    <template #item-sub_type_line_of_business="{ sub_type_line_of_business }">
      {{ sub_type_line_of_business ?? 'N/A' }}
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
    <template #item-insurer_invoice_number="{ insurer_invoice_number }">
      {{ insurer_invoice_number ?? 'N/A' }}
    </template>
    <template #item-insurer_tax_invoice_date="{ insurer_tax_invoice_date }">
      {{ insurer_tax_invoice_date ?? 'N/A' }}
    </template>
    <template #item-broker_invoice_number="{ broker_invoice_number }">
      {{ broker_invoice_number ?? 'N/A' }}
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
