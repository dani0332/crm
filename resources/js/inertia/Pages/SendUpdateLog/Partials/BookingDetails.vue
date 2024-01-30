<script setup>
const { isRequired } = useRules();

const props = defineProps({
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  insuranceProviders: {
    type: Array,
    required: true,
  },
  selectedCategory: {
    type: Object,
    required: true,
  },
  quote: {
    type: Object,
    required: true,
  },
  quoteType: {
    type: Object,
    required: true,
  },
  bookingDetails: {
    type: Object,
    required: true,
    default: () => {}
  },
  payments: {
    type: Array,
    required: true,
    default: () => []
  }
});

const state = reactive({
  isEdit: false,
  isSectionOneEdit: false,
  isSectionTwoEdit: false,
});

const page = usePage();
const notification = useToast();
const sendUpdateStatusEnum = page.props.sendUpdateStatusEnum;

const dateToYMD = date => {
  if (date) {
    const [year, month, day] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};

const isEndorsementFinancial = computed(() => {
  return props.selectedCategory.subCategory.slug === sendUpdateStatusEnum.EF;
});

const isCIR = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CIR;
});

const isCPD = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CPD;
});

const issuanceStatusOptions = computed(() => {
  return [
    { label: 'Portal Down', value: 'portal_down' },
    {
      label: 'Waiting for client confirmation',
      value: 'waiting_for_client_confirmation',
    },
    { label: 'Issue found', value: 'issue_found' },
    { label: 'Underwriter Issuance', value: 'underwriter_issuance' },
    { label: 'Portal Issuance', value: 'portal_issuance' },
    {
      label: 'Policy already issued by the underwriter',
      value: 'policy_already_issued_by_the_underwriter',
    },
    {
      label: 'Renewal, Direct to Underwriter',
      value: 'renewal_direct_to_underwriter',
    },
    { label: 'Policy Issued', value: 'policy_issued' },
    { label: 'Other', value: 'other' },
  ];
});

const transactionPaymentStatus = computed(() => {
  if (Number(props?.quote?.price_with_vat) === 0) {
    return 'Not Paid';
  }
  if (Number(props?.quote?.premium) > Number(props?.quote?.price_with_vat)) {
    return 'Partially Paid';
  }
  if (Number(props?.quote?.premium) === Number(props?.quote?.price_with_vat)) {
    return 'Paid';
  }
});

const bookingDetailsForm = useForm({
  booking_date: dateToYMD(props.quote?.policy_booking_date) || new Date().toJSON().slice(0, 10),
  invoice_description: props.bookingDetails?.invoiceDescription || '',
  broker_invoice_number: props.bookingDetails?.brokerInvoiceNo || '',
  transaction_payment_status: transactionPaymentStatus.value,
  invoice_date: dateToYMD(props?.payments[0]?.insurer_invoice_date) || '',
  insurer_tax_invoice_number: props?.payments[0]?.insurer_tax_number || '',
  discount: props?.payments[0]?.discount_value || '',
  insurer_commmission_invoice_number: props?.payments[0]?.insurer_commmission_invoice_number || '',
  commission_percentage: props?.payments[0]?.commmission_percentage || '',
  commission_vat_not_applicable: props?.payments[0]?.commission_vat_not_applicable || '',
  vat_on_commission: props?.payments[0]?.commission_vat || '',
  commission_vat_applicable: props?.payments[0]?.commission_vat_applicable || '',
  total_commission: props?.payments[0]?.commission || '',
});

const caculateCommission = () => {
  if (bookingDetailsForm.commission_vat_applicable > 0) {
    if (Number(props.quote?.price_with_vat > 0)) {
      bookingDetailsForm.commission_percentage = (
        (bookingDetailsForm.commission_vat_applicable / props.quote?.price_with_vat) *
        100
      ).toFixed(2);

      bookingDetailsForm.vat_on_commission = (
        bookingDetailsForm.commission_percentage * props.vat
      ).toFixed(2);
      bookingDetailsForm.total_commission =
        Number(bookingDetailsForm.vat_on_commission) +
        Number(bookingDetailsForm.commission_vat_applicable);
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT APPLICABLE)',
        position: 'top',
      });
    }
  } else if (bookingDetailsForm.commission_vat_not_applicable > 0) {
    if (Number(props.quote?.price_vat_not_applicable) > 0) {
      bookingDetailsForm.commission_percentage = (
        (bookingDetailsForm.commission_vat_not_applicable /
          props.quote?.price_vat_not_applicable) *
        100
      ).toFixed(2);

      bookingDetailsForm.total_commission = bookingDetailsForm.commission_vat_not_applicable;
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT NOT APPLICABLE)',
        position: 'top',
      });
    }
  } else {
    bookingDetailsForm.commission_percentage = '';
    bookingDetailsForm.vat_on_commission = '';
    bookingDetailsForm.total_commission = '';
  }
};
</script>

