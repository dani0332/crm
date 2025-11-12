<script setup>
import NProgress from 'nprogress';
import { computed, onMounted, ref } from 'vue';
import {
  AmlApprovalModal,
  DeleteParentPaymentModal,
  DeleteSplitPaymentModal,
  ImageGalleryModal,
  RetryPaymentModal,
  VoidPaymentModal,
} from './PaymentComponents/PaymentModal/index.js';

// New Flow Implementation
import { useAMLKYC } from '../Composables/useAMLKYC';
import { usePayment } from '../Composables/usePayment';
import {
  CreatePaymentForm,
  PaymentHeader,
  PaymentRow,
  PaymentSplitRow,
  PaymentTableHeader,
} from './PaymentComponents/index.js';

// Assign barrel-imported components to prevent IDE from showing them as unused
const components = { PaymentTableHeader, ImageGalleryModal };

const notification = useNotifications('toast');
const page = usePage();

const policyIssuanceEnum = page.props.policyIssuanceEnum;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const paymentLookups = page.props.paymentLookups;

const quoteDocuments = page.props.quoteDocuments;
const can = permission => useCan(permission);

const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentCaptureValidationEnum = page.props.paymentCaptureValidationEnum;
const paymentMethodsEnums = page.props.paymentMethodsEnum;

const { filterCCPayments } = usePayment();
const { isAmlVerified, isKycVerified } = useAMLKYC();

const props = defineProps({
  payments: Array,
  can: Object,
  paymentTooltipEnum: Object,
  proformaPayment: Object,
  paymentDocument: Object,
  quoteRequest: Object,
  paymentMethods: Array,
  quoteType: String,
  storageUrl: String,
  eCommercePrice: {
    type: [String, Number],
    default: '0',
  },
  quoteSubType: {
    type: String,
    default: '',
  },
  sendUpdate: {
    type: Object,
    default: null,
  },
  sendUpdateStatusEnum: {
    type: Array,
    default: null,
  },
  insuranceProviders: {
    type: Array,
    required: false,
  },
  eCommercePriceWithLP: {
    type: [String, Number],
    default: '0',
  },
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
  isPlanDetailEnabled: {
    type: Boolean,
    default: false,
  },
  isPlanDetailSectionEnabled: {
    type: Boolean,
    default: false,
  },
  bookPolicyDetails: {
    type: Array,
    default: [],
  },
  paymentGatewayEnum: {
    type: Array,
    default: [],
  },
  isFuncsEnabled: {
    type: Object,
    default: () => ({}),
  },
  realQuote: Object,
  // For car commercial vehicles
  isCapBtnEnabled: {
    type: Boolean,
    default: false,
  },
  isAllianceProvider: {
    type: Boolean,
    default: false,
  },
});

// All reactive properties are defined here
const createPaymentModal = ref(false);
const isGalleryModelOpen = ref(false);
const isAmlApprovalRequired = ref(false);
const isRetryModalOpen = ref(false);
const retryProcessJobId = ref(0);
const retryPaymentErrorMessage = ref('');
const isDeleteModalOpen = ref(false);
const deleteSplitPaymentId = ref(0);
const deleteSplitPaymentStatus = ref(0);
const deleteSplitPaymentCode = ref('');
const capturePaymentValidationInProcess = ref(false);
const createPaymentFormRef = ref({});
const paymentMethodsFormReplicated = ref({});
const isApproveConfirmedReplicated = ref(false);
const isViewEnabledReplicated = ref(false);
const filesTestReplicated = ref([]);
const currentFileIndexReplicated = ref(0);
const isCreditApprovalViewReplicated = ref(false);
const isCreditCardViewReplicated = ref(false);
const isTransactionCaptureButtonEnabled = ref(true);
const capturePaymentValidationErrorMessage = ref('');
const selectedPaymentForEdit = ref(null);
const showInsurerReceiptNumberInputField = ref(false);
const isInsurerReceiptNumberExistsModalOpen = ref(false);
const insurerReceiptNumberCheckInProcess = ref(false);

// Short: is life plan details enabled
const isLifePlanDetailsEnabled = computed(() => {
  return (
    props.quoteType === quoteTypeCodeEnum.Life &&
    props.isPlanDetailSectionEnabled
  );
});

// for life only
const exchangeRate = ref(props.quoteRequest?.life_quote?.exchange_rate ?? 0);

// Array of quote types to check against
const quoteTypesToCheck = [
  quoteTypeCodeEnum.Car,
  quoteTypeCodeEnum.Health,
  quoteTypeCodeEnum.Travel,
  quoteTypeCodeEnum.Home,
  quoteTypeCodeEnum.SAVINGS,
  quoteTypeCodeEnum.CYBER,
]; //Ecommerce LOBs

if (
  !isLifePlanDetailsEnabled.value &&
  props.quoteType === quoteTypeCodeEnum.Life
) {
  quoteTypesToCheck.push(quoteTypeCodeEnum.Life);
}

// Declare initialAmount.value variable
const initialAmount = ref(0);

const showLackingPayment = () => {
  if (is_lacking_payment.value && props.payments.length > 0) {
    notification.error({
      title: paymentTooltipEnum.PAYMENT_REVISED_ACTION_NEEDED,
      position: 'top',
      timeout: 5000,
    });
  }
};

const getInitalAmountForLifeLOB = () => {
  if (props.quoteRequest?.quote_customer_plan?.plan?.currency !== 'AED') {
    const premiumInAED =
      Math.round(props.quoteRequest.premium * exchangeRate.value * 100) / 100;
    return premiumInAED * props.quoteRequest?.life_quote?.payment_term;
  } else {
    return (
      props.quoteRequest.premium * props.quoteRequest?.life_quote?.payment_term
    );
  }
};

