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
    type: Array,
    required: true,
    default: () => { }
  },
  payments: {
    type: Array,
    required: true,
    default: () => []
  },
  isNegativeValue: {
    type: Boolean,
    required: false,
  },
  realQuote: {
    type: Object,
    required: true,
  },
  updateToCustomerBtn: {
    type: Boolean,
    required: false,
  },
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

const isEF = computed(() => {
  return props.selectedCategory.subCategory.slug === sendUpdateStatusEnum.EF;
});

const isCI = computed(() => {
  return props.selectedCategory.subCategory.slug === sendUpdateStatusEnum.CI;
});

const isCIR = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CIR;
});

const isCPD = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CPD;
});

const isCPU = computed(() => {
  return props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CPU;
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

const invoiceDescription = computed(() => {
  if (props.selectedCategory.subCategory.slug === sendUpdateStatusEnum.EF) {
    return 'E.' + props.bookingDetails.invoice_description;
  } else if (props.selectedCategory.subCategory.slug === sendUpdateStatusEnum.CI) {
    return 'CI.' + props.bookingDetails.invoice_description;
  }

  return props.bookingDetails.invoice_description;
});

const bookingDetailsForm = useForm({
  id: props.sendUpdateLog.id,
  send_update_type: props.selectedCategory.subCategory.slug,
  booking_date: props.bookingDetails?.booking_date || dateToYMD(props.quote?.policy_booking_date) || new Date().toJSON().slice(0, 10),
  invoice_description: invoiceDescription.value || '',
  broker_invoice_number: props.bookingDetails?.broker_invoice_number || '',
  transaction_payment_status: props.bookingDetails?.transaction_payment_status || transactionPaymentStatus.value,
  invoice_date: props.bookingDetails?.invoice_date || dateToYMD(props?.payments[0]?.insurer_invoice_date) || '',
  insurer_tax_invoice_number: props.bookingDetails?.insurer_tax_invoice_number || props?.payments[0]?.insurer_tax_number || '',
  discount: props.bookingDetails?.discount || props?.payments[0]?.discount_value || '0.00',
  insurer_commission_invoice_number: props.bookingDetails?.insurer_commission_invoice_number || props?.payments[0]?.insurer_commmission_invoice_number || '',
  commission_percentage: props.bookingDetails?.commission_percentage || props?.payments[0]?.commmission_percentage || '',
  commission_vat_not_applicable: props.bookingDetails?.commission_vat_not_applicable || props?.payments[0]?.commission_vat_not_applicable || '0.00',
  vat_on_commission: props.bookingDetails?.vat_on_commission || props?.payments[0]?.commission_vat || '',
  commission_vat_applicable: props.bookingDetails?.commission_vat_applicable || props?.payments[0]?.commission_vat_applicable || '',
  total_commission: props.bookingDetails?.total_commission || props?.payments[0]?.commission || '',
  total_vat_amount: props.bookingDetails?.total_vat_amount || null,
  price_vat_applicable: props.bookingDetails?.price_vat_applicable || '',
  price_vat_not_applicable: props.bookingDetails?.price_vat_not_applicable || '0.00',
  total_price: props.bookingDetails?.total_price || '0.00',
});

// convertToNegative function will replace all values in negative if the isNegativeValue is true.
const calculateCommission = () => {
  if (bookingDetailsForm.commission_vat_applicable > 0) {
    if (Number(props.realQuote?.price_with_vat > 0)) {
      let vat_on_commission = bookingDetailsForm.commission_vat_applicable * Number(5 / 100);
      bookingDetailsForm.vat_on_commission = convertToNegative(vat_on_commission);

      let total_commission = Number(bookingDetailsForm.commission_vat_not_applicable) + Number(bookingDetailsForm.commission_vat_applicable) +
        vat_on_commission;
      bookingDetailsForm.total_commission = convertToNegative(total_commission);

      // in this calculation, number 5 is not VAT amount, we need to * the price_vat and price_not_vat with 5% to get the total VAT amount.
      let total_price_with_vat_and_not_vat_applicable = (
        Number(bookingDetailsForm.price_vat_applicable) + Number(bookingDetailsForm.price_vat_not_applicable)
      );
      let total_vat_amount = Number(bookingDetailsForm.price_vat_applicable) * Number(5 / 100);
      bookingDetailsForm.total_vat_amount = convertToNegative(total_vat_amount);

      let total_price = total_price_with_vat_and_not_vat_applicable + Number(total_vat_amount - total_price_with_vat_and_not_vat_applicable);
      bookingDetailsForm.total_price = convertToNegative(total_price);

      bookingDetailsForm.commission_percentage = convertToNegative((total_commission / total_price) * 100);
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT APPLICABLE)',
        position: 'top',
      });
    }
  } else if (bookingDetailsForm.commission_vat_not_applicable > 0) {
    if (Number(props.realQuote?.price_vat_not_applicable) > 0) {
      bookingDetailsForm.commission_percentage = (
        (bookingDetailsForm.commission_vat_not_applicable /
          props.realQuote?.price_vat_not_applicable) *
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

// this function is used to convert the value to negative if the isNegativeValue is true.
function convertToNegative(value) {
  if (props.isNegativeValue) {
    value = -value;
  }
  value = isNaN(value) ? 0 : Number(value);

  return Number(value.toFixed(2)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const saveBookingDetail = (isValid) => {
  if (!isValid) return;
  bookingDetailsForm.post(route('send-update-logs.save-booking-details'),
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'The request has been updated.',
          position: 'top',
        });
      },
      onError: () => {
        notification.error({
          title: 'The data not update.',
          position: 'top',
        });
      },
    },
  );
};
</script>

<template>
  <template v-if="isEF || isCI || isCIR">
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
          <x-form @submit="saveBookingDetail">
            <div class="text-xs">
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
                    <x-tooltip position="left">
                      <span>INVOICE DESCRIPTION</span>
                      <template #tooltip>
                        This field provides a brief description of the invoice, summarizing its content or purpose within
                        the booking.
                      </template>
                    </x-tooltip>
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
                        This status provides a real-time snapshot of the payment progress for each insurer tax invoice.
                        Make sure to update these statuses regularly to maintain financial accuracy.
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
                        Signifies the date when the insurer's invoice within the booking was issued.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <DatePicker v-model="bookingDetailsForm.invoice_date" name="issuance_date" :disabled="!state.isEdit"
                      placeholder="Enter Insurer Invoice date" :rules="[isRequired]" size="xs" />
                    <!-- <span>{{ bookingDetailsForm.invoice_date }}</span> -->
                  </dd>
                </div>

                <div class="grid sm:grid-cols-2 ml-[-50px]">
                  <dt class="font-bold text-right mr-10">
                    <x-tooltip position="left">
                      <span>BROKER INVOICE NUMBER</span>
                      <template #tooltip>
                        Invoice number provided by the broker.
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
                        Enter the unique tax invoice number provided by the insurer. It helps in proper identification and
                        tracking of transactions.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <x-input maxlength="60" v-model="bookingDetailsForm.insurer_tax_invoice_number" class="w-full"
                      :disabled="!state.isEdit" placeholder="Enter insurer Tax Invoice Number" :rules="[isRequired]"
                      size="xs" />
                    <!-- <span>{{ bookingDetailsForm.insurer_tax_invoice_number }}</span> -->
                  </dd>
                </div>

                <div class="grid sm:grid-cols-2 ml-[-50px]">
                  <dt class="font-bold text-right mr-10">
                    <x-tooltip position="left">
                      <span>DISCOUNT</span>
                      <template #tooltip>
                        If applicable, this field indicates the exact amount or percentage reduced from the original
                        price.
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
                        Input the invoice number issued by the insurer for commission purposes. Double-check for accuracy.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <x-input maxlength="60" v-model="bookingDetailsForm.insurer_commission_invoice_number" class="w-full"
                      :disabled="!state.isEdit" placeholder="Enter Commission Tax Invoice No" :rules="[isRequired]"
                      size="xs" />
                    <!--<span>{{ bookingDetailsForm.insurer_commission_invoice_number }}</span>-->
                  </dd>
                </div>

                <div class="grid sm:grid-cols-2 ml-[-50px]">
                  <dt class="font-bold text-right mr-10">
                    <x-tooltip position="left">
                      <span>COMMISSION (%)</span>
                      <template #tooltip>
                        Commission percentage for this transaction.
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
                      <span>PRICE (VAT APPLICABLE)</span>
                      <template #tooltip>
                        Price as per the insurer's tax invoice that VAT is applicable. Please enter the price without
                        including Value Added Tax (VAT). VAT will be calculated separately.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <x-input type="number" v-model="bookingDetailsForm.price_vat_applicable" @change="calculateCommission"
                      class="w-full" :disabled="!state.isEdit" placeholder="Enter Price" :rules="[isRequired]"
                      size="xs" />
                    <!-- <span>{{ bookingDetailsForm.price_vat_applicable }}</span> -->
                  </dd>
                </div>

                <div class="grid sm:grid-cols-2 ml-[-50px]">
                  <dt class="font-bold text-right mr-10">
                    <x-tooltip position="left">
                      <span>VAT ON COMMISSION</span>
                      <template #tooltip>
                        Value Added Tax (VAT) amount applicable to the commission.
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
                      <span>PRICE (VAT NOT APPLICABLE)</span>
                      <template #tooltip>
                        Price that VAT is not applicable. Remember, VAT is exempt for Life Insurance policies.
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
                      <span>TOTAL COMMISSION</span>
                      <template #tooltip>
                        Display the total commission amount including VAT for this transaction. Ensure it matches the
                        calculations.
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
                      <span>COMMISSION VAT APPLICABLE</span>
                      <template #tooltip>
                        Commission amount as per the tax invoice raised by buyer that VAT is applicable. Enter commission
                        amount without including Value Added Tax (VAT). VAT will be calculated separately.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <x-input type="number" v-model="bookingDetailsForm.commission_vat_applicable"
                      @change="calculateCommission" class="w-full" :disabled="!state.isEdit"
                      placeholder="Enter Commission Amount" :rules="[isRequired]" size="xs" />
                    <!-- <span>{{ bookingDetailsForm.commission_vat_applicable }}</span> -->
                  </dd>
                </div>

                <div class="grid sm:grid-cols-2 ml-[-50px]">
                  <dt class="font-bold text-right mr-10">
                    <x-tooltip position="left">
                      <span>TOTAL VAT AMOUNT</span>
                      <template #tooltip>
                        Display the total Value Added Tax (VAT) amount for this transaction. Verify this amount before
                        submission.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <span>{{ bookingDetailsForm.total_vat_amount }}</span>
                  </dd>
                </div>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-bold text-right mr-10">
                    <x-tooltip position="left">
                      <span>COMMISSION (VAT NOT APPLICABLE)</span>
                      <template #tooltip>
                        Commission amount as per the tax invoice raised by buyer that VAT is not applicable.
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
                      <span>TOTAL PRICE</span>
                      <template #tooltip>
                        Display the total price including all charges and VAT as per tax invoice. Make sure it aligns with
                        the final transaction amount.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <span>{{ bookingDetailsForm.total_price }}</span>
                  </dd>
                </div>
              </dl>
            </div>
            <x-divider class="my-4 mt-10" />
            <div class="flex justify-end gap-2">
              <template v-if="!state.isEdit">
                <x-button
                  size="sm"
                  @click="state.isEdit = true"
                >
                  Edit
                </x-button>
                <x-button
                  size="sm"
                  color="orange"
                  v-if="props.updateToCustomerBtn"
                >
                  Send update to customer
                </x-button>
              </template>
              <template v-else>
                <x-button
                  size="sm"
                  color="orange"
                  @click="state.isEdit = false"
                  :loading="bookingDetailsForm.processing"
                  :disabled="bookingDetailsForm.processing"
                >
                  Cancel
                </x-button>
                <x-button
                  size="sm"
                  color="#0CA789"
                  type="submit"
                  :loading="bookingDetailsForm.processing"
                  :disabled="bookingDetailsForm.processing"
                >
                  Update
                </x-button>
              </template>
            </div>
          </x-form>
        </template>
      </Collapsible>
    </div>
  </template>
  <template v-else-if="!isCPU">
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
          <div class="text-xs">
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
                  <span>{{ isCIR ? 'N.' : 'R.' }}{{ bookingDetailsForm.invoice_description }}</span>
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
                  <span>{{ bookingDetailsForm.insurer_commission_invoice_number }}</span>
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
                  <span>{{ bookingDetailsForm.total_vat_amount }}</span>
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
              <x-button size="sm" color="orange" @click="state.isSectionOneEdit = false"
                :loading="bookingDetailsForm.processing" :disabled="bookingDetailsForm.processing">Cancel
              </x-button>
              <x-button size="sm" color="primary" :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing">Update
              </x-button>
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
          <div class="text-xs">
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
                  <span>{{ isCIR ? 'CI.' : 'C.' }}{{ bookingDetailsForm.invoice_description }}</span>
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
                  <span>{{ bookingDetailsForm.insurer_commission_invoice_number }}</span>
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
                  <span>{{ bookingDetailsForm.total_vat_amount }}</span>
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
              <x-button size="sm" color="orange" @click="state.isSectionTwoEdit = false"
                :loading="bookingDetailsForm.processing" :disabled="bookingDetailsForm.processing">Cancel
              </x-button>
              <x-button size="sm" color="primary" :loading="bookingDetailsForm.processing"
                :disabled="bookingDetailsForm.processing">Update
              </x-button>
            </template>
          </div>
        </template>
      </Collapsible>
    </div>
  </template>
</template>
