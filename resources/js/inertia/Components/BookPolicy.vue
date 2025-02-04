<script setup>
import { useRoundIt } from '../Composables/utilities';
const page = usePage();
const notification = useNotifications('toast');
import SageAPILogs from '@/inertia/Components/SageAPILogs.vue';
import NProgress from 'nprogress';
const { isRequired } = useRules();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  isAmlClearedForQuote: {
    type: Boolean,
    default: false,
  },
  quoteType: {
    type: String,
    default: '',
  },
  modelType: {
    type: String,
    default: '',
  },
  modelClass: {
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
const isAMLNotClearedForTravelQuote = ref(false);
const isExpandedCommissionSchedule = ref([]);
const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const kycEnums = page.props.kycEnums;
const paymentMethodsEnum = page.props.paymentMethodsEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;
const insuranceProviderCodeEnum = page.props.insuranceProviderCodeEnum;
const sendPolicyTypeEnum = page.props.sendPolicyTypeEnum;
const canAny = permissions => useCanAny(permissions);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteBusinessTypeIdEnum = page.props.quoteBusinessTypeIdEnum;
const dateToYMD = date => {
  if (date) {
    // Check if date is already in YMD format
    const ymdRegex = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/;
    if (ymdRegex.test(date)) {
      return date.split(' ')[0]; // Return only the date part
    }
    const [year, month, day] = date.split('-');
    return `${year}-${month}-${day}`;
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
const transactionPaymentStatus = computed(() => {
  let firstPayment = page.props?.payments[0];

  let totalPrice = Number(firstPayment?.total_price);
  let capturedAmount = Number(firstPayment?.captured_amount);
  let discountValue = Number(firstPayment?.discount_value);

  let capturedAmountWithDiscount = capturedAmount + discountValue;

  if (capturedAmount === 0) {
    return 'Not Paid';
  }
  if (totalPrice > capturedAmountWithDiscount) {
    return 'Partially Paid';
  }
  if (capturedAmountWithDiscount >= totalPrice) {
    return 'Paid';
  }
});

const formatAmount = amount => {
  const parsedAmount = parseFloat(amount);
  if (isNaN(parsedAmount)) {
    return '0.00';
  }
  return parsedAmount.toLocaleString('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const isNonSelfBillingEnabledForInsuranceProvider = computed(() => {
  let insuranceProvider = page.props.quote?.insurance_provider;
  if (!insuranceProvider) {
    // If insurance_provider is not available, use insurance_provider_details
    insuranceProvider = page.props.quote?.insurance_provider_details;
    // If insurance_provider_details is not available, use insurer from payment
    if (!insuranceProvider) {
      insuranceProvider = props.payments[0]?.insurance_provider;
    }
  }

  return insuranceProvider?.non_self_billing == 1;
});
// use Broker Invoice Number as Insurer Commission Tax Invoice Number for specific insurance providers
const binAsInsurerCommissionTaxInvoiceNumber = () => {
  console.log(
    'page.props.bookPolicyDetails',
    isNonSelfBillingEnabledForInsuranceProvider.value,
  );
  let brokerInvoiceNo = page.props.bookPolicyDetails?.brokerInvoiceNo;
  if (isNonSelfBillingEnabledForInsuranceProvider.value) {
    return brokerInvoiceNo;
  }
  return '';
};

const bpForm = useForm({
  parent_duplicate_quote_id: page.props.quote?.parent_duplicate_quote_id,
  booking_date: dateToDMYWithTime(page.props.quote?.policy_booking_date) || '',
  transaction_payment_status:
    page.props.bookPolicyDetails.transactionPaymentStatus,
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
  commission_percentage: page.props?.payments[0]?.commmission_percentage || 0,
  vat_on_commission: page.props?.payments[0]?.commission_vat || '',
  total_commission: page.props?.payments[0]?.commission || '',
  payment_code: page.props?.payments[0]?.code,
  discount: page.props?.payments[0]?.discount_value || '',
  model_type: props.quoteType,
  quote_id: page.props.quote.id,
  modelType: props.modelType,
  transaction_payment_status_tool_tip:
    page.props.bookPolicyDetails.paymentStatusTooltip,
  line_of_business: page.props?.bookPolicyDetails?.lineOfBusiness,
  isPolicyCancelledOrPending:
    page.props?.bookPolicyDetails?.isPolicyCancelledOrPending,
  isPolicyCancelledOrPendingToolTtip:
    page.props?.bookPolicyDetails?.isPolicyCancelledOrPendingToolTtip,
});

let is_lacking_payment = ref(
  page.props.bookPolicyDetails.isLackingOfPayment || false,
);

watch(
  () => page.props.bookPolicyDetails.isLackingOfPayment,
  newVal => {
    is_lacking_payment.value = newVal || false;
  },
);

const onUpdatebookPolicyDetails = isValid => {
  showInsufficientPaymentAlert();
  if (isValid) {
    bpForm.post('/quotes/update-booking-policy', {
      preserveScroll: true,
      onSuccess: () => {
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

const isAllowedToSendPolicy = ref(false);

const modals = reactive({
  sendPolicyConfirm: false,
  isConfirmed: false,
  sendPolicyPopup: false,
});

const confirmSendPolicy = () => {
  if (page.props.bookPolicyDetails.isInsufficientPayment) {
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
    is_send_policy: isAllowedToSendPolicy.value,
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
        if (flash_messages[key]) {
          notification.error({
            title: flash_messages[key],
            position: 'top',
          });
        }
      });
    })
    .finally(() => {
      modals.sendPolicyConfirm = false;
      isLoading.value = false;
    });
};

const calculateVatOnCommission = commissionVatApplicable => {
  if (Number(commissionVatApplicable) > 0) {
    return useRoundIt(commissionVatApplicable * page.props.vat);
  } else {
    return 0;
  }
};
const calculateCommissionPercentage = (
  totalCommissionWithoutVat,
  totalPriceWithoutVat,
) => {
  if (totalCommissionWithoutVat > 0) {
    return useRoundIt((totalCommissionWithoutVat / totalPriceWithoutVat) * 100);
  } else {
    return 0;
  }
};

const calculateCommission = () => {
  let totalPriceWithoutVat =
    Number(props.quote?.price_vat_applicable) +
    Number(props.quote?.price_vat_not_applicable);

  let totalCommissionWithoutVat =
    Number(bpForm.commission_vat_not_applicable) +
    Number(bpForm.commission_vat_applicable);

  if (totalCommissionWithoutVat > 0) {
    bpForm.vat_on_commission = calculateVatOnCommission(
      bpForm.commission_vat_applicable,
    );
    bpForm.total_commission =
      totalCommissionWithoutVat + useRoundIt(bpForm.vat_on_commission);

    if (totalPriceWithoutVat > 0) {
      bpForm.commission_percentage = calculateCommissionPercentage(
        totalCommissionWithoutVat,
        totalPriceWithoutVat,
      );
    } else {
      bpForm.commission_percentage = 0;
      notification.error({
        title: 'Total Price is zero for this Policy!',
        position: 'top',
      });
    }
  } else {
    bpForm.commission_percentage = 0;
    bpForm.vat_on_commission = 0;
    bpForm.total_commission = 0;
  }
};
let isLifeLead = page.props.quoteType == quoteTypeCodeEnum.Life;
let isBusinessLead = page.props.quoteType == quoteTypeCodeEnum.Business;

const commissionVatNotApplicableTooltip = computed(() => {
  let toolTip = null;
  if (bpForm.commission_vat_applicable > 0) {
    if (isLifeLead) {
      toolTip = productionProcessTooltipEnum.COMMISSION_VAT_APPLICABLE_FILLED;
    } else if (isBusinessLead) {
      let insuranceBusinessType =
        page.props.quote?.business_type_of_insurance_id;
      let allowedBusinessTypes = [
        quoteBusinessTypeIdEnum.MARINE_CARGO_INDIVIDUAL_SHIPMENT,
        quoteBusinessTypeIdEnum.MARINE_HULL,
        quoteBusinessTypeIdEnum.MARINE_CARGO_OPEN_COVER,
        quoteBusinessTypeIdEnum.GROUP_LIFE,
      ];
      if (allowedBusinessTypes.includes(insuranceBusinessType)) {
        toolTip = productionProcessTooltipEnum.COMMISSION_VAT_APPLICABLE_FILLED;
      }
    }
  }

  return toolTip;
});
const commissionVatApplicableTooltip = computed(() => {
  let toolTip = null;
  if (bpForm.commission_vat_not_applicable > 0) {
    if (isLifeLead) {
      toolTip =
        productionProcessTooltipEnum.COMMISSION_VAT_NOT_APPLICABLE_FILLED;
    } else if (isBusinessLead) {
      let insuranceBusinessType =
        page.props.quote?.business_type_of_insurance_id;
      let allowedBusinessTypes = [
        quoteBusinessTypeIdEnum.MARINE_CARGO_INDIVIDUAL_SHIPMENT,
        quoteBusinessTypeIdEnum.MARINE_HULL,
        quoteBusinessTypeIdEnum.MARINE_CARGO_OPEN_COVER,
        quoteBusinessTypeIdEnum.GROUP_LIFE,
      ];
      if (allowedBusinessTypes.includes(insuranceBusinessType)) {
        toolTip =
          productionProcessTooltipEnum.COMMISSION_VAT_NOT_APPLICABLE_FILLED;
      }
    }
  }
  return toolTip;
});

const disableCommissionVatNotApplicable = computed(() => {
  if (isLifeLead) {
    return !bp.isEditing || bpForm.commission_vat_applicable > 0;
  } else if (isBusinessLead) {
    let insuranceBusinessType = page.props.quote?.business_type_of_insurance_id;
    let allowedBusinessTypes = [
      quoteBusinessTypeIdEnum.MARINE_CARGO_INDIVIDUAL_SHIPMENT,
      quoteBusinessTypeIdEnum.MARINE_HULL,
      quoteBusinessTypeIdEnum.MARINE_CARGO_OPEN_COVER,
      quoteBusinessTypeIdEnum.GROUP_LIFE,
    ];
    if (allowedBusinessTypes.includes(insuranceBusinessType)) {
      return !bp.isEditing || bpForm.commission_vat_applicable > 0;
    }
  }
  return true;
});

const disableCommissionVatApplicable = computed(() => {
  // Enable Commission vat not applicable for all LOBs or when commission vat not applicable is  empty
  return !bp.isEditing || bpForm.commission_vat_not_applicable > 0;
});
const showSendAndBookPolicyButtonBlock = computed(() => {
  const { quote_status_id } = props.quote;
  const {
    TransactionApproved,
    PolicyIssued,
    POLICY_BOOKING_FAILED,
    AMLScreeningCleared,
  } = page.props.quoteStatusEnum;

  const isQuoteTypeTravel = page.props.quoteType == quoteTypeCodeEnum.Travel;

  if (isQuoteTypeTravel) {
    return [
      TransactionApproved,
      PolicyIssued,
      POLICY_BOOKING_FAILED,
      AMLScreeningCleared,
    ].includes(quote_status_id);
  }

  return [TransactionApproved, POLICY_BOOKING_FAILED, PolicyIssued].includes(
    quote_status_id,
  );
});

const showSendAndBookPolicyButton = computed(() => {
  let sendPolicyType = props.bookPolicyDetails?.sendPolicyType;
  let permission = permissionsEnum.SEND_POLICY_TO_CUSTOMER_BUTTON;
  if (sendPolicyType == sendPolicyTypeEnum.SAGE) {
    permission = permissionsEnum.SEND_AND_BOOK_POLICY_BUTTON;
  }
  return props.bookPolicyDetails?.sendButton && can(permission);
});

const disableSendAndBookPolicyButton = computed(() => {
  let sendPolicyType = props.bookPolicyDetails?.sendPolicyType;
  let permission = permissionsEnum.SEND_POLICY_TO_CUSTOMER_BUTTON;
  if (sendPolicyType == sendPolicyTypeEnum.SAGE) {
    permission = permissionsEnum.SEND_AND_BOOK_POLICY_BUTTON;
  }

  let isPolicyStatusCancellationPending =
    props.quote.quote_status_id ==
    page.props.quoteStatusEnum.CancellationPending;
  console.log(
    'disableSendAndBookPolicyButton',
    props.bookPolicyDetails,
    !props.bookPolicyDetails?.sendButton,
    !isPolicyStatusCancellationPending,
    disableIfPolicyFailedAndNoBookingFailedEditPermission.value,
    !can(permission),
  );
  return (
    !props.bookPolicyDetails?.sendButton &&
    !isPolicyStatusCancellationPending &&
    disableIfPolicyFailedAndNoBookingFailedEditPermission.value &&
    !can(permission)
  );
});

const disableBookPolicyButton = computed(() => {
  return (
    !props.bookPolicyDetails?.bookButton ||
    bp.isEditing ||
    disableIfPolicyFailedAndNoBookingFailedEditPermission.value ||
    !can(permissionsEnum.BOOK_POLICY_BUTTON)
  );
});

let isPolicyBookingFailed =
  props.quote.quote_status_id ==
  page.props.quoteStatusEnum.POLICY_BOOKING_FAILED;

const disableIfPolicyFailedAndNoBookingFailedEditPermission = computed(() => {
  let disableEditBookingDetails = false;

  let hasBookingFailedEditPermission = can(permissionsEnum.BOOKING_FAILED_EDIT);

  let policyIssuanceSteps = page.props.lockStatusOfPolicyIssuanceSteps;
  console.log(
    '!isPolicyBookingFailed , !hasBookingFailedEditPermission',
    !isPolicyBookingFailed,
    !hasBookingFailedEditPermission,
  );
  if (
    policyIssuanceSteps?.isPolicyAutomationEnabled &&
    !isPolicyBookingFailed &&
    !hasBookingFailedEditPermission
  ) {
    disableEditBookingDetails =
      policyIssuanceSteps?.isEditBookingDetailsDisabled;
  }

  if (isPolicyBookingFailed && !hasBookingFailedEditPermission) {
    disableEditBookingDetails = true;
  }

  console.log(
    'disableEditBookingDetails',
    disableEditBookingDetails,
    policyIssuanceSteps,
  );
  return disableEditBookingDetails;
});

const showBookingFailedAlert = () => {
  console.log(
    'showBookingFailedAlert',
    disableIfPolicyFailedAndNoBookingFailedEditPermission.value &&
      isPolicyBookingFailed,
  );
  if (
    disableIfPolicyFailedAndNoBookingFailedEditPermission.value &&
    isPolicyBookingFailed
  ) {
    notification.error({
      title:
        'Policy Booking Failed! Please contact finance for correction of details',
      position: 'top',
      timeout: 30000,
    });
  }
};

const isTravelQuoteAndAMLNotCleared = () => {
  const bookPolicyButtonLabel = props.bookPolicyDetails?.text;
  const isSendPolicyToCustomerButton =
    bookPolicyButtonLabel === sendPolicyTypeEnum.CUSTOMER_BUTTON_TEXT;
  const isQuoteTypeTravel = page.props.quoteType == quoteTypeCodeEnum.Travel;
  const isPolicyAMLScreeningCleared = props.isAmlClearedForQuote;
  console.log('isPolicyAMLScreeningCleared', isPolicyAMLScreeningCleared);
  if (
    isQuoteTypeTravel &&
    !isPolicyAMLScreeningCleared &&
    !isSendPolicyToCustomerButton
  ) {
    let allowedQuoteStatuesForAMLAlert = [
      page.props.quoteStatusEnum.TransactionApproved,
      page.props.quoteStatusEnum.PolicyIssued,
      page.props.quoteStatusEnum.PolicySentToCustomer,
    ];
    if (allowedQuoteStatuesForAMLAlert.includes(props.quote.quote_status_id)) {
      notification.error({
        title: 'Kindly clear the AML.',
        position: 'top',
        timeout: 30000,
      });
    }

    isAMLNotClearedForTravelQuote.value = true;
  }
};

const showActionButtons = computed(() => {
  // Hide buttons only when policy is cancelled and have a chilrd lead
  return (
    props.quote.quote_status_id != page.props.quoteStatusEnum.PolicyCancelled ||
    page.props.linkedQuoteDetails.childLeadsCount == 0
  );
});

// Watch for changes in paymentMethodsForm.collection_date
watch(
  () => page.props.bookPolicyDetails.transactionPaymentStatus,
  (newValue, oldValue) => {
    if (newValue && oldValue) {
      bpForm.transaction_payment_status_tool_tip =
        props.bookPolicyDetails.paymentStatusTooltip;
      bpForm.transaction_payment_status =
        props.bookPolicyDetails.transactionPaymentStatus;
    }
  },
);

const sendPolicyConfirmation = () => {
  if (page.props.bookPolicyDetails.isInsufficientPayment) {
    isAllowedToSendPolicy.value = true;
  }
  modals.sendPolicyPopup = false;
  modals.sendPolicyConfirm = true;
};

const getPayment = () => {
  return page.props?.payments[0] ?? null;
};

const showInsufficientPaymentAlert = () => {
  if (page.props.bookPolicyDetails.isInsufficientPayment) {
    notification.error({
      title: 'Insufficient payment',
      position: 'top',
      timeout: 30000,
    });
  }
};

const [EditBookPolicyBtnTemplate, EditBookPolicyBtnResuseTemplate] =
  createReusableTemplate();

const isShowingTransactionPaymentStatus = computed(() => {
  const policyStatuses = [
    page.props.quoteStatusEnum.PolicyBooked,
    page.props.quoteStatusEnum.CancellationPending,
    page.props.quoteStatusEnum.PolicyCancelled,
    page.props.quoteStatusEnum.PolicyCancelledReissued,
  ];
  return policyStatuses.includes(props.quote.quote_status_id);
});
onBeforeMount(() => {
  showBookingFailedAlert();
  isTravelQuoteAndAMLNotCleared();
});
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
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
        <x-form @submit.prevent :auto-focus="false">
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Booking Date</label
                    >
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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                    >
                      Invoice Description
                    </label>
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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Line of Business</label
                    >
                    <template #tooltip>
                      <span class="custom-tooltip-content">{{
                        productionProcessTooltipEnum.LINE_OF_BUSINESS
                      }}</span>
                    </template>
                  </x-tooltip>
                </dt>
                <dd>{{ bpForm.line_of_business }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <x-tooltip>
                  <label
                    class="font-medium border-b-2 border-dotted border-black uppercase"
                    >Transaction Payment Status</label
                  >
                  <template #tooltip>
                    <span class="custom-tooltip-content">
                      {{
                        productionProcessTooltipEnum.TRANSACTION_PAYMENT_STATUS
                      }}
                    </span>
                  </template>
                </x-tooltip>
                <template v-if="isShowingTransactionPaymentStatus">
                  <x-tooltip placement="left">
                    <dd class="border-b border-dotted border-black inline">
                      {{ bpForm.transaction_payment_status }}
                    </dd>
                    <template #tooltip>
                      {{ bpForm.transaction_payment_status_tool_tip }}
                    </template>
                  </x-tooltip>
                </template>
                <template v-else>
                  <dd>N/A</dd>
                </template>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Sub Type</label
                    >

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
                <dt class="font-medium uppercase">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Insurer Invoice Date</label
                    >

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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Broker Invoice No</label
                    >

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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Insurer Tax Invoice No</label
                    >
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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Discount Value</label
                    >
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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Insurer Commission Tax Invoice No</label
                    >

                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{
                          isNonSelfBillingEnabledForInsuranceProvider
                            ? productionProcessTooltipEnum.NON_SELF_BILLING_INSURER_COM_TAX_INVOICE_NUMBER_TOOLTIP
                            : productionProcessTooltipEnum.INSURER_COMMISSION_TAX_INVOICE_NUMBER
                        }}
                      </span>
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    v-model="bpForm.insurer_commmission_invoice_number"
                    placeholder="Insurer Commission Tax Invoice Number"
                    class="w-full"
                    :disabled="!bp.isEditing"
                    :rules="[isRequired]"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                    >
                      Commission(%)</label
                    >
                    <template #tooltip>
                      <span class="custom-tooltip-content">{{
                        productionProcessTooltipEnum.COMMISSION_PERCENTAGE
                      }}</span>
                    </template>
                  </x-tooltip>
                </dt>
                <dd>{{ bpForm.commission_percentage }}%</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Commission (VAT NOT APPLICABLE)</label
                    >

                    <template #tooltip>
                      <span class="custom-tooltip-content">{{
                        productionProcessTooltipEnum.COMMISSION_VAT_NOT_APPLICABLE
                      }}</span>
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <template v-if="commissionVatNotApplicableTooltip">
                    <x-tooltip class="w-full">
                      <x-input
                        v-model="bpForm.commission_vat_not_applicable"
                        @change="calculateCommission"
                        placeholder="Commission VAT NOT APPLICABLE"
                        class="w-full"
                        :disabled="disableCommissionVatNotApplicable"
                      />

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          commissionVatNotApplicableTooltip
                        }}</span>
                      </template>
                    </x-tooltip>
                  </template>
                  <template v-else>
                    <x-input
                      v-model="bpForm.commission_vat_not_applicable"
                      @change="calculateCommission"
                      placeholder="Commission VAT NOT APPLICABLE"
                      class="w-full"
                      :disabled="disableCommissionVatNotApplicable"
                    />
                  </template>
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >VAT on Commission</label
                    >

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
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Commission (VAT APPLICABLE)</label
                    >

                    <template #tooltip>
                      <span class="custom-tooltip-content">{{
                        productionProcessTooltipEnum.COMMISSION_VAT_APPLICABLE
                      }}</span>
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <template v-if="commissionVatApplicableTooltip">
                    <x-tooltip class="w-full">
                      <x-input
                        v-model="bpForm.commission_vat_applicable"
                        @change="calculateCommission"
                        placeholder="Commission VAT APPLICABLE"
                        class="w-full"
                        :disabled="disableCommissionVatApplicable"
                      />

                      <template #tooltip>
                        <span class="custom-tooltip-content">{{
                          commissionVatApplicableTooltip
                        }}</span>
                      </template>
                    </x-tooltip>
                  </template>
                  <template v-else>
                    <x-input
                      v-model="bpForm.commission_vat_applicable"
                      @change="calculateCommission"
                      placeholder="Commission VAT APPLICABLE"
                      class="w-full"
                      :disabled="disableCommissionVatApplicable"
                    />
                  </template>
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  <x-tooltip>
                    <label
                      class="border-b-2 border-dotted border-black uppercase"
                      >Total Commission</label
                    >

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
            <div class="grid-cols-12 mt-5">
              <div class="p-4 rounded shadow mb-6 bg-white">
                <div class="flex justify-between gap-4 items-center mb-4">
                  <h3 class="font-semibold text-primary-800 text-lg">
                    Commission Schedule
                  </h3>
                </div>
                <div class="vue3-easy-data-table tablefixed custom-height">
                  <div
                    class="vue3-easy-data-table__main fixed-header hoverable border-cell custom-height manage-payment-table-parent-div"
                  >
                    <table>
                      <thead class="vue3-easy-data-table__header">
                        <tr>
                          <th class="relative group text-center">
                            <span class="">Payment No</span>
                          </th>
                          <th class="inner-th-class">
                            <span class="">Payment Ref ID</span>
                          </th>

                          <th class="inner-th-class">
                            <span class="">Commission (without VAT)</span>
                          </th>
                          <th class="inner-th-class">
                            <span class="">VAT</span>
                          </th>

                          <th class="inner-th-class">
                            <span class="">Total Commission</span>
                          </th>
                        </tr>
                      </thead>

                      <tbody class="vue3-easy-data-table__body">
                        <template
                          v-for="(item, index) in payments"
                          :key="item.code"
                        >
                          <template
                            v-if="
                              item.total_payments > 0 && item.commission > 0
                            "
                          >
                            <tr>
                              <td class="text-center">
                                <span
                                  class="expand-pointer"
                                  @click="
                                    isExpandedCommissionSchedule[index] =
                                      !isExpandedCommissionSchedule[index]
                                  "
                                  >{{
                                    isExpandedCommissionSchedule[index]
                                      ? '&and;'
                                      : '&or;'
                                  }}
                                </span>
                              </td>
                              <td>{{ item.code }}</td>
                              <td>
                                {{
                                  formatAmount(
                                    item.commission_vat_applicable != 0
                                      ? item.commission_vat_applicable
                                      : item.commission_vat_not_applicable,
                                  )
                                }}
                              </td>
                              <td>{{ formatAmount(item.commission_vat) }}</td>
                              <td>{{ formatAmount(item.commission) }}</td>
                            </tr>
                            <template
                              v-if="isExpandedCommissionSchedule[index]"
                            >
                              <tr
                                v-for="splitPayment in item.payment_splits"
                                :key="splitPayment.id"
                              >
                                <td class="text-center">
                                  {{ splitPayment.sr_no }}
                                </td>
                                <td></td>
                                <td>
                                  {{
                                    formatAmount(
                                      splitPayment.commission_vat_applicable,
                                    )
                                  }}
                                </td>
                                <td>
                                  {{
                                    formatAmount(splitPayment.commission_vat)
                                  }}
                                </td>
                                <td>
                                  {{
                                    formatAmount(
                                      Number(
                                        splitPayment.commission_vat_applicable,
                                      ) + Number(splitPayment.commission_vat),
                                    )
                                  }}
                                </td>
                              </tr>
                            </template>
                          </template>
                        </template>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <EditBookPolicyBtnTemplate v-slot="{ isDisabled }">
              <x-button
                class="mt-4 mr-2"
                color="emerald"
                size="sm"
                @click.prevent="bp.isEditing = true"
                :disabled="
                  isDisabled ||
                  disableIfPolicyFailedAndNoBookingFailedEditPermission
                "
                v-if="readOnlyMode.isDisable === true"
              >
                Edit
              </x-button>
            </EditBookPolicyBtnTemplate>

            <div
              v-if="bpForm.isPolicyCancelledOrPending"
              class="flex justify-end"
            >
              <x-tooltip>
                <x-button class="mt-4 mr-2" color="emerald" size="sm" disabled>
                  Edit
                </x-button>
                <x-button size="sm" color="orange" class="mt-4" disabled>
                  Send and Book Policy
                </x-button>
                <template #tooltip>
                  <span class="custom-tooltip-content">{{
                    bpForm.isPolicyCancelledOrPendingToolTtip
                  }}</span>
                </template>
              </x-tooltip>
            </div>

            <div v-if="showActionButtons" class="flex justify-end">
              <div class="mt-5 mr-2">
                <SageAPILogs
                  :quoteType="props.quoteType"
                  :record="props.quote"
                  :modelClass="props.modelClass"
                  :permissionsEnum="page.props.permissionsEnum"
                />
              </div>
              <template v-if="showSendAndBookPolicyButtonBlock">
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
                  @click.prevent="onUpdatebookPolicyDetails"
                >
                  Update
                </x-button>
                <x-tooltip
                  v-if="page.props.lockLeadSectionsDetails.lead_details"
                  position="bottom"
                >
                  <EditBookPolicyBtnResuseTemplate
                    v-if="
                      !bp.isEditing &&
                      props.bookPolicyDetails?.editButton &&
                      can(permissionsEnum.BOOK_POLICY_DETAILS_ADD)
                    "
                    :isDisabled="true"
                  />
                  <template #tooltip>
                    This lead is now locked as the policy has been booked. If
                    changes are needed, go to 'Send Update', select 'Add
                    Update', and choose 'Correction of Policy'
                  </template>
                </x-tooltip>

                <template v-else>
                  <EditBookPolicyBtnResuseTemplate
                    v-if="
                      !bp.isEditing &&
                      props.bookPolicyDetails?.editButton &&
                      can(permissionsEnum.BOOK_POLICY_DETAILS_ADD)
                    "
                    :isDisabled="
                      disableIfPolicyFailedAndNoBookingFailedEditPermission
                    "
                  />
                </template>
                <x-tooltip>
                  <x-button
                    size="sm"
                    color="orange"
                    class="mt-4"
                    disabled
                    v-if="disableSendAndBookPolicyButton"
                  >
                    {{ props.bookPolicyDetails?.text }}
                  </x-button>
                  <template #tooltip>
                    <span>{{ 'Please update the booking details.' }}</span>
                  </template>
                </x-tooltip>
                <template v-if="is_lacking_payment">
                  <x-tooltip>
                    <x-button
                      size="sm"
                      color="orange"
                      class="mt-4"
                      @click.prevent="confirmSendPolicy"
                      :disabled="
                        bp.isEditing ||
                        is_lacking_payment ||
                        disableIfPolicyFailedAndNoBookingFailedEditPermission
                      "
                      v-if="showSendAndBookPolicyButton"
                    >
                      {{ props.bookPolicyDetails?.text }}
                    </x-button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        Action Needed: Please revise payment details to reflect
                        plan changes.
                      </span>
                    </template>
                  </x-tooltip>
                </template>
                <template v-else>
                  <x-button
                    size="sm"
                    color="orange"
                    class="mt-4"
                    @click.prevent="confirmSendPolicy"
                    :disabled="
                      bp.isEditing ||
                      is_lacking_payment ||
                      isAMLNotClearedForTravelQuote ||
                      disableIfPolicyFailedAndNoBookingFailedEditPermission
                    "
                    v-if="showSendAndBookPolicyButton"
                  >
                    {{ props.bookPolicyDetails?.text }}
                  </x-button>
                </template>
              </template>

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
                      "This lead is now locked as the policy has been booked. If changes are needed, go to 'Send Update', select 'Add Update', and choose 'Correction of Policy'"
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
                    Book Policy
                  </x-button>
                  <template #tooltip>
                    <span>{{
                      'The button is not accessible because policy has been booked'
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
                    @click.prevent="onUpdatebookPolicyDetails"
                  >
                    Update
                  </x-button>

                  <x-tooltip
                    v-if="page.props.lockLeadSectionsDetails.lead_details"
                    position="bottom"
                  >
                    <EditBookPolicyBtnResuseTemplate
                      v-if="
                        !bp.isEditing &&
                        props.bookPolicyDetails?.editButton &&
                        can(permissionsEnum.BOOK_POLICY_DETAILS_ADD)
                      "
                      :isDisabled="true"
                    />
                    <template #tooltip>
                      This lead is now locked as the policy has been booked. If
                      changes are needed, go to 'Send Update', select 'Add
                      Update', and choose 'Correction of Policy'
                    </template>
                  </x-tooltip>

                  <template v-else>
                    <EditBookPolicyBtnResuseTemplate
                      v-if="
                        !bp.isEditing &&
                        props.bookPolicyDetails?.editButton &&
                        can(permissionsEnum.BOOK_POLICY_DETAILS_ADD)
                      "
                      :isDisabled="
                        !props.bookPolicyDetails?.editButton ||
                        disableIfPolicyFailedAndNoBookingFailedEditPermission
                      "
                    />
                  </template>

                  <template
                    v-if="
                      (props.bookPolicyDetails?.bookButton ||
                        props.bookPolicyDetails?.policyCancelled) &&
                      can(permissionsEnum.BOOK_POLICY_BUTTON)
                    "
                  >
                    <x-tooltip v-if="props.bookPolicyDetails.policyCancelled">
                      <x-button
                        size="sm"
                        class="mt-4 mr-2"
                        color="orange"
                        :disabled="
                          disableBookPolicyButton ||
                          isAMLNotClearedForTravelQuote ||
                          disableIfPolicyFailedAndNoBookingFailedEditPermission
                        "
                        @click.prevent="confirmSendPolicy"
                      >
                        {{ props.bookPolicyDetails?.text }}
                      </x-button>
                      <template #tooltip>
                        <span>
                          {{
                            `Cancellation for the ${bpForm.parent_duplicate_quote_id} is still pending`
                          }}
                        </span>
                      </template>
                    </x-tooltip>

                    <x-button
                      v-else
                      size="sm"
                      class="mt-4 mr-2"
                      color="orange"
                      :disabled="
                        disableBookPolicyButton ||
                        isAMLNotClearedForTravelQuote ||
                        disableIfPolicyFailedAndNoBookingFailedEditPermission
                      "
                      @click.prevent="confirmSendPolicy"
                    >
                      <x-tooltip>
                        <span>{{ props.bookPolicyDetails?.text }}</span>
                        <template #tooltip>
                          <span>Please update the booking details.</span>
                        </template>
                      </x-tooltip>
                    </x-button>
                  </template>
                  <template v-else>
                    <x-tooltip>
                      <x-button
                        v-if="can(permissionsEnum.BOOK_POLICY_BUTTON)"
                        size="sm"
                        class="mt-4 mr-2"
                        color="orange"
                        :disabled="true"
                      >
                        Book Policy
                      </x-button>
                      <template #tooltip>
                        <span>Please update the booking details.</span>
                      </template>
                    </x-tooltip>
                  </template>
                </template>
              </template>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>

    <x-modal
      v-model="modals.sendPolicyConfirm"
      size="lg"
      :title="props.bookPolicyDetails?.text"
      show-close
      backdrop
    >
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
      <div class="flex items-center">
        <x-checkbox v-model="modals.isConfirmed" />
        <div class="ml-2">
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
    <x-modal
      v-model="modals.sendPolicyPopup"
      title="Are you sure you want to continue?"
      show-close
      backdrop
    >
      <div class="text-center">
        <p class="font-semibold pt-3">
          {{ props.bookPolicyDetails.paymentStatusHeading }}
        </p>
        <p>{{ props.bookPolicyDetails.paymentStatusDescription }}</p>
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
.expand-pointer {
  cursor: pointer;
  font-size: 20px;
  font-weight: bold;
  color: #1d83bc;
}
</style>
