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
    text: 'Customer Name',
    value: 'customer_name',
    tooltip: 'The name of customer (insured name)',
  },
  {
    text: 'Policy Number',
    value: 'policy_number',
    tooltip: 'The policy number of the expiring policy',
  },
  {
    text: 'Insurer',
    value: 'insurer',
    tooltip: 'The insurer of the expiring policy',
  },
  {
    text: 'Line Of Business',
    value: 'line_of_business',
    tooltip: 'The line of business of the expiring policy',
  },
  {
    text: 'Policy Start Date',
    value: 'policy_start_date',
    tooltip: 'Policy inception date',
  },
  {
    text: 'Policy Expiry Date',
    value: 'policy_end_date',
    tooltip: 'Policy expiry date',
  },
  {
    text: 'Collected Amount',
    value: 'collected_amount',
    tooltip: 'Amount paid by the user',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_vat_applicable',
    tooltip:
      'Vatable price. Any amount appearing in this column will be computed with VAT.',
  },
  {
    text: 'Total VAT',
    value: 'total_vat',
    tooltip: 'VAT of Price (VAT applicable)',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'price_vat_not_applicable',
    tooltip:
      'Non-vatable price. Any amount appearing in this column will not be computed with VAT. For example: BASMAH, rider, etc',
  },
  {
    text: 'Discount',
    value: 'discount',
    tooltip: 'Discount applied to the transaction',
  },
  {
    text: 'Total Price',
    value: 'total_price',
    tooltip:
      'Total price less the discount. This was computed using the following formula: Price(VAT Applicable) + VAT + Price(VAT Not Applicable) - Discount',
  },
  {
    text: 'Pending Balance',
    value: 'pending_balance',
    tooltip:
      'Pending balance of the policy. Formula as follows: Total Price - Discount - Collected Amount',
  },
  {
    text: 'Commission (VAT applicable)',
    value: 'commission_vat_applicable',
    tooltip:
      'Vatable commission. Any amount appearing in this column will be computed with VAT.',
  },
  {
    text: 'VAT on Commission',
    value: 'commission_vat',
    tooltip: 'VAT of Commission (VAT applicable)',
  },
  {
    text: 'Commission (VAT not applicable)',
    value: 'commission_vat_not_applicable',
    tooltip:
      'Non-vatable commission. Any amount appearing in this column will not be computed with VAT.',
  },
  {
    text: 'Policy Issuer',
    value: 'policy_issuer',
    tooltip: 'The user who booked the policy.',
  },
  {
    text: 'Advisor',
    value: 'advisor',
    tooltip: 'The advisor assigned to the policy.',
  },
  {
    text: 'Lead Source',
    value: 'source',
    tooltip: 'The lead source of the lead.',
  },
  {
    text: 'Notes ',
    value: 'notes',
    tooltip: 'Any notes added within lead level will reflect here.',
  },
]);

const calculateTotalSum = useCalculateTotalSum;

const isIntegerColumn = key => {
  // Add logic to determine if the column contains an integer
  // For example, check if the key corresponds to an integer column
  return [
    'collected_amount',
    'price_with_vat',
    'price_vat_applicable',
    'total_vat',
    'price_without_vat',
    'price_vat_not_applicable',
    'discount',
    'total_price',
    'pending_balance',
    'commission_with_vat',
    'vat_on_commission',
    'commission_without_vat',
    'commission_vat_applicable',
    'commission_vat',
    'commission_vat_not_applicable',
  ].includes(key);
};
</script>
<template>
  <DataTable
    class="mt-4"
    table-class-name="table-fixed"
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
    <template
      v-for="header in tableHeader"
      :key="header.value"
      #[`header-${header.value}`]="header"
    >
      <HeaderWithTooltip :header="header" />
    </template>
    <template #item-customer_name="{ customer_name }">
      {{ customer_name ?? 'N/A' }}
    </template>
    <template #item-policy_number="{ policy_number }">
      {{ policy_number ?? 'N/A' }}
    </template>
    <template #item-insurer="{ insurer }">
      {{ insurer ?? 'N/A' }}
    </template>
    <template #item-line_of_bussiness="{ line_of_bussiness }">
      {{ line_of_bussiness ?? 'N/A' }}
    </template>
    <template #item-policy_start_date="{ policy_start_date }">
      {{ policy_start_date ?? 'N/A' }}
    </template>
    <template #item-policy_expiry_date="{ policy_expiry_date }">
      {{ policy_expiry_date ?? 'N/A' }}
    </template>
    <template #item-collected_amount="{ collected_amount }">
      {{ collected_amount ? collected_amount : 0.0 }}
    </template>
    <template #item-price_with_vat="{ price_with_vat }">
      {{ price_with_vat ? price_with_vat : 0.0 }}
    </template>
    <template #item-total_vat="{ total_vat }">
      {{ total_vat ? total_vat : 0.0 }}
    </template>
    <template #item-price_without_vat="{ price_without_vat }">
      {{ price_without_vat ? price_without_vat : 0.0 }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ? discount : 0.0 }}
    </template>
    <template #item-total_price="{ total_price }">
      {{ total_price ? total_price : 0.0 }}
    </template>
    <template #item-pending_balance="{ pending_balance }">
      {{ pending_balance ? pending_balance : 0.0 }}
    </template>
    <template #item-commission_vat_applicable="{ commission_vat_applicable }">
      {{ commission_vat_applicable ? commission_vat_applicable : 0.0 }}
    </template>
    <template #item-vat_on_commission="{ vat_on_commission }">
      {{ vat_on_commission ? vat_on_commission : 0.0 }}
    </template>
    <template
      #item-commission_vat_not_applicable="{ commission_vat_not_applicable }"
    >
      {{ commission_vat_not_applicable ? commission_vat_not_applicable : 0.0 }}
    </template>
    <template #item-policy_issuer="{ policy_issuer }">
      {{ policy_issuer ?? 'N/A' }}
    </template>
    <template #item-advisor="{ advisor }">
      {{ advisor ?? 'N/A' }}
    </template>
    <template #item-lead_source="{ lead_source }">
      {{ lead_source ?? 'N/A' }}
    </template>
    <template #item-notes="{ notes }">
      {{ notes ?? 'N/A' }}
    </template>
    <template #body-append>
      <tr v-if="reportData.data.length > 0" class="total-row sticky bottom-0">
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