<template>
  <template v-if="isEndorsementFinancial">
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible expanded>
        <template #header>
          <div class="flex justify-between gap-4 items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Booking Details
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-y-4">
              <div class="grid sm:grid-cols-2 mt-4">
                <dt class="font-bold text-right mr-10"></dt>
                <dd></dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px] mt-4">
                <dt class="font-bold text-right mr-10">
                  <span>BOOKING DATE</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.booking_date }}</span>
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <span>INVOICE DESCRIPTION</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.invoice_description }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <span>MAIN CLASS OF INSURANCE</span>
                </dt>
                <dd>
                  <span>{{ quoteType }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TRANSACTION PAYMENT STATUS</span>
                    <template #tooltip>
                      Name of the insurance company responsible for the coverage.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.transaction_payment_status }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>SUB CLASS</span>
                    <template #tooltip>
                      Identifies the specific coverage or insurance plan offered
                      by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER INVOICE DATE</span>
                    <template #tooltip>
                      The unique Insurance policy number for the chosen insurance
                      plan offered by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <DatePicker
                    v-model="bookingDetailsForm.invoice_date"
                    name="issuance_date"
                    :disabled="!state.isEdit"
                    placeholder="Enter insurer invoice date"
                    :rules="[isRequired]"
                  />
                  <!-- <span>{{ bookingDetailsForm.invoice_date }}</span> -->
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>BROKER INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.broker_invoice_number }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER TAX INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    type="number"
                    v-model="bookingDetailsForm.insurer_tax_invoice_number"
                    class="w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter insurer Tax Invoice Number"
                  />
                  <!-- <span>{{ bookingDetailsForm.insurer_tax_invoice_number }}</span> -->
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>DISCOUNT</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.discount }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER COMMISSION INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.insurer_commmission_invoice_number }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION (%)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_percentage }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION (VAT NOT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_vat_not_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>VAT ON COMMISSION</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.vat_on_commission }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION VAT APPLICABLE</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    type="number"
                    v-model="bookingDetailsForm.commission_vat_applicable"
                    @change="caculateCommission"
                    class="w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter Commission Amount"
                  />
                  <!-- <span>{{ bookingDetailsForm.commission_vat_applicable }}</span> -->
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TOTAL COMMISSION</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.total_commission }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>PRICE (VAT NOT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.price_vat_not_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TOTAL VAT AMOUNT</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.totol_vat_amount }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>PRICE (VAT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    type="number"
                    v-model="bookingDetailsForm.price_vat_applicable"
                    class="w-full"
                    :disabled="!state.isEdit"
                    placeholder="Enter Price"
                  />
                  <!-- <span>{{ bookingDetailsForm.price_vat_applicable }}</span> -->
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <span>TOTAL PRICE</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.total_price }}</span>
                </dd>
              </div>
            </dl>
          </div>
          <x-divider class="my-4 mt-10" />
          <div class="flex justify-end gap-2">
            <x-button size="sm" @click="state.isEdit = true" v-if="!state.isEdit">
              Edit
            </x-button>
            <template v-else>
              <x-button
                size="sm"
                color="orange"
                @click="state.isEdit = false"
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
                >Cancel</x-button
              >
              <x-button
                size="sm"
                color="primary"                
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
                >Update</x-button
              >
            </template>
          </div>
        </template>
      </Collapsible>
    </div>
  </template>
  <template v-else>

    <!-- Secton One -->
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible expanded>
        <template #header>
          <div class="flex justify-between gap-4 items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Booking Details - {{ isCPD ? 'Reversal Entry' : 'New Policy' }}
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-y-4">
              <div class="grid sm:grid-cols-2 mt-4">
                <dt class="font-bold text-right mr-10"></dt>
                <dd></dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px] mt-4">
                <dt class="font-bold text-right mr-10">
                  <span>BOOKING DATE</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.booking_date }}</span>
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <span>INVOICE DESCRIPTION</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.invoice_description }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <span>MAIN CLASS OF INSURANCE</span>
                </dt>
                <dd>
                  <span>{{ quoteType }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TRANSACTION PAYMENT STATUS</span>
                    <template #tooltip>
                      Name of the insurance company responsible for the coverage.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.transaction_payment_status }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>SUB CLASS</span>
                    <template #tooltip>
                      Identifies the specific coverage or insurance plan offered
                      by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER INVOICE DATE</span>
                    <template #tooltip>
                      The unique Insurance policy number for the chosen insurance
                      plan offered by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.invoice_date }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>BROKER INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.broker_invoice_number }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER TAX INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.insurer_tax_invoice_number }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>DISCOUNT</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.discount }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER COMMISSION INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.insurer_commmission_invoice_number }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION (%)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_percentage }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION (VAT NOT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_vat_not_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>VAT ON COMMISSION</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.vat_on_commission }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION VAT APPLICABLE</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_vat_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TOTAL COMMISSION</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.total_commission }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>PRICE (VAT NOT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.price_vat_not_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TOTAL VAT AMOUNT</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.totol_vat_amount }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>PRICE (VAT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.price_vat_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <span>TOTAL PRICE</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.total_price }}</span>
                </dd>
              </div>
            </dl>
          </div>
          <x-divider class="my-4 mt-10" />
          <div class="flex justify-end gap-2">
            <x-button size="sm" @click="state.isSectionOneEdit = true" v-if="!state.isSectionOneEdit">
              Edit
            </x-button>
            <template v-else>
              <x-button
                size="sm"
                color="orange"
                @click="state.isSectionOneEdit = false"
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
                >Cancel</x-button
              >
              <x-button
                size="sm"
                color="primary"                
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
                >Update</x-button
              >
            </template>
          </div>
        </template>
      </Collapsible>
    </div>

    <!-- Section two -->
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible expanded>
        <template #header>
          <div class="flex justify-between gap-4 items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Booking Details - {{ isCPD ? 'New Entry' : 'Previous Policy' }}
            </h3> 
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-y-4">
              <div class="grid sm:grid-cols-2 mt-4">
                <dt class="font-bold text-right mr-10"></dt>
                <dd></dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px] mt-4">
                <dt class="font-bold text-right mr-10">
                  <span>BOOKING DATE</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.booking_date }}</span>
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <span>INVOICE DESCRIPTION</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.invoice_description }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <span>MAIN CLASS OF INSURANCE</span>
                </dt>
                <dd>
                  <span>{{ quoteType }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TRANSACTION PAYMENT STATUS</span>
                    <template #tooltip>
                      Name of the insurance company responsible for the coverage.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.transaction_payment_status }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>SUB CLASS</span>
                    <template #tooltip>
                      Identifies the specific coverage or insurance plan offered
                      by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER INVOICE DATE</span>
                    <template #tooltip>
                      The unique Insurance policy number for the chosen insurance
                      plan offered by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.invoice_date }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>BROKER INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.broker_invoice_number }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER TAX INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.insurer_tax_invoice_number }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>DISCOUNT</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.discount }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>INSURER COMMISSION INVOICE NUMBER</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.insurer_commmission_invoice_number }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION (%)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_percentage }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION (VAT NOT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_vat_not_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>VAT ON COMMISSION</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.vat_on_commission }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>COMMISSION VAT APPLICABLE</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.commission_vat_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TOTAL COMMISSION</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.total_commission }}</span>
                </dd>
              </div>
  
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>PRICE (VAT NOT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.price_vat_not_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>TOTAL VAT AMOUNT</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.totol_vat_amount }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2">
                <dt class="font-bold text-right mr-10">
                  <x-tooltip position="left">
                    <span>PRICE (VAT APPLICABLE)</span>
                    <template #tooltip>
                      Signifies the date when the insurance policy was officially
                      issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.price_vat_applicable }}</span>
                </dd>
              </div>
              
              <div class="grid sm:grid-cols-2 ml-[-50px]">
                <dt class="font-bold text-right mr-10">
                  <span>TOTAL PRICE</span>
                </dt>
                <dd>
                  <span>{{ bookingDetailsForm.total_price }}</span>
                </dd>
              </div>
            </dl>
          </div>
          <x-divider class="my-4 mt-10" />
          <div class="flex justify-end gap-2">
            <x-button size="sm" @click="state.isSectionTwoEdit = true" v-if="!state.isSectionTwoEdit">
              Edit
            </x-button>
            <template v-else>
              <x-button
                size="sm"
                color="orange"
                @click="state.isSectionTwoEdit = false"
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
                >Cancel</x-button
              >
              <x-button
                size="sm"
                color="primary"                
                :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing"
                >Update</x-button
              >
            </template>
          </div>
        </template>
      </Collapsible>
    </div>
  </template>
</template>
