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
    text: 'Line Of Business',
    value: 'line_of_business',
  },
  {
    text: 'Policy Start Date',
    value: 'policy_start_date',
  },
  {
    text: 'Policy Expiry Date',
    value: 'policy_end_date',
  },
  {
    text: 'Collected Amount',
    value: 'collected_amount',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_vat_applicable',
  },
  {
    text: 'Total VAT',
    value: 'total_vat',
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
    text: 'Pending Balance',
    value: 'pending_balance',
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
    text: 'Policy Issuer',
    value: 'policy_issuer',
  },
  {
    text: 'Advisor',
    value: 'advisor',
  },
  {
    text: 'Lead Source',
    value: 'source',
  },
  {
    text: 'Notes ',
    value: 'notes',
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
