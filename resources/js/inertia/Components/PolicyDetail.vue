<script setup>
import moment from 'moment';
import { ref, onMounted, onUnmounted, h, defineComponent } from 'vue';
const { isRequired } = useRules();

const page = usePage();

const { price_vat_notapplicable, price_vat_applicable, isNumber } = useRules();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  availablePlans: {
    type: Object,
    default: {},
  },
  modelType: {
    type: String,
    default: '',
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
  showOcrNotification: {
    required: false,
    type: Boolean,
    default: false,
  },
  ocrLoadingDocType: {
    required: false,
    type: [String, null],
    default: null,
  },
  ocrLoadingDocTypes: {
    required: false,
    type: Object,
    default: () => new Set(),
  },
  isDocTypeLoading: {
    required: false,
    type: Function,
    default: () => () => false,
  },
});
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const ocrDocumentTypeEnum = page.props.ocrDocumentTypeEnum;
const notification = useNotifications('toast');

// Helper function to check if a document type is currently being processed
const isDocTypeLoading = docType => {
  let result = false;
  let source = 'none';

  // Handle both function and string types for backwards compatibility
  if (typeof props.ocrLoadingDocType === 'function') {
    result = props.ocrLoadingDocType(docType);
    source = 'function';
  }
  // Check the reactive Set if available
  else if (
    props.ocrLoadingDocTypes &&
    props.ocrLoadingDocTypes.has &&
    props.ocrLoadingDocTypes.has(docType)
  ) {
    result = true;
    source = 'reactiveSet';
  }
  // Check the function prop if available
  else if (typeof props.isDocTypeLoading === 'function') {
    result = props.isDocTypeLoading(docType);
    source = 'functionProp';
  }
  // Fall back to old string comparison
  else {
    result = props.ocrLoadingDocType === docType;
    source = 'stringComparison';
  }

  return result;
};

