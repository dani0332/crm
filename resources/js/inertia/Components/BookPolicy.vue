<script setup>
const page = usePage();
const notification = useNotifications('toast');
const { isRequired } = useRules();
const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  quoteType: {
    type: String,
    default: '',
  },
  modelType: {
    type: String,
    default: '',
  },
  bookPolicyDetails: {
    type: Array,
    default: [],
  },
  payments: {
    type: Array,
    default: [],
  },
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const isLoading = ref(false);
const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;

const dateToYMD = date => {
  if (date) {
    // Check if date is already in YMD format
    const ymdRegex = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/;
    if (ymdRegex.test(date)) {
      return date.split(' ')[0]; // Return only the date part
    }
    const [year, month, day] = date.split('-');
    return `${year}-${month}-${day}`;3
  }
  return '';
};
const dateToDMY = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = `0${d.getMonth() + 1}`.slice(-2);
    const day = `0${d.getDate()}`.slice(-2);
    return `${day}-${month}-${year}`;
  }
  return '';
};
const dateToDMYWithTime = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = `0${d.getMonth() + 1}`.slice(-2);
    const day = `0${d.getDate()}`.slice(-2);
    const hours = `0${d.getHours()}`.slice(-2);
    const minutes = `0${d.getMinutes()}`.slice(-2);
    const seconds = `0${d.getSeconds()}`.slice(-2);
    return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
  }
  return '';
};

const bp = reactive({
  isEditing: false,
});

const currentDate = computed(() => {
  const d = new Date();
  const year = d.getFullYear();
  const month = `0${d.getMonth() + 1}`.slice(-2);
  const day = `0${d.getDate()}`.slice(-2);
  return `${day}-${month}-${year}`;
});

const currentDateTime = computed(() => {
  const d = new Date();
  const year = d.getFullYear();
  const month = `0${d.getMonth() + 1}`.slice(-2);
  const day = `0${d.getDate()}`.slice(-2);
  const hours = `0${d.getHours()}`.slice(-2);
  const minutes = `0${d.getMinutes()}`.slice(-2);
  const seconds = `0${d.getSeconds()}`.slice(-2);
  return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
});


const bpForm = useForm({
  booking_date: dateToDMYWithTime(page.props.quote?.policy_booking_date) ||
  currentDateTime.value,
  transaction_payment_status: page.props.bookPolicyDetails.transactionPaymentStatus,
  invoice_date: dateToYMD(page.props.payments[0]?.insurer_invoice_date) || '',
  invoice_description: page.props.bookPolicyDetails.invoiceDescription || '',
  broker_invoice_number: page.props.bookPolicyDetails.brokerInvoiceNo || '',
  insurer_tax_invoice_number: page.props?.payments[0]?.insurer_tax_number || '',
  insurer_commmission_invoice_number:
    page.props?.payments[0]?.insurer_commmission_invoice_number || '',
  commission_vat_not_applicable:
    page.props?.payments[0]?.commission_vat_not_applicable || '',
  commission_vat_applicable:
    page.props?.payments[0]?.commission_vat_applicable || '',
  commission_percentage: page.props?.payments[0]?.commmission_percentage || '',
  vat_on_commission: page.props?.payments[0]?.commission_vat || '',
  total_commission: page.props?.payments[0]?.commission || '',
  payment_code: page.props?.payments[0]?.code,
  discount: page.props?.payments[0]?.discount_value || '',
  model_type: props.quoteType,
  quote_id: page.props.quote.id,
  modelType: props.modelType,
  transaction_payment_status_tool_tip: page.props.bookPolicyDetails.paymentStatusTooltip
});

const onUpdatebookPolicyDetails = isValid => {
  showInsufficientPaymentAlert();
  if (isValid) {
    bpForm.booking_date = currentDateTime;
    bpForm.post('/quotes/update-booking-policy', {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Book policy details update Successfully',
          position: 'top',
        });

        bp.isEditing = false;
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    });
  } else {
    console.log('Invalid');
  }
};

const isAllowToSendPolicy = ref(false);

const modals = reactive({
  sendPolicyConfirm: false,
  isConfirmed: false,
  sendPolicyPopup: false
});

const confirmSendPolicy = () => {
  if (isPending() || isPartiallyPaid() || isCreditApproved()) {
      modals.sendPolicyPopup = true;
  } else {
      modals.sendPolicyConfirm = true;
  }
};

