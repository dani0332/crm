<script setup>
const props = defineProps({
  reportData: Object,
  loader: {
    type: Boolean,
    default: false,
  },
  groupBy: {
    type: String || null,
  },
});

const tableHeader = reactive([
  {
    text: 'T. Policies',
    value: 'total_policies',
  },
  {
    text: 'T. Endorsements',
    value: 'total_endorsements',
  },
  {
    text: 'T. Transactions',
    value: 'total_transaction',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_vat_applicable',
  },
  {
    text: 'T. VAT',
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
    text: 'Commission (VAT applicable)',
    value: 'commission',
  },
  {
    text: 'T. Price',
    value: 'total_price',
  },
]);

watchEffect(() => {
  const headerMap = {
    advisor: 'Advisor',
    policy_issuer: 'Policy Issuer',
    customer_group: 'Customer Group',
    insurer: 'Insurer',
    line_of_business: 'Line of Business',
  };

  console.log(tableHeader);
  const headerText = headerMap[props.groupBy] || null;

  const newItem = { text: headerText, value: props.groupBy };
  headerText && tableHeader[0].text === 'T. Policies'
    ? tableHeader.unshift(newItem)
    : tableHeader.splice(0, 1, newItem);
});
</script>
<template>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loader"
    :headers="tableHeader"
    :items="reportData.data || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
    <template #item-total_policies="{ total_policies }">
      {{ total_policies ?? 0 }}
    </template>
    <template #item-total_transaction="{ total_transaction }">
      {{ total_transaction ?? 0 }}
    </template>
    <template #item-price_vat_applicable="{ price_vat_applicable }">
      {{ price_vat_applicable ?? 0 }}
    </template>
    <template #item-total_vat="{ total_vat }">
      {{ total_vat ?? 0 }}
    </template>
    <template #item-price_vat_not_applicable="{ price_vat_not_applicable }">
      {{ price_vat_not_applicable ?? 0 }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ?? 0 }}
    </template>
    <template #item-commission="{ commission }">
      {{ commission ?? 0 }}
    </template>
    <template #item-total_price="{ total_price }">
      {{ total_price ?? 0 }}
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