const dateToYMD = date => {
  if (date) {
    // Check if date is already in YMD format
    const ymdRegex = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/;
    if (ymdRegex.test(date)) {
      return date.split(' ')[0]; // Return only the date part
    }
    const [day, month, year] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};

const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteIssuanceStatusEnum = page.props.quoteIssuanceStatusEnum;
const quoteStatusEnum = page.props.quoteStatusEnum;
const isPolicyCancelledOrPending =
  page.props?.bookPolicyDetails?.isPolicyCancelledOrPending;
const isPolicyCancelledOrPendingToolTtip =
  page.props?.bookPolicyDetails?.isPolicyCancelledOrPendingToolTtip;

const policyIssuanceStatusOptions = computed(() => {
  let policyIssuanceStatus = page.props.policyIssuanceStatus;
  if (
    page.props.quote.policy_issuance_status_id !=
    page.props.policyIssuanceStatusEnum.PolicyIssued
  ) {
    policyIssuanceStatus = policyIssuanceStatus.filter(
      item => item.text !== 'Policy Issued',
    );
  }
  return policyIssuanceStatus.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

const currencyOptions = computed(() => {
  let currencyOptions = page.props.currencyOptions;
  return currencyOptions.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

const setQuoteInsurerNumber = () => {
  let quotePlanList = props.availablePlans;
  if (!quotePlanList || typeof quotePlanList === 'string') return null;
  let obj = quotePlanList?.filter(item => item.id == page.props.quote.plan_id);

  policyDetailsForm.quote_plan_insurer_quote_number =
    obj === undefined ? null : obj[0]?.insurerQuoteNo || null;
};

const policyDetailsState = reactive({
  isEditing: false,
});
const policyDetailsForm = useForm({
  quote_policy_number:
    page.props.quote.policy_number == 'NULL'
      ? ''
      : page.props.quote.policy_number || '',

  quote_policy_issuance_date:
    dateToYMD(page.props.quote.policy_issuance_date) ||
    new Date().toJSON().slice(0, 10),
  price_vat_notapplicable: page.props.quote.price_vat_not_applicable || 0,
  price_vat_applicable: page.props.quote.price_vat_applicable || 0,
  vat: page.props.quote.vat || 0,
  quote_policy_start_date: dateToYMD(page.props.quote.policy_start_date) || '',
  quote_policy_expiry_date:
    dateToYMD(page.props.quote.policy_expiry_date) || '',
  amount_with_vat: 0,
  quote_plan_insurer_quote_number:
    page.props.quote.insurer_quote_number ?? null,
  quote_policy_issuance_status: page.props.quote.policy_issuance_status_id,
  quote_policy_issuance_status_other:
    page.props.quote.policy_issuance_status_other || '',
  modelType: props.modelType,
  quote_id: page.props.quote.id,
  policy_sum_assured_currency_id:
    page.props.quote?.life_quote?.policy_sum_assured_currency_id,
  policy_sum_assured: page.props.quote?.life_quote?.policy_sum_assured,
});

watch(
  () => page.props.quote.insurer_quote_number,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      policyDetailsForm.quote_plan_insurer_quote_number = newValue;
    }
  },
);

watch(
  () => page.props.quote.policy_issuance_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      policyDetailsForm.quote_policy_issuance_status = newValue;
    }
  },
);

watch(
  () => page.props.quote?.price_with_vat,
  (newValue, oldValue) => {
    policyDetailsForm.price_vat_notapplicable =
      page.props.quote.price_vat_not_applicable || 0;
    policyDetailsForm.price_vat_applicable =
      page.props.quote.price_vat_applicable || 0;
    policyDetailsForm.vat = page.props.quote.vat || 0;

    calculateVatAmount();
  },
);

// We can recalculate vat amount if the vat amount is not set or if the vat amount is 0
// We can recalculate on page load if the vat amount is 0
// Second we can recalculate vat when the price vat applicable changes
const calculateVatAmount = (
  isVatAmountRecalculated = false,
  isInitialLoad = false,
) => {
  let priceVatApplicable = Number(policyDetailsForm.price_vat_applicable);
  let priceVatNotApplicable = Number(policyDetailsForm.price_vat_notapplicable);

  // if price vat applicable and not applicable both are there
  if (priceVatApplicable > 0 && priceVatNotApplicable > 0) {
    let vat = policyDetailsForm.vat;
    if (isVatAmountRecalculated || (isInitialLoad && vat == 0)) {
      vat = priceVatApplicable * useRoundIt(page.props.vat).toFixed(2);
      policyDetailsForm.vat = useRoundIt(vat).toFixed(2);
    }
    policyDetailsForm.amount_with_vat = useRoundIt(
      Number(vat) + Number(priceVatApplicable) + Number(priceVatNotApplicable),
    ).toFixed(2);
  } else if (priceVatApplicable > 0) {
    let vat = policyDetailsForm.vat;
    if (isVatAmountRecalculated || (isInitialLoad && vat == 0)) {
      vat = priceVatApplicable * useRoundIt(page.props.vat).toFixed(2);
      policyDetailsForm.vat = useRoundIt(vat).toFixed(2);
    }
    policyDetailsForm.amount_with_vat = useRoundIt(
      Number(vat) + Number(priceVatApplicable),
    ).toFixed(2);
  } else if (priceVatNotApplicable > 0) {
    policyDetailsForm.amount_with_vat = useRoundIt(
      Number(priceVatNotApplicable),
    ).toFixed(2);
  } else {
    policyDetailsForm.vat = 0;
    policyDetailsForm.amount_with_vat = 0;
  }
};
const quoteType = page.props.quoteType.toLowerCase();
const isLifeQuote = quoteType == quoteTypeCodeEnum.Life.toLowerCase();

const isPriceVatApplicableEnabled =
  quoteType == quoteTypeCodeEnum.Life.toLowerCase() ||
  quoteType == quoteTypeCodeEnum.SAVINGS.toLowerCase();

const isPriceVatApplicableRequired = computed(() => {
  if (isPriceVatApplicableEnabled) {
    return true;
  } else if (
    [
      quoteTypeCodeEnum.Health.toLowerCase(),
      quoteTypeCodeEnum.Yacht.toLowerCase(),
      quoteTypeCodeEnum.GroupMedical.toLowerCase(),
      quoteTypeCodeEnum.CORPLINE.toLowerCase(),
      quoteTypeCodeEnum.BusinessQuote.toLowerCase(),
    ].includes(quoteType) &&
    policyDetailsForm.price_vat_applicable == ''
  ) {
    //for health, corpline, groupmedical, yatch, price vat not applicable is required when price vat applicable is empty
    return true;
  }
  return false;
});

const isCarOrBikeQuote = [
  quoteTypeCodeEnum.Car.toLowerCase(),
  quoteTypeCodeEnum.Bike.toLowerCase(),
].includes(quoteType);

const rules = {
  quote_policy_number: v => {
    return !!v || 'This field is required';
  },
  price_vat_applicable: v => {
    //for life, price vat applicable is not required
    if (isPriceVatApplicableEnabled) return true;
    if (v) {
      return (
        /^\d+$/.test(v) || !isNaN(Number(v)) || 'This field must be a number'
      );
    }
    return !!v || 'This field is required';
  },
  price_vat_not_applicable: v => {
    //for life, price vat not applicable is required
    if (isPriceVatApplicableEnabled) {
      if (v) {
        return (
          /^\d+$/.test(v) || !isNaN(Number(v)) || 'This field must be a number'
        );
      }
      return !!v || 'This field is required';
    } else if (
      [
        quoteTypeCodeEnum.Health.toLowerCase(),
        quoteTypeCodeEnum.Yacht.toLowerCase(),
        quoteTypeCodeEnum.GroupMedical.toLowerCase(),
        quoteTypeCodeEnum.CORPLINE.toLowerCase(),
        quoteTypeCodeEnum.BusinessQuote.toLowerCase(),
      ].includes(quoteType) &&
      policyDetailsForm.price_vat_applicable == ''
    ) {
      //for health, corpline, groupmedical, yatch, price vat not applicable is required when price vat applicable is empty
      if (v) {
        return (
          /^\d+$/.test(v) || !isNaN(Number(v)) || 'This field must be a number'
        );
      }
      return !!v || 'This field is required';
    }
    return true;
  },
  quote_plan_insurer_quote_number: v => {
    if (isCarOrBikeQuote) {
      return !!v || 'This field is required';
    }
    return true;
  },

  policy_sum_assured_currency_id: v => {
    if (isLifeQuote) {
      return !!v || 'This field is required';
    }
    return true;
  },

  policy_sum_assured: v => {
    if (isLifeQuote) {
      if (!v) return 'This field is required';

      // Check for incomplete decimal numbers (ending with decimal point)
      if (v.toString().endsWith('.')) {
        return 'Please enter a complete number';
      }

      // Check if it's a valid number
      const num = Number(v);
      if (isNaN(num)) {
        return 'This field must be a valid number';
      }

      // Check if it's negative
      if (num < 0) {
        return 'Policy sum assured cannot be negative';
      }

      // Check if it's zero
      if (num === 0) {
        return 'Policy sum assured must be greater than 0';
      }

      return true;
    }
    return true;
  },

  quote_policy_issuance_date: v => {
    if (v) {
      const date = new Date(v);
      let isDate = date instanceof Date;
      return isDate || 'Date format is incorrect';
    }
    return !!v || 'This field is required';
  },
  policy_start_date: v => {
    if (v) {
      // For Travel LOB, there is no start date validation. For other LOBs, start date can be within 2 months from current date.
      let isTravelQuote =
        props.modelType === quoteTypeCodeEnum.Travel.toLowerCase();

      if (!isTravelQuote) {
        const date = new Date(policyDetailsForm.quote_policy_start_date);

        const currentDate = new Date();
        currentDate.setHours(0, 0, 0, 0);

        const allowedMaxDate = new Date(currentDate);

        allowedMaxDate.setMonth(currentDate.getMonth() + 3);

        allowedMaxDate.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);

        if (date > allowedMaxDate) {
          return 'Please select a date within the next three months';
        }
      }

      return true;
    }
  },
  policy_expiry_date: v => {
    if (v) {
      const policyExpiryDate = new Date(
        policyDetailsForm.quote_policy_expiry_date,
      );
      const policyStartDate = new Date(
        policyDetailsForm.quote_policy_start_date,
      );

      policyExpiryDate.setHours(0, 0, 0, 0);
      policyStartDate.setHours(0, 0, 0, 0);

      if (policyStartDate >= policyExpiryDate) {
        return 'Policy expiry date should be greater than policy start date';
      }

      const allowedMinDate = getMinPolicyExpiryDate();
      const allowedMaxDate = getMaxPolicyExpiryDate();
      if (
        allowedMaxDate !== null &&
        (policyExpiryDate < allowedMinDate || policyExpiryDate > allowedMaxDate)
      ) {
        return 'Please select an end date within 13 months from the start date';
      }

      return true;
    }
  },
};