// Check quoteType and set initialAmount.value accordingly
if (props.sendUpdate) {
  initialAmount.value = props.sendUpdate.price_with_vat;
} else if (
  props.quoteType === quoteTypeCodeEnum.Health &&
  props.quoteRequest?.source !== 'Revival'
) {
  initialAmount.value = props.eCommercePrice;
} else if (
  props.quoteType === quoteTypeCodeEnum.Health &&
  props.quoteRequest?.source === 'Revival'
) {
  initialAmount.value = props.quoteRequest.premium;
} else if (props.quoteType === quoteTypeCodeEnum.Bike) {
  initialAmount.value = props.quoteRequest.premium;
} else if (
  props.quoteType === quoteTypeCodeEnum.Life &&
  !props.isPlanDetailSectionEnabled
) {
  initialAmount.value = getInitalAmountForLifeLOB();
} else if (props.isPlanDetailEnabled) {
  initialAmount.value = props.quoteRequest.price_with_vat;
} else if (
  props.isPlanDetailSectionEnabled &&
  props.quoteType === quoteTypeCodeEnum.Home
) {
  initialAmount.value = props.quoteRequest.price_with_vat;
} else {
  initialAmount.value = quoteTypesToCheck.includes(props.quoteType)
    ? props.quoteRequest.premium
    : props.quoteRequest.price_with_vat;
}

const isPaymentAuthorized = computed(() => {
  const payments = props.payments;
  if (payments.length > 0) {
    const notPaidStatusIds = [
      paymentStatusEnum.AUTHORISED,
      paymentStatusEnum.PARTIALLY_PAID,
      paymentStatusEnum.PARTIAL_CAPTURED,
    ];
    const hasNotPaidPayments = payments.some(payment =>
      notPaidStatusIds.includes(payment.payment_status_id),
    );
    if (hasNotPaidPayments) {
      return payments.some(payment =>
        payment.payment_splits.some(
          item =>
            item.payment_method.code === 'CC' &&
            item.payment_status_id == paymentStatusEnum.AUTHORISED,
        ),
      );
    }
  }
  return false;
});

const totalPrice = ref(initialAmount.value); // Initial total price
const totalAmount = ref(initialAmount.value); // Initial total price

const paymentProofDocument = props.paymentDocument.find(
  item => item.text === 'Payment Proof',
);
const approveProofDocument = props.paymentDocument.find(
  item => item.text === 'Receipt',
);

let initalPlanDetails = [];
if (
  props.quoteType == quoteTypeCodeEnum.Business ||
  props.isPlanDetailEnabled
) {
  initalPlanDetails =
    props.quoteRequest?.insurance_provider_details ??
    props.quoteRequest?.insurance_provider;
} else if (props.quoteType == quoteTypeCodeEnum.Home) {
  initalPlanDetails =
    props.quoteRequest.insurance_provider_plan ||
    props.quoteRequest.insurance_provider;
} else if (
  !isLifePlanDetailsEnabled.value &&
  props.quoteType === quoteTypeCodeEnum.Life
) {
  initalPlanDetails = props.quoteRequest.insurance_provider_plan;
} else if (props.quoteType == quoteTypeCodeEnum.SAVINGS) {
  initalPlanDetails = props.quoteRequest.insurance_provider_plan;
} else if (props.quoteType == quoteTypeCodeEnum.CYBER) {
  initalPlanDetails = props.quoteRequest.insurance_provider_plan
} else if (quoteTypesToCheck.includes(props.quoteType)) {
  initalPlanDetails = props.quoteRequest.plan;
} else if (props.quoteType == quoteTypeCodeEnum.Bike) {
  initalPlanDetails = props.quoteRequest?.car_plan;
} else {
  initalPlanDetails = props.quoteRequest?.insurance_provider;
}

let planDetail = ref(initalPlanDetails);

const { copy, copied } = useClipboard();
const onCopyPaymentLink = (paymentLink, paymentStatus) => {
  if (paymentStatus == paymentStatusEnum.PAID) {
    notification.error({
      title: "Payment already 'Paid', button deactivated for this transaction",
      position: 'top',
    });
  } else {
    copy(paymentLink);
    if (copied)
      notification.success({
        title: 'Link copied to clipboard',
        position: 'top',
      });
  }
};

const openModal = () => {
  isGalleryModelOpen.value = true;
};

/**
 * Closes the image gallery modal without affecting the parent payment modal
 * Used by the ImageGalleryModal component through event binding
 */
const closeInnerModal = () => {
  // Just close the gallery modal, not the payment modal
  isGalleryModelOpen.value = false;
};

// Add state for expanded rows
const expandedPaymentRows = ref({});

const toggleExpand = index => {
  expandedPaymentRows.value[index] = !expandedPaymentRows.value[index];
};

const closeAmlConfirmModal = () => {
  isAmlApprovalRequired.value = false;
};

// Define payment approval reasons
const creditApprovalReasons = paymentLookups.paymentCreditApprovalReasons.map(
  item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  }),
);
creditApprovalReasons.unshift({ value: '', label: 'Approval Reason' });

// Define payment discount reasons
const discountReasons = paymentLookups.paymentDiscountReasons.map(item => ({
  value: item.code,
  label: item.text,
  tooltip: item.description,
}));
discountReasons.unshift({ value: '', label: 'Select a reason' });