const submitPolicy = () => {
  isLoading.value = true;
  let url = '/quotes/send-booking-policy';
  let data = {
    send_policy_type: props.bookPolicyDetails.sendPolicyType,
    model_type: props?.quoteType,
    quote_id: props?.quote?.id,
    is_send_policy: isAllowToSendPolicy.value,
    transaction_payment_status: bpForm.transaction_payment_status,
    modelType: props.modelType,
  };
  axios
    .post(url, data)
    .then(response => {
      console.log(response);
      if (response.status == 200) {
        notification.success({
          title: response.data.message,
          position: 'top',
        });
        location.reload();
        modals.sendPolicyConfirm = false;
      }
    })
    .catch(err => {
      const flash_messages = err.response.data.errors;

      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      modals.sendPolicyConfirm = false;
      isLoading.value = false;
    });
};

const calculateCommission = () => {
  if (bpForm.commission_vat_applicable > 0) {
    if (Number(props.quote?.price_without_vat > 0)) {
      bpForm.commission_percentage = (
        (bpForm.commission_vat_applicable / props.quote?.price_without_vat) *
        100
      ).toFixed(2);

      bpForm.vat_on_commission = (
        bpForm.commission_vat_applicable * page.props.vat
      ).toFixed(2);
      bpForm.total_commission = (
        Number(bpForm.vat_on_commission) +
        Number(bpForm.commission_vat_applicable)
      ).toFixed(2);
    } else {
      bpForm.commission_vat_applicable = '';
      notification.error({
        title: 'Please add Policy Detail Price (VAT APPLICABLE)',
        position: 'top',
      });
    }
  } else if (bpForm.commission_vat_not_applicable > 0) {
    if (Number(props.quote?.price_vat_not_applicable) > 0) {
      bpForm.commission_percentage = (
        (bpForm.commission_vat_not_applicable /
          props.quote?.price_vat_not_applicable) *
        100
      ).toFixed(2);

      bpForm.total_commission = Number(
        bpForm.commission_vat_not_applicable,
      ).toFixed(2);
    } else {
      bpForm.commission_vat_not_applicable = '';
      notification.error({
        title: 'Please add Policy Detail Price (VAT NOT APPLICABLE)',
        position: 'top',
      });
    }
  } else {
    bpForm.commission_percentage = '';
    bpForm.vat_on_commission = '';
    bpForm.total_commission = '';
  }
};

// Watch for changes in paymentMethodsForm.collection_date
watch(() => page.props.bookPolicyDetails.transactionPaymentStatus, (newValue, oldValue) => {
  if (newValue && oldValue) {
    bpForm.transaction_payment_status_tool_tip= props.bookPolicyDetails.paymentStatusTooltip;
    bpForm.transaction_payment_status= props.bookPolicyDetails.transactionPaymentStatus;
  }  
});