const onUpdatePolicyDetails = isValid => {
  if (!isValid) return;
  policyDetailsForm.post(`/quotes/${props.modelType}/update-quote-policy`, {
    preserveScroll: true,
    onSuccess: () => {
      if (page.props?.bookPolicyDetails?.isLackingOfPayment) {
        notification.error({
          title:
            'Action Needed: Please revise payment details to reflect plan changes.',
          position: 'top',
          timeout: 10000,
        });
      }
      policyDetailsState.isEditing = false;
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
    onFinish: () => {
      policyDetailsState.isEditing = false;
      router.visit(route(route().current(), props?.quote.uuid), {
        method: 'get',
        preserveScroll: true,
      });
    },
  });
};

onBeforeMount(() => {
  calculateVatAmount(false, true);
});

watch(
  () => policyDetailsForm.quote_policy_start_date,
  quote_policy_start_date => {
    // Validation
    validateField(
      policyDetailsForm,
      quote_policy_start_date,
      'quote_policy_start_date',
      rules.policy_start_date,
    );

    let isCarQuote = props.modelType === quoteTypeCodeEnum.Car.toLowerCase();

    let isHealthOrBusinessQuote =
      props.modelType === quoteTypeCodeEnum.Health.toLowerCase() ||
      props.modelType === quoteTypeCodeEnum.GroupMedical.toLowerCase() ||
      props.modelType === quoteTypeCodeEnum.Business.toLowerCase(); // model type is ""Business"" when visiting the business quote page so added this condition

    if (isCarQuote) {
      //for Car quote, add 13 months to start date to calculate expiry date.
      policyDetailsForm.quote_policy_expiry_date = moment(
        quote_policy_start_date,
      )
        .add(13, 'months')
        .subtract(1, 'days');
    } else if (isHealthOrBusinessQuote) {
      // For Health and Business quote, add 12 months to start date to calculate expiry date
      policyDetailsForm.quote_policy_expiry_date = moment(
        quote_policy_start_date,
      )
        .add(12, 'months')
        .subtract(1, 'days');
    }
  },
);

watch(
  () => policyDetailsForm.quote_policy_expiry_date,
  quote_policy_expiry_date => {
    // Validation
    validateField(
      policyDetailsForm,
      quote_policy_expiry_date,
      'quote_policy_expiry_date',
      rules.policy_expiry_date,
    );
  },
);

const [EditPolicyButtonTemplate, EditPolicyButtonReuseTemplate] =
  createReusableTemplate();

const setQuotePlanInsurerNumber = () => {
  // policyDetailsForm.quote_plan_insurer_quote_number = '';
};
const disableIfPolicyFailedAndNoBookingFailedEditPermission = computed(() => {
  let disableEditPolicyDetails = false;

  let policyIssuanceSteps = page.props.lockStatusOfPolicyIssuanceSteps;
  if (policyIssuanceSteps?.isPolicyAutomationEnabled) {
    disableEditPolicyDetails = policyIssuanceSteps?.isEditPolicyDetailsDisabled;
  }

  let isPolicyBookingFailed =
    page.props.quote.quote_status_id == quoteStatusEnum.POLICY_BOOKING_FAILED;
  let hasBookingFailedEditPermission = can(permissionsEnum.BOOKING_FAILED_EDIT);

  if (isPolicyBookingFailed && !hasBookingFailedEditPermission) {
    disableEditPolicyDetails = true;
  }

  return disableEditPolicyDetails;
});

const getMinPolicyExpiryDate = () => {
  let policyMinStartDate = new Date(policyDetailsForm.quote_policy_start_date);
  policyMinStartDate.setDate(policyMinStartDate.getDate() + 1);
  policyMinStartDate.setHours(0, 0, 0, 0);
  return policyMinStartDate;
};

const getMaxPolicyExpiryDate = () => {
  let isCarQuote = props.modelType === quoteTypeCodeEnum.Car.toLowerCase();
  if (isCarQuote) {
    let policyMaxExpiryDate = new Date(
      policyDetailsForm.quote_policy_start_date,
    );
    policyMaxExpiryDate.setMonth(policyMaxExpiryDate.getMonth() + 13);
    policyMaxExpiryDate.setHours(0, 0, 0, 0);
    return policyMaxExpiryDate;
  }
  return null;
};

watch(
  () => props.availablePlans,
  availablePlans => {
    // setQuotePlanInsurerNumber();
    if (!policyDetailsForm.quote_plan_insurer_quote_number)
      setQuoteInsurerNumber();
  },
);
const readOnlyMode = reactive({
  isDisable: true,
});

watch(
  () => policyDetailsForm.vat,
  newValue => {
    if (Number(newValue) < 0) {
      policyDetailsForm.vat = 0;
    }
  },
);

watch(
  () => policyDetailsForm.price_vat_applicable,
  newValue => {
    if (Number(newValue) < 0) {
      policyDetailsForm.price_vat_applicable = 0;
    }
  },
);

watch(
  () => policyDetailsForm.price_vat_notapplicable,
  newValue => {
    if (Number(newValue) < 0) {
      policyDetailsForm.price_vat_notapplicable = 0;
    }
  },
);

const calculateTotalPrice = () => {
  const vat = useRoundIt(policyDetailsForm.vat).toFixed(2);
  const priceVatApplicable = useRoundIt(
    policyDetailsForm.price_vat_applicable,
  ).toFixed(2);
  // Ensure priceVatNotApplicable is always a number, default to 0 if null/empty
  const priceVatNotApplicable = useRoundIt(
    Number(policyDetailsForm.price_vat_notapplicable) || 0,
  ).toFixed(2);
  // Add priceVatNotApplicable to amountWithVat only if greater than zero
  const amountWithVat = useRoundIt(
    Number(vat) +
      Number(priceVatApplicable) +
      (Number(priceVatNotApplicable) > 0 ? Number(priceVatNotApplicable) : 0),
  ).toFixed(2);
  policyDetailsForm.amount_with_vat = amountWithVat;
  policyDetailsForm.vat = vat;
};

// Define a custom field wrapper component to handle loading state consistently
const FieldLoader = defineComponent({
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { slots }) {
    return () =>
      h('div', { class: 'relative' }, [
        slots.default && slots.default(),
        props.loading &&
          h(
            'div',
            {
              class:
                'absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded z-10',
            },
            [
              h('div', {
                class:
                  'animate-spin h-5 w-5 border-2 border-gray-600 border-t-transparent rounded-full',
              }),
            ],
          ),
      ]);
  },
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex flex-wrap gap-4 justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        </div>
      </template>
      <template #body>
        <x-form @submit="onUpdatePolicyDetails" :auto-focus="false">
          <div class="my-4">
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Policy Number<span class="text-red-500">*</span>
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.POLICY_NUMBER
                    }}</span>
                  </template>
                </x-tooltip>
                <FieldLoader
                  :loading="
                    showOcrNotification &&
                    (typeof props.isDocTypeLoading === 'function'
                      ? props.isDocTypeLoading(
                          ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value,
                        )
                      : props.ocrLoadingDocType ===
                        ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value)
                  "
                >
                  <x-input
                    v-model="policyDetailsForm.quote_policy_number"
                    type="text"
                    placeholder="Policy Number"
                    class="w-full"
                    :custom-error="
                      rules.quote_policy_number(
                        policyDetailsForm.quote_policy_number,
                      )
                    "
                    :rules="[rules.quote_policy_number]"
                    :disabled="!policyDetailsState.isEditing"
                  />
                </FieldLoader>
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    ISSUANCE DATE <span class="text-red-500">*</span>
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.ISSUANCE_DATE
                    }}</span>
                  </template>
                </x-tooltip>
                <DatePicker
                  v-model="policyDetailsForm.quote_policy_issuance_date"
                  :disabled="!policyDetailsState.isEditing"
                  :rules="[isRequired]"
                  class="w-full"
                />
              </div>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Price (VAT NOT APPLICABLE)
                    <span
                      v-if="isPriceVatApplicableRequired"
                      class="text-red-500"
                      >*</span
                    >
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.PRICE_VAT_NOT_APPLICABLE
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.price_vat_notapplicable"
                  @change="calculateVatAmount()"
                  :rules="[rules.price_vat_not_applicable]"
                  type="number"
                  placeholder="Price (VAT NOT APPLICABLE)"
                  class="w-full"
                  :disabled="
                    !policyDetailsState.isEditing ||
                    (page.props.quoteType != quoteTypeCodeEnum.Life &&
                      page.props.quoteType != quoteTypeCodeEnum.SAVINGS &&
                      page.props.quoteType != quoteTypeCodeEnum.Business &&
                      page.props.quoteType != quoteTypeCodeEnum.Health)
                  "
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Start Date <span class="text-red-500">*</span>
                  </label>
                  <template #tooltip>
                    <span>{{ productionProcessTooltipEnum.START_DATE }}</span>
                  </template>
                </x-tooltip>
                <FieldLoader
                  :loading="
                    showOcrNotification &&
                    (typeof props.isDocTypeLoading === 'function'
                      ? props.isDocTypeLoading(
                          ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value,
                        )
                      : props.ocrLoadingDocType ===
                        ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value)
                  "
                >
                  <DatePicker
                    v-model="policyDetailsForm.quote_policy_start_date"
                    :rules="[isRequired, rules.policy_start_date]"
                    placeholder="Start Date"
                    class="w-full"
                    :disabled="!policyDetailsState.isEditing"
                    :error="policyDetailsForm.errors.quote_policy_start_date"
                  />
                </FieldLoader>
              </div>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Price (VAT APPLICABLE)
                    <span v-if="!isLifeQuote" class="text-red-500">*</span>
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.PRICE_VAT_APPLICABLE
                    }}</span>
                  </template>
                </x-tooltip>
                <FieldLoader
                  :loading="
                    showOcrNotification &&
                    (typeof props.isDocTypeLoading === 'function'
                      ? props.isDocTypeLoading(
                          ocrDocumentTypeEnum?.TAX_INVOICE?.value,
                        )
                      : props.ocrLoadingDocType ===
                        ocrDocumentTypeEnum?.TAX_INVOICE?.value)
                  "
                >
                  <x-input
                    v-model="policyDetailsForm.price_vat_applicable"
                    @change="calculateVatAmount(true)"
                    :rules="[rules.price_vat_applicable]"
                    type="number"
                    placeholder="Price (VAT APPLICABLE)"
                    class="w-full"
                    :disabled="
                      !policyDetailsState.isEditing ||
                      ((page.props.quoteType == quoteTypeCodeEnum.Life ||
                        page.props.quoteType == quoteTypeCodeEnum.SAVINGS) &&
                        page.props.quoteType != quoteTypeCodeEnum.Business &&
                        page.props.quoteType != quoteTypeCodeEnum.Health)
                    "
                  />
                </FieldLoader>
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Expiry Date <span class="text-red-500">*</span>
                  </label>
                  <template #tooltip>
                    <span>{{ productionProcessTooltipEnum.EXPIRY_DATE }}</span>
                  </template>
                </x-tooltip>
                <FieldLoader
                  :loading="
                    showOcrNotification &&
                    (typeof props.isDocTypeLoading === 'function'
                      ? props.isDocTypeLoading(
                          ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value,
                        )
                      : props.ocrLoadingDocType ===
                        ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value)
                  "
                >
                  <DatePicker
                    v-model="policyDetailsForm.quote_policy_expiry_date"
                    :rules="[isRequired, rules.policy_expiry_date]"
                    placeholder="Expiry Date"
                    class="w-full"
                    :disabled="!policyDetailsState.isEditing"
                    :error="policyDetailsForm.errors.quote_policy_expiry_date"
                  />
                </FieldLoader>
              </div>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Total VAT Amount
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.TOTAL_VAT_AMOUNT
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.vat"
                  type="number"
                  placeholder="Total VAT Amount"
                  class="w-full"
                  :disabled="
                    !can(permissionsEnum.POLICY_DETAILS_ADD_VAT) ||
                    !policyDetailsState.isEditing
                  "
                  @change="calculateTotalPrice"
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Total Price
                  </label>
                  <template #tooltip>
                    <span>{{ productionProcessTooltipEnum.TOTAL_PRICE }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.amount_with_vat"
                  type="number"
                  placeholder="Price"
                  class="w-full"
                  readonly
                  :disabled="true"
                />
              </div>
            </div>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Insurer Quote Number
                    <span v-if="isCarOrBikeQuote" class="text-red-500">*</span>
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.INSURER_QUOTE_NUMBER
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.quote_plan_insurer_quote_number"
                  type="text"
                  placeholder="Insurer Quote Number"
                  :custom-error="
                    rules.quote_plan_insurer_quote_number(
                      policyDetailsForm.quote_plan_insurer_quote_number,
                    )
                  "
                  :rules="[rules.quote_plan_insurer_quote_number]"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip>
                  <label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                  >
                    Issuance Status
                  </label>
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.ISSURANEC_STATUS
                    }}</span>
                  </template>
                </x-tooltip>
                <x-select
                  v-model="policyDetailsForm.quote_policy_issuance_status"
                  class="w-full"
                  placeholder="Select any option"
                  :disabled="!policyDetailsState.isEditing"
                  :options="policyIssuanceStatusOptions"
                />
              </div>
            </div>
            <div
              v-if="isLifeQuote"
              class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5"
            >
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                    >Sum Assured currency
                    <span class="text-red-500">*</span></label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.SUM_ASSURED_CURRENCY
                    }}</span>
                  </template>
                </x-tooltip>
                <x-select
                  v-model="policyDetailsForm.policy_sum_assured_currency_id"
                  class="w-full"
                  placeholder="Select the currency of sum assured as per the policy schedule"
                  :disabled="!policyDetailsState.isEditing"
                  :options="currencyOptions"
                  :custom-error="
                    rules.policy_sum_assured_currency_id(
                      policyDetailsForm.policy_sum_assured_currency_id,
                    )
                  "
                  :rules="[rules.policy_sum_assured_currency_id]"
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1 uppercase border-b-2 border-dotted border-black"
                    >Policy Sum Assured
                    <span class="text-red-500">*</span></label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.POLICY_SUM_ASSURED
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.policy_sum_assured"
                  type="text"
                  placeholder="Enter the sum assured as per the issued policy schedule"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                  :custom-error="
                    rules.policy_sum_assured(
                      policyDetailsForm.policy_sum_assured,
                    )
                  "
                  :rules="[rules.policy_sum_assured]"
                />
              </div>
            </div>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-input
                  v-if="
                    policyDetailsForm.quote_policy_issuance_status ==
                    quoteIssuanceStatusEnum.Other
                  "
                  v-model="policyDetailsForm.quote_policy_issuance_status_other"
                  type="text"
                  label="Additionl Info"
                  placeholder="Additionl Info"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
            </div>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
              <div class="w-full md:w-1/2"></div>
              <div class="w-full md:w-1/2" />
            </div>

            <EditPolicyButtonTemplate v-slot="{ isDisabled }">
              <x-button
                class="mt-4"
                color="emerald"
                size="sm"
                @click.prevent="policyDetailsState.isEditing = true"
                :disabled="isDisabled"
              >
                Edit
              </x-button>
            </EditPolicyButtonTemplate>

            <div v-if="isPolicyCancelledOrPending" class="flex justify-end">
              <x-tooltip>
                <x-button class="mt-4 mr-2" color="emerald" size="sm" disabled>
                  Edit
                </x-button>
                <template #tooltip>
                  <span class="custom-tooltip-content">
                    {{ isPolicyCancelledOrPendingToolTtip }}
                  </span>
                </template>
              </x-tooltip>
            </div>
            <div
              class="flex justify-end"
              v-if="readOnlyMode.isDisable === true"
            >
              <template
                v-if="
                  quote.quote_status_id ==
                    quoteStatusEnum.TransactionApproved ||
                  quote.quote_status_id == quoteStatusEnum.PolicyPending ||
                  quote.quote_status_id == quoteStatusEnum.PolicyIssued ||
                  quote.quote_status_id ==
                    quoteStatusEnum.PolicySentToCustomer ||
                  quote.quote_status_id == quoteStatusEnum.POLICY_BOOKING_FAILED
                "
              >
                <x-button
                  v-if="policyDetailsState.isEditing"
                  class="mt-4 mr-2"
                  color="emerald"
                  size="sm"
                  :loading="policyDetailsForm.processing"
                  @click.prevent="
                    () => {
                      policyDetailsState.isEditing = false;
                      policyDetailsForm.reset();
                      calculateVatAmount();
                      setQuotePlanInsurerNumber();
                    }
                  "
                >
                  Cancel
                </x-button>
                <x-button
                  v-if="policyDetailsState.isEditing"
                  class="mt-4"
                  color="emerald"
                  size="sm"
                  :loading="policyDetailsForm.processing"
                  type="submit"
                  :disabled="!can(permissionsEnum.POLICY_DETAILS_ADD)"
                >
                  Update
                </x-button>

                <x-tooltip
                  v-if="page.props.lockLeadSectionsDetails.lead_details"
                  placement="bottom"
                >
                  <template
                    v-if="
                      props.modelType === quoteTypeCodeEnum.Car.toLowerCase()
                    "
                  >
                    <EditPolicyButtonReuseTemplate
                      v-if="
                        !policyDetailsState.isEditing &&
                        can(permissionsEnum.POLICY_DETAILS_ADD)
                      "
                      :isDisabled="true"
                    />
                  </template>
                  <template #tooltip
                    >TThis lead is now locked as the policy has been booked. If
                    changes are needed, go to 'Send Update', select 'Add
                    Update', and choose 'Correction of Policy'</template
                  >
                </x-tooltip>

                <template v-else>
                  <EditPolicyButtonReuseTemplate
                    v-if="
                      !policyDetailsState.isEditing &&
                      can(permissionsEnum.POLICY_DETAILS_ADD)
                    "
                    :isDisabled="
                      disableIfPolicyFailedAndNoBookingFailedEditPermission
                    "
                  />
                </template>
              </template>
              <template v-else>
                <x-tooltip>
                  <x-button
                    v-if="quote.quote_status_id == quoteStatusEnum.PolicyBooked"
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
              </template>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