const generateCCLink = async (code, splitPaymentId, paymentStatus) => {
  if (paymentStatus == paymentStatusEnum.PAID) {
    notification.error({
      title: "Payment already 'Paid', button deactivated for this transaction",
      position: 'top',
    });
  } else {
    try {
      const response = await axios.post('/generate-payment-link-new', {
        quoteId: props.quoteRequest.id,
        quoteUuid: props.quoteRequest.uuid,
        modelType: props.quoteType,
        paymentCode: code,
        splitPaymentId: splitPaymentId,
        isInertia: true,
        new_payment_structure: true,
        isPlanDetailEnabled: props.isPlanDetailEnabled,
      });

      if (response.data.success) {
        const el = document.createElement('textarea');
        el.value = response.data.payment_link;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);

        notification.success({
          title: 'Link copied to clipboard',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Payment Link Generation Failed',
          position: 'top',
        });
      }
    } catch (err) {
      notification.error({
        title: 'Payment Link Generation Failed',
        position: 'top',
      });
    }
  }
};

const sendUpdateStatusEnum = props.sendUpdateStatusEnum;
const isEF = computed(() => {
  return (
    props.sendUpdate !== null &&
    props.sendUpdate?.category?.code === sendUpdateStatusEnum.EF
  );
});

const isCPD = computed(() => {
  return (
    props.sendUpdate !== null &&
    props.sendUpdate?.category?.code === sendUpdateStatusEnum.CPD
  );
});

const addPaymentModal = async () => {
  if (
    !isLifePlanDetailsEnabled.value &&
    props.quoteType === quoteTypeCodeEnum.Life
  ) {
    if (
      exchangeRate.value == 0 &&
      props.quoteRequest?.quote_customer_plan?.plan?.currency !== 'AED'
    ) {
      notification.error({
        title: 'Please update the Exchange Rate in the Available Plan Section.',
        position: 'top',
      });
      return;
    }
  }
  if (props.sendUpdate) {
    if (isEF.value && !props.sendUpdate?.price_with_vat) {
      notification.error({
        title: 'Please update indicative additional price.',
        position: 'top',
      });
      return;
    }
    if (isCPD.value && !props.sendUpdate?.price_with_vat) {
      notification.error({
        title: 'Please update the Total Price in the Plan Details section.',
        position: 'top',
      });
      return;
    }
  }
  if (props.payments.length > 0) {
    notification.error({
      title: "Payment already added, click 'Edit' for changes.",
      position: 'top',
    });
    return;
  }

  if (
    (totalPrice.value > 0 && planDetail.value) ||
    (totalPrice.value > 0 && props.sendUpdate)
  ) {
    totalAmount.value = totalPrice.value;
  } else {
    let errorMsg = 'Please update the Total Price in the Plan Details section.';
    if (quoteTypesToCheck.includes(props.quoteType)) {
      errorMsg = 'Please select a plan.';
    }
    notification.error({
      title: errorMsg,
      position: 'top',
    });
    return;
  }

  createPaymentModal.value = true;
  await new Promise(resolve => setTimeout(resolve, 200));
  createPaymentFormRef.value.resetPaymentMethodsForm();
  const paymentFormUpdateData = {};
  paymentFormUpdateData.payment_method = 'CHQ';
  createPaymentFormRef.value.resetPaymentMethodsModal();
  createPaymentFormRef.value.resetSplitAmountModels();
  createPaymentFormRef.value.resetDueDateModels();
  createPaymentFormRef.value.resetFileUploadModals();
  createPaymentFormRef.value.resetCheckDetailModels();
  createPaymentFormRef.value.resetIsDiscountReasonEnabled();
  createPaymentFormRef.value.resetIsDiscountEnabled();
  createPaymentFormRef.value.resetIsPaymentCalculationError();
  createPaymentFormRef.value.resetShowDiscountOptions();
  createPaymentFormRef.value.resetIsDiscountReasonError();
  createPaymentFormRef.value.resetIsPaymentMetodNotSelected();
  createPaymentFormRef.value.resetIsDocumentNotUploaded();
  createPaymentFormRef.value.resetIsDiscountError();
  createPaymentFormRef.value.resetDiscountError();
  createPaymentFormRef.value.resetIsDiscountDocumentNotUploaded();
  createPaymentFormRef.value.resetDiscountDocumentModel();

  const quoteCollectedBy = [
    quoteTypeCodeEnum.Business,
    quoteTypeCodeEnum.Health,
    quoteTypeCodeEnum.Life,
    quoteTypeCodeEnum.Marine,
    quoteTypeCodeEnum.Pet,
    quoteTypeCodeEnum.Cycle,
    quoteTypeCodeEnum.Yacht,
    quoteTypeCodeEnum.SAVINGS,
  ];

  if (
    (quoteCollectedBy.includes(props.quoteType) &&
      props.quoteSubType != quoteTypeCodeEnum.CORPLINE) ||
    !createPaymentFormRef.value.isBrokerHavePermission()
  ) {
    paymentFormUpdateData.collection_type = 'insurer';
  } else {
    paymentFormUpdateData.collection_type = 'broker';
  }

  paymentFormUpdateData.amount = '';
  paymentFormUpdateData.payment_reference = '';
  paymentFormUpdateData.paymentCode = '';

  paymentFormUpdateData.status = 'create';
  paymentFormUpdateData.collection_date = new Date();
  createPaymentModal.value = true;

  // Special handling for life quotes - map payment term to frequency
  if (
    !isLifePlanDetailsEnabled.value &&
    props.quoteRequest?.life_quote?.payment_term
  ) {
    const paymentTermToFrequency = {
      12: paymentFrequencyEnum.MONTHLY,
      4: paymentFrequencyEnum.QUARTERLY,
      2: paymentFrequencyEnum.SEMI_ANNUAL,
      1: paymentFrequencyEnum.UPFRONT,
    };
    paymentFormUpdateData.frequency =
      paymentTermToFrequency[props.quoteRequest?.life_quote?.payment_term] ||
      paymentFrequencyEnum.UPFRONT;
  } else {
    paymentFormUpdateData.frequency = paymentFrequencyEnum.UPFRONT;
  }

  paymentFormUpdateData.discount = '';
  paymentFormUpdateData.credit_approval = '';
  createPaymentFormRef.value.updateTotalPayments([{ value: '1', label: '1' }]);
  paymentFormUpdateData.payment_no = '1';
  createPaymentFormRef.value.updatePaymentForm(paymentFormUpdateData);
  createPaymentFormRef.value.handleCollectionTypeChange();
  createPaymentFormRef.value.calculatePaymentBreakup();

  // Trigger frequency change for life quotes to update payment schedule
  if (props.quoteType === quoteTypeCodeEnum.Life) {
    createPaymentFormRef.value.handleFrequencyChange();
  }

  createPaymentFormRef.value.applyPermissions();
};

