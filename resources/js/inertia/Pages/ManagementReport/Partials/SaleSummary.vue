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
    text: 'Total Policies',
    value: 'total_policies',
  },
  {
    text: 'Total Endorsements',
    value: 'total_endorsements',
  },
  {
    text: 'Total Transactions',
    value: 'total_transaction',
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
    text: 'Commission (VAT applicable)',
    value: 'commission',
  },
  {
    text: 'Total Price',
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

  const headerText = headerMap[props.groupBy] || null;

  if (headerText) {
    tableHeader.splice(0, 1, { text: headerText, value: props.groupBy });
  }
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
