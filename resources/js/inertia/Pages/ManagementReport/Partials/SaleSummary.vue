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
    return item.total_policies > 0 || item.total_endorsements > 0;
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
    tooltip:
      'Total count of policies incurred within the selected group by filter',
  },
  {
    text: 'T. Endorsements',
    value: 'total_endorsements',
    tooltip:
      'Total count of endorsements incurred within the selected group by filter',
  },
  {
    text: 'T. Transactions',
    value: 'total_transaction',
    tooltip:
      'Sum of total policies and total endorsements incurred within the selected group by filter',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_vat_applicable',
    tooltip:
      'vatable price incurred within the selected group by filter. Any amount appearing in this column will be computed with VAT',
  },
  {
    text: 'T. VAT',
    value: 'total_vat',
    tooltip: 'VAT of Price (VAT applicable)',
  },
  {
    text: 'Price (VAT not applicable)',
    value: 'price_vat_not_applicable',
    tooltip:
      'Non-vatable price incurred within the selected group by filter. Any amount appearing in this column will not be computed with VAT. For example: BASMAH, rider, etc',
  },
  {
    text: 'Discount',
    value: 'discount',
    tooltip: 'Total discount incurred within the selected group by filter',
  },
  {
    text: 'Commission (VAT applicable)',
    value: 'commission_vat_applicable',
    tooltip:
      'Vatable commission incurred within the selected group by filter. Any amount appearing in this column will be computed with VAT.',
  },
  {
    text: 'VAT ON Commission',
    value: 'commission_vat',
    tooltip: 'VAT on Commission (VAT applicable)',
  },
  {
    text: 'Commission (VAT Not applicable)',
    value: 'commission_vat_not_applicable',
    tooltip:
      'Non-vatable commission incurred within the selected group by filter. Any amount appearing in this column will not be computed with VAT.',
  },
  {
    text: 'T. Endorsement Amount',
    value: 'endorsements_amount',
    tooltip:
      'Total endorsement incurred within the selected group by filter. This was computed using the following formula: Endorsement[Price(VAT Applicable)] + Endorsement[VAT] + Endorsement[Price(VAT Not Applicable)] - Endorsement[Discount]',
  },
  {
    text: 'T. Price',
    value: 'total_price',
    tooltip:
      'Total price plus total endorsement amount less discount. This was computed using the following formula: Price(VAT Applicable) + VAT + Price(VAT Not Applicable) - Discount + Total Endorsement Amount',
  },
]);
// v-if="props.groupBy == 'advisor'"

watchEffect(() => {
  const columns = {
    advisor: {
      text: 'Advisor',
      tooltip: 'The advisor assigned to the policy.',
    },
    support_user: {
      text: 'OE/AE',
      tooltip: 'The OE/AE assigned to the policy.',
    },
    policy_issuer: {
      text: 'Policy Issuer',
      tooltip: 'The user who booked the policy.',
    },
    customer_group: {
      text: 'Customer Group',
      tooltip: 'Customer Group',
    },
    insurer: {
      text: 'Insurer',
      tooltip: 'Insurance provider',
    },
    line_of_business: {
      text: 'Line of Business',
      tooltip: 'Line of business of the lead',
    },
    department: {
      text: 'Department',
      tooltip: 'The department of the advisor assigned to this lead',
    },
    pqa: {
      text: 'PQA',
      tooltip: 'The PQA advisor assigned to the policy.',
    },
    branch_name: {
      text: 'Branch',
      tooltip: 'The branch of the lead',
      value: 'branch_name',
    },
  };

  const headerMap = {
    advisor: ['advisor', 'department'],
    support_user: ['support_user', 'department'],
    policy_issuer: ['policy_issuer'],
    customer_group: ['customer_group'],
    insurer: ['insurer'],
    line_of_business: ['line_of_business'],
    department: ['department'],
    branch_name: ['branch_name'],
    pqa: ['pqa'],
  };

  // Remove all columns in tableHeader that match any key in the columns object
  Object.keys(columns).forEach(colKey => {
    const idx = tableHeader.findIndex(item => item.value === colKey);
    if (idx !== -1) {
      tableHeader.splice(idx, 1);
    }
  });

  const headerText =
    props.groupBy != null ? headerMap[props.groupBy] : headerMap['advisor'];
  let columnsToAdd = [];
  for (const column of headerText) {
    if (!tableHeader.some(item => item.value === column)) {
      columnsToAdd.push({ ...columns[column], value: column });
    }
  }
  tableHeader.unshift(...columnsToAdd);

  if (props.groupBy != 'branch_name') {
    tableHeader.push(columns['branch_name']);
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
    'commission_vat',
    'commission_vat_not_applicable',
    'total_price',
    'endorsements_amount',
  ].includes(key);
};
</script>
<template>
  <DataTable
    class="mt-4"
    table-class-name="table-fixed"
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
    <template
      v-for="header in tableHeader"
      :key="header.value"
      #[`header-${header.value}`]="header"
    >
      <HeaderWithTooltip :header="header" />
    </template>
    <template #item-customer_group="{ customer_name }">
      {{ customer_name ?? '' }}
    </template>
    <template #item-policy_issuer="{ policy_issuer_name }">
      {{ policy_issuer_name ?? '' }}
    </template>
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
      {{ price_vat_applicable ? price_vat_applicable : 0.0 }}
    </template>
    <template #item-total_vat="{ total_vat }">
      {{ total_vat ? total_vat : 0.0 }}
    </template>
    <template #item-price_vat_not_applicable="{ price_vat_not_applicable }">
      {{ price_vat_not_applicable ? price_vat_not_applicable : 0.0 }}
    </template>
    <template #item-discount="{ discount }">
      {{ discount ? discount : 0.0 }}
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
    <template #item-endorsements_amount="{ endorsements_amount }">
      {{
        endorsements_amount ? priceFormat(endorsements_amount, true) : '0.00'
      }}
    </template>
    <template #item-total_price="{ total_price }">
      {{ total_price ? priceFormat(total_price, true) : '0.00' }}
    </template>

    <template #body-append>
      <tr v-if="reportData?.length > 0" class="total-row sticky bottom-0">
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