const retrySplitPaymentModal = (process_job_id, message) => {
  retryProcessJobId.value = process_job_id;
  retryPaymentErrorMessage.value = message;
  isRetryModalOpen.value = true;
};

const closeRetryModal = () => {
  isRetryModalOpen.value = false;
};

const deleteSplitPaymentModal = (payment_split_id, payment_status_id, code) => {
  deleteSplitPaymentId.value = payment_split_id;
  deleteSplitPaymentStatus.value = payment_status_id;
  deleteSplitPaymentCode.value = code;
  isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false;
};

const closeInsurerReceiptNumberExistsModal = () => {
  isInsurerReceiptNumberExistsModalOpen.value = false;
};

const checkInsurerReceiptNumber = () => {
  if (
    showInsurerReceiptNumberInputField.value &&
    paymentMethodsFormReplicated.value.insurer_receipt_number
  ) {
    insurerReceiptNumberCheckInProcess.value = true;
    axios
      .post(`/payments/${props.quoteType}/check-insurer-receipt-number`, {
        insurer_receipt_number:
          paymentMethodsFormReplicated.value.insurer_receipt_number,
      })
      .then(res => {
        if (res.data.status) {
          createPaymentFormRef.value?.submitPaymentForm();
        } else {
          isInsurerReceiptNumberExistsModalOpen.value = true;
        }
      })
      .catch(err => {
        notification.error({
          title:
            err?.response?.data?.message ||
            'Insurer receipt number check failed',
          position: 'top',
        });
      })
      .finally(() => {
        insurerReceiptNumberCheckInProcess.value = false;
      });
  } else {
    createPaymentFormRef.value?.submitPaymentForm();
  }
};

/**
 * Opens the payment modal for editing a payment
 * Loads payment data, initializes form, and sets appropriate view/edit state
 *
 * @param {Object} payment - Payment data object
 * @param {number} split_payment_id - ID of the split payment to edit
 * @param {number} sr_no - Serial number of the payment split
 * @param {number} capture_approval - Flag to indicate if this is a capture approval operation
 * @returns {boolean|void} False if edit not allowed, otherwise void
 */
const editPaymentModal = async (
  payment,
  split_payment_id,
  sr_no,
  capture_approval,
) => {
  isTransactionCaptureButtonEnabled.value = true;
  showInsurerReceiptNumberInputField.value = false;
  isInsurerReceiptNumberExistsModalOpen.value = false;
  selectedPaymentForEdit.value = payment;

  if (
    sr_no === 0 &&
    payment.payment_status.id === paymentStatusEnum.PAID &&
    capture_approval === 0 &&
    isPaidEditable.value === false
  ) {
    notification.error({
      title: 'No further actions allowed to paid payments',
      position: 'top',
    });
    return false;
  }

  if (
    payment.collection_type === 'insurer' &&
    isEditPaymentEnabled(payment) &&
    split_payment_id == 0 &&
    sr_no == 0 &&
    capture_approval == 0
  ) {
    notification.error({
      title: paymentTooltipEnum.PAYMENT_AUTHORISED_CANNOT_EDIT,
      position: 'top',
      timeout: 10000,
    });
    return false;
  }

  // Check and enable insurer receipt number input field
  if (split_payment_id) {
    const splitPayment = payment?.payment_splits?.find(
      split => split.id === split_payment_id,
    );
    if (
      payment.collection_type === 'insurer' &&
      splitPayment &&
      splitPayment.payment_method.code == paymentMethodsEnums.InsurerPayment
    ) {
      showInsurerReceiptNumberInputField.value = true;
    }
  }

  isTransactionCaptureButtonEnabled.value = true;

  // Payment Capture Validation for GIG
  if (
    capture_approval == 1 &&
    payment?.insurance_provider?.code == 'AXA' &&
    !props.sendUpdate?.id &&
    (props.quoteType === quoteTypeCodeEnum.Bike ||
      props.quoteType === quoteTypeCodeEnum.Car ||
      props.quoteType === quoteTypeCodeEnum.Home ||
      props.quoteType === quoteTypeCodeEnum.Travel)
  ) {
    capturePaymentValidationInProcess.value = true;
    isTransactionCaptureButtonEnabled.value = false;
    await doCapturePaymentValidation(payment.total_amount, payment?.code);
  }
  createPaymentModal.value = true;
  await new Promise(resolve => setTimeout(resolve, 200));
  createPaymentFormRef.value.resetPaymentForm();
  createPaymentFormRef.value.initializePaymentForm(
    payment,
    split_payment_id,
    sr_no,
    capture_approval,
  );
  createPaymentFormRef.value.handleCollectionTypeChange();
  createPaymentFormRef.value.handleFrequencyChange(false);
  createPaymentFormRef.value.handleApprovalReasonChange(false);
  createPaymentFormRef.value.handleDiscountChange();
  createPaymentFormRef.value.handleDeclinedReasonChange();
  createPaymentFormRef.value.calculateTotalAmount();
  createPaymentFormRef.value.applyPermissions();
  createPaymentFormRef.value.processPaymentSplits(payment);
  createPaymentFormRef.value.finalizePaymentForm(payment, capture_approval);
  createPaymentFormRef.value.setFrequencyTypes();
};

