<script setup>
const props = defineProps({
  reportData: Object,
  loader: Boolean,
  groupBy: {
    type: String || null,
  },
});

const calculateTotalSum = useCalculateTotalSum;
const dateFormat = date => useDateFormat(date, 'YYYY-MM-DD');
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
    value: 'quote_uuid',
    tooltip: 'Ref ID of the lead/policy',
  },
  {
    text: 'Department',
    value: 'department',
    tooltip: 'The department of the advisor assigned to this lead',
  },
  {
    text: 'Policy Number',
    value: 'policy_number',
    tooltip: 'The policy number of the lead',
  },
  {
    text: 'Transactions',
    value: 'transactions',
    tooltip: 'The tax invoice number + payment ref id of the lead',
  },
  {
    text: 'Policy Start Date',
    value: 'policy_start_date',
    tooltip: 'Policy inception date',
  },

  {
    text: 'Payment Due Date',
    value: 'payment_due_date',
    tooltip: 'The due date of the child installment',
  },
  {
    text: 'Price (VAT applicable)',
    value: 'price_vat_applicable',
    tooltip:
      'Vatable price. Any amount appearing in this column will be computed with VAT',
  },
  {
    text: 'Total VAT',
    value: 'vat',
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
    text: 'Commission (VAT applicable)',
    value: 'commission_vat_applicable',
    tooltip:
      'Vatable commission. Any amount appearing in this column will be computed with VAT',
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
      'Non-vatable commission. Any amount appearing in this column will not be computed with VAT',
  },
  {
    text: 'Collected Amount',
    value: 'collected_amount',
    tooltip:
      'Total commission of the lead. Formula as follows: Commission(VAT applicable) + VAT on Commission + Commission(VAT not applicable)',
  },
  {
    text: 'Payment Date',
    value: 'payment_date',
    tooltip: 'Payment date',
  },
  {
    text: 'Unpaid',
    value: 'pending_balance',
    tooltip:
      'Pending balance of the policy. Formula as follows: Total Price - Discount - Collected Amount',
  },
  {
    text: 'Collects',
    value: 'collects',
    tooltip: 'Collection type of the lead',
  },
  {
    text: 'Insurer',
    value: 'insurer',
    tooltip: 'Insurance provider',
  },
  {
    text: 'Line of Business',
    value: 'line_of_business',
    tooltip: 'Line of business of the lead',
  },
  {
    text: 'Sub-Type',
    value: 'sub_type_line_of_business',
    tooltip:
      'The the business insurance type (i.e. property, holiday homes, etc). This is only applicable for business/corpline',
  },
  {
    text: 'Customer Name',
    value: 'customer_name',
    tooltip: 'Name of the customer',
  },
  {
    text: 'Advisor',
    value: 'advisor',
    tooltip: 'The advisor assigned to the lead',
  },
  {
    text: 'Policy Issuer',
    value: 'policy_issuer',
    tooltip: 'The user who booked the policy/send update.',
  },
  {
    text: 'Invoice Description',
    value: 'invoice_description',
    tooltip:
      '" the invoice description found in the booking details of the lead. Format of the description as follows: InsurerCode-LOB-Subtype(if corpline)-PolicyNo',
  },
  {
    text: 'Payment Method',
    value: 'payment_method',
    tooltip: 'The method of payment',
  },
  {
    text: 'Payment Gateway',
    value: 'payment_gateway',
    tooltip: 'The payment gateway of the selected payment method',
  },

  {
    text: 'Insurer Invoice No.',
    value: 'insurer_invoice_number',
    tooltip: 'Tax Invoice Number (DN) in Booking Details of the lead',
  },
  {
    text: 'Insurer Invoice Date',
    value: 'insurer_tax_invoice_date',
    tooltip: 'Invoice Date of Tax Invoice',
  },
  {
    text: 'Broker Invoice No',
    value: 'broker_invoice_number',
    tooltip:
      'The system generated broker invoice no for non-self billing insurers',
  },
  {
    text: 'Booking Date',
    value: 'booking_date',
    tooltip: 'The date the endorsement was booked',
  },
  {
    text: 'Endorsement Sub-type',
    value: 'endorsement_sub_type',
    tooltip: 'The type of endorsement',
  },
  {
    text: 'SU Ref-ID',
    value: 'code',
    tooltip: 'The Ref-ID of the Send Update',
  },
  {
    text: 'Commission Tax Invoice Number',
    value: 'insurer_commmission_invoice_number',
    tooltip:
      'Commission Tax Invoice Number (CN) in Booking Details of the lead',
  },
  {
    text: 'Commission Percentage',
    value: 'commmission_percentage',
    tooltip:
      'Commission percentage of the lead based on the inputted commission (vat applicable)/commission (vat not applicable) against the inputted price (vat applicable)/price (vat not applicable). Computation as follows: Commission(VAT applicable) / Price(VAT applicable) or(VAT not applicable)',
  },
  {
    text: 'Transaction Type',
    value: 'transaction_type',
    tooltip:
      "The type of transaction in a customer level namely: new business, existing customer's new business, existing customer's renewal and endorsement",
  },
  {
    text: 'Lead Source',
    value: 'source',
    tooltip: 'The source of the lead',
  },
  {
    text: 'SU Status',
    value: 'status',
    tooltip: 'The status of the Send Update',
  },
  {
    text: 'Sage Receipt ID',
    value: 'sage_reciept_id',
    tooltip: 'Sage Receipt ID',
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
    table-class-name="compact table-fixed"
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
    <template #item-quote_uuid="{ quote_uuid, routeName, main_lead_code }">
      <a
        :href="route(routeName, quote_uuid)"
        class="text-primary-500 hover:underline"
        target="_blank"
      >
        {{ main_lead_code }}
      </a>
    </template>
    <template #item-code="{ uuid, code }">
      <a
        :href="route('send-update.show', { uuid: uuid, refURL: $page.url })"
        class="text-primary-800 underline"
        target="_blank"
        >{{ code }}</a
      >
    </template>
    <template #item-policy_number="{ policy_number, main_lead_policy_number }">
      {{ policy_number ? policy_number : (main_lead_policy_number ?? 'N/A') }}
    </template>
    <template #item-transactions="{ transactions }">
      {{ transactions ? transactions : 'N/A' }}
    </template>
    <template
      #item-policy_start_date="{
        policy_start_date,
        main_lead_policy_start_date,
      }"
    >
      {{
        policy_start_date
          ? policy_start_date
          : (main_lead_policy_start_date ?? 'N/A')
      }}
    </template>
    <template #item-payment_due_date="{ payment_due_date, due_date }">
      {{ payment_due_date ? payment_due_date : due_date ? due_date : 'N/A' }}
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
      {{ pending_balance ?? 0 }}
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
    <template #item-booking_date="{ booking_date }">
      {{ booking_date ?? 'N/A' }}
    </template>
    <template #item-endorsement_sub_type="{ endorsement_sub_type }">
      {{ endorsement_sub_type ?? 'N/A' }}
    </template>
    <template
      #item-insurer_commmission_invoice_number="{
        insurer_commmission_invoice_number,
      }"
    >
      {{ insurer_commmission_invoice_number ?? 'N/A' }}
    </template>
    <template #item-commmission_percentage="{ commmission_percentage }">
      {{ commmission_percentage ?? 'N/A' }}
    </template>
    <template #item-source="{ source }">
      {{ source ?? 'N/A' }}
    </template>
    <template #item-sage_reciept_id="{ sage_reciept_id }">
      {{ sage_reciept_id ?? 'N/A' }}
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
