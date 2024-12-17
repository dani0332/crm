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
    text: 'Insurer',
    value: 'insurer',
    tooltip: 'The name of the insurer',
  },
  {
    text: 'Line of Business',
    value: 'line_of_business',
    tooltip: 'Line of business',
  },
  {
    text: 'Active Policy Count',
    value: 'active_policy_count',
    tooltip:
      'The active policy count of the insurer its corresponding line of business',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_with_vat',
    tooltip:
      'Vatable price. Any amount appearing in this column will be computed with VAT.',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'price_without_vat',
    tooltip:
      'Non-vatable price. Any amount appearing in this column will not be computed with VAT. For example: BASMAH, rider, etc',
  },
]);

const isIntegerColumn = key => {
  // Add logic to determine if the column contains an integer
  // For example, check if the key corresponds to an integer column
  return [
    'active_policy_count',
    'price_with_vat',
    'price_without_vat',
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
    <template #item-insurer="{ insurer }">
      {{ insurer ?? 'N/A' }}
    </template>
    <template #item-line_of_business="{ line_of_business }">
      {{ line_of_business ?? 'N/A' }}
    </template>
    <template #item-active_policy_count="{ active_policy_count }">
      {{ active_policy_count ?? 0 }}
    </template>
    <template #item-price_with_vat="{ price_with_vat }">
      {{ price_with_vat ? price_with_vat : 0 }}
    </template>
    <template #item-price_without_vat="{ price_without_vat }">
      {{ price_without_vat ? price_without_vat : 0 }}
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
