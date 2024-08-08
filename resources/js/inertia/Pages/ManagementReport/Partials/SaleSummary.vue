<script setup>
const props = defineProps({
  reportData: Array,
  loader: {
    type: Boolean,
    default: false,
  },
  groupBy: {
    type: String || null,
  },
});

const formattedReportData = computed(() => {
  return props?.reportData?.filter(item => {
    return (item.total_policies > 0 || item.total_endorsements > 0);
  });
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
    value: 'commission_vat_applicable',
  },
  {
    text: 'T. Endorsement Amount',
    value: 'endorsements_amount',
  },
  {
    text: 'T. Price',
    value: 'total_price',
  },

]);
// v-if="props.groupBy == 'advisor'"

watchEffect(() => {
  const headerMap = {
    advisor: 'Advisor',
    policy_issuer: 'Policy Issuer',
    customer_group: 'Customer Group',
    insurer: 'Insurer',
    line_of_business: 'Line of Business',
    department: 'Department',

  };

  const headerText = props.groupBy != null ? headerMap[props.groupBy] : headerMap['advisor'];

  const newItem = { text: headerText, value: props.groupBy };
   headerText && tableHeader[0].text === 'T. Policies' ? tableHeader.unshift(newItem) : tableHeader.splice(0, 1, newItem);
   console.log(';props.groupBy',props.groupBy,'| headerMap[props.groupBy]',headerMap[props.groupBy],'| headerMap',  headerMap['advisor'])
    if (props.groupBy === 'advisor') {
        tableHeader.push({text: 'Department', value: 'department'});
    } else {
        const index = tableHeader.findIndex(item => item.text === 'Department' && item.value === 'department');
        if (index !== -1) {
            tableHeader.splice(index, 1);
        }
    }

});

const calculateTotalSum = useCalculateTotalSum;

const isIntegerColumn = key => {
  // Add logic to determine if the column contains an integer
  // For example, check if the key corresponds to an integer column
  return [
    'total_policies',
    'total_endorsements',
    'total_transaction',
    'price_vat_applicable',
    'total_vat',
    'price_vat_not_applicable',
    'discount',
    'commission_vat_applicable',
    'total_price',
    'endorsements_amount'
  ].includes(key);
};
</script>
<template>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loader"
    :headers="tableHeader"
    :items="formattedReportData || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
    :rows-per-page="100"
  >
    <template #item-total_policies="{ total_policies }">
      {{ total_policies ?? 0 }}
    </template>
    <template #item-total_endorsements="{ total_endorsements }">
      {{ total_endorsements ?? 0 }}
    </template>
    <template #item-total_transaction="{ total_transaction }">
      {{ total_transaction ?? 0 }}
    </template>
    <template #item-price_vat_applicable="{ price_vat_applicable }">
      {{ price_vat_applicable ? price_vat_applicable : 0.00 }}
    </template>
    <template #item-total_vat="{ total_vat }">
      {{ total_vat ? total_vat : 0.00 }}
    </template>
    <template #item-price_vat_not_applicable="{ price_vat_not_applicable }">
      {{ price_vat_not_applicable ? price_vat_not_applicable : 0.00 }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ? discount : 0.00 }}
    </template>
    <template #item-commission_vat_applicable="{ commission_vat_applicable }">
      {{
        commission_vat_applicable ? commission_vat_applicable : 0.00
      }}
    </template>
    <template #item-endorsements_amount="{ endorsements_amount }">
      {{ endorsements_amount ? priceFormat(endorsements_amount, true) : '0.00'}}
    </template>
    <template #item-total_price="{ total_price}">
      {{ total_price ? priceFormat(total_price, true) :  '0.00'}}
    </template>


    <template #body-append>
      <tr v-if="reportData?.length > 0" class="total-row">
        <td class="direction-left">Total</td>
        <td
          v-for="header in tableHeader.slice(1, tableHeader.length)"
          :key="header.value"
          class="direction-center"
        >
          {{
            isIntegerColumn(header.value)
              ? priceFormat(calculateTotalSum(reportData, header.value), true)
              : 'N/A'
          }}
        </td>
      </tr>
    </template>
  </DataTable>
</template>