const doCapturePaymentValidation = (totalAmount, paymentCode) => {
  const data = {
    modelType: props.quoteType,
    uuid: props.quoteRequest.uuid,
    captureAmount: totalAmount,
    paymentCode: paymentCode,
    quoteCode: props.quoteRequest?.code,
  };

  return axios
    .post(`/payments/${props.quoteType}/payments-capture-validation`, data)
    .then(async res => {
      if (res?.data?.response?.status == paymentCaptureValidationEnum.SUCCESS) {
        createPaymentModal.value = true;
        await new Promise(resolve => setTimeout(resolve, 200));
        createPaymentFormRef.value.updatePremiumToCapture(
          res?.data?.response?.premiumAmount,
        );
        isTransactionCaptureButtonEnabled.value = true;
      } else {
        isTransactionCaptureButtonEnabled.value = false;
        capturePaymentValidationErrorMessage.value =
          res?.data?.response?.message;
      }
    })
    .catch(err => {
      isTransactionCaptureButtonEnabled.value = false;
    })
    .finally(() => {
      capturePaymentValidationInProcess.value = false;
    });
};

const alertCapture = payment => {
  let errorMsg = 'Pending payment';
  if (payment.is_approved === 1) {
    errorMsg = 'Transaction already approved';
  }
  notification.error({
    title: errorMsg,
    position: 'top',
  });
};

const planText = ref();
const fetchPlans = () => {
  let providerId = props.sendUpdate?.insurance_provider_id;
  let planId = props.sendUpdate?.plan_id;
  if (!providerId || !planId) {
    providerId = props.realQuote?.insurance_provider_id;
    planId = props.realQuote?.plan_id;
  }
  let url = `/get-plans/${props.quoteType}/${providerId}/${planId}`;
  axios
    .get(url)
    .then(res => {
      planText.value = res.data?.text ?? res.data?.planName;
    })
    .catch(err => {});
};

// Ecom leads
watch(
  () => props.realQuote?.plan_id,
  () => {
    if (props.realQuote?.plan_id) {
      fetchPlans();
    }
  },
);

// Insly Leads
watch(
  () => props.sendUpdate?.plan_id,
  () => {
    if (props.sendUpdate?.plan_id) {
      fetchPlans();
    }
  },
);

onMounted(() => {
  if (props.realQuote?.plan_id || props.sendUpdate?.plan_id) {
    fetchPlans();
  }
  showLackingPayment();
});

const isChildPaymentDeletable = computed(() => {
  if (props.payments.length !== 2) return false;
  const childPaymentNotAuthorised = [
    paymentStatusEnum.PENDING,
    paymentStatusEnum.NEW,
    paymentStatusEnum.DRAFT,
    paymentStatusEnum.OVERDUE,
  ].includes(props.payments[1].payment_status_id);
  return (
    props.quoteType == quoteTypeCodeEnum.Travel &&
    page.props?.aboveAgeMembers &&
    childPaymentNotAuthorised
  );
});

const setPaymentInitialPrice = () => {
  if (paymentMethodsFormReplicated.value.status !== 'edit') {
    if (props.isPlanDetailEnabled) {
      initialAmount.value = props.quoteRequest.price_with_vat;
    } else if (props.sendUpdate) {
      initialAmount.value = props.sendUpdate?.price_with_vat;
    } else if (props.quoteType === quoteTypeCodeEnum.Health) {
      initialAmount.value = props.eCommercePrice;
    } else if (props.quoteType === quoteTypeCodeEnum.Bike) {
      initialAmount.value = props.quoteRequest.premium;
    } else if (
      props.isPlanDetailSectionEnabled &&
      props.quoteType === quoteTypeCodeEnum.Home
    ) {
      initialAmount.value = props.quoteRequest.price_with_vat;
    } else if (
      !isLifePlanDetailsEnabled.value &&
      props.quoteType === quoteTypeCodeEnum.Life
    ) {
      initialAmount.value = getInitalAmountForLifeLOB();
    } else {
      initialAmount.value = quoteTypesToCheck.includes(props.quoteType)
        ? props.quoteRequest.premium
        : props.quoteRequest.price_with_vat;
    }
    totalPrice.value = initialAmount.value;
  }
};

const setPlanDetail = () => {
  if (props.quoteType == 'Business' || props.isPlanDetailEnabled) {
    initalPlanDetails = props.quoteRequest.insurance_provider_details;
  } else if (props.quoteType == quoteTypeCodeEnum.Home) {
    initalPlanDetails =
      props.quoteRequest.insurance_provider_plan ||
      props.quoteRequest.insurance_provider;
  } else if (
    !isLifePlanDetailsEnabled.value &&
    props.quoteType === quoteTypeCodeEnum.Life
  ) {
    initalPlanDetails =
      props.quoteRequest.insurance_provider_plan ||
      props.quoteRequest.insurance_provider;
  } else if (props.quoteType == quoteTypeCodeEnum.SAVINGS) {
    initalPlanDetails =
      props.quoteRequest.insurance_provider_plan ||
      props.quoteRequest.insurance_provider;
  } else if (props.quoteType == quoteTypeCodeEnum.CYBER) {
    initalPlanDetails = props.quoteRequest.insurance_provider_plan
  } else if (quoteTypesToCheck.includes(props.quoteType)) {
    initalPlanDetails = props.quoteRequest.plan;
  } else if (props.quoteType == quoteTypeCodeEnum.Bike) {
    initalPlanDetails = props.quoteRequest?.car_plan;
  } else if (quoteTypesToCheck.includes(props.quoteType)) {
    initalPlanDetails = props.quoteRequest.plan;
  } else {
    initalPlanDetails = props.quoteRequest.insurance_provider;
  }
  planDetail.value = initalPlanDetails;
};

watch(
  () => props.quoteRequest,
  (newValue, oldValue) => {
    //refresh premium
    setPaymentInitialPrice();
    //refresh plan
    setPlanDetail();
  },
);

const is_lacking_payment = ref(
  page.props?.bookPolicyDetails?.isLackingOfPayment || false,
);

const isPaidEditable = ref(
  page.props?.bookPolicyDetails?.isPaidEditable ||
    page.props?.isPaidEditable ||
    false,
);

const updatePlanDetail = planDetailValue => {
  planDetail.value = planDetailValue;
};

watch(
  () => is_lacking_payment.value,
  newVal => {
    if (newVal) {
      showLackingPayment();
    }
  },
);

watch(
  () => page.props?.bookPolicyDetails?.isLackingOfPayment,
  newVal => {
    is_lacking_payment.value = newVal || false;
  },
);

watch(
  () => page.props?.bookPolicyDetails?.isPaidEditable,
  newVal => {
    isPaidEditable.value = newVal || false;
  },
);

watch(
  () => page.props?.isPaidEditable,
  newVal => {
    isPaidEditable.value = newVal || false;
  },
);

// Watch for Ecommerce Price changes

watch(
  () => props.eCommercePrice,
  (newValue, oldValue) => {
    initialAmount.value = newValue;
    totalPrice.value = newValue;
  },
);

watch(
  () => props.sendUpdate?.price_with_vat,
  (newValue, oldValue) => {
    totalPrice.value = newValue;
  },
);

const openAmlVerificationModal = () => {
  isAmlApprovalRequired.value = true;
};

const isCCEnabled = ref(
  page.props?.bookPolicyDetails?.isCreditCardEnabled || false,
);

const isMultiPaymentsEnabled = ref(
  page.props?.bookPolicyDetails?.isMultiplePaymentsEnabled || false,
);

watch(
  () => page.props?.bookPolicyDetails?.isCreditCardEnabled,
  newVal => {
    isCCEnabled.value = newVal || false;
  },
);

const hasAnyCCSplitPayment = () => {
  if (props.payments.length > 0) {
    const paymentSplits = props.payments[0].payment_splits;
    return paymentSplits.some(item => item.payment_method.code === 'CC');
  }
  return false;
};

const isEditPaymentEnabled = payment => {
  const statusesToCheck = [
    paymentStatusEnum.AUTHORISED,
    paymentStatusEnum.PAID,
    paymentStatusEnum.CAPTURED,
  ];

  const hasAnyAuthorizedPayment = payment.payment_splits.some(
    item =>
      statusesToCheck.includes(item.payment_status_id) &&
      item.payment_method.code === 'CC',
  );

  let isMultiPaymentEnabled = isMultiPaymentsEnabled.value;
  if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
    isMultiPaymentEnabled = payment?.isMultiplePaymentsEnabled;
  }
  return !isMultiPaymentEnabled && hasAnyAuthorizedPayment;
};

let voidPaymentObject = {};
const voidPaymentModelPopup = ref(false);
const voidPaymentModel = payment => {
  voidPaymentModelPopup.value = true;
  voidPaymentObject = payment;
};

/**
 * Void payment functionality is now handled in the VoidPaymentModal component
 */

let deletePaymentObject = {};
const deletePaymentProcess = ref(false);
const deletePaymentModelPopup = ref(false);
const deletePaymentModel = payment => {
  deletePaymentModelPopup.value = true;
  deletePaymentObject = payment;
};

const fetchInsurerAMLStatus = async () => {
  if (props.quoteRequest?.payments[0]?.payment_gateway_id == 3) {
    NProgress.start();
    const response = await axios.get(route('insurer-aml-status-logs'), {
      params: {
        quoteRequestId: props.quoteRequest.id,
        quoteType: page.props.quoteTypeId,
        insurerAMLStatus: props.quoteRequest.insurer_aml_status,
        insuranceProviderId: props.quoteRequest?.insurance_provider_id,
      },
    });
    NProgress.done();
    if (response.data?.status) {
      notification.error({
        title: response.data?.message,
        position: 'top',
        timeout: 5000,
      });
    }
  }
};

onBeforeMount(() => {
  fetchInsurerAMLStatus();
});

const closeVoidPaymentModal = () => {
  voidPaymentModelPopup.value = false;
};

watch(
  () => createPaymentFormRef.value?.paymentMethodsForm,
  newVal => {
    paymentMethodsFormReplicated.value = newVal ?? {};
  },
);
watch(
  () => createPaymentFormRef.value?.isApproveConfirmed,
  newVal => {
    isApproveConfirmedReplicated.value = newVal;
  },
);
watch(
  () => createPaymentFormRef.value?.isViewEnabled,
  newVal => {
    isViewEnabledReplicated.value = newVal;
  },
);
watch(
  () => createPaymentFormRef.value?.filesTest,
  newVal => {
    filesTestReplicated.value = newVal;
  },
);
watch(
  () => createPaymentFormRef.value?.currentFileIndex,
  newVal => {
    currentFileIndexReplicated.value = newVal;
  },
);
watch(
  () => createPaymentFormRef.value?.isCreditApprovalView,
  newVal => {
    isCreditApprovalViewReplicated.value = newVal;
  },
);
watch(
  () => createPaymentFormRef.value?.isCreditCardView,
  newVal => {
    isCreditCardViewReplicated.value = newVal;
  },
);


</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Toasty v-if="isPaymentAuthorized"
      >Payment is authorised. Please capture the payment</Toasty
    >
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Manage Payments
          </h3>
        </div>
      </template>
      <template #body>
        <PaymentHeader
          :payments="payments"
          :proformaPayment="proformaPayment"
          :quoteRequest="quoteRequest"
          :quoteType="quoteType"
          :quoteDocuments="quoteDocuments"
          :totalPrice="totalPrice"
          :planDetail="planDetail"
          @add-payment-modal="addPaymentModal"
        />

        <div class="vue3-easy-data-table tablefixed custom-height">
          <div
            class="vue3-easy-data-table__main fixed-header hoverable border-cell custom-height manage-payment-table-parent-div"
          >
            <table>
              <!-- Payment Table Header Component -->
              <PaymentTableHeader />

              <tbody class="vue3-easy-data-table__body">
                <tr v-if="payments.length === 0">
                  <td colspan="14" class="text-center py-4">
                    No payments found.
                  </td>
                </tr>
                <!-- Payment Rows with Splits -->
                <template
                  v-for="(payment, index) in payments"
                  :key="payment.code"
                >
                  <!-- Main payment row -->
                  <PaymentRow
                    :payments="payments"
                    :payment="payment"
                    :index="index"
                    :isExpanded="expandedPaymentRows[index]"
                    :isChildPaymentDeletable="isChildPaymentDeletable"
                    :isLackingPayment="is_lacking_payment"
                    :isApproveConfirmed="isApproveConfirmedReplicated"
                    :capturePaymentValidationInProcess="
                      capturePaymentValidationInProcess
                    "
                    :isFuncsEnabled="props.isFuncsEnabled"
                    :bookPolicyDetails="props.bookPolicyDetails"
                    :quoteRequest="quoteRequest"
                    :sendUpdate="sendUpdate"
                    :quoteType="quoteType"
                    :isCapBtnEnabled="isCapBtnEnabled"
                    :isAllianceProvider="isAllianceProvider"
                    :isEditPaymentEnabled="isEditPaymentEnabled"
                    @toggle-expand="toggleExpand"
                    @edit-payment="editPaymentModal"
                    @delete-payment="deletePaymentModel"
                    @void-payment="voidPaymentModel"
                    @alert-capture="alertCapture"
                    @open-aml-verification="openAmlVerificationModal"
                  />

                  <!-- Payment split rows (visible when payment is expanded) -->
                  <template v-if="expandedPaymentRows[index]">
                    <PaymentSplitRow
                      v-for="(
                        splitPayment, splitIndex
                      ) in payment.payment_splits"
                      :key="splitPayment.id"
                      :splitPayment="splitPayment"
                      :parentPayment="payment"
                      :splitIndex="splitIndex"
                      :linkedQuoteDetails="props.linkedQuoteDetails"
                      :quoteRequest="quoteRequest"
                      :sendUpdate="sendUpdate"
                      :paymentMethodsForm="paymentMethodsFormReplicated"
                      :sendUpdateStatusEnum="sendUpdateStatusEnum"
                      :quoteType="quoteType"
                      :isHealthAUHLead="
                        page.props?.bookPolicyDetails?.isHealthAUHLead
                      "
                      @view-payment="
                        (payment, splitId, splitNo, action) =>
                          editPaymentModal(payment, splitId, splitNo, action)
                      "
                      @generate-cc-link="
                        (code, srNo, statusId) =>
                          generateCCLink(code, srNo, statusId)
                      "
                      @delete-split-payment="
                        (splitId, statusId, code) =>
                          deleteSplitPaymentModal(splitId, statusId, code)
                      "
                      @retry-split-payment="
                        (jobId, message) =>
                          retrySplitPaymentModal(jobId, message)
                      "
                    />
                  </template>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <x-modal
          v-model="createPaymentModal"
          size="xl"
          :title="
            isCreditCardViewReplicated
              ? 'Capture Transaction'
              : isCreditApprovalViewReplicated
                ? 'Approve Transaction'
                : isViewEnabledReplicated
                  ? 'View Payment'
                  : paymentMethodsFormReplicated.status == 'create'
                    ? 'New Payment'
                    : 'Update Payment'
          "
          show-close
          backdrop
          persistent
        >
          <CreatePaymentForm
            ref="createPaymentFormRef"
            :totalPrice="totalPrice"
            :creditApprovalReasons="creditApprovalReasons"
            :discountReasons="discountReasons"
            :paymentDocument="paymentDocument"
            :totalAmount="totalAmount"
            :isPaidEditable="isPaidEditable"
            :paymentProofDocument="paymentProofDocument"
            :isMultiPaymentsEnabled="isMultiPaymentsEnabled"
            :quoteType="quoteType"
            :sendUpdate="sendUpdate"
            :payments="payments"
            :isCCEnabled="isCCEnabled"
            :quoteRequest="quoteRequest"
            :sendUpdateStatusEnum="sendUpdateStatusEnum"
            :approveProofDocument="approveProofDocument"
            :paymentStatusEnum="paymentStatusEnum"
            :planDetail="planDetail"
            :quoteTypesToCheck="quoteTypesToCheck"
            :insuranceProviders="insuranceProviders"
            :createPaymentModal="createPaymentModal"
            :paymentMethods="paymentMethods"
            :eCommercePriceWithLP="eCommercePriceWithLP"
            :isPlanDetailSectionEnabled="isPlanDetailSectionEnabled"
            :quoteSubType="quoteSubType"
            :isLackingPayment="is_lacking_payment"
            :planText="planText"
            :capturePaymentValidationErrorMessage="
              capturePaymentValidationErrorMessage
            "
            :isTransactionCaptureButtonEnabled="
              isTransactionCaptureButtonEnabled
            "
            :selectedPaymentForEdit="selectedPaymentForEdit"
            :showInsurerReceiptNumberInputField="
              showInsurerReceiptNumberInputField
            "
            :insurerReceiptNumberCheckInProcess="
              insurerReceiptNumberCheckInProcess
            "
            :isInsurerReceiptNumberExistsModalOpen="
              isInsurerReceiptNumberExistsModalOpen
            "
            :isLifePlanDetailsEnabled="isLifePlanDetailsEnabled"
            @cancel-modal="createPaymentModal = !createPaymentModal"
            @aml-verification="openAmlVerificationModal"
            @update-plan-detail="updatePlanDetail"
            @update-gallery-model-open="value => (isGalleryModelOpen = value)"
            @update-is-aml-approval-required="
              value => (isAmlApprovalRequired = value)
            "
            @update-create-payment-modal="value => (createPaymentModal = value)"
            @update-total-amount="value => (totalAmount = value)"
            @update-total-price="value => (totalPrice = value)"
            @check-insurer-receipt-number="checkInsurerReceiptNumber"
            @close-insurer-receipt-number-exists-modal="
              closeInsurerReceiptNumberExistsModal
            "
          />

          <!-- Image Gallery Modal -->
          <ImageGalleryModal
            v-model="isGalleryModelOpen"
            :files="filesTestReplicated"
            :initial-index="currentFileIndexReplicated"
            :storage-url="storageUrl"
            @update:model-value="val => val === false && closeInnerModal()"
            class="max-w-6xl mx-auto"
          />
        </x-modal>

        <!--  Clear AML KYC Screening         -->
        <AmlApprovalModal
          v-model="isAmlApprovalRequired"
          :quote-type-id="
            page.props.quoteTypeId ?? props.sendUpdate.quote_type_id
          "
          :quote-request-id="props.quoteRequest.id"
          :is-processing="paymentMethodsFormReplicated.processing"
          @update:model-value="closeAmlConfirmModal"
        />

        <!-- Retry Payment Modal -->
        <RetryPaymentModal
          v-model="isRetryModalOpen"
          :error-message="retryPaymentErrorMessage"
          :payment-process-job-id="retryProcessJobId"
          :quote-type="props.quoteType"
          :quote-id="props.quoteRequest.id"
          @update:model-value="closeRetryModal"
        />

        <!-- Delete Split Payment Modal -->
        <DeleteSplitPaymentModal
          v-model="isDeleteModalOpen"
          :payment-split-id="deleteSplitPaymentId"
          :payment-status-id="deleteSplitPaymentStatus"
          :quote-type="props.quoteType"
          :quote-id="props.quoteRequest.id"
          :code="deleteSplitPaymentCode"
          @update:model-value="closeDeleteModal"
        />

        <!-- Delete Parent Payment Modal -->
        <DeleteParentPaymentModal
          v-model="deletePaymentModelPopup"
          :payment-id="deletePaymentObject?.id"
          :payment-code="deletePaymentObject?.code"
          :quote-type="props.quoteType"
          :quote-id="props.quoteRequest.id"
        />

        <!-- Void Payment Modal -->
        <VoidPaymentModal
          v-model="voidPaymentModelPopup"
          :payment-id="voidPaymentObject.id"
          :payment-code="voidPaymentObject.code"
          :quote-type="props.quoteType"
          :quote-id="props.quoteRequest.id"
          :quote-uuid="props.quoteRequest.uuid"
          :quote-type-id="
            page.props.quoteTypeId ?? props.sendUpdate.quote_type_id
          "
          :send-update-id="props.sendUpdate?.id"
          @update:model-value="closeVoidPaymentModal"
        />
      </template>
    </Collapsible>
  </div>