const sendPolicyConfirmation = () => {
  if (isPartiallyPaid() || isPending() || isCreditApproved()){
    isAllowToSendPolicy.value = true;
  }
  modals.sendPolicyPopup = false;
  modals.sendPolicyConfirm = true;
}
const isUpfrontOrSplitPayments = () => {
  return getPayment()?.frequency == 'upfront' || getPayment()?.frequency == 'split_payments';
}
const isPartiallyPaid = () => {
  return getPayment()?.payment_status?.text == 'PARTIALLY_PAID';
}
const isPending = () => {
  return getPayment()?.payment_status?.text == 'PENDING';
}
const isCreditApproved = () => {
  return getPayment()?.payment_status?.text == 'CREDIT_APPROVED';
}
const getPayment = () => {
  return page.props?.payments[0] ?? null;
}
const showInsufficientPaymentAlert = () => {
  if (isUpfrontOrSplitPayments() && isPartiallyPaid()) {
    notification.error({
      title: 'Insufficient payment',
      position: 'top',
      timeout: 30000
    });
  }
}
const sendPolicyConfirmationHeading = computed(() => {
    if(isPartiallyPaid()) {
        return 'Insufficient payment received';
    } else if (isPending()) {
        return 'Payment not yet completed';
    } else if (isCreditApproved()){
        return "Pending payment under 'Credit approval'";
    }
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex flex-wrap gap-4 justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Booking Details
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form @submit="onUpdatebookPolicyDetails" :auto-focus="false">
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Booking Date
                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.BOOKING_DATE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>{{ bpForm.booking_date.split(' ')[0] }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                    <x-tooltip>
                      Invoice Description
                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.INVOICE_DESCRIPTION
                        }}</span>
                      </template>
                    </x-tooltip>
                </dt>
                <dd>{{ bpForm.invoice_description }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                    <x-tooltip>
                      Line of Business
                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.LINE_OF_BUSINESS
                        }}</span>
                      </template>
                    </x-tooltip>
                </dt>
                <dd>{{ props?.quoteType }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <x-tooltip>
                  Transaction Payment Status
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      productionProcessTooltipEnum.TRANSACTION_PAYMENT_STATUS
                    }}</span>
                  </template>
                </x-tooltip>
                <template v-if="props.quote.quote_status_id == page.props.quoteStatusEnum.PolicyBooked">
                   <x-tooltip position="center">
                    <dd class="border-b border-dotted border-black"> {{ bpForm.transaction_payment_status }}</dd>
                    <template #tooltip> {{ bpForm.transaction_payment_status_tool_tip }}</template>
                  </x-tooltip>
                </template>
                <template v-else>
                  <dd>N/A</dd>
                </template>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                     Sub Type

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.SUB_TYPE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd></dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Insurer Invoice Date

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.INSURER_INVOICE_DATE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>
                  <DatePicker
                    v-model="bpForm.invoice_date"
                    type="date"
                    placeholder="Insurer Invoice Date"
                    class="w-full"
                    :disabled="!bp.isEditing"
                    :rules="[isRequired]"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Broker Invoice No

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.BROKER_INVOICE_NUMBER
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>{{ bpForm.broker_invoice_number }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Insurer Tax Invoice No
                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.INSURER_TAX_INVOICE_NUMBER
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>
                  <x-input
                    v-model="bpForm.insurer_tax_invoice_number"
                    placeholder="Insurer Tax Invoice Number"
                    class="w-full"
                    :disabled="!bp.isEditing"
                    :rules="[isRequired]"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Discount Value
                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.DISCOUNT_VALUE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>{{ bpForm.discount }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Insurer Commission Tax Invoice No

                      <template #tooltip>
                        <span class="custom-tooltip-content">
                          {{
                            productionProcessTooltipEnum.INSURER_COMMISSION_TAX_INVOICE_NUMBER
                          }}
                        </span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>
                  <x-input
                    v-model="bpForm.insurer_commmission_invoice_number"
                    placeholder="Insurer Tax Invoice Number"
                    class="w-full"
                    :disabled="!bp.isEditing"
                    :rules="[isRequired]"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Commission(%)
                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.COMMISSION_PERCENTAGE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>{{ bpForm.commission_percentage }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Commission (VAT NOT APPLICABLE)

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.COMMISSION_VAT_NOT_APPLICABLE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>
                  <x-input
                    v-model="bpForm.commission_vat_not_applicable"
                    @change="calculateCommission"
                    placeholder="Commission VAT NOT APPLICABLE"
                    class="w-full"
                    :disabled="
                      !bp.isEditing || bpForm.commission_vat_applicable !== ''
                    "
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      VAT on Commission

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.VAT_ON_COMMISSION
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>{{ bpForm.vat_on_commission }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Commission (VAT APPLICABLE)

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.COMMISSION_VAT_APPLICABLE
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>
                  <x-input
                    v-model="bpForm.commission_vat_applicable"
                    @change="calculateCommission"
                    placeholder="Commission VAT APPLICABLE"
                    class="w-full"
                    :disabled="
                      !bp.isEditing ||
                      bpForm.commission_vat_not_applicable !== ''
                    "
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip>
                      Total Commission

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          productionProcessTooltipEnum.TOTAL_COMMISSION
                        }}</span>
                      </template>
                    </x-tooltip>
                  </dt>
                <dd>{{ bpForm.total_commission }}</dd>
              </div>
            </dl>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
              <div class="w-full md:w-1/2"></div>
              <div class="w-full md:w-1/2" />
            </div>
            <div class="flex justify-end">
              <template
                v-if="
                  props.quote.quote_status_id ==
                    page.props.quoteStatusEnum.TransactionApproved ||
                  props.quote.quote_status_id ==
                    page.props.quoteStatusEnum.PolicyIssued
                "
              >
                <x-button
                  v-if="bp.isEditing"
                  class="mt-4 mr-2"
                  color="emerald"
                  size="sm"
                  :loading="bpForm.processing"
                  @click.prevent="
                    () => {
                      bp.isEditing = false;
                      bpForm.reset();
                    }
                  "
                >
                  Cancel
                </x-button>
                <x-button
                  v-if="bp.isEditing"
                  class="mt-4 mr-2"
                  color="emerald"
                  size="sm"
                  :loading="bpForm.processing"
                  type="submit"
                >
                  Update
                </x-button>
                <x-button
                  v-if="!bp.isEditing && props.bookPolicyDetails?.editButton"
                  class="mt-4 mr-2"
                  color="emerald"
                  size="sm"
                  @click.prevent="bp.isEditing = true"
                >
                  Edit
                </x-button>
                <x-button
                  size="sm"
                  color="orange"
                  class="mt-4"
                  @click.prevent="confirmSendPolicy"
                  :disabled="bp.isEditing"
                  v-if="props.bookPolicyDetails?.sendButton"
                >
                  {{ props.bookPolicyDetails?.text }}
                </x-button></template
              >

              <template
                v-else-if="
                  props.quote.quote_status_id ==
                  page.props.quoteStatusEnum.PolicyBooked
                "
              >
                <x-tooltip>
                  <x-button
                    class="mt-4 mr-2"
                    size="sm"
                    color="emerald"
                    :disabled="true"
                    >Edit
                  </x-button>
                  <template #tooltip>
                    <span>{{
                      'The button is not accessable because policy has been booked'
                    }}</span>
                  </template>
                </x-tooltip>
                <x-tooltip>
                  <x-button
                    size="sm"
                    class="mt-4 mr-2"
                    color="orange"
                    :disabled="true"
                  >
                    Send Policy
                  </x-button>
                  <template #tooltip>
                    <span>{{
                      'The button is not accessable because policy has been booked'
                    }}</span>
                  </template>
                </x-tooltip>
              </template>

              <template v-else>
                <template
                  v-if="
                    props.quote.quote_status_id ==
                    page.props.quoteStatusEnum.PolicySentToCustomer
                  "
                >
                  <x-button
                    v-if="bp.isEditing"
                    class="mt-4 mr-2"
                    color="emerald"
                    size="sm"
                    :loading="bpForm.processing"
                    @click.prevent="bp.isEditing = false"
                  >
                    Cancel
                  </x-button>
                  <x-button
                    v-if="bp.isEditing"
                    class="mt-4 mr-2"
                    color="emerald"
                    size="sm"
                    :loading="bpForm.processing"
                    type="submit"
                  >
                    Update
                  </x-button>
                  <div v-if="!bp.isEditing && props.bookPolicyDetails?.editButton">
                    <x-button
                      class="mt-4 mr-2"
                      color="emerald"
                      size="sm"
                      :disabled="!props.bookPolicyDetails?.editButton"
                      @click.prevent="bp.isEditing = true"
                    >
                      Edit
                    </x-button>
                  </div>

                  <template v-if="props.bookPolicyDetails?.editButton">
                    <x-button
                      size="sm"
                      class="mt-4 mr-2"
                      color="orange"
                      :disabled="!props.bookPolicyDetails?.editButton || bp.isEditing"
                      @click.prevent="confirmSendPolicy"
                    >
                      Send Policy
                    </x-button></template
                  >
                  <template v-else>
                    <x-tooltip>
                      <x-button
                        size="sm"
                        class="mt-4 mr-2"
                        color="orange"
                        :disabled="!props.bookPolicyDetails?.editButton"
                      >
                        Sending Policy To Customer
                      </x-button>
                      <template #tooltip>
                        <span>{{
                          'The button is not accessable because policy has been sent to customer'
                        }}</span>
                      </template>
                    </x-tooltip></template
                  >
                </template>
              </template>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>
    <x-modal v-model="modals.sendPolicyConfirm" size="lg" show-close backdrop>
      <template #header> Send Policy </template>
      <x-alert
        color="orange"
        light
        type="error"
        class="text-sm mb-4"
        v-if="bookPolicyDetails.sendPolicyType == 'customer'"
      >
        Please be aware that your current action involves sending the policy to
        the customer only.
      </x-alert>
      <div class="multilabel-checkbox">
        <x-checkbox v-model="modals.isConfirmed" />
          <div class="multiline-label">
            <p>I confirm and attest that all the information is correct.</p>
            <p>I confirm I am in compliance with the COC.</p>
          </div>
      </div>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="modals.sendPolicyConfirm = false"
          >
            Cancel
          </x-button>

          <x-button
            size="sm"
            color="error"
            :disabled="!modals.isConfirmed"
            @click.prevent="submitPolicy"
            :loading="isLoading"
          >
            Confirm
          </x-button>
        </div>
      </template>
    </x-modal>
    <x-modal v-model="modals.sendPolicyPopup" show-close backdrop>
      <template #header>  Are you sure you want to continue? </template>
       <div class="text-center">
          <p class="font-semibold pt-3">{{  sendPolicyConfirmationHeading  }}</p>
          <p>Unpaid policies breach our Code of Conduct and will be escalated to management. Do you still want to continue?</p>
       </div>
      <template #actions>
        <div class="text-center space-x-4">
          <x-button
            size="sm"
            ghost
            @click.prevent="modals.sendPolicyPopup = false"
          >
            Go Back
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="sendPolicyConfirmation"
          >
            Continue
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
<style scoped>
.multilabel-checkbox {
  display: flex;
  align-items: center;
}
.multiline-label {
  margin-left: 10px;
}
</style>