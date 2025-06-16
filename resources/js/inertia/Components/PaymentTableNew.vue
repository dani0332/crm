<script setup>
import ToolTip from './../Components/ToolTip.vue';
import { onMounted, reactive, ref, nextTick } from 'vue';
import moment from 'moment';
import NProgress from 'nprogress';
import { computed } from 'vue';
import UpdateTotalPrice from './../Components/UpdateTotalPrice.vue';
import { time } from 'highcharts';

// New Flow Implementation
import { usePayment } from '../Composables/usePayment';
import { useAMLKYC } from '../Composables/useAMLKYC';
import {
  PaymentTableHeader,
  PaymentHeader,
  PaymentRow,
  PaymentSplitRow,
  CreatePaymentForm,
} from './PaymentComponents/index.js';

// Assign barrel-imported components to prevent IDE from showing them as unused
const components = { PaymentTableHeader };

const notification = useNotifications('toast');
const page = usePage();

const policyIssuanceEnum = page.props.policyIssuanceEnum;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;
const permissionEnum = page.props.permissionsEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const paymentLookups = page.props.paymentLookups;
const vatValue = page.props.vatValue;
const documentTypeEnum = page.props.documentTypeEnum;
const quoteDocuments = page.props.quoteDocuments;
const can = permission => useCan(permission);
const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;
const paymentAllocationStatus = page.props.paymentAllocationStatus;
const paymentMethodsEnums = page.props.paymentMethodsEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentCaptureValidationEnum = page.props.paymentCaptureValidationEnum;

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
    type: Array,
    default: [],
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
const isPaymentNoEnabled = ref(false);
const isCustomReasonEnabled = ref(false);
const isResetCreditApproval = ref(false);
const isCustomDiscountReasonEnabled = ref(false);
const isDiscountEnabled = ref(false);
const isDiscountReasonEnabled = ref(false);
const isCheckDetailsEnabled = ref([]);
const isExpandedSplitPayments = ref([]);
const isPaymentCalculationError = ref(false);
const isDowngradeFrequencyError = ref(false);
const isFieldReadonly = ref(false);
const isUploading = ref(false);
const isFileError = ref(false);
const isViewEnabled = ref(false);
const fileErrorMessage = ref('');
const splitPaymentNo = ref(0);
const isApproveClicked = ref(false);
const isApproveConfirm = ref(false);
const isDeclineClicked = ref(false);
const isDeclinedReasonError = ref(false);
const isDeclineCustomReason = ref(false);
const isApprovePaymentError = ref(false);
const showDiscountOptions = ref(true);
const isApprovedDocumentNotUploaded = ref(false);
const isMultipleDocumentEnabled = ref(true);
const approvedDocument = ref('');
const resetDiscountReason = ref('');
const approveErrorMessage = ref('');
const discountValue = ref(0); // Initial discount value
const calculatedDiscount = ref('');
const discountDocumentModel = ref([]);
const isDiscountDocumentNotUploaded = ref(false);
const paymentTypesFiltered = ref([]);
const approvedDocumentModel = ref([]);
const isPaymentMetodNotSelected = ref([]);
const isDocumentNotUploaded = ref([]);
const paymentMethodsModels = ref([]);
const splitAmountModels = ref([]);
const dueDateModels = ref([]);
const collectionAmountModels = ref([]);
const fileUploadModels = ref([]);
const checkDetailModels = ref([]);
const readOnlyPayments = ref([]);
const authorizedPayments = ref([]);
const splitPaymentRecord = ref([]);
const filesTest = ref([]);
const isCreditPaymentInvalid = ref([]);
const isCreditPaymentInvalidError = ref([]);
const currentFileIndex = ref(0);
const oldTotalPayments = ref(0);
const zoomLevel = ref(1);
const isGalleryModelOpen = ref(false);
const isDiscountReasonError = ref(false);
const isCreditApprovalView = ref(false);
const isCreditCardView = ref(false);
const isDiscountError = ref(false);
const discountError = ref('');
const isTotalPriceUpdated = ref(false);
const trashedFilesModal = ref([]);
const isApproveNotChecked = ref(true);
const isApproveConfirmed = ref(false);
const isAmlApprovalRequired = ref(false);
const isCreditApprovalAllowed = ref(true);
const isVerificationAllowed = ref(true);
const isPaymentFrequencyNotSelected = ref(false);
const isDiscountAllowed = ref(true);
const isRetryModalOpen = ref(false);
const retryProcessJobId = ref(0);
const retryPaymentErrorMessage = ref('');
const isSplitAmountInvalid = ref([]);
const isSplitAmountInvalidError = ref([]);
const isDeleteModalOpen = ref(false);
const deleteSplitPaymentId = ref(0);
const deleteSplitPaymentStatus = ref(0);
const isCollectedByEnabled = ref(false);
const isTransactionCaptureButtonEnabled = ref(true);
const premiumToCapture = ref(0);
const capturePaymentValidationInProcess = ref(false);
const capturePaymentValidationErrorMessage = ref('');
const modal2Ref = ref(null);
const insurerPaymentLinkChanged = ref(false);
const confirmModalClose = ref(false);
const insurerPaymentComponent = ref(null);
const createPaymentFormRef = ref(null);

const familyEmployeDiscount = [
  quoteTypeCodeEnum.Car,
  quoteTypeCodeEnum.Health,
  quoteTypeCodeEnum.Home,
  quoteTypeCodeEnum.Travel,
];
// Array of quote types to check against
const quoteTypesToCheck = [
  quoteTypeCodeEnum.Car,
  quoteTypeCodeEnum.Health,
  quoteTypeCodeEnum.Travel,
  quoteTypeCodeEnum.Home,
]; //Ecommerce LOBs
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

// Here we define the computed properties
const isisUpfrontFrequency = computed(
  () => paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT,
);

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
        payment.payment_splits.some(item => item.payment_method.code === 'CC'),
      );
    }
  }
  return false;
});
const isCustomFrequency = computed(
  () => paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM,
);
const isSinglePayment = computed(() => paymentMethodsForm.payment_no == 1);
const isCreditApprovalApplied = computed(
  () => paymentMethodsForm.credit_approval !== '',
);

const isPaymentMethodEnabled = computed(() => {
  return (
    isCreditApprovalApplied.value &&
    isCustomFrequency.value &&
    isSinglePayment.value
  );
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
} else if (quoteTypesToCheck.includes(props.quoteType)) {
  initalPlanDetails = props.quoteRequest.plan;
} else if (props.quoteType == quoteTypeCodeEnum.Bike) {
  initalPlanDetails = props.quoteRequest?.car_plan;
} else {
  initalPlanDetails = props.quoteRequest?.insurance_provider;
}

let planDetail = ref(initalPlanDetails);
const paidAmountSum = ref(0);
const totalPaidAmount = ref(0);
const masterPaymentStatus = ref('NEW');

const getCustomReasonIndex = value => {
  const index = declinedReasons.findIndex(reason => reason.value === value);
  return index !== -1 ? index : null;
};

// Define a computed property to deduct insure now pay later
const isInsureNowPayLaterAllowed = computed(() => {
  //handle edit scenario for insure now pay later
  if (
    paymentMethodsForm.status == 'edit' &&
    paymentMethodsForm.collection_type === 'broker'
  ) {
    if (props.payments.length > 0) {
      let inureNowPayLaterExists = props.payments[0].payment_splits.find(
        item =>
          item.payment_method.code ===
          page.props.paymentMethodsEnum?.InsureNowPayLater,
      );
      if (inureNowPayLaterExists) {
        return true;
      }
    }
  }
  if (
    paymentMethodsForm.collection_type === 'broker' &&
    can(permissionEnum.INPL_USER)
  ) {
    return true;
  }
  return false;
});

const isPolicyIssuanceDiscount = computed(() => {
  if (
    can(permissionEnum.PAYMENTS_DISCOUNT_EDIT) &&
    (props.quoteRequest.quote_status_id ===
      page.props.quoteStatusEnum.TransactionApproved ||
      props.quoteRequest.quote_status_id ===
        page.props.quoteStatusEnum.PolicyIssued ||
      props.quoteRequest.quote_status_id ===
        page.props.quoteStatusEnum.PolicySentToCustomer)
  ) {
    return true;
  }
  return false;
});

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
const nextFile = () => {
  if (currentFileIndex.value < filesTest.value.length - 1) {
    currentFileIndex.value++;
    zoomLevel.value = 1;
  }
};

const zoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 0.25, 3);
};

const zoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25);
};

const previousFile = () => {
  if (currentFileIndex.value > 0) {
    currentFileIndex.value--;
    zoomLevel.value = 1;
  }
};

const handleKeyDown = event => {
  if (event.key === 'ArrowLeft' && hasPreviousFile) {
    previousFile();
  } else if (event.key === 'ArrowRight' && hasNextFile) {
    nextFile();
  }
};

const currentFile = computed(() => {
  return filesTest.value[currentFileIndex.value];
});

// Define a computed property to calculate the initial total price without VAT
const initialTotalPriceWithoutVat = computed(() => {
  if (props.quoteType === quoteTypeCodeEnum.Health) {
    return props.eCommercePriceWithLP; // premium with loading price,excluding vat
  }
  const vatRate = vatValue ? vatValue / 100 : 0;
  return totalPrice.value / (1 + vatRate);
});

// Add state for expanded rows
const expandedPaymentRows = ref({});

const toggleExpand = index => {
  expandedPaymentRows.value[index] = !expandedPaymentRows.value[index];
};

const closeInnerModal = () => {
  zoomLevel.value = 1;
  isGalleryModelOpen.value = false;
  isGalleryModelOpen.value = false;
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
};

const closeConfirmModal = () => {
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
  isApproveConfirm.value = false;
};

const closeAmlConfirmModal = () => {
  isAmlApprovalRequired.value = false;
};

const hasNextFile = computed(() => {
  return currentFileIndex.value < filesTest.value.length - 1;
});

const hasPreviousFile = computed(() => {
  return currentFileIndex.value > 0;
});

const rules = {
  isRequired: v => !!v || 'This field is required',
  isBankReferenceRequird: v => {
    if (
      paymentMethodsForm.collection_type === 'insurer' &&
      paymentMethodsModels.value[splitPaymentNo.value] ===
        page.props.paymentMethodsEnum?.Cheque &&
      paymentMethodsForm.credit_approval != ''
    ) {
      return true;
    } else {
      return !!v || 'This field is required';
    }
    return true;
  },
  reference: v => {
    if (
      paymentMethodsForm.payment_method !==
      page.props.paymentMethodsEnum?.CreditCard
    ) {
      return !!v || 'This field is required';
    }
    return true;
  },
  amount: v => {
    const regex = /^\d+(\.\d{1,2})?$/;
    if (regex.test(v)) {
      return true;
    }
    return 'Amount must be a valid number';
  },
  notEmptyOrZero: v => {
    if (v !== '') {
      return true;
    }
    return 'Value cannot be empty';
  },
  isValidUrl: v => {
    if (!v) return true; // Allow empty value
    try {
      const url = new URL(v);
      return (
        url.protocol === 'http:' ||
        url.protocol === 'https:' ||
        'Please enter a valid URL starting with http:// or https://'
      );
    } catch {
      return 'Please enter a valid URL';
    }
  },
};

const isPaymentLocked = computed(() => {
  const { status } = paymentMethodsForm;
  const { quote_status_id } = props.quoteRequest;
  const { quoteStatusEnum } = page.props;
  const lockedStatuses = new Set([
    quoteStatusEnum.CancellationPending,
    quoteStatusEnum.PolicyCancelled,
    quoteStatusEnum.PolicyBooked,
    quoteStatusEnum.PolicyCancelledReissued,
    quoteStatusEnum.POLICY_BOOKING_QUEUED,
  ]);

  if (status === 'edit') {
    if (props.sendUpdate && can(permissionEnum.BOOKING_FAILED_EDIT)) {
      return false;
    }
    if (
      !props.sendUpdate &&
      (lockedStatuses.has(quote_status_id) ||
        (quote_status_id === quoteStatusEnum.POLICY_BOOKING_FAILED &&
          !can(permissionEnum.BOOKING_FAILED_EDIT)))
    ) {
      return true;
    }
  }
  return false;
});

const calculateTotalSplitAmount = () => {
  let totalSplitAmount = 0;
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
    totalSplitAmount += parseFloat(splitAmountModels.value[i]);
  }
  return totalSplitAmount;
};

const totalPayments = ref([{ value: '1', label: '1' }]);

const paymentTypes = ref(
  props.paymentMethods.filter(
    item =>
      ![
        page.props.paymentMethodsEnum?.GMApproval,
        page.props.paymentMethodsEnum?.CMOApproval,
        page.props.paymentMethodsEnum?.COOApproval,
        page.props.paymentMethodsEnum?.Credit,
      ].includes(item.value),
  ),
);
paymentTypes.value.unshift({ value: '', label: 'Select Payment' });

// Define payment collection types
const collectionTypes = computed(() => {
  if (paymentMethodsForm.status != 'view' && !isBrokerHavePermission()) {
    return paymentLookups.paymentCollectionTypes
      .filter(item => item.code !== 'broker')
      .map(item => ({
        value: item.code,
        label: item.text,
        tooltip: item.description,
      }));
  }
  return paymentLookups.paymentCollectionTypes.map(item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  }));
});

// Define frequency types
const frequencyTypes = ref(
  paymentLookups.paymentFrequencyTypes.map(item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  })),
);

// Define payment decline reasons
const declinedReasons = paymentLookups.paymentDeclineReasons.map(item => ({
  value: item.id,
  label: item.text,
}));
declinedReasons.unshift({ value: '', label: 'Select Reason' });

// Define payment approval reasons
const creditApprovalReasons = paymentLookups.paymentCreditApprovalReasons.map(
  item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  }),
);
creditApprovalReasons.unshift({ value: '', label: 'Approval Reason' });

// Define payment discount types
let discountTypes = paymentLookups.paymentDispountTypes.map(item => ({
  value: item.code,
  label: item.text,
  tooltip: item.description,
}));
discountTypes.unshift({ value: '', label: 'Discount Type' });
if (!familyEmployeDiscount.includes(props.quoteType)) {
  discountTypes = discountTypes.filter(
    type =>
      type.value !== 'employee_discount' &&
      type.value !== 'family_employee_discount',
  );
}

// Define payment discount reasons
const discountReasons = paymentLookups.paymentDiscountReasons.map(item => ({
  value: item.code,
  label: item.text,
  tooltip: item.description,
}));
discountReasons.unshift({ value: '', label: 'Select a reason' });

const isProformaPaymentRequest = computed(() => {
  return (
    paymentMethodsForm.payment_method ===
    page.props.paymentMethodsEnum?.ProformaPaymentRequest
  );
});

const handleCreditApproval = () => {
  if (paymentMethodsForm.credit_approval !== '') {
    if (paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT) {
      paymentMethodsForm.frequency = paymentFrequencyEnum.CUSTOM;
    }
    if (paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM) {
      if (
        paymentMethodsForm.status === 'edit' &&
        isAnyPaid(props.payments[0])
      ) {
        return;
      }
      resetTotalPayments();
      isPaymentNoEnabled.value = true;
      paymentMethodsForm.payment_no = '1';
    }
  }
};

const notPaidDates = serialNo => {
  if (
    readOnlyPayments.value[serialNo] != undefined &&
    readOnlyPayments.value[serialNo] === true
  ) {
    return false;
  }
  return true;
};

const calculateDueDates = () => {
  if (notPaidDates(1)) {
    dueDateModels.value[1] = paymentMethodsForm.collection_date;
  }
  if (
    paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS ||
    paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT
  ) {
    //dueDateModels.value[1] = new Date();
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
      if (notPaidDates(i)) {
        dueDateModels.value[i] = paymentMethodsForm.collection_date;
      }
    }
  } else if (paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM) {
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) {
      if (i > 12) continue;
      const currentDueDate = dueDateModels.value[i - 1];
      const nextDueDate = new Date(currentDueDate);
      // Set the month to the next month
      nextDueDate.setMonth(nextDueDate.getMonth() + 1);
      // Update the due date model
      if (notPaidDates(i)) {
        dueDateModels.value[i] = nextDueDate;
      }
    }
  } else if (paymentMethodsForm.frequency === paymentFrequencyEnum.MONTHLY) {
    dueDateModels.value[1] = paymentMethodsForm.collection_date;
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) {
      const nextDueDate = new Date(dueDateModels.value[i - 1]);
      nextDueDate.setMonth(nextDueDate.getMonth() + 1);
      nextDueDate.setDate(1); // Set the day to 1st of the month
      if (notPaidDates(i)) {
        dueDateModels.value[i] = nextDueDate;
      }
    }
  } else if (paymentMethodsForm.frequency === paymentFrequencyEnum.QUARTERLY) {
    if (notPaidDates(1)) {
      dueDateModels.value[1] = paymentMethodsForm.collection_date;
    }
    for (let i = 2; i <= paymentMethodsForm.payment_no; i++) {
      const nextDueDate = new Date(paymentMethodsForm.collection_date);
      if (i === 2) {
        nextDueDate.setDate(nextDueDate.getDate() + 90);
      } else if (i === 3) {
        nextDueDate.setDate(nextDueDate.getDate() + 180);
      } else if (i === 4) {
        nextDueDate.setDate(nextDueDate.getDate() + 270);
      }
      if (notPaidDates(i)) {
        dueDateModels.value[i] = nextDueDate;
      }
    }
  } else if (
    paymentMethodsForm.frequency === paymentFrequencyEnum.SEMI_ANNUAL
  ) {
    if (notPaidDates(1)) {
      dueDateModels.value[1] = paymentMethodsForm.collection_date;
    }
    const nextDueDate = new Date(dueDateModels.value[1]);
    nextDueDate.setDate(nextDueDate.getDate() + 180);
    if (notPaidDates(2)) {
      dueDateModels.value[2] = nextDueDate;
    }
  }
};

const resetTotalPayments = () => {
  totalPayments.value = [];
  for (let i = 1; i <= 20; i++) {
    totalPayments.value.push({ value: i.toString(), label: i.toString() });
  }
};

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
        modelType: props.quoteType,
        paymentCode: code,
        splitPaymentId: splitPaymentId,
        isInertia: true,
        new_payment_structure: true,
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
// Function to verify if the broker has permission to add payment
const isBrokerHavePermission = () => {
  const hasPermissionToBroker = can(
    permissionEnum.PAYMENTS_FREQUENCY_UPRONT_SPLIT_COLLECTED_BY_BROKER_ADD,
  );
  const hasPermissionToTermFrequencies = can(
    permissionEnum.PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_BROKER_ADD,
  );
  if (!hasPermissionToBroker && !hasPermissionToTermFrequencies) {
    return false;
  }
  return true;
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

// use in insurer payment link
const updateFromInsurerPaymentLink = (
  closePaymentModal = false,
  paymentLinkChanged = false,
  closeModal = false,
) => {
  closePaymentModal == true &&
    (createPaymentModal.value = !createPaymentModal.value);
  confirmModalClose.value = closeModal;
  insurerPaymentLinkChanged.value = paymentLinkChanged;
};

const addPaymentModal = () => {
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
  paymentMethodsForm.reset();
  paymentMethodsForm.payment_method = 'CHQ';
  paymentMethodsModels.value = [];
  splitAmountModels.value = [];
  dueDateModels.value = [];
  fileUploadModels.value = [];
  checkDetailModels.value = [];
  isDiscountReasonEnabled.value = false;
  isDiscountEnabled.value = false;
  isPaymentCalculationError.value = false;
  showDiscountOptions.value = true;
  isDiscountReasonError.value = false;
  isPaymentMetodNotSelected.value[1] = false;
  isDocumentNotUploaded.value = [];
  isDiscountError.value = false;
  discountError.value = '';
  isDiscountDocumentNotUploaded.value = false;
  discountDocumentModel.value = [];
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

  const quoteCollectedBy = [
    quoteTypeCodeEnum.Business,
    quoteTypeCodeEnum.Health,
    quoteTypeCodeEnum.Life,
    quoteTypeCodeEnum.Marine,
    quoteTypeCodeEnum.Pet,
    quoteTypeCodeEnum.Cycle,
    quoteTypeCodeEnum.Yacht,
  ];

  if (
    (quoteCollectedBy.includes(props.quoteType) &&
      props.quoteSubType != quoteTypeCodeEnum.CORPLINE) ||
    !isBrokerHavePermission()
  ) {
    paymentMethodsForm.collection_type = 'insurer';
  } else {
    paymentMethodsForm.collection_type = 'broker';
  }

  paymentMethodsForm.amount = '';
  paymentMethodsForm.payment_reference = '';
  paymentMethodsForm.paymentCode = '';

  paymentMethodsForm.status = 'create';
  paymentMethodsForm.collection_date = new Date();
  paymentMethodsForm;
  createPaymentModal.value = true;

  paymentMethodsForm.frequency = paymentFrequencyEnum.UPFRONT;
  paymentMethodsForm.discount = '';
  paymentMethodsForm.credit_approval = '';
  totalPayments.value = [];
  totalPayments.value.push({ value: '1', label: '1' });
  paymentMethodsForm.payment_no = '1';
  createPaymentFormRef.value.handleCollectionTypeChange();
  createPaymentFormRef.value.calculatePaymentBreakup();
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

const handleRetryPayment = async () => {
  let retryData = {
    payment_process_job_id: retryProcessJobId.value,
    model_type: props.quoteType,
    quote_id: props.quoteRequest.id,
  };
  retryForm
    .transform(data => retryData)
    .post('/payments/' + props.quoteType + '/retry-payment', {
      preserveScroll: true,
      onSuccess: () => {
        isRetryModalOpen.value = false;
      },
    });
};

const deleteSplitPaymentModal = (payment_split_id, payment_status_id) => {
  deleteSplitPaymentId.value = payment_split_id;
  deleteSplitPaymentStatus.value = payment_status_id;
  isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false;
};

const handleDeletePayment = async () => {
  let retryData = {
    payment_split_id: deleteSplitPaymentId.value,
    payment_status_id: deleteSplitPaymentStatus.value,
    model_type: props.quoteType,
    quote_id: props.quoteRequest.id,
  };
  deleteForm
    .transform(data => retryData)
    .post('/payments/' + props.quoteType + '/delete-split-payment', {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Split Payment has been deleted',
          position: 'top',
        });
        isDeleteModalOpen.value = false;
      },
      onError: () => {
        notification.error({
          title: 'Payment delete failed',
          position: 'top',
        });
      },
    });
};

const editPaymentModal = async (
  payment,
  split_payment_id,
  sr_no,
  capture_approval,
) => {
  isTransactionCaptureButtonEnabled.value = true;

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

  // Payment Capture Validation for GIG
  if (
    capture_approval == 1 &&
    payment?.insurance_provider?.code == 'AXA' &&
    (props.quoteType === quoteTypeCodeEnum.Bike ||
      props.quoteType === quoteTypeCodeEnum.Car ||
      props.quoteType === quoteTypeCodeEnum.Home)
  ) {
    capturePaymentValidationInProcess.value = true;
    isTransactionCaptureButtonEnabled.value = false;
    await doCapturePaymentValidation(payment.total_amount, payment?.code);
  }

  createPaymentFormRef.value.resetPaymentForm();
  createPaymentFormRef.value.initializePaymentForm(payment, split_payment_id, sr_no, capture_approval);
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
    .then(res => {
      if (res?.data?.response?.status == paymentCaptureValidationEnum.SUCCESS) {
        premiumToCapture.value = res?.data?.response?.premiumAmount;
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

const isAnyPaid = payment => {
  const paidStatusIds = [
    paymentStatusEnum.PAID,
    paymentStatusEnum.PARTIALLY_PAID,
    paymentStatusEnum.AUTHORISED,
    paymentStatusEnum.CAPTURED,
    paymentStatusEnum.PARTIAL_CAPTURED,
  ];

  return payment.payment_splits.some(split =>
    paidStatusIds.includes(split.payment_status_id),
  );
};

const paymentMethodsForm = useForm({
  payment_method: '',
  collection_type: '',
  amount: '',
  payment_reference: '',
  paymentCode: '',
  status: 'create',
  approvalModal: '',
  insurerPaymentLink: '',
  insurerPaymentLinkError: '',
});

const validatePaymentAmount = isValid => {
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
    isSplitAmountInvalid.value[i] = false;
    if (
      parseFloat(splitAmountModels.value[i]) >
      parseFloat(collectionAmountModels.value[i])
    ) {
      isSplitAmountInvalid.value[i] = true;
      isSplitAmountInvalidError.value[i] =
        'Amount should not exceed ' + collectionAmountModels.value[i] + ' AED';
    }
  }
  if (isSplitAmountInvalid.value.includes(true)) {
    return true;
  }
  return false;
};

const retryForm = useForm({
  payment_process_job_id: null,
});

const deleteForm = useForm({
  payment_process_job_id: null,
});

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
const homePlanText = ref();
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

const insurerPaymentLinkIndex = computed(() => {
  var insurerPaymentIndex = paymentMethodsModels.value.findIndex(item => {
    return item === page.props.paymentMethodsEnum?.InsurerPaymentLink;
  });
  return insurerPaymentIndex;
});

// Watch for changes in the modal's state
watch(createPaymentModal, async (newVal, oldVal) => {
  if (oldVal === true && newVal === false) {
    // Modal is closing
    const checkInsurerPaymentLink =
      paymentMethodsModels.value[1] ===
      page.props.paymentMethodsEnum?.InsurerPaymentLink;
    if (
      insurerPaymentLinkChanged.value &&
      paymentMethodsForm.collection_type === 'insurer' &&
      checkInsurerPaymentLink
    ) {
      // Prevent the close event from triggering
      if (confirmModalClose.value == false) {
        await nextTick();
        createPaymentModal.value = true;
        await nextTick();
        if (insurerPaymentComponent.value) {
          insurerPaymentComponent.value.closeNotification();
        }
      }
      return;
    }
  }
});

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

const getPlanName = computed(() => {
  const plan = planDetail.value;
  if (props.quoteType === quoteTypeCodeEnum.Bike) {
    return plan ? props.quoteRequest.car_plan.text : 'Not Available';
  }
  if (props.sendUpdate) {
    return planText.value || 'Not Available';
  }

  if (props.quoteType === quoteTypeCodeEnum.Home) {
    if (props.quoteRequest?.insurance_provider_plan?.text && plan) {
      homePlanText.value = props.quoteRequest.insurance_provider_plan.text;
    }
    return homePlanText.value || 'Not Available';
  }

  return quoteTypesToCheck.includes(props.quoteType) && plan
    ? plan.text
    : 'Not Available';
});

// Watch for changes in paymentMethodsForm.collection_date
watch(
  () => paymentMethodsForm.collection_date,
  (newValue, oldValue) => {
    if (newValue && oldValue) {
      // Get the date part without the time from the newValue and oldValue
      const newDate = new Date(newValue).toISOString().split('T')[0];
      const oldDate = new Date(oldValue).toISOString().split('T')[0];
      // Compare the dates
      if (newDate !== oldDate) {
        calculateDueDates();
      }
    }
  },
);

const setPaymentInitialPrice = () => {
  if (paymentMethodsForm.status !== 'edit') {
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
  } else if (quoteTypesToCheck.includes(props.quoteType)) {
    initalPlanDetails = props.quoteRequest.plan;
  } else if (props.quoteType == quoteTypeCodeEnum.Bike) {
    initalPlanDetails = props.quoteRequest?.car_plan?.insurance_provider;
    if (props.sendUpdate) {
      initalPlanDetails =
        props.quoteRequest.insurance_provider_details ??
        props.quoteRequest.insurance_provider;
    }
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

const discountTypeLabel = computed(() => {
  let systemAplliedDiscount = '';
  if (
    paymentMethodsForm.status === 'view' &&
    (paymentMethodsForm.discount === 'system_adjusted_discount' ||
      paymentMethodsForm.system_adjusted_discount > 0)
  ) {
    systemAplliedDiscount = 'System adjusted discount';
  }
  let discountType = discountTypes.find(
    item => item.value === paymentMethodsForm.discount,
  );
  if (discountType) {
    if (systemAplliedDiscount !== '') {
      if (discountType.label == systemAplliedDiscount) {
        return discountType.label;
      }
      return discountType.label + ' + ' + systemAplliedDiscount;
    } else {
      return discountType.label;
    }
  } else if (systemAplliedDiscount !== '') {
    return systemAplliedDiscount;
  } else {
    return 'N/A';
  }
});
// Watch for Ecommerce Price changes

watch(
  () => props.eCommercePrice,
  (newValue, oldValue) => {
    initialAmount.value = newValue;
    totalPrice.value = newValue;
  },
);

// verify if verify option is enabled
const isVerifiedEnabled = computed(() => {
  if (
    paymentMethodsModels.value[splitPaymentNo.value] === 'CC' ||
    paymentMethodsModels.value[splitPaymentNo.value] === 'CA' ||
    paymentMethodsModels.value[splitPaymentNo.value] === 'PPR'
  ) {
    return false;
  }
  return true;
});

watch(
  () => props.sendUpdate?.price_with_vat,
  (newValue, oldValue) => {
    totalPrice.value = newValue;
  },
);

const openAmlVerificationModal = () => {
  isAmlApprovalRequired.value = true;
};

const transactionActionText = computed(() => {
  if (paymentMethodsForm.approvalModal === 'child') {
    return 'PAYMENT VERIFICATION';
  } else if (isCreditApprovalView.value && isCreditCardView.value) {
    return 'CAPTURE TRANSACTION';
  } else {
    return 'APPROVE TRANSACTION';
  }
});

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
    isMultiPaymentEnabled = props.payments[0].isMultiPaymentsEnabled;
  }
  return !isMultiPaymentEnabled && hasAnyAuthorizedPayment;
};

let voidPaymentObject = {};
const voidPaymentProcess = ref(false);
const voidPaymentModelPopup = ref(false);
const voidPaymentModel = payment => {
  voidPaymentModelPopup.value = true;
  voidPaymentObject = payment;
};

let deletePaymentObject = {};
const deletePaymentProcess = ref(false);
const deletePaymentModelPopup = ref(false);
const deletePaymentModel = payment => {
  deletePaymentModelPopup.value = true;
  deletePaymentObject = payment;
};

const voidPayment = () => {
  voidPaymentProcess.value = true;
  let data = {
    quote_type_id: page.props.quoteTypeId,
    quote_id: props.quoteRequest.id,
    quote_uuid: props.quoteRequest.uuid,
    payment_id: voidPaymentObject.id,
    payment_code: voidPaymentObject.code,
    send_update_log_id: props.sendUpdate?.id ?? null,
  };

  axios
    .post(`/payments/${props.quoteType}/void-payment`, data)
    .then(res => {
      voidPaymentProcess.value = false;
      voidPaymentModelPopup.value = false;
      if (res.data.status === false) {
        notification.error({
          title: res.data.message,
          position: 'top',
        });
        return;
      }
      notification.success({
        title: 'Processed',
        position: 'top',
      });

      router.reload({
        only: ['payments'],
      });
    })
    .catch(err => {
      voidPaymentProcess.value = false;
      if (err.response.data) {
        notification.error({
          title: err.response.data[0],
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Void authorized payment process failed',
          position: 'top',
        });
      }
    });
};

const deletePayment = () => {
  deletePaymentProcess.value = true;
  let data = {
    payment_id: deletePaymentObject.id,
    payment_code: deletePaymentObject.code,
  };

  axios
    .post(`/payments/${props.quoteType}/delete-payment`, data)
    .then(res => {
      deletePaymentProcess.value = false;
      deletePaymentModelPopup.value = false;
      if (res.data.status === false) {
        notification.error({
          title: res.data.message,
          position: 'top',
        });
        return;
      }
      notification.success({
        title: 'Processed',
        position: 'top',
      });

      router.reload({
        only: ['payments'],
      });
    })
    .catch(err => {
      deletePaymentProcess.value = false;
      if (err.response.data) {
        notification.error({
          title: err.response.data?.message,
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Delete authorized payment process failed',
          position: 'top',
        });
      }
    });
};

const fetchInsurerAMLStatus = async () => {
  if (props.quoteRequest?.payments[0]?.payment_gateway_id == 3) {
    NProgress.start();
    const response = await axios.get(route('insurer-aml-status-logs'), {
      params: {
        quoteRequestId: props.quoteRequest.id,
        quoteType: page.props.quoteTypeId,
        insurerAMLStatus: props.quoteRequest.insurer_aml_status,
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

const triggerPostPrepayment = async splitPayment => {
  console.log(' triggerPostPrepayment : ', splitPayment.id);
  let quoteStatusId = props.quoteRequest.quote_status_id;
  let isPolicyBooked =
    page.props.quoteStatusEnum.PolicyBooked === quoteStatusId;
  if (!isPolicyBooked) {
    notification.warning({
      title:
        'Posting of Prepayment cannot be triggered as Policy is not Booked yet!',
      position: 'top',
    });
  }
  try {
    NProgress.start();
    const response = await axios.post(route('can-post-premium-prepayment'), {
      paymentSplitId: splitPayment.id,
      quoteRequestId: props.quoteRequest.id,
      quoteType: page.props.quoteType,
      sendUpdateId: props.sendUpdate?.id,
    });
    NProgress.done();
    if (response.data.success) {
      notification.success({
        title: 'Post Prepayment to Sage Process Started',
        position: 'top',
      });
      router.reload({
        only: ['payments'],
      });
    }
  } catch (error) {
    let errorMessages = error.response.data.errors;
    Object.keys(errorMessages).forEach(function (key) {
      notification.error({
        title: errorMessages[key],
        position: 'top',
      });
    });
  }
};

onBeforeMount(() => {
  fetchInsurerAMLStatus();
});
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
                    :isApproveConfirmed="isApproveConfirmed"
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
                      :paymentMethodsForm="paymentMethodsForm"
                      :sendUpdateStatusEnum="sendUpdateStatusEnum"
                      :quoteType="quoteType"
                      @view-payment="
                        (payment, splitId, splitNo, action) =>
                          editPaymentModal(payment, splitId, splitNo, action)
                      "
                      @generate-cc-link="
                        (code, srNo, statusId) =>
                          generateCCLink(code, srNo, statusId)
                      "
                      @delete-split-payment="
                        (splitId, statusId) =>
                          deleteSplitPaymentModal(splitId, statusId)
                      "
                      @retry-split-payment="
                        (jobId, message) =>
                          retrySplitPaymentModal(jobId, message)
                      "
                      @post-prepayment="triggerPostPrepayment"
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
            isCreditCardView
              ? 'Capture Transaction'
              : isCreditApprovalView
                ? 'Approve Transaction'
                : isViewEnabled
                  ? 'View Payment'
                  : paymentMethodsForm.status == 'create'
                    ? 'New Payment'
                    : 'Update Payment'
          "
          show-close
          backdrop
        >
            <CreatePaymentForm
              ref="createPaymentFormRef"
              :isFieldReadonly="isFieldReadonly"
              :paymentMethodsForm="paymentMethodsForm"
              :rules="rules"
              :totalPrice="totalPrice"
              :collectionTypes="collectionTypes"
              :isCollectedByEnabled="isCollectedByEnabled"
              :frequencyTypes="frequencyTypes"
              :isPaymentFrequencyNotSelected="isPaymentFrequencyNotSelected"
              :getPlanName="getPlanName"
              :isPaymentNoEnabled="isPaymentNoEnabled"
              :totalPayments="totalPayments"
              :masterPaymentStatus="masterPaymentStatus"
              :isCreditApprovalAllowed="isCreditApprovalAllowed"
              :creditApprovalReasons="creditApprovalReasons"
              :isPaymentLocked="isPaymentLocked"
              :isTotalPriceUpdated="isTotalPriceUpdated"
              :isCustomReasonEnabled="isCustomReasonEnabled"
              :showDiscountOptions="showDiscountOptions"
              :isDiscountAllowed="isDiscountAllowed"
              :discountTypes="discountTypes"
              :discountTypeLabel="discountTypeLabel"
              :isDiscountReasonEnabled="isDiscountReasonEnabled"
              :discountReasons="discountReasons"
              :isDiscountReasonError="isDiscountReasonError"
              :isCustomDiscountReasonEnabled="isCustomDiscountReasonEnabled"
              :isDiscountEnabled="isDiscountEnabled"
              :paymentDocument="paymentDocument"
              :isDiscountDocumentNotUploaded="isDiscountDocumentNotUploaded"
              :discountDocumentModel="discountDocumentModel"
              :isDiscountError="isDiscountError"
              :discountValue="discountValue"
              :discountError="discountError"
              :totalAmount="totalAmount"
              :documentForm="documentForm"
              :isDowngradeFrequencyError="isDowngradeFrequencyError"
              :isPaymentCalculationError="isPaymentCalculationError"
              :fileErrorMessage="fileErrorMessage"
              :isViewEnabled="isViewEnabled"
              :isCreditApprovalView="isCreditApprovalView"
              :isVerifiedEnabled="isVerifiedEnabled"
              :isPaymentMethodEnabled="isPaymentMethodEnabled"
              :isPaidEditable="isPaidEditable"
              :isCreditCardView="isCreditCardView"
              :splitPaymentNo="splitPaymentNo"
              :splitPaymentRecord="splitPaymentRecord"
              :paymentMethodsModels="paymentMethodsModels"
              :checkDetailModels="checkDetailModels"
              :splitAmountModels="splitAmountModels"
              :dueDateModels="dueDateModels"
              :collectionAmountModels="collectionAmountModels"
              :fileUploadModels="fileUploadModels"
              :readOnlyPayments="readOnlyPayments"
              :isPaymentMetodNotSelected="isPaymentMetodNotSelected"
              :isSplitAmountInvalid="isSplitAmountInvalid"
              :isSplitAmountInvalidError="isSplitAmountInvalidError"
              :isDocumentNotUploaded="isDocumentNotUploaded"
              :isCreditPaymentInvalid="isCreditPaymentInvalid"
              :isCreditPaymentInvalidError="isCreditPaymentInvalidError"
              :isCheckDetailsEnabled="isCheckDetailsEnabled"
              :authorizedPayments="authorizedPayments"
              :paymentProofDocument="paymentProofDocument"
              :capturePaymentValidationErrorMessage="capturePaymentValidationErrorMessage"
              :paymentTypes="paymentTypes"
              :paymentTypesFiltered="paymentTypesFiltered"
              :isMultiPaymentsEnabled="isMultiPaymentsEnabled"
              :quoteType="quoteType"
              :sendUpdate="sendUpdate"
              :payments="payments"
              :isCCEnabled="isCCEnabled"
              :quoteRequest="quoteRequest"
              :sendUpdateStatusEnum="sendUpdateStatusEnum"
              :isDeclineClicked="isDeclineClicked"
              :isDeclinedReasonError="isDeclinedReasonError"
              :declinedReasons="declinedReasons"
              :isDeclineCustomReason="isDeclineCustomReason"
              :isApproveClicked="isApproveClicked"
              :isApprovePaymentError="isApprovePaymentError"
              :approveErrorMessage="approveErrorMessage"
              :approveProofDocument="approveProofDocument"
              :isApprovedDocumentNotUploaded="isApprovedDocumentNotUploaded"
              :approvedDocumentModel="approvedDocumentModel"
              :paymentStatusEnum="paymentStatusEnum"
              :permissionEnum="permissionEnum"
              :paymentMethodsEnum="paymentMethodsEnums"
              :isVerificationAllowed="isVerificationAllowed"
              :isProformaPaymentRequest="isProformaPaymentRequest"
              :isTransactionCaptureButtonEnabled="isTransactionCaptureButtonEnabled"
              :isApproveConfirmed="isApproveConfirmed"
              :processing="paymentMethodsForm.processing"
              :formStatus="paymentMethodsForm.status"
              :insurerPaymentLinkIndex="insurerPaymentLinkIndex"
              :planDetail="planDetail"
              :quoteTypesToCheck="quoteTypesToCheck"
              :insuranceProviders="insuranceProviders"
              :isApproveConfirm="isApproveConfirm"
              @handle-declined-reason-change="handleDeclinedReasonChange"
              @handle-collection-type-change="handleCollectionTypeChange"
              @handle-frequency-change="handleFrequencyChange"
              @calculate-payment-breakup="calculatePaymentBreakup"
              @reset-credit-approval="resetCreditApproval"
              @handle-approval-reason-change="handleApprovalReasonChange"
              @reset-discount="resetDiscount"
              @handle-discount-change="handleDiscountChange"
              @handle-discount-reason-change="handleDiscountReasonChange"
              @upload-document="uploadDocument"
              @open-inner-modal="openInnerModal"
              @delete-document="deleteDocument"
              @calculate-total-amount="calculateTotalAmount"
              @cancel="handleCancelChanges"
              @decline="handleDeclinedChange"
              @approve="isApproveClicked = !isApproveClicked"
              @cancel-modal="createPaymentModal = !createPaymentModal"
              @aml-verification="openAmlVerificationModal"
              @handle-payment-options="handlePaymentOptions"
              @handle-discount-value-change="(value) => discountValue = value"
              @validate-insurer-payment-link="validateInsurerPaymentLink"
              @update-from-insurer-payment-link="(e, f, g) => updateFromInsurerPaymentLink(e, f, g)"
            />

          <div
            class="modal-overlay fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center"
            v-if="isGalleryModelOpen"
          >
            <div
              class="modal-container bg-white w-full max-w-full overflow-hidden rounded-lg"
              tabindex="0"
              ref="modal2Ref"
              @keydown="handleKeyDown"
            >
              <div class="modal-header text-base text-white bg-gray-800">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    {{ currentFile.original_name }}
                  </div>
                  <div class="flex items-center space-x-2">
                    <span
                      @click="closeInnerModal"
                      class="text-gray-300 font-bold cursor-pointer pr-1"
                    >
                      <!-- SVG for Close Modal -->
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        tabindex="0"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="w-4 h-4"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"
                        ></path>
                      </svg>
                    </span>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <div
                    class="flex items-center space-x-2 cursor-pointer"
                    @click="previousFile"
                    :class="{
                      'opacity-50 cursor-not-allowed': !hasPreviousFile,
                    }"
                  >
                    <!-- SVG for Previous -->
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      class="w-6 h-6 text-gray-300"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                      ></path>
                    </svg>
                    Previous
                  </div>
                  <div
                    class="flex items-center space-x-2"
                    v-if="currentFile.doc_mime_type != 'application/pdf'"
                  >
                    <div
                      class="flex items-center space-x-2 cursor-pointer"
                      @click="zoomOut"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="h-6 w-6 text-gray-300"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M20 12H4"
                        />
                      </svg>
                    </div>
                    <div class="flex flex-initial w-24 justify-center">
                      <span class="text-gray-300 font-bold"
                        >{{ zoomLevel * 100 }}%</span
                      >
                    </div>
                    <div
                      class="flex items-center space-x-2 cursor-pointer"
                      @click="zoomIn"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="h-6 w-6 text-gray-300"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                      </svg>
                    </div>
                  </div>
                  <div
                    class="flex items-center space-x-2 cursor-pointer"
                    @click="nextFile"
                    :class="{ 'opacity-50 cursor-not-allowed': !hasNextFile }"
                  >
                    <!-- SVG for Next -->
                    Next
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      class="w-6 h-6 text-gray-300"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                      ></path>
                    </svg>
                  </div>
                </div>
              </div>
              <div class="modal-body w-full h-full mt-2">
                <div
                  v-if="
                    currentFile.doc_mime_type === 'image/jpeg' ||
                    currentFile.doc_mime_type === 'image/png'
                  "
                  class="flex items-center justify-center"
                >
                  <div class="overflow-auto items-center justify-center">
                    <img
                      :src="storageUrl + currentFile.doc_url"
                      :style="{ transform: `scale(${zoomLevel})` }"
                      class="max-w-full max-h-full"
                    />
                  </div>
                </div>
                <div
                  v-else-if="currentFile.doc_mime_type === 'application/pdf'"
                  class="w-full h-80vh"
                >
                  <embed
                    :src="storageUrl + currentFile.doc_url"
                    type="application/pdf"
                    class="w-full h-full"
                  />
                </div>
              </div>
            </div>
          </div>
        </x-modal>

        <!--  Clear AML KYC Screening         -->
        <x-modal v-model="isAmlApprovalRequired" size="lg">
          <div class="flex items-center justify-end space-x-2">
            <span
              @click="closeAmlConfirmModal"
              class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
            >
              <!-- Cross icon -->
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                tabindex="0"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="w-4 h-4 text-gray-800"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </span>
          </div>
          <x-form :auto-focus="false">
            <div class="text-lg text-center">
              <span>Please complete the AML screening to proceed.</span>
            </div>
            <div class="mt-2 text-center">
              <Link
                :href="`/kyc/aml/${
                  page.props.quoteTypeId ?? props.sendUpdate.quote_type_id
                }/details/${props.quoteRequest.id}`"
              >
                <x-tooltip>
                  <x-button
                    v-if="can(permissionEnum.AMLList)"
                    size="lg"
                    color="orange"
                    class="px-4 py-4 mt-4"
                    :loading="paymentMethodsForm.processing"
                  >
                    <span>Go to AML & KYC page</span></x-button
                  >
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.GOTO_AML_AND_KYC_PAGE }}</span>
                  </template>
                </x-tooltip>
              </Link>
            </div>
          </x-form>
        </x-modal>

        <!--  Clear AML KYC Screening         -->

        <div
          class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
          v-if="isRetryModalOpen"
        >
          <div
            class="modal-retry-container bg-white w-full max-w-full overflow-hidden rounded-lg"
          >
            <div class="modal-confirm-header text-base text-white bg-white">
              <div
                class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
              >
                <div class="flex items-center space-x-2">
                  Retry Payment Verification
                </div>
                <div class="flex items-center space-x-2">
                  <span
                    @click="closeRetryModal"
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      class="w-4 h-4 text-gray-800"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      ></path>
                    </svg>
                  </span>
                </div>
              </div>
            </div>
            <x-form @submit="handleRetryPayment" :auto-focus="false">
              <div class="w-full h-full mt-2 flex flex-col">
                <div
                  class="text-lg px-6 py-4 border-b flex justify-between items-start"
                >
                  <div class="text-left">
                    <span> {{ retryPaymentErrorMessage }}</span>
                  </div>
                </div>
              </div>
              <div class="w-full h-full mt-2 flex flex-col items-center">
                <x-button
                  size="lg"
                  type="submit"
                  color="orange"
                  class="px-4 py-2 mt-4 mb-4"
                  :loading="retryForm.processing"
                >
                  <span>Retry</span></x-button
                >
              </div>
            </x-form>
          </div>
        </div>

        <div
          class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
          v-if="isDeleteModalOpen"
        >
          <div
            class="modal-retry-container bg-white w-full max-w-full overflow-hidden rounded-lg"
          >
            <div class="modal-confirm-header text-base text-white bg-white">
              <div
                class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
              >
                <div class="flex items-center space-x-2">
                  Delete Split Payment
                </div>
                <div class="flex items-center space-x-2">
                  <span
                    @click="closeDeleteModal"
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      class="w-4 h-4 text-gray-800"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      ></path>
                    </svg>
                  </span>
                </div>
              </div>
            </div>
            <x-form @submit="handleDeletePayment" :auto-focus="false">
              <div class="w-full h-full mt-2 flex flex-col">
                <div
                  class="text-lg px-6 py-4 border-b flex justify-between items-start"
                >
                  <div class="text-left">
                    <span> Are you sure to delete this payment?</span>
                  </div>
                </div>
              </div>
              <div class="w-full h-full mt-2 flex flex-col items-center">
                <x-button
                  size="lg"
                  type="submit"
                  color="orange"
                  class="px-4 py-2 mt-4 mb-4"
                  :loading="deleteForm.processing"
                >
                  <span>Delete</span></x-button
                >
              </div>
            </x-form>
          </div>
        </div>

        <x-modal
          v-model="voidPaymentModelPopup"
          size="lg"
          title="Void Authorized Payment"
          show-close
          backdrop
        >
          <x-form :auto-focus="false">
            <div class="text-lg text-center">
              <span> Are you sure to void this payment?</span>
            </div>
            <div class="mt-2 text-center">
              <x-button
                size="sm"
                color="orange"
                class="mt-4 text-center"
                :loading="voidPaymentProcess"
                @click="voidPayment"
              >
                <span>Confirm</span>
              </x-button>
            </div>
          </x-form>
        </x-modal>
        <x-modal
          v-model="deletePaymentModelPopup"
          size="lg"
          title="Delete Payment"
          show-close
          backdrop
        >
          <x-form :auto-focus="false">
            <div class="text-lg text-center">
              <span> Are you sure to Delete this payment?</span>
            </div>
            <div class="mt-2 text-center">
              <x-button
                size="sm"
                color="orange"
                class="mt-4 text-center"
                :loading="deletePaymentProcess"
                @click="deletePayment"
              >
                <span>Delete</span>
              </x-button>
            </div>
          </x-form>
        </x-modal>
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
</style>