</template>
<style scoped>
/* Apply cursor: not-allowed when select is disabled */
.disabled-select {
  cursor: not-allowed;
}

.h-80vh {
  height: 85vh;
}
.tooltip-display {
  display: inherit;
}
/* Modal overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}
/* Modal container */
.modal-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  background-color: hsl(0, 4%, 9%);
  border-radius: 4px;
  padding: 5px;
  z-index: 1050;
}
/* Modal header */
.modal-header {
  background-color: hsl(0, 4%, 9%);
}
/* Modal body */
.modal-body {
  padding: 10px 0;
}

.modal-confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #33333333;
  z-index: 1040;
}
.modal-confirm-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 75%;
  height: 37%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px; /* Adjust the radius for desired roundness */
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc; /* Grey color for the border */
}

.modal-retry-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 45%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px; /* Adjust the radius for desired roundness */
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc; /* Grey color for the border */
}
/* Modal header */
.modal-confirm-header {
  color: #000;
}
.inner-th-class {
  min-width: 160px;
}
/* Add your custom styling here */
.delete-pointer {
  cursor: pointer;
  padding-left: 5px;
  font-weight: bold;
  font-size: 12px;
}
.expand-pointer {
  cursor: pointer;
  font-size: 20px;
  font-weight: bold;
  color: #1d83bc;
}
.custom-tooltip-content {
  max-width: 200px; /* Adjust the max-width as needed */
  white-space: normal; /* Allow the text to wrap */
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
}
.custom-height {
  min-height: 185px;
}
.manage-payment-table-parent-div {
  overflow-y: hidden;
}
.manage-payment-table-parent-div::-webkit-scrollbar {
  width: 6px;
  background-color: #c1c1c1;
}
.receipt-number-exists-modal-container {
  max-height: 270px;
  max-width: 630px;
}
</style>
