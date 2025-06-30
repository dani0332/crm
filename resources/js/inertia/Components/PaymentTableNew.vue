<script setup>
import ToolTip from './../Components/ToolTip.vue';
import { onMounted, reactive, ref, nextTick } from 'vue';
import moment from 'moment';
import NProgress from 'nprogress';
import { computed } from 'vue';
import UpdateTotalPrice from './../Components/UpdateTotalPrice.vue';
import { time } from 'highcharts';
import {
  ImageGalleryModal,
  AmlApprovalModal,
  RetryPaymentModal,
  DeleteSplitPaymentModal,
  DeleteParentPaymentModal,
  VoidPaymentModal,
} from './PaymentComponents/PaymentModal/index.js';

// New Flow Implementation
import { usePayment } from '../Composables/usePayment';
import { useAMLKYC } from '../Composables/useAMLKYC';
import {
  PaymentTableHeader,
  PaymentHeader,
  PaymentRow,
  PaymentSplitRow,
} from './PaymentComponents/index.js';

// Assign barrel-imported components to prevent IDE from showing them as unused
const components = { PaymentTableHeader, ImageGalleryModal };

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

const {
  formatDate,
  formatAmount,
  formatString,
  filterCCPayments,
  filterCAPayments,
  verifyCreditApproved,
} = usePayment();
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
const deleteSplitPaymentCode = ref('');
const isCollectedByEnabled = ref(false);
const isTransactionCaptureButtonEnabled = ref(true);
const premiumToCapture = ref(0);
const capturePaymentValidationInProcess = ref(false);
const capturePaymentValidationErrorMessage = ref('');
const insurerPaymentLinkChanged = ref(false);
const confirmModalClose = ref(false);
const insurerPaymentComponent = ref(null);
const selectedPaymentForEdit = ref(null);

const familyEmployeDiscount = [
  quoteTypeCodeEnum.Car,
  quoteTypeCodeEnum.Health,
  quoteTypeCodeEnum.Home,
  quoteTypeCodeEnum.Travel,
  quoteTypeCodeEnum.SAVINGS,
];
// Array of quote types to check against
const quoteTypesToCheck = [
  quoteTypeCodeEnum.Car,
  quoteTypeCodeEnum.Health,
  quoteTypeCodeEnum.Travel,
  quoteTypeCodeEnum.Home,
  quoteTypeCodeEnum.SAVINGS,
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
const isCustomFrequency = computed(
  () => paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM,
);
const isSplitFrequency = computed(
  () => paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS,
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

const discountProofDocument = props.paymentDocument.find(
  item => item.text === 'Discount Proof',
);
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
} else if (props.quoteType == quoteTypeCodeEnum.SAVINGS) {
  initalPlanDetails = props.quoteRequest.insurance_provider_plan;
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

/**
 * Calculates the total amount after applying discount
 * Updates totalAmount value and triggers payment breakup recalculation
 */
const calculateTotalAmount = () => {
  const discount = discountValue.value;
  if (
    discount > 50 &&
    paymentMethodsForm.discount_reason === 'refer_a_friend'
  ) {
    totalAmount.value = totalPrice.value;
  } else {
    totalAmount.value = totalPrice.value - discount;
  }
  calculatePaymentBreakup(false);
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

/**
 * Opens the image gallery modal to display a specific file
 *
 * @param {number} fileId - ID of the file to display initially
 */
const openInnerModal = fileId => {
  // Prepare the files array for the gallery modal by combining files from different sources
  filesTest.value = [
    ...fileUploadModels.value.flat(),
    ...approvedDocumentModel.value.flat(),
    ...discountDocumentModel.value.flat(),
  ];

  // Find the index of the file to display in the combined array
  currentFileIndex.value = filesTest.value.findIndex(
    item => item.id === fileId,
  );

  // Open the modal
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

// Close confirmation modal function remains unchanged
const closeConfirmModal = () => {
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
  isApproveConfirm.value = false;
};

const closeAmlConfirmModal = () => {
  isAmlApprovalRequired.value = false;
};

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

const handleDeclinedChange = () => {
  isDeclineClicked.value = true;
  isApproveClicked.value = false;
  isDeclineCustomReason.value = false;
  isApproveNotChecked.value = false;
  handleDeclinedReasonChange();
  return true;
};

const calculateTotalSplitAmount = () => {
  let totalSplitAmount = 0;
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
    totalSplitAmount += parseFloat(splitAmountModels.value[i]);
  }
  return totalSplitAmount;
};

/**
 * Validates payment options before submission
 * Checks for issues such as:
 * - Missing payment frequency
 * - Missing payment methods
 * - Payment calculation errors
 * - Missing required documents
 * - Discount validation
 *
 * @returns {boolean} True if there are validation issues, false if all validations pass
 */
const validatePaymentOption = () => {
  var totalSplitAmount = 0;
  var issueFound = false;
  isPaymentCalculationError.value = false;
  isPaymentFrequencyNotSelected.value = false;
  // Check if payment frequency is selected
  if (paymentMethodsForm.frequency === '') {
    isPaymentFrequencyNotSelected.value = true;
    issueFound = true;
  }
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
    totalSplitAmount =
      parseFloat(totalSplitAmount) + parseFloat(splitAmountModels.value[i]);
    const validationResult = rules.notEmptyOrZero(
      paymentMethodsModels.value[i],
    );
    isPaymentMetodNotSelected.value[i] = false;
    if (validationResult !== true) {
      isPaymentMetodNotSelected.value[i] = true;
      issueFound = true;
    }
  }

  if (isPolicyIssuanceDiscount.value === true) {
    if (
      totalSplitAmount.toFixed(2) ===
        parseFloat(totalAmount.value).toFixed(2) ||
      discountValue.value > 0
    ) {
      isPaymentCalculationError.value = false;
    } else {
      isPaymentCalculationError.value = true;
      issueFound = true;
    }
  } else if (
    totalSplitAmount.toFixed(2) !== parseFloat(totalAmount.value).toFixed(2)
  ) {
    isPaymentCalculationError.value = true;
    issueFound = true;
  }
  const validFrequencies = [
    paymentFrequencyEnum.MONTHLY,
    paymentFrequencyEnum.QUARTERLY,
    paymentFrequencyEnum.SEMI_ANNUAL,
    paymentFrequencyEnum.CUSTOM,
  ];
  if (
    validFrequencies.includes(paymentMethodsForm.frequency) &&
    paymentMethodsModels.value[1] ===
      page.props.paymentMethodsEnum?.InsurerPayment &&
    paymentMethodsForm.collection_type === 'insurer'
  ) {
    if (
      fileUploadModels.value[1] === undefined ||
      fileUploadModels.value[1].length === 0
    ) {
      isDocumentNotUploaded.value[1] = true;
      issueFound = true;
    }
  } else {
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
      isDocumentNotUploaded.value[i] = false;
      if (
        (paymentMethodsModels.value[i] ==
          page.props.paymentMethodsEnum?.InsureNowPayLater ||
          paymentMethodsModels.value[i] ==
            page.props.paymentMethodsEnum?.BankTransfer ||
          paymentMethodsModels.value[i] ==
            page.props.paymentMethodsEnum?.Cheque ||
          paymentMethodsModels.value[i] ==
            page.props.paymentMethodsEnum?.PostDatedCheque ||
          (paymentMethodsForm.credit_approval === '' &&
            paymentMethodsModels.value[i] ==
              page.props.paymentMethodsEnum?.InsurerPayment)) &&
        (fileUploadModels.value[i] === undefined ||
          fileUploadModels.value[i].length === 0)
      ) {
        isDocumentNotUploaded.value[i] = true;
        issueFound = true;
      } else if (
        paymentMethodsModels.value[i] ==
          page.props.paymentMethodsEnum?.CreditApproval &&
        i === 1 &&
        (fileUploadModels.value[i] === undefined ||
          fileUploadModels.value[i].length === 0)
      ) {
        isDocumentNotUploaded.value[i] = true;
        issueFound = true;
      }
    }
  }
  if (
    isDiscountEnabled.value === true &&
    isCreditApprovalView.value === false
  ) {
    isDiscountError.value = false;
    // Check if discount document uploaded
    if (
      discountDocumentModel.value[0] === undefined ||
      discountDocumentModel.value[0].length === 0
    ) {
      isDiscountDocumentNotUploaded.value = true;
      issueFound = true;
    } else {
      isDiscountDocumentNotUploaded.value = false;
    }

    if (discountValue.value === '' || parseFloat(discountValue.value) <= 0) {
      issueFound = true;
      isDiscountError.value = true;
      discountError.value = 'This field is required';
    }
    const regex = /^\d+(\.\d{1,2})?$/;
    if (!regex.test(discountValue.value)) {
      issueFound = true;
      isDiscountError.value = true;
      discountError.value = 'Discount must be a valid number';
    }
    if (parseFloat(discountValue.value) > parseFloat(totalPrice.value)) {
      issueFound = true;
      isDiscountError.value = true;
      totalAmount.value = totalPrice.value;
      calculatePaymentBreakup();
      discountError.value = 'Discount should not exceed total amount';
    }
    if (
      paymentMethodsForm.discount_reason === 'refer_a_friend' ||
      paymentMethodsForm.discount === 'incentive_offset' ||
      paymentMethodsForm.discount === 'managerial_approval_discount'
    ) {
      isDiscountReasonEnabled.value = true;
      if (paymentMethodsForm.discount_reason === '') {
        issueFound = true;
        isDiscountReasonError.value = true;
      } else {
        isDiscountReasonError.value = false;
      }

      // Check if the discount exceeds 50 and return an error message
      if (
        discountValue.value > 50 &&
        paymentMethodsForm.discount_reason === 'refer_a_friend'
      ) {
        issueFound = true;
        isDiscountError.value = true;
        discountError.value = 'Discount should not exceed 50 AED';
      }
    } else if (
      paymentMethodsForm.discount === 'employee_discount' ||
      paymentMethodsForm.discount === 'family_employee_discount'
    ) {
      if (
        parseFloat(discountValue.value) > parseFloat(calculatedDiscount.value)
      ) {
        issueFound = true;
        isDiscountError.value = true;
        discountError.value =
          'Discount should not exceed ' + calculatedDiscount.value + ' AED';
      }
    } else {
      isDiscountReasonEnabled.value = false;
      isDiscountReasonError.value = false;
    }
  }
  if (issueFound) {
    return true;
  }
  return false;
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

const getPaymentTypeLabel = code => {
  const paymentType = paymentTypes.value.find(item => item.value === code);
  if (paymentType) {
    return paymentType.label;
  }
  return '';
};

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

const handleDiscountReasonChange = () => {
  isDiscountReasonError.value = false;
  isCustomDiscountReasonEnabled.value = false;
  if (paymentMethodsForm.discount_reason === 'refer_a_friend') {
    calculateTotalAmount();
  }
  if (paymentMethodsForm.discount_reason === 'discount_custom_reason') {
    paymentMethodsForm.discount_custom_reason = '';
    isCustomDiscountReasonEnabled.value = true;
  }
};

const handleDeclinedReasonChange = () => {
  if (getCustomReasonIndex(paymentMethodsForm.declined_reason) === 6) {
    isDeclineCustomReason.value = true;
  } else {
    isDeclineCustomReason.value = false;
  }
};

const handleCancelChanges = () => {
  isDeclineClicked.value = !isDeclineClicked.value;
  isApproveClicked.value = false;
  isDeclinedReasonError.value = false;
};

const isProformaPaymentRequest = computed(() => {
  return (
    paymentMethodsForm.payment_method ===
    page.props.paymentMethodsEnum?.ProformaPaymentRequest
  );
});

const handlePaymentOptions = count => {
  isPaymentMetodNotSelected.value[count] = false;
  if (
    paymentMethodsModels.value[count] ===
      page.props.paymentMethodsEnum?.Cheque ||
    paymentMethodsModels.value[count] ===
      page.props.paymentMethodsEnum?.PostDatedCheque
  ) {
    isCheckDetailsEnabled.value[count] = true;
  } else {
    isCheckDetailsEnabled.value[count] = false;
  }
  setFrequencyTypes();
};

const handleCollectionTypeChange = () => {
  //customize payment method based on collection type
  paymentTypesFiltered.value = paymentTypes.value;
  let excludedPaymentTypes = [
    page.props.paymentMethodsEnum?.InsureNowPayLater,
    page.props.paymentMethodsEnum?.CreditApproval,
    page.props.paymentMethodsEnum?.MultiplePayment,
    page.props.paymentMethodsEnum?.PartialPayment,
  ];

  if (isInsureNowPayLaterAllowed.value) {
    excludedPaymentTypes = excludedPaymentTypes.filter(
      paymentType =>
        paymentType !== page.props.paymentMethodsEnum?.InsureNowPayLater,
    );
  }

  if (paymentMethodsForm.frequency != paymentFrequencyEnum.UPFRONT) {
    /*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is not UpFront*/
    excludedPaymentTypes.push(
      page.props.paymentMethodsEnum?.ProformaPaymentRequest,
    );
  } else if (
    can(permissionEnum.ADD_PROFORMA_PAYMENT_REQUEST_DROPDOWN_OPTION) == false
  ) {
    /*Add Proforma Payment Request to excluded Payment Methods When dont have ADD_PROFORMA_PAYMENT_REQUEST_DROPDOWN_OPTION permission */
    excludedPaymentTypes.push(
      page.props.paymentMethodsEnum?.ProformaPaymentRequest,
    );
  }
  paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
    item => !excludedPaymentTypes.includes(item.value),
  );

  if (paymentMethodsForm.collection_type === 'insurer') {
    let isIPLPermission = can(permissionEnum.INSURER_PAYMENT_LINK);
    let isHealthQuote = props.quoteType === quoteTypeCodeEnum.Health;
    let checkCondition = !isHealthQuote || !isIPLPermission;
    const excludedPaymentMethods = [
      page.props.paymentMethodsEnum?.BankTransfer,
      page.props.paymentMethodsEnum?.Cheque,
      page.props.paymentMethodsEnum?.Cash,
      checkCondition && page.props.paymentMethodsEnum?.InsurerPaymentLink,
    ];
    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
      item => !excludedPaymentMethods.includes(item.value),
    );
    paymentMethodsModels.value[1] =
      page.props.paymentMethodsEnum?.InsurerPayment;
  } else {
    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => {
      return ![
        page.props.paymentMethodsEnum?.InsurerPayment,
        page.props.paymentMethodsEnum?.InsurerPaymentLink,
      ].includes(item.value);
    });
    paymentMethodsModels.value[1] = '';
  }
  applyPermissions();
};

const handlePaymentTypes = count => {
  var paymentTypesWithoutCheck = paymentTypesFiltered.value;
  const frequenciesToFilterForCount = [
    paymentFrequencyEnum.SEMI_ANNUAL,
    paymentFrequencyEnum.QUARTERLY,
    paymentFrequencyEnum.MONTHLY,
  ];

  const frequenciesToFilterForInsurer = [
    paymentFrequencyEnum.SEMI_ANNUAL,
    paymentFrequencyEnum.SPLIT_PAYMENTS,
    paymentFrequencyEnum.CUSTOM,
    paymentFrequencyEnum.QUARTERLY,
    paymentFrequencyEnum.MONTHLY,
  ];

  if (
    count >= 2 &&
    frequenciesToFilterForCount.includes(paymentMethodsForm.frequency)
  ) {
    paymentTypesWithoutCheck = filterPaymentTypes(paymentTypesFiltered.value, [
      page.props.paymentMethodsEnum.Cheque,
    ]);
  }

  if (
    paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT ||
    paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
  ) {
    paymentTypesWithoutCheck = filterPaymentTypes(paymentTypesWithoutCheck, [
      page.props.paymentMethodsEnum.PostDatedCheque,
    ]);
  }

  if (paymentMethodsForm.collection_type === 'insurer') {
    let isMultiPaymentEnabled = isMultiPaymentsEnabled.value;
    if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
      isMultiPaymentEnabled = selectedPaymentForEdit.value
        ? selectedPaymentForEdit.value?.isMultiplePaymentsEnabled
        : isMultiPaymentEnabled;
    }
    const frequenciesToFilter = isMultiPaymentEnabled
      ? frequenciesToFilterForCount
      : frequenciesToFilterForInsurer;

    // Bypass multi payment for ADNIC and showing CREDIT CARD for all child payments for Split Frequency
    const isADNICProvider =
      page.props?.bookPolicyDetails?.isADNICProvider || false;
    if (isADNICProvider && isSplitFrequency.value) {
      return paymentTypesWithoutCheck;
    }

    if (count >= 2 || !isMultiPaymentEnabled) {
      if (frequenciesToFilter.includes(paymentMethodsForm.frequency)) {
        paymentTypesWithoutCheck = filterPaymentTypes(
          paymentTypesWithoutCheck,
          [page.props.paymentMethodsEnum.CreditCard],
        );
      }
    }
  }
  return paymentTypesWithoutCheck;
};

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

const handleApprovalReasonChange = (noPaymentUpdate = true) => {
  if (paymentMethodsForm.credit_approval === 'other_reasons') {
    isCustomReasonEnabled.value = true;
  } else {
    isCustomReasonEnabled.value = false;
  }
  //customize payment method based on collection type
  if (paymentMethodsForm.credit_approval !== '') {
    if (noPaymentUpdate) {
      handleCreditApproval();
    }
    paymentTypesFiltered.value = paymentTypes.value;

    paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
      item =>
        ![
          isInsureNowPayLaterAllowed.value
            ? null
            : page.props.paymentMethodsEnum?.InsureNowPayLater,
          page.props.paymentMethodsEnum?.ProformaPaymentRequest,
          page.props.paymentMethodsEnum?.MultiplePayment,
          page.props.paymentMethodsEnum?.PartialPayment,
        ]
          .filter(Boolean)
          .includes(item.value),
    );

    if (paymentMethodsForm.collection_type === 'insurer') {
      if (paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT) {
        /*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is  UpFront*/
        const excludedPaymentMethods = [
          page.props.paymentMethodsEnum?.BankTransfer,
          page.props.paymentMethodsEnum?.Cheque,
          page.props.paymentMethodsEnum?.Cash,
          page.props.paymentMethodsEnum?.PostDatedCheque,
        ];
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
          item => !excludedPaymentMethods.includes(item.value),
        );
      } else if (
        paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
      ) {
        /*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is split_payments*/
        const excludedPaymentMethods = [
          page.props.paymentMethodsEnum?.PostDatedCheque,
          page.props.paymentMethodsEnum?.ProformaPaymentRequest,
          page.props.paymentMethodsEnum?.Cheque,
          page.props.paymentMethodsEnum?.Cash,
          page.props.paymentMethodsEnum?.BankTransfer,
        ];
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
          item => !excludedPaymentMethods.includes(item.value),
        );
      } else {
        const excludedPaymentMethods = [
          page.props.paymentMethodsEnum?.Cheque,
          page.props.paymentMethodsEnum?.Cash,
          page.props.paymentMethodsEnum?.BankTransfer,
        ];
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
          item => !excludedPaymentMethods.includes(item.value),
        );
      }
    } else {
      if (paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT) {
        /*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is upfront*/
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
          item =>
            ![
              page.props.paymentMethodsEnum?.PostDatedCheque,
              page.props.paymentMethodsEnum?.InsurerPayment,
            ].includes(item.value),
        );
      } else if (
        paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
      ) {
        /*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is split_payments*/
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
          item =>
            ![
              page.props.paymentMethodsEnum?.PostDatedCheque,
              page.props.paymentMethodsEnum?.ProformaPaymentRequest,
              page.props.paymentMethodsEnum?.InsurerPayment,
            ].includes(item.value),
        );
      } else {
        paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
          item =>
            ![page.props.paymentMethodsEnum?.InsurerPayment].includes(
              item.value,
            ),
        );
      }
    }
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
      if (readOnlyPayments.value[i] === true) {
        continue;
      }
      paymentMethodsModels.value[i] =
        page.props.paymentMethodsEnum?.CreditApproval;
    }
  } else {
    if (isTotalPriceUpdated.value === true && isPaymentLocked.value === false) {
      handleCollectionTypeChange();
    }
  }
};

const resetCreditApproval = () => {
  paymentMethodsForm.credit_approval = '';
  isResetCreditApproval.value = true;
  isCustomReasonEnabled.value = false;
  if (isCustomFrequency.value && isSinglePayment.value) {
    paymentMethodsForm.frequency = paymentFrequencyEnum.UPFRONT;
  }
  handleApprovalReasonChange();
  handleFrequencyChange(false);
  if (isPaymentLocked.value && paymentMethodsForm.status == 'edit') {
    // If payment is locked, reset the payment method for split payments
    for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
      if (readOnlyPayments.value[i] === true) {
        continue;
      }
      paymentMethodsModels.value[i] = '';
    }
  }
};

const resetDiscount = (callDiscountChang = true) => {
  isDiscountEnabled.value = false;
  isDiscountReasonEnabled.value = false;
  paymentMethodsForm.discount = '';
  totalAmount.value = totalPrice.value;
  discountValue.value = 0;
  paymentMethodsForm.discount_reason = '';
  if (callDiscountChang) {
    handleDiscountChange();
  }
  handleDiscountReasonChange();
  calculateTotalAmount();
};

const handleDiscountChange = (editDiscountValue = 0) => {
  isDiscountError.value = false;
  discountError.value = '';
  if (paymentMethodsForm.status === 'create') {
    discountValue.value = 0;
    paymentMethodsForm.discount_reason = '';
  }
  if (resetDiscountReason.value === '') {
    paymentMethodsForm.discount_reason = '';
  }

  isDiscountReasonEnabled.value = false;
  if (
    paymentMethodsForm.discount === '' ||
    paymentMethodsForm.discount === undefined
  ) {
    resetDiscount(false);
    isDiscountEnabled.value = false;
  } else {
    isDiscountEnabled.value = true;
    isDiscountReasonEnabled.value = true;
  }
  if (paymentMethodsForm.discount === 'managerial_approval_discount') {
    isDiscountReasonEnabled.value = true;
  } else {
    isDiscountReasonEnabled.value = false;
  }

  if (paymentMethodsForm.discount === 'employee_discount') {
    if (props.quoteType === quoteTypeCodeEnum.Health) {
      discountValue.value = (
        initialTotalPriceWithoutVat.value *
        (5 / 100)
      ).toFixed(2);
    } else if (
      props.quoteType === quoteTypeCodeEnum.Home ||
      props.quoteType === quoteTypeCodeEnum.Travel
    ) {
      discountValue.value = (
        initialTotalPriceWithoutVat.value *
        (15 / 100)
      ).toFixed(2);
    } else {
      discountValue.value = (
        initialTotalPriceWithoutVat.value *
        (12.5 / 100)
      ).toFixed(2); // for car
    }
  }
  if (paymentMethodsForm.discount === 'family_employee_discount') {
    if (props.quoteType === quoteTypeCodeEnum.Health) {
      discountValue.value = (
        initialTotalPriceWithoutVat.value *
        (2.5 / 100)
      ).toFixed(2);
    } else if (
      props.quoteType === quoteTypeCodeEnum.Home ||
      props.quoteType === quoteTypeCodeEnum.Travel
    ) {
      discountValue.value = (
        initialTotalPriceWithoutVat.value *
        (12.5 / 100)
      ).toFixed(2);
    } else {
      discountValue.value = (
        initialTotalPriceWithoutVat.value *
        (7.5 / 100)
      ).toFixed(2); // for car
    }
  }
  if (
    paymentMethodsForm.discount === 'family_employee_discount' ||
    paymentMethodsForm.discount === 'employee_discount'
  ) {
    calculatedDiscount.value = discountValue.value;
    if (editDiscountValue > 0) {
      discountValue.value = editDiscountValue;
    }
  }
  calculateTotalAmount();
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

const calculatePaymentBreakup = (changeMethod = true) => {
  isDocumentNotUploaded.value = [];
  isDowngradeFrequencyError.value = false;
  var perInstallmentPrice = parseFloat(
    (
      (totalAmount.value - paidAmountSum.value) /
      (paymentMethodsForm.payment_no - totalPaidAmount.value)
    ).toFixed(2),
  );
  if (paymentMethodsForm.status === 'edit') {
    var trueValuesArray = readOnlyPayments.value.filter(function (value) {
      return value === true;
    });
    // Get the count of true values
    var trueValuesCount = trueValuesArray.length;
    if (paymentMethodsForm.payment_no <= trueValuesCount) {
      paymentMethodsForm.payment_no = oldTotalPayments.value;
      if (paymentMethodsForm.payment_no < trueValuesCount) {
        isDowngradeFrequencyError.value = true;
      }
      return;
    }
  }
  for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
    if (
      readOnlyPayments.value[i] != undefined &&
      readOnlyPayments.value[i] === true
    ) {
      continue;
    }
    splitAmountModels.value[i] = perInstallmentPrice.toFixed(2);

    const isFirstChildPayment = i === 1;
    const isCreditApprovalReset = isResetCreditApproval.value;
    const isCreditApprovalEmpty = paymentMethodsForm.credit_approval === '';

    if (
      changeMethod &&
      isFirstChildPayment &&
      isCreditApprovalReset &&
      isisUpfrontFrequency.value &&
      isCreditApprovalEmpty
    ) {
      paymentMethodsModels.value[i] = '';
    }

    if (i > 1 && changeMethod) {
      if (
        paymentMethodsModels.value[i] !== undefined &&
        paymentMethodsModels.value[i] !== null &&
        (paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS ||
          paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM)
      ) {
        if (
          paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM &&
          paymentMethodsForm.credit_approval !== ''
        ) {
          paymentMethodsModels.value[i] =
            page.props.paymentMethodsEnum?.CreditApproval;
        } else {
          paymentMethodsModels.value[i] = paymentMethodsModels.value[i];
        }
      } else {
        if (
          paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM &&
          paymentMethodsForm.credit_approval !== ''
        ) {
          paymentMethodsModels.value[i] =
            page.props.paymentMethodsEnum?.CreditApproval;
        } else {
          paymentMethodsModels.value[i] = '';
        }
      }
    }
  }
  calculateDueDates();
};

const resetTotalPayments = () => {
  totalPayments.value = [];
  for (let i = 1; i <= 20; i++) {
    totalPayments.value.push({ value: i.toString(), label: i.toString() });
  }
};

/**
 * Handles payment frequency changes
 * Updates payment number and calculates payment breakups based on selected frequency
 *
 * @param {boolean} noPaymentUpdate - If true, will reset payment number to default
 */
const handleFrequencyChange = (noPaymentUpdate = true) => {
  var resetPaymentMethod = false;
  isPaymentFrequencyNotSelected.value = false;
  totalPayments.value = [];
  if (paymentMethodsForm.status === 'create') {
    paymentMethodsForm.credit_approval = '';
  }

  isCustomReasonEnabled.value = false;
  handleApprovalReasonChange(noPaymentUpdate);
  resetTotalPayments();
  calculatePaymentBreakup();
  isPaymentNoEnabled.value = false;
  if (paymentMethodsForm.frequency === paymentFrequencyEnum.MONTHLY) {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '12';
  } else if (paymentMethodsForm.frequency === paymentFrequencyEnum.QUARTERLY) {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '4';
  } else if (
    paymentMethodsForm.frequency === paymentFrequencyEnum.SEMI_ANNUAL
  ) {
    resetPaymentMethod = true;
    paymentMethodsForm.payment_no = '2';
  } else if (
    paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
  ) {
    isPaymentNoEnabled.value = true;
    if (noPaymentUpdate) {
      paymentMethodsForm.payment_no = '2';
    }
    totalPayments.value.splice(-15);
    totalPayments.value.splice(0, 1);
  } else if (paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM) {
    isPaymentNoEnabled.value = true;
    if (noPaymentUpdate) {
      paymentMethodsForm.payment_no = '2';
      handleCreditApproval();
    }
    if (
      !(
        paymentMethodsForm.credit_approval !== '' &&
        paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM
      )
    ) {
      totalPayments.value.splice(0, 1);
    }
  } else {
    paymentMethodsForm.payment_no = '1';
  }
  calculatePaymentBreakup();
  // readOnlyPayments.value[1]===undefined this condition is missed from incoming (feat/insly-project-central), that's why added.
  if (
    paymentMethodsModels.value[1] ===
      page.props.paymentMethodsEnum?.CreditCard &&
    resetPaymentMethod &&
    readOnlyPayments.value[1] === undefined
  ) {
    paymentMethodsModels.value[1] = page.props.paymentMethodsEnum?.BankTransfer;
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
    quoteTypeCodeEnum.SAVINGS,
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
  handleCollectionTypeChange();
  calculatePaymentBreakup();
  applyPermissions();
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

  // Payment Capture Validation for GIG
  if (
    capture_approval == 1 &&
    payment?.insurance_provider?.code == 'AXA' &&
    (props.quoteType === quoteTypeCodeEnum.Bike ||
      props.quoteType === quoteTypeCodeEnum.Car ||
      props.quoteType === quoteTypeCodeEnum.Home ||
      props.quoteType === quoteTypeCodeEnum.Travel)
  ) {
    capturePaymentValidationInProcess.value = true;
    isTransactionCaptureButtonEnabled.value = false;
    await doCapturePaymentValidation(payment.total_amount, payment?.code);
  }

  resetPaymentForm();
  initializePaymentForm(payment, split_payment_id, sr_no, capture_approval);
  handleCollectionTypeChange();
  handleFrequencyChange(false);
  handleApprovalReasonChange(false);
  handleDiscountChange();
  handleDeclinedReasonChange();
  calculateTotalAmount();
  applyPermissions();
  processPaymentSplits(payment);
  finalizePaymentForm(payment, capture_approval);
  setFrequencyTypes();
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

/**
 * Resets the payment form to its initial state
 * Clears all form data, validation states, and temporary storage
 */
const resetPaymentForm = () => {
  paymentMethodsForm.reset();
  splitPaymentNo.value = 0;
  isFieldReadonly.value = false;
  isViewEnabled.value = false;
  paymentMethodsForm.splitPaymentId = 0;
  paymentMethodsForm.status = 'edit';
  paymentMethodsForm.declined_reason = '1';
  isPaymentCalculationError.value = false;
  isDowngradeFrequencyError.value = false;
  isApproveClicked.value = false;
  isFileError.value = false;
  isApprovePaymentError.value = false;
  totalPaidAmount.value = 0;
  isDeclineClicked.value = false;
  isDiscountReasonError.value = false;
  isApprovedDocumentNotUploaded.value = false;
  approvedDocument.value = '';
  approvedDocumentModel.value = [];
  isPaymentMetodNotSelected.value = [];
  isDocumentNotUploaded.value = [];
  resetDiscountReason.value = '';
  isApproveConfirm.value = false;
  isCreditApprovalView.value = false;
  isCreditCardView.value = false;
  approveErrorMessage.value = '';
  isCreditPaymentInvalid.value = [];
  isCreditPaymentInvalidError.value = [];
  paymentMethodsForm.declined_custom_reason = '';
  paidAmountSum.value = 0;
  isDiscountError.value = false;
  discountError.value = '';
  isDiscountDocumentNotUploaded.value = false;
  discountDocumentModel.value = [];
  trashedFilesModal.value = [];
  isDiscountEnabled.value = false;
  isTotalPriceUpdated.value = false;
  isGalleryModelOpen.value = false;
  authorizedPayments.value = [];
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
  isAmlApprovalRequired.value = false;
};

const initializePaymentForm = (
  payment,
  split_payment_id,
  sr_no,
  capture_approval,
) => {
  if (sr_no > 0) {
    splitPaymentNo.value = sr_no;
    isFieldReadonly.value = true;
    isViewEnabled.value = true;
    paymentMethodsForm.splitPaymentId = split_payment_id;
    paymentMethodsForm.status = 'view';
    paymentMethodsForm.collection_amount = '';
    paymentMethodsForm.payment_method = payment.payment_method.code;
    paymentMethodsForm.bank_reference_number = '';
    splitPaymentRecord.value = payment.payment_splits.find(
      item => item.sr_no === sr_no,
    );
    paymentMethodsForm.system_adjusted_discount =
      payment.system_adjusted_discount;
  }

  masterPaymentStatus.value = payment.payment_status.text;
  paymentMethodsForm.paymentCode = payment.code;
  paymentMethodsForm.insurance_provider_id = payment.insurance_provider_id;
  paymentMethodsForm.collection_type = payment.collection_type;
  paymentMethodsForm.payment_no = payment.total_payments;
  oldTotalPayments.value = payment.total_payments;
  paymentMethodsForm.frequency = payment.frequency;
  showDiscountOptions.value = true;
  paymentMethodsForm.discount_reason =
    payment.discount_reason !== null ? payment.discount_reason : '';

  if (payment.discount_reason !== null && payment.discount_type !== null) {
    resetDiscountReason.value = payment.discount_reason;
  }

  paymentMethodsForm.custom_reason = payment.custom_reason;
  paymentMethodsForm.discount_custom_reason = payment.discount_custom_reason;
  paymentMethodsForm.notes = payment.notes;
  paymentMethodsForm.total_amount = payment.total_amount; // after discount calculation
  paymentMethodsForm.total_price = payment.total_price;
  paymentMethodsForm.collection_date = payment.collection_date;
  discountValue.value = payment.discount_value; // discount amount

  if (paymentMethodsForm.status === 'view' || capture_approval > 0) {
    paymentMethodsForm.credit_approval =
      payment.credit_approval !== null ? payment.credit_approval : 'N/A';
    paymentMethodsForm.discount =
      payment.discount_type !== null ? payment.discount_type : 'N/A';
  } else {
    paymentMethodsForm.credit_approval =
      payment.credit_approval !== null ? payment.credit_approval : '';
    paymentMethodsForm.discount =
      payment.discount_type !== null ? payment.discount_type : '';
  }
  paymentMethodsForm.declined_reason =
    splitPaymentRecord.value.decline_reason_id == null
      ? ''
      : splitPaymentRecord.value.decline_reason_id;
  paymentMethodsForm.declined_custom_reason =
    splitPaymentRecord.value.decline_custom_reason;
  if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
    paymentMethodsForm.isCreditCardEnabled = payment.isCreditCardEnabled;
    paymentMethodsForm.isGIGProvider = payment.isGIGProvider;
    paymentMethodsForm.isMultiplePaymentsEnabled =
      payment.isMultiplePaymentsEnabled;
    paymentMethodsForm.isCaptureButtonEnabled = payment.isCaptureButtonEnabled;
  }
};

const processPaymentSplits = payment => {
  const paidStatusIds = [
    paymentStatusEnum.PAID,
    paymentStatusEnum.PARTIALLY_PAID,
    paymentStatusEnum.AUTHORISED,
    paymentStatusEnum.CAPTURED,
    paymentStatusEnum.PARTIAL_CAPTURED,
  ];

  // TODO: Permanent fix from here we are starting from 1 and not 0
  // We are sending null for the first split collection_amount
  for (let i = 1; i <= payment.total_payments; i++) {
    const split = payment.payment_splits[i - 1];
    console.log('split : ', split);
    readOnlyPayments.value[i] = paidStatusIds.includes(split.payment_status_id);
    if (readOnlyPayments.value[i]) {
      totalPaidAmount.value++;
      paidAmountSum.value += parseFloat(split.payment_amount);
    }
    authorizedPayments.value[i] =
      split.payment_status_id === paymentStatusEnum.AUTHORISED;
    fileUploadModels.value[i] = [];
    paymentMethodsModels.value[i] = split.payment_method.code;
    splitAmountModels.value[i] = split.payment_amount;
    if (i === insurerPaymentLinkIndex.value) {
      paymentMethodsForm.insurerPaymentLink = split.insurer_payment_link;
    }
    dueDateModels.value[i] = split.due_date
      ? moment(split.due_date).format('YYYY-MM-DD')
      : '';
    collectionAmountModels.value[i] = premiumToCapture.value
      ? premiumToCapture.value
      : split.collection_amount;

    if (['CHQ', 'PDC'].includes(split.payment_method.code)) {
      isCheckDetailsEnabled.value[i] = true;
      checkDetailModels.value[i] = split.check_detail;
    }

    if (split.documents.length > 0) {
      split.documents.forEach(doc => {
        if (doc.payment_split_type === 'discount') {
          if (!discountDocumentModel.value[0]) {
            discountDocumentModel.value[0] = [];
          }
          discountDocumentModel.value[0].push(doc);
        } else {
          if (!fileUploadModels.value[i]) {
            fileUploadModels.value[i] = [];
          }
          fileUploadModels.value[i].push(doc);
        }
      });
    }
  }

  if (
    paymentMethodsForm.status == 'view' &&
    paymentMethodsForm.collection_type === 'insurer'
  ) {
    approvedDocumentModel.value = fileUploadModels.value.slice();
  }
};

const finalizePaymentForm = (payment, capture_approval) => {
  const updateTotalValues = () => {
    totalPrice.value = payment.total_price;
    totalAmount.value = payment.total_price - payment.discount_value;
  };

  const handleEditStatus = () => {
    updateTotalValues();
    const isAnyChildPaymentPaid = isAnyPaid(payment);

    // Check if the payment is locked
    if (isPaymentLocked.value) {
      isFieldReadonly.value = true;
    } else if (
      isAnyChildPaymentPaid &&
      payment.total_price <= payment.total_amount + payment.discount_value
    ) {
      isFieldReadonly.value = true;
      isTotalPriceUpdated.value = is_lacking_payment.value;
    } else {
      isFieldReadonly.value = false;
    }

    // Check if the total price is greater than the total amount plus discount
    if (payment.total_price > payment.total_amount + payment.discount_value) {
      isTotalPriceUpdated.value = false;
    }

    // Check if the total amount is greater than the collected amount and frequency is upfront
    if (
      payment.total_amount > payment.collected_amount &&
      payment.frequency === paymentFrequencyEnum.UPFRONT
    ) {
      isTotalPriceUpdated.value = false;
      isFieldReadonly.value = false;
    }

    // Enable collected by field if any child payment is paid
    if (isAnyChildPaymentPaid) {
      isCollectedByEnabled.value = true;
    }
  };

  const handleViewStatus = () => {
    updateTotalValues();
  };

  const handleDiscount = () => {
    if (
      ['family_employee_discount', 'employee_discount'].includes(
        payment.discount_type,
      ) &&
      payment.discount_value > 0
    ) {
      discountValue.value = payment.discount_value;
      calculatedDiscount.value = payment.discount_value;
    }
  };

  const handleTravelQuoteType = () => {
    if (
      props.quoteType === quoteTypeCodeEnum.Travel &&
      ['edit', 'view'].includes(paymentMethodsForm.status)
    ) {
      planDetail.value =
        payment?.travel_plan ??
        props.sendUpdate?.travel_plan ??
        props.quoteRequest?.plan ??
        null;
      if (!(props.quoteRequest.insly_migrated || props.quoteRequest.insly_id)) {
        planDetail.value['insurance_provider'] =
          payment?.travel_plan?.insurance_provider ??
          props.sendUpdate?.insurance_provider;
      }
    }
  };

  const handleCaptureApproval = () => {
    if (capture_approval > 0) {
      isApproveClicked.value = true;
      if (capture_approval == 1) {
        isCreditCardView.value = true;
      }
      for (let i = 1; i <= payment.total_payments; i++) {
        readOnlyPayments.value[i] = true;
      }
      isFieldReadonly.value = true;
      isCreditApprovalView.value = true;
      isVerificationAllowed.value = true;
    }
  };

  if (paymentMethodsForm.status == 'edit') {
    handleEditStatus();
  } else if (paymentMethodsForm.status == 'view') {
    handleViewStatus();
  }

  handleDiscount();
  handleTravelQuoteType();
  handleCaptureApproval();

  createPaymentModal.value = true;
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

const validateViewPayment = isValid => {
  let amountExceeded = false;
  if (
    parseFloat(splitAmountModels.value[splitPaymentNo.value]) >
    parseFloat(paymentMethodsForm.collection_amount)
  ) {
    approveErrorMessage.value =
      'The entered amount is smaller than the total amount.';
    isApprovePaymentError.value = true;
    return true;
  }

  if (
    parseFloat(paymentMethodsForm.collection_amount) >
    parseFloat(splitAmountModels.value[splitPaymentNo.value])
  ) {
    approveErrorMessage.value =
      'Collected amount exceeds total amount, do you still want to continue?';
    isApprovePaymentError.value = true;
    amountExceeded = true;
    //return true;
  }

  // document validdation for insurer
  if (paymentMethodsForm.collection_type === 'insurer') {
    if (
      approvedDocumentModel.value[splitPaymentNo.value] === undefined ||
      approvedDocumentModel.value[splitPaymentNo.value].length === 0
    ) {
      isApprovedDocumentNotUploaded.value = true;
      return true;
    } else {
      isApprovedDocumentNotUploaded.value = false;
    }
  }
  if (isApproveConfirmed.value === false && isValid) {
    if (!amountExceeded) {
      isApprovePaymentError.value = false;
    }
    paymentMethodsForm.approvalModal = 'child';
    isApproveConfirmed.value = true;
    return true;
  }

  if (isApproveNotChecked.value === true) {
    return true;
  }
  return false;
};

const validateCapturePayment = isValid => {
  if (isApproveConfirm.value === false && isValid) {
    let noError = true;
    let regex = /^\d+(\.\d{1,2})?$/;
    isCreditPaymentInvalid.value = [];
    if (isCreditCardView.value === true) {
      for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
        //isCreditPaymentInvalid.value[i] = false;
        if (paymentMethodsModels.value[i] === 'CC') {
          if (
            collectionAmountModels.value[i] === null ||
            collectionAmountModels.value[i] === undefined ||
            collectionAmountModels.value[i] === 0
          ) {
            isCreditPaymentInvalid.value[i] = true;
            isCreditPaymentInvalidError.value[i] = 'This field is required';
          }

          if (!regex.test(collectionAmountModels.value[i])) {
            isCreditPaymentInvalid.value[i] = true;
            isCreditPaymentInvalidError.value[i] =
              'Amount must be a valid number';
          }

          if (
            parseFloat(collectionAmountModels.value[i]) >
            parseFloat(splitAmountModels.value[i])
          ) {
            isCreditPaymentInvalid.value[i] = true;
            isCreditPaymentInvalidError.value[i] =
              'Capture amount should not exceed total amount';
          }
        }
      }
    }
    if (isCreditPaymentInvalid.value.includes(true)) {
      return true;
    }
    paymentMethodsForm.approvalModal = 'master';
    isApproveConfirm.value = true;
    isApproveConfirmed.value = true;
    return true;
  }
  return false;
};

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

const validateInsurerPaymentLink = () => {
  const validationResult = rules.isValidUrl(
    paymentMethodsForm.insurerPaymentLink,
  );
  if (validationResult !== true) {
    paymentMethodsForm.errors.insurerPaymentLink = validationResult;
  } else {
    delete paymentMethodsForm.errors.insurerPaymentLink;
  }
};

const addPayment = isValid => {
  if (
    insurerPaymentLinkIndex.value >= 0 &&
    paymentMethodsForm.insurerPaymentLink
  ) {
    const urlValidation = rules.isValidUrl(
      paymentMethodsForm.insurerPaymentLink,
    );
    if (urlValidation !== true) {
      paymentMethodsForm.errors.insurerPaymentLink = urlValidation;
      return;
    }
  }

  if (
    !props.sendUpdate?.insurance_provider_id &&
    (providerId.value === null || providerId.value === undefined)
  ) {
    notification.error({
      title: 'Please select an insurance provider.',
      position: 'top',
    });
    return;
  }
  if (isCreditApprovalView.value === true && isDeclineClicked.value === false) {
    if (validateCapturePayment(isValid)) return;
  } else if (paymentMethodsForm.status === 'view' && isApproveClicked.value) {
    if (validateViewPayment(isValid)) return;
  } else if (paymentMethodsForm.status !== 'view') {
    if (validatePaymentOption()) return;
    if (isPaidEditable.value === true) {
      if (validatePaymentAmount()) return;
    }
  }
  if (!isValid) return;

  // making modal close after payment
  confirmModalClose.value = true;

  //define main payment method
  let mainPaymentMethod = paymentMethodsModels.value[0]
    ? paymentMethodsModels.value[0]
    : paymentMethodsModels.value[1];
  if (
    paymentMethodsForm.credit_approval !== '' &&
    paymentMethodsForm.credit_approval !== null
  ) {
    mainPaymentMethod = 'CA';
  } else if (
    paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM ||
    paymentMethodsForm.frequency === paymentFrequencyEnum.MONTHLY ||
    paymentMethodsForm.frequency === paymentFrequencyEnum.QUARTERLY ||
    paymentMethodsForm.frequency === paymentFrequencyEnum.SEMI_ANNUAL
  ) {
    mainPaymentMethod = 'PP';
  } else if (paymentMethodsForm.frequency === 'split_payments') {
    mainPaymentMethod = 'MP';
  }

  if (mainPaymentMethod === '' || mainPaymentMethod === null) {
    mainPaymentMethod = 'CSH';
  }

  // Send FTC email if the payment is edited and the insurer payment link is changed or if the payment is created from renewal upload
  var sendFtcEmail = false;
  if(page.props.quote.source == 'Renewal_upload' && paymentMethodsForm.status == 'edit') {
    sendFtcEmail = true;
  } else {
    sendFtcEmail = insurerPaymentLinkChanged?.value ?? false;
  }

  let data = {
    code: paymentMethodsForm.payment_method,
    modelType: props.quoteType,
    quote_id: props.quoteRequest.id,
    plan_id: planDetail?.value?.id ?? null, // handling null exception when plan is not found
    captured_amount: paymentMethodsForm.amount,
    insurance_provider_id: providerId.value,
    sendFTCEmail: sendFtcEmail,
    new_payment_structure: true,
    isInertia: true,
    send_update_id: props.sendUpdate?.id || null,
  };

  data.payment = {
    collection_type: paymentMethodsForm.collection_type,
    payment_methods: mainPaymentMethod,
    reference: paymentMethodsForm.payment_reference,
    payment_no: paymentMethodsForm.payment_no,
    frequency: paymentMethodsForm.frequency,
    credit_approval: paymentMethodsForm.credit_approval,
    discount: paymentMethodsForm.discount,
    discount_reason: paymentMethodsForm.discount_reason,
    custom_reason: paymentMethodsForm.custom_reason,
    discount_custom_reason: paymentMethodsForm.discount_custom_reason,
    collection_date: paymentMethodsForm.collection_date,
    notes: paymentMethodsForm.notes,
    total_amount: totalAmount.value, // after discount calculation
    total_price: totalPrice.value,
    discount_value: discountValue.value, // discount amount
  };
  let splitPayments = [];
  for (let i = 1; i < splitAmountModels.value.length; i++) {
    if (i <= paymentMethodsForm.payment_no) {
      splitPayments[i] = {
        sr_no: i,
        payment_method: paymentMethodsModels.value[i],
        payment_amount: splitAmountModels.value[i],
        due_date: dueDateModels.value[i],
        collection_amount: collectionAmountModels.value[i],
        document_detail: fileUploadModels.value[i],
        check_detail: checkDetailModels.value[i],
      };
      if (i === 1) {
        splitPayments[i]['discount_documents'] = discountDocumentModel.value;
      }
      if (i === insurerPaymentLinkIndex.value) {
        // Todo: For time being only saving insurer_payment_link for only first split payment
        splitPayments[i]['insurer_payment_link'] =
          paymentMethodsForm.insurerPaymentLink;
      }
    }
  }
  splitPayments = splitPayments.filter(item => item !== null);
  data.payment.payment_splits = splitPayments;

  let declinedCustomReason = paymentMethodsForm.declined_custom_reason;

  if (isCreditApprovalView.value === true && !isApproveNotChecked.value) {
    let viewData = {
      modelType: props.quoteType,
      quote_id: props.quoteRequest.id,
      plan_id: planDetail?.value?.id || 0,
      customer_id: props.quoteRequest.customer_id,
      payment_code: paymentMethodsForm.paymentCode,
      collection_amount: collectionAmountModels.value,
      is_declined: isDeclineClicked.value,
      is_capture: isCreditCardView.value,
      is_approved: isApproveClicked.value,
      declined_reason: paymentMethodsForm.declined_reason,
      declined_custom_reason: declinedCustomReason,
      send_update_id: props.sendUpdate?.id || null,
      collection_type: paymentMethodsForm.collection_type,
    };
    paymentMethodsForm
      .transform(data => viewData)
      .post(
        '/payments/' + props.quoteType + '/master-payment-approve-capture',
        {
          preserveScroll: true,
          onSuccess: res => {
            createPaymentModal.value = false;
            setTimeout(() => {
              location.reload();
            }, 500);
          },
          onError: errors => {
            Object.keys(errors).forEach(function (key) {
              notification.error({
                title: errors[key],
                position: 'top',
              });
            });
          },
        },
      );
    return;
  }

  if (paymentMethodsForm.status === 'view' && !isApproveNotChecked.value) {
    let viewData = {
      modelType: props.quoteType,
      quote_id: props.quoteRequest.id,
      plan_id: planDetail?.value?.id || 0,
      customer_id: props.quoteRequest.customer_id,
      collection_amount: paymentMethodsForm.collection_amount,
      bank_reference_number: paymentMethodsForm.bank_reference_number,
      splitPaymentId: paymentMethodsForm.splitPaymentId,
      is_declined: isDeclineClicked.value,
      is_approved: isApproveClicked.value,
      declined_reason: paymentMethodsForm.declined_reason,
      approved_document_model: approvedDocumentModel.value,
      declined_custom_reason: declinedCustomReason,
      send_update_id: props.sendUpdate?.id || null,
      collection_type: paymentMethodsForm.collection_type,
    };
    paymentMethodsForm
      .transform(data => viewData)
      .post('/payments/' + props.quoteType + '/split-payment-approve-decline', {
        preserveScroll: true,
        onSuccess: () => {
          createPaymentModal.value = false;
          isApproveConfirmed.value = false;
          if (props.sendUpdate) {
            location.reload();
          }
        },
        onError: res => {
          isApproveConfirmed.value = false;
          notification.error({
            title: res.error,
            position: 'top',
          });
        },
      });
    return;
  }
  if (props.isPlanDetailSectionEnabled) {
    data.plan_id = null;
  }
  if (paymentMethodsForm.status === 'edit') {
    if (
      totalPaidAmount.value == paymentMethodsForm.payment_no &&
      isPolicyIssuanceDiscount.value === false &&
      isPaidEditable.value === false
    ) {
      notification.error({
        title: 'No further actions allowed to paid payments',
        position: 'top',
      });
      return false;
    }
    let editData = {
      ...data,
      paymentCode: paymentMethodsForm.paymentCode,
      trashedFilesModal: trashedFilesModal.value,
      isPaymentLocked: isPaymentLocked.value,
      isPolicyIssuanceDiscount: isPolicyIssuanceDiscount.value,
      isPaidEditable: isPaidEditable.value,
    };
    paymentMethodsForm
      .transform(data => editData)
      .post('/payments/' + props.quoteType + '/update-new', {
        preserveScroll: true,
        onSuccess: () => {
          createPaymentModal.value = false;
        },
        onError: errors => {
          Object.keys(errors).forEach(function (key) {
            if (key === 'insurer_payment_link') {
              paymentMethodsForm.errors.insurerPaymentLink = errors[key];
            }
            notification.error({
              title: errors[key],
              position: 'top',
            });
          });

          notification.error({
            title: 'Payment Update Failed',
            position: 'top',
          });
        },
      });
    return;
  }
  let storeData = {
    ...data,
  };

  paymentMethodsForm
    .transform(data => storeData)
    .post('/payments/' + props.quoteType + '/store-new', {
      preserveScroll: true,
      onSuccess: () => {
        createPaymentModal.value = false;
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          if (key === 'insurer_payment_link') {
            paymentMethodsForm.errors.insurerPaymentLink = errors[key];
          }
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
        notification.error({
          title: 'Payment Add Failed',
          position: 'top',
        });
      },
    });
};

const setFrequencyTypes = () => {
  let allFrequencyTypes = paymentLookups.paymentFrequencyTypes.map(item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  }));
  frequencyTypes.value = allFrequencyTypes;
};

const applyPermissions = () => {
  const setDiscountAndCreditApprovalPermissions = () => {
    isDiscountAllowed.value = can(permissionEnum.PAYMENTS_DISCOUNT_ADD);
    isCreditApprovalAllowed.value = can(
      permissionEnum.PAYMENTS_CREDIT_APPROVAL_ADD,
    );
  };

  const setBrokerPermissions = () => {
    const hasPermissionToBroker = can(
      permissionEnum.PAYMENTS_FREQUENCY_UPRONT_SPLIT_COLLECTED_BY_BROKER_ADD,
    );
    const hasPermissionToTermFrequencies = can(
      permissionEnum.PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_BROKER_ADD,
    );

    if (!hasPermissionToBroker) {
      frequencyTypes.value = frequencyTypes.value.filter(
        item =>
          item.value !== paymentFrequencyEnum.UPFRONT &&
          item.value !== paymentFrequencyEnum.SPLIT_PAYMENTS,
      );
    } else if (
      paymentMethodsForm.status === 'create' &&
      paymentMethodsForm.frequency === ''
    ) {
      paymentMethodsForm.frequency = paymentFrequencyEnum.UPFRONT;
    }

    if (!hasPermissionToTermFrequencies) {
      frequencyTypes.value = frequencyTypes.value.filter(
        item =>
          ![
            paymentFrequencyEnum.CUSTOM,
            paymentFrequencyEnum.MONTHLY,
            paymentFrequencyEnum.QUARTERLY,
            paymentFrequencyEnum.SEMI_ANNUAL,
          ].includes(item.value),
      );
    }

    isVerificationAllowed.value =
      paymentMethodsForm.status === 'view' &&
      can(permissionEnum.PAYMENT_VERIFICATION_COLLECTED_BY_BROKER);
  };

  const setInsurerPermissions = () => {
    if (
      !can(permissionEnum.PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_INSURER_ADD)
    ) {
      frequencyTypes.value = frequencyTypes.value.filter(
        item =>
          ![
            paymentFrequencyEnum.CUSTOM,
            paymentFrequencyEnum.MONTHLY,
            paymentFrequencyEnum.QUARTERLY,
            paymentFrequencyEnum.SEMI_ANNUAL,
          ].includes(item.value),
      );
    }

    if (
      paymentMethodsForm.status === 'create' &&
      paymentMethodsForm.frequency === ''
    ) {
      paymentMethodsForm.frequency = paymentMethodsForm.frequency =
        paymentFrequencyEnum.UPFRONT;
    }

    isVerificationAllowed.value =
      paymentMethodsForm.status === 'view' &&
      can(permissionEnum.PAYMENT_VERIFICATION_COLLECTED_BY_INSURER);
  };

  const setInplApproverPermission = () => {
    if (
      paymentMethodsForm.status === 'view' &&
      can(permissionEnum.INPL_APPROVER) &&
      splitPaymentRecord.value.payment_method.code ===
        page.props.paymentMethodsEnum?.InsureNowPayLater
    ) {
      isVerificationAllowed.value = true;
    }
  };

  setFrequencyTypes();
  setDiscountAndCreditApprovalPermissions();

  if (paymentMethodsForm.collection_type === 'broker') {
    setBrokerPermissions();
  } else if (paymentMethodsForm.collection_type === 'insurer') {
    setInsurerPermissions();
  }

  setInplApproverPermission();
};

const documentForm = useForm({
  quote_id: props.quoteRequest.id || null,
  quote_uuid: props.quoteRequest.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const deleteForm = useForm({
  payment_process_job_id: null,
});

const deleteDocument = (docName, count, doc_id, doc_uuid) => {
  if (paymentMethodsForm.status == 'edit') {
    if (fileUploadModels.value[count]) {
      fileUploadModels.value[count] = fileUploadModels.value[count].filter(
        item => item.doc_name !== docName,
      );
    }
    if (approvedDocumentModel.value[count]) {
      approvedDocumentModel.value[count] = approvedDocumentModel.value[
        count
      ].filter(item => item.doc_name !== docName);
    }
    if (discountDocumentModel.value[count]) {
      discountDocumentModel.value[0] = discountDocumentModel.value[0].filter(
        item => item.doc_name !== docName,
      );
    }
    trashedFilesModal.value.push(doc_id);
  } else if (
    paymentMethodsForm.status == 'view' &&
    paymentMethodsForm.collection_type === 'insurer' &&
    approvedDocumentModel.value[count]
  ) {
    approvedDocumentModel.value[count] = approvedDocumentModel.value[
      count
    ].filter(item => item.doc_name !== docName);
  } else if (
    paymentMethodsForm.status == 'view' &&
    paymentMethodsForm.collection_type === 'insurer' &&
    approvedDocumentModel.value[count]
  ) {
    approvedDocumentModel.value[count] = approvedDocumentModel.value[
      count
    ].filter(item => item.doc_name !== docName);
  } else {
    router.post(
      `/documents/delete`,
      {
        doc_id,
        doc_uuid,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          if (fileUploadModels.value[count]) {
            fileUploadModels.value[count] = fileUploadModels.value[
              count
            ].filter(item => item.doc_name !== docName);
          }
          if (approvedDocumentModel.value[count]) {
            approvedDocumentModel.value[count] = approvedDocumentModel.value[
              count
            ].filter(item => item.doc_name !== docName);
          }
          if (discountDocumentModel.value[count]) {
            discountDocumentModel.value[0] =
              discountDocumentModel.value[0].filter(
                item => item.doc_name !== docName,
              );
          }
        },
      },
    );
  }
};

const uploadDocument = (doc, files, count) => {
  files = files.files;
  // Error if invalid files are selected
  if (files.length == 0) {
    notification.error({
      title: 'Document upload failed, invalid file selected',
      position: 'top',
    });
    return;
  }

  let url = '/quotes/' + props.quoteType + '/documents/store-multiple';
  let splitPaymentDocType = null;
  if (count === 0) {
    // documents for master discount
    splitPaymentDocType = 'discount';
  }

  if (!discountDocumentModel.value[0]) {
    discountDocumentModel.value[0] = [];
  }

  if (!fileUploadModels.value[count]) {
    fileUploadModels.value[count] = [];
  }

  if (!approvedDocumentModel.value[count]) {
    approvedDocumentModel.value[count] = [];
  }

  //let allUploadedDocuments = fileUploadModels.value.flat();
  let allUploadedDocuments = [
    ...fileUploadModels.value.flat(),
    ...discountDocumentModel.value.flat(),
  ];

  let duplicateFileNames = files.map(file => file.file.name);
  isFileError.value = false;

  if (
    allUploadedDocuments &&
    allUploadedDocuments.some(uploadedFile =>
      duplicateFileNames.includes(uploadedFile.original_name),
    )
  ) {
    isFileError.value = true;
    fileErrorMessage.value = paymentTooltipEnum.PAYMENT_ADD_DUPLICATE_FILES;
    return false;
  }

  isUploading.value = true;

  return new Promise((resolve, reject) => {
    documentForm
      .transform(data => ({
        ...data,
        quote_type_id: doc.quote_type_id,
        document_type_code: doc.code,
        folder_path: doc.folder_path,
        split_payment_doc_type: splitPaymentDocType,
        file: files,
        send_update_id: props.sendUpdate?.id || null,
      }))
      .post(url, {
        preserveScroll: true,
        preserveState: true,
        onError: errors => {
          documentForm.setError(errors.error);
          notification.error({
            title: 'File upload failed',
            position: 'top',
          });
          reject(errors);
        },
        onSuccess: data => {
          let quoteTypes = quoteTypesToCheck.filter(
            quoteType => quoteType !== quoteTypeCodeEnum.Home,
          );
          let quoteDocuments =
            quoteTypes.includes(props.quoteType) ||
            props.quoteSubType === quoteTypeCodeEnum.CORPLINE ||
            props.sendUpdate
              ? data.props.quoteDocuments
              : data.props.quote.documents;
          quoteDocuments.sort((a, b) => b.id - a.id);
          if (count === 0) {
            isDiscountDocumentNotUploaded.value = false;
            for (let i = 0; i < files.length; i++) {
              discountDocumentModel.value[count].push(quoteDocuments[i]);
            }
            resolve(data);
            return;
          }

          if (paymentMethodsForm.status === 'view') {
            isApprovedDocumentNotUploaded.value = false;
            for (let i = 0; i < files.length; i++) {
              approvedDocumentModel.value[count].push(quoteDocuments[i]);
            }
            resolve(data);
            return;
          }

          for (let i = 0; i < files.length; i++) {
            fileUploadModels.value[count].push(quoteDocuments[i]);
          }
          isDocumentNotUploaded.value[count] = false;

          resolve(data);
        },
        onFinish: () => {
          isUploading.value = false;
        },
      });
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

const getCaptureOption = computed(() => {
  return payment => {
    // Return early if there are no payments
    if (props.payments.length === 0) return;

    let isCaptureButtonEnabled =
      page.props?.bookPolicyDetails?.isCaptureButtonEnabled || false;
    if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
      isCaptureButtonEnabled = payment.isCaptureButtonEnabled || false;
    }

    const paymentMethodCC = filterCCPayments(payment);

    // Check if the conditions for 'capture' are met
    const isCreditCardPayment = paymentMethodCC.length > 0;
    const isNotInsurerPayment = payment.collection_type !== 'insurer';

    // Return 'capture' if all conditions are met, otherwise return 'approve'
    if (
      (isCreditCardPayment && isNotInsurerPayment && !isCaptureButtonEnabled) ||
      (isCaptureButtonEnabled && hasAnyCCSplitPayment())
    ) {
      return 'capture';
    }
    return 'approve';
  };
});

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

  if (props.quoteType === quoteTypeCodeEnum.SAVINGS) {
    return props.quoteRequest?.insurance_provider_plan?.text || 'Not Available';
  }

  return quoteTypesToCheck.includes(props.quoteType) && plan
    ? plan.text
    : 'Not Available';
});

const providerId = computed(() => {
  const plan = planDetail.value;
  if (props.sendUpdate) {
    return (
      props.sendUpdate?.insurance_provider_id ||
      props.quoteRequest?.insurance_provider_details?.id ||
      props.quoteRequest?.plan?.provider_id ||
      props.quoteRequest?.insurance_provider?.id
    );
  } else if (plan && plan.insurance_provider) {
    return plan.insurance_provider.id;
  } else if (plan && plan.provider_id) {
    return plan.provider_id;
  } else if (plan && plan.id) {
    return plan.id;
  }
  return null;
});

const providerName = computed(() => {
  const plan = planDetail.value;
  if (props.quoteType == quoteTypeCodeEnum.Home) {
    return props.quoteRequest.insurance_provider?.text || 'Not Available';
  }
  if (props.quoteType == quoteTypeCodeEnum.SAVINGS) {
    return props.quoteRequest.insurance_provider?.text || 'Not Available';
  }
  const ecomQuoteType = [...quoteTypesToCheck, quoteTypeCodeEnum.Bike];
  if (props.sendUpdate) {
    let provider = props?.insuranceProviders?.find(
      provider => provider.id === providerId.value,
    );

    return provider.text || 'Not Available';
  } else if (
    ecomQuoteType.includes(props.quoteType) &&
    plan.insurance_provider
  ) {
    return plan ? plan.insurance_provider.text : 'Not Available';
  } else {
    return plan ? plan.text : 'Not Available';
  }
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
  } else if (props.quoteType == quoteTypeCodeEnum.SAVINGS) {
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

// verify if master payment is paid
const isMasterPaymentPaid = computed(() => {
  if (
    props.payments.length > 0 &&
    props.payments[0].payment_status_id === page.props.paymentStatusEnum.PAID
  ) {
    return true;
  }
  return false;
});

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

const lookupsEnum = page.props.lookupsEnum;

const masterPaymentStatusFormat = computed(() => {
  return formatString(masterPaymentStatus.value);
});

const totalPriceFormat = computed(() => {
  return formatAmount(totalPrice.value);
});

const totalAmountFormat = computed(() => {
  return formatAmount(totalAmount.value);
});

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

const hasAnyCCPayment = () => {
  const paymentMM = Object.values(paymentMethodsModels.value);
  return paymentMM.some(item => item == 'CC');
};

const hasAnyCCSplitPayment = () => {
  if (props.payments.length > 0) {
    const paymentSplits = props.payments[0].payment_splits;
    return paymentSplits.some(item => item.payment_method.code === 'CC');
  }
  return false;
};

const isCCPaymentDisabled = option => {
  let isCreditCardEnabled = isCCEnabled.value;
  if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
    isCreditCardEnabled = paymentMethodsForm.isCreditCardEnabled;
  }
  return (
    !isCreditCardEnabled &&
    paymentMethodsForm.collection_type === 'insurer' &&
    option == 'CC'
  );
};

const isPolicySendUpdateBooked = option => {
  const isInsurerCollection = paymentMethodsForm.collection_type === 'insurer';
  const isCCOption = option === 'CC';
  const isPolicyBooked =
    props.quoteRequest.quote_status_id ===
    page.props.quoteStatusEnum.PolicyBooked;
  const isUpdateBooked =
    props.sendUpdate &&
    props.sendUpdate.status === props.sendUpdateStatusEnum?.UPDATE_BOOKED;
  const isCCAndInsurer = isCCEnabled.value && isInsurerCollection && isCCOption;

  if (isUpdateBooked && isCCAndInsurer) {
    return true;
  }
  return isCCAndInsurer && isPolicyBooked && !props.sendUpdate;
};

const filterPaymentTypes = (paymentTypes, methodsToExclude) => {
  return paymentTypes.filter(item => !methodsToExclude.includes(item.value));
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

const closeVoidPaymentModal = () => {
  voidPaymentModelPopup.value = false;
};
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
                        (splitId, statusId, code) =>
                          deleteSplitPaymentModal(splitId, statusId, code)
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
          <x-form @submit="addPayment" :auto-focus="false">
            <div class="w-full grid md:grid-cols-2 gap-3">
              <div>
                <ToolTip
                  title="COLLECTION DATE"
                  :tooltip="paymentTooltipEnum.COLLECTION_DATE"
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ formatDate(paymentMethodsForm.collection_date) }}
                  </span>
                  <DatePicker
                    v-if="!isFieldReadonly"
                    name="collection_date"
                    v-model="paymentMethodsForm.collection_date"
                    :rules="[rules.isRequired]"
                  />
                </x-field>
              </div>
              <div>
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >TOTAL PRICE</span
                  >
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.TOTAL_PRICE }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ formatAmount(totalPrice) }}
                  </span>
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    v-model="totalPriceFormat"
                    :disabled="true"
                  />
                </x-field>
              </div>
              <div>
                <ToolTip
                  title="COLLECTED BY"
                  :tooltip="paymentTooltipEnum.COLLECTED_BY"
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{
                      collectionTypes.find(
                        item =>
                          item.value === paymentMethodsForm.collection_type,
                      ).label
                    }}
                  </span>
                  <select
                    v-if="!isFieldReadonly"
                    class="custom-select"
                    v-model="paymentMethodsForm.collection_type"
                    :rules="[rules.isRequired]"
                    @change="handleCollectionTypeChange"
                    :disabled="isCollectedByEnabled"
                  >
                    <template
                      v-for="option in collectionTypes"
                      :key="option.value"
                    >
                      <option :value="option.value" :title="option.tooltip">
                        {{ option.label }}
                      </option>
                    </template>
                  </select>
                </x-field>
              </div>
              <div>
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >PROVIDER NAME</span
                  >
                  <template #tooltip>
                    <span v-if="isFieldReadonly">{{
                      paymentTooltipEnum.PROVIDER_NAME_VIEW
                    }}</span>
                    <span v-else>{{ paymentTooltipEnum.PROVIDER_NAME }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ providerName }}
                  </span>
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    v-model="providerName"
                    :disabled="true"
                  />
                </x-field>
              </div>

              <div>
                <ToolTip
                  title="FREQUENCY"
                  :tooltip="paymentTooltipEnum.FREQUENCY"
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{
                      frequencyTypes.find(
                        item => item.value === paymentMethodsForm.frequency,
                      ).label
                    }}
                  </span>
                  <select
                    v-if="!isFieldReadonly"
                    :class="{
                      'custom-select-error': isPaymentFrequencyNotSelected,
                    }"
                    class="custom-select"
                    v-model="paymentMethodsForm.frequency"
                    :rules="[rules.isRequired]"
                    @change="handleFrequencyChange"
                  >
                    <template
                      v-for="option in frequencyTypes"
                      :key="option.value"
                    >
                      <option :value="option.value" :title="option.tooltip">
                        {{ option.label }}
                      </option>
                    </template>
                  </select>
                  <p
                    v-if="isPaymentFrequencyNotSelected"
                    class="text-sm text-red-500 dark:text-red-400 mt-1"
                  >
                    This field is required
                  </p>
                </x-field>
              </div>

              <div>
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >PLAN NAME</span
                  >
                  <template #tooltip>
                    <span v-if="isFieldReadonly">{{
                      paymentTooltipEnum.PLAN_NAME_VIEW
                    }}</span>
                    <span v-else>{{ paymentTooltipEnum.PLAN_NAME }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ getPlanName }}
                  </span>
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    v-model="getPlanName"
                    :disabled="true"
                  />
                </x-field>
              </div>

              <div>
                <ToolTip
                  title="PAYMENT NO"
                  :tooltip="paymentTooltipEnum.PAYMENT_NO"
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ paymentMethodsForm.payment_no }}
                  </span>
                  <select
                    v-if="!isFieldReadonly"
                    class="custom-select"
                    v-model="paymentMethodsForm.payment_no"
                    :rules="[rules.isRequired]"
                    :disabled="!isPaymentNoEnabled"
                    @change="calculatePaymentBreakup()"
                  >
                    <template
                      v-for="option in totalPayments"
                      :key="option.value"
                    >
                      <option :value="option.value" :title="option.tooltip">
                        {{ option.label }}
                      </option>
                    </template>
                  </select>
                </x-field>
              </div>
              <div>
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >PAYMENT STATUS</span
                  >
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_STATUS }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ formatString(masterPaymentStatus) }}
                  </span>
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    v-model="masterPaymentStatusFormat"
                    :disabled="true"
                  />
                </x-field>
              </div>

              <div
                v-if="
                  isCreditApprovalAllowed &&
                  (!isFieldReadonly ||
                    (isPaymentLocked && paymentMethodsForm.status == 'edit'))
                "
              >
                <ToolTip
                  title="CREDIT APPROVAL"
                  :tooltip="paymentTooltipEnum.CREDIT_APPROVAL"
                />
                <x-field class="w-full">
                  <div class="custom-dropdown">
                    <span
                      v-if="paymentMethodsForm.credit_approval != ''"
                      class="close-icon"
                      @mousedown.stop="resetCreditApproval()"
                    >
                      &#10006;
                    </span>
                    <select
                      class="custom-select"
                      v-model="paymentMethodsForm.credit_approval"
                      @change="handleApprovalReasonChange"
                      :disabled="isTotalPriceUpdated"
                    >
                      <template
                        v-for="option in creditApprovalReasons"
                        :key="option.value"
                      >
                        <option :value="option.value" :title="option.tooltip">
                          {{ option.label }}
                        </option>
                      </template>
                    </select>
                  </div>
                </x-field>
              </div>
              <div
                v-if="
                  isFieldReadonly &&
                  !(isPaymentLocked && paymentMethodsForm.status == 'edit')
                "
              >
                <ToolTip
                  title="CREDIT APPROVAL"
                  :tooltip="paymentTooltipEnum.CREDIT_APPROVAL"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{
                      creditApprovalReasons.find(
                        item =>
                          item.value === paymentMethodsForm.credit_approval,
                      )?.label || 'N/A'
                    }}
                  </span>
                </x-field>
              </div>
              <x-field
                v-if="
                  isCustomReasonEnabled &&
                  isCreditApprovalAllowed &&
                  (!isFieldReadonly ||
                    (isPaymentLocked && paymentMethodsForm.status == 'edit'))
                "
                label="CUSTOM REASON"
                required
                class="w-full"
              >
                <x-input
                  class="w-full"
                  v-model="paymentMethodsForm.custom_reason"
                  :rules="[rules.isRequired]"
                />
              </x-field>
              <x-field
                v-if="
                  isCustomReasonEnabled &&
                  isFieldReadonly &&
                  !(isPaymentLocked && paymentMethodsForm.status == 'edit')
                "
                label="CUSTOM REASON"
                class="w-full"
              >
                <span v-if="isFieldReadonly">{{
                  paymentMethodsForm.custom_reason
                }}</span>
              </x-field>
              <div
                v-if="
                  showDiscountOptions && isDiscountAllowed && !isFieldReadonly
                "
              >
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >DISCOUNT APPLICABLE (DISCOUNT TYPE)</span
                  >
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.DISCOUNT_APPLICABLE }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <div v-if="!isFieldReadonly" class="custom-dropdown">
                    <span
                      v-if="paymentMethodsForm.discount != ''"
                      class="close-icon"
                      @mousedown.stop="resetDiscount()"
                    >
                      &#10006;
                    </span>
                    <select
                      class="custom-select"
                      v-model="paymentMethodsForm.discount"
                      @change="handleDiscountChange"
                    >
                      <template
                        v-for="option in discountTypes"
                        :key="option.value"
                      >
                        <option
                          :value="option.value"
                          :title="option.tooltip"
                          v-if="
                            option.value !==
                            lookupsEnum.SYSTEM_ADJUSTED_DISCOUNT
                          "
                        >
                          {{ option.label }}
                        </option>
                      </template>
                    </select>
                  </div>
                </x-field>
              </div>
              <div v-if="showDiscountOptions && isFieldReadonly">
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >DISCOUNT APPLICABLE (DISCOUNT TYPE)</span
                  >
                  <template #tooltip>
                    <span v-if="isFieldReadonly">{{
                      paymentTooltipEnum.DISCOUNT_APPLICABLE_VIEW
                    }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ discountTypeLabel }}
                  </span>
                </x-field>
              </div>

              <div
                v-if="
                  isDiscountReasonEnabled &&
                  isDiscountAllowed &&
                  !isFieldReadonly
                "
                class=""
              >
                <ToolTip
                  title="DISCOUNT REASON"
                  :tooltip="
                    isFieldReadonly
                      ? discountReasons.find(
                          item =>
                            item.value === paymentMethodsForm.discount_reason,
                        ).tooltip
                      : paymentTooltipEnum.DISCOUNT_REASON
                  "
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <select
                    v-if="!isFieldReadonly"
                    :class="{ 'custom-select-error': isDiscountReasonError }"
                    class="custom-select"
                    v-model="paymentMethodsForm.discount_reason"
                    :rules="[rules.isRequired]"
                    @change="handleDiscountReasonChange"
                  >
                    <template
                      v-for="option in discountReasons"
                      :key="option.value"
                    >
                      <option :value="option.value" :title="option.tooltip">
                        {{ option.label }}
                      </option>
                    </template>
                  </select>
                  <p
                    v-if="isDiscountReasonError"
                    class="text-sm text-red-500 dark:text-red-400 mt-1"
                  >
                    This field is required
                  </p>
                </x-field>
              </div>
              <div v-if="isDiscountReasonEnabled && isFieldReadonly" class="">
                <ToolTip
                  title="DISCOUNT REASON"
                  :tooltip="
                    isFieldReadonly
                      ? discountReasons.find(
                          item =>
                            item.value === paymentMethodsForm.discount_reason,
                        ).tooltip
                      : paymentTooltipEnum.DISCOUNT_REASON
                  "
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{
                      discountReasons.find(
                        item =>
                          item.value === paymentMethodsForm.discount_reason,
                      ).label
                    }}
                  </span>
                </x-field>
              </div>

              <x-field
                v-if="
                  isCustomDiscountReasonEnabled &&
                  isDiscountAllowed &&
                  !isFieldReadonly
                "
                label="CUSTOM DISCOUNT REASON"
                :required="!isFieldReadonly"
                class="w-full"
              >
                <span v-if="isFieldReadonly">{{
                  paymentMethodsForm.discount_custom_reason
                }}</span>
                <x-input
                  v-if="!isFieldReadonly"
                  class="w-full"
                  v-model="paymentMethodsForm.discount_custom_reason"
                  :rules="[rules.isRequired]"
                />
              </x-field>
              <x-field
                v-if="isCustomDiscountReasonEnabled && isFieldReadonly"
                label="CUSTOM DISCOUNT REASON"
                :required="!isFieldReadonly"
                class="w-full"
              >
                <span v-if="isFieldReadonly">{{
                  paymentMethodsForm.discount_custom_reason
                }}</span>
              </x-field>

              <div
                v-if="
                  isDiscountEnabled &&
                  paymentMethodsForm.discount != 'N/A' &&
                  isDiscountAllowed &&
                  !isFieldReadonly
                "
                class=""
              >
                <ToolTip
                  title="DISCOUNT PROOF"
                  :tooltip="paymentTooltipEnum.PAYMENT_DISCOUNT_PROOF_TITLE"
                  :required="!isFieldReadonly"
                />
                <x-field v-if="!isFieldReadonly" class="w-full">
                  <div class="relative group text-center">
                    <span>
                      <Dropzone
                        :id="discountProofDocument.id"
                        :customDisplay="true"
                        :multiple="true"
                        :accept="discountProofDocument.accepted_files"
                        :max-files="discountProofDocument.max_files"
                        :max-size="discountProofDocument.max_size"
                        :loading="documentForm.processing"
                        @change="
                          uploadDocument(discountProofDocument, $event, 0)
                        "
                      />
                    </span>
                    <div
                      class="absolute text-left hidden group-hover:block transform transition-transform z-40 h-fit _popoverContent_1wc81_3 top-full bottom-0 _popoverBottom_1wc81_14 left-1/4 right-full -translate-x-1/2 max-w-xs"
                    >
                      <div class="dark">
                        <div
                          class="x-popover-container block w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-200 dark:border-gray-800 p-2 text-white text-sm w-max max-w-xs"
                        >
                          <span
                            data-v-d0063695=""
                            class="custom-tooltip-content"
                          >
                            {{ paymentTooltipEnum.DOCUMENTS_UPLOAD }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p
                    v-if="isDiscountDocumentNotUploaded"
                    class="text-sm text-red-500 dark:text-red-400 mt-1"
                  >
                    This field is required
                  </p>
                </x-field>
                <div
                  v-for="fileData in discountDocumentModel[0]"
                  :key="fileData.id"
                >
                  <span style="display: flex; align-items: center">
                    <span
                      :key="fileData.id"
                      class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                      style="flex: 1; text-decoration: none; cursor: pointer"
                      @click="openInnerModal(fileData.id)"
                    >
                      {{ fileData.original_name }}
                    </span>
                    <span
                      class="delete-pointer"
                      @click="
                        deleteDocument(
                          fileData.doc_name,
                          0,
                          fileData.id,
                          fileData.doc_uuid,
                        )
                      "
                      v-if="!isFieldReadonly"
                    >
                      &#10006;
                    </span>
                  </span>
                </div>
              </div>

              <div
                v-if="
                  isDiscountEnabled &&
                  paymentMethodsForm.discount != 'N/A' &&
                  isFieldReadonly
                "
                class=""
              >
                <ToolTip
                  title="DISCOUNT PROOF"
                  :tooltip="paymentTooltipEnum.PAYMENT_DISCOUNT_PROOF_VIEW"
                  :required="!isFieldReadonly"
                />
                <div
                  v-for="fileData in discountDocumentModel[0]"
                  :key="fileData.id"
                >
                  <span style="display: flex; align-items: center">
                    <span
                      :key="fileData.id"
                      class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                      style="flex: 1; text-decoration: none; cursor: pointer"
                      @click="openInnerModal(fileData.id)"
                    >
                      {{ fileData.original_name }}
                    </span>
                  </span>
                </div>
              </div>

              <div
                v-if="
                  isDiscountEnabled &&
                  paymentMethodsForm.discount != 'N/A' &&
                  isDiscountAllowed &&
                  !isFieldReadonly
                "
              >
                <ToolTip
                  title="DISCOUNT VALUE"
                  :tooltip="paymentTooltipEnum.DISCOUNT_VALUE"
                  class="w-3/6"
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    :class="{ 'custom-select-error': isDiscountError }"
                    v-model="discountValue"
                    name="discount_value"
                    @keyup="calculateTotalAmount()"
                  />
                  <sup
                    v-if="isDiscountError"
                    class="text-sm text-red-500 dark:text-red-400"
                    >{{ discountError }}
                  </sup>
                </x-field>
              </div>
              <div
                v-if="
                  isDiscountEnabled &&
                  paymentMethodsForm.discount != 'N/A' &&
                  isFieldReadonly
                "
              >
                <ToolTip
                  title="DISCOUNT VALUE"
                  :tooltip="paymentTooltipEnum.DISCOUNT_VALUE"
                  class="w-3/6"
                  :required="!isFieldReadonly"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ formatAmount(discountValue) }}
                  </span>
                </x-field>
              </div>

              <div
                v-if="
                  isDiscountEnabled &&
                  paymentMethodsForm.discount != 'N/A' &&
                  isDiscountAllowed &&
                  !isFieldReadonly
                "
              >
                <ToolTip
                  title="TOTAL AMOUNT"
                  :tooltip="paymentTooltipEnum.TOTAL_AMOUNT_VIEW"
                />
                <x-field class="w-full">
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    v-model="totalAmountFormat"
                    :disabled="true"
                  />
                </x-field>
              </div>
              <div
                v-if="
                  isDiscountEnabled &&
                  paymentMethodsForm.discount != 'N/A' &&
                  isFieldReadonly
                "
              >
                <ToolTip
                  title="TOTAL AMOUNT"
                  :tooltip="paymentTooltipEnum.TOTAL_AMOUNT_VIEW"
                />
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ formatAmount(totalAmount) }}
                  </span>
                </x-field>
              </div>
              <div
                class="col-span-2"
                v-if="
                  insurerPaymentLinkIndex >= 0 &&
                  can(permissionEnum.INSURER_PAYMENT_LINK)
                "
              >
                <x-tooltip>
                  <span class="border-b-2 border-dotted border-black text-sm"
                    >INSURER PAYMENT LINK -
                    {{ paymentMethodsModels[insurerPaymentLinkIndex] }}</span
                  >
                  <template #tooltip>
                    <span>{{ paymentTooltipEnum.PAYMENT_LIST_IPL }}</span>
                  </template>
                </x-tooltip>
                <x-field class="w-full">
                  <span v-if="isFieldReadonly">
                    {{ paymentMethodsModels[insurerPaymentLinkIndex] }}
                  </span>
                  <x-input
                    v-if="!isFieldReadonly"
                    class="w-full"
                    :class="{
                      'custom-select-error':
                        paymentMethodsForm.errors.insurerPaymentLink,
                    }"
                    placeholder="Enter valid url e.g: https://imcrm.alfred.ae/login"
                    v-model="paymentMethodsForm.insurerPaymentLink"
                    :disabled="isMasterPaymentPaid"
                    @input="validateInsurerPaymentLink"
                  />
                  <p
                    v-if="paymentMethodsForm.errors.insurerPaymentLink"
                    class="text-sm text-red-500 dark:text-red-400 mt-1"
                  >
                    {{ paymentMethodsForm.errors.insurerPaymentLink }}
                  </p>
                </x-field>
              </div>
            </div>

            <x-divider class="mb-4 mt-10" />

            <div
              v-if="isDowngradeFrequencyError"
              class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500"
              role="alert"
            >
              <svg
                class="flex-shrink-0 inline w-4 h-4 mr-3"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                />
              </svg>
              <div>
                The system has detected a discrepancy. You cannot downgrade the
                frequency of the payment. Some payments are already paid.
              </div>
            </div>

            <div
              v-if="isPaymentCalculationError"
              class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500"
              role="alert"
            >
              <svg
                class="flex-shrink-0 inline w-4 h-4 mr-3"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                />
              </svg>
              <div>
                The system has detected a discrepancy. Before you hit 'Add
                Manual Payment,' please check the each payment transaction. If
                you spot any discrepancies, make the necessary adjustments. Once
                everything lines up, you're good to proceed.
              </div>
            </div>

            <div
              v-if="isPaymentLocked && paymentMethodsForm.status == 'edit'"
              class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500"
              role="alert"
            >
              <svg
                class="flex-shrink-0 inline w-4 h-4 mr-3"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                />
              </svg>
              <div>
                {{ paymentTooltipEnum.PAYMENT_LOCKED }}
              </div>
            </div>

            <div
              v-if="isFileError"
              class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500"
              role="alert"
            >
              <svg
                class="flex-shrink-0 inline w-4 h-4 mr-3"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                />
              </svg>
              <div>
                {{ fileErrorMessage }}
              </div>
            </div>

            <div class="w-full grid">
              <!-- Header -->
              <div class="mb-3">
                <h3>Payment Schedule</h3>
              </div>
              <div class="flex w-full">
                <div class="w-1/6 px-2 text-center">
                  <span class="relative group text-sm">
                    <span class="border-b-2 border-dotted border-black text-sm"
                      >PAYMENT NO</span
                    >
                    <sup
                      v-if="
                        !isViewEnabled &&
                        !isCreditApprovalView &&
                        !hasAnyCCPayment()
                      "
                      class="text-red-500"
                      >*</sup
                    >
                    <div
                      class="absolute text-left hidden group-hover:block transform transition-transform z-40 h-fit _popoverContent_1wc81_3 top-full bottom-0 _popoverBottom_1wc81_14 left-1/2 right-full -translate-x-1/2 max-w-xs"
                    >
                      <div class="dark">
                        <div
                          class="x-popover-container block w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-200 dark:border-gray-800 p-2 text-white text-sm w-max max-w-xs"
                        >
                          <span data-v-d0063695="">
                            {{ paymentTooltipEnum.PAYMENT_NO_2 }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </span>
                </div>
                <div class="w-1/5 px-2">
                  <x-tooltip>
                    <span class="text-sm">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >PAYMENT METHOD</span
                      >
                      <sup
                        v-if="!isViewEnabled && !isCreditApprovalView"
                        class="text-red-500"
                        >*</sup
                      >
                    </span>
                    <template #tooltip>
                      <span v-if="isFieldReadonly">{{
                        paymentTooltipEnum.PAYMENT_METHOD_VIEW
                      }}</span>
                      <span v-else>{{
                        paymentTooltipEnum.PAYMENT_METHOD
                      }}</span>
                    </template>
                  </x-tooltip>
                </div>
                <div class="w-1/5 px-2">
                  <x-tooltip>
                    <span class="text-sm">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >TOTAL AMOUNT</span
                      >
                      <sup
                        v-if="!isViewEnabled && !isCreditApprovalView"
                        class="text-red-500"
                        >*</sup
                      >
                    </span>
                    <template #tooltip>
                      <span v-if="isFieldReadonly">{{
                        paymentTooltipEnum.TOTAL_AMOUNT_SPLIT_VIEW
                      }}</span>
                      <span v-else>{{ paymentTooltipEnum.TOTAL_AMOUNT }}</span>
                    </template>
                  </x-tooltip>
                </div>
                <div class="w-1/5 px-2">
                  <x-tooltip v-if="isCreditApprovalView">
                    <span v-if="isCreditCardView" class="text-sm">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >CAPTURE AMOUNT</span
                      >
                      <sup class="text-red-500">*</sup>
                    </span>
                    <span v-else class="text-sm">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >COLLECTED AMOUNT</span
                      >
                    </span>
                    <template #tooltip>
                      <span v-if="isCreditCardView">{{
                        paymentTooltipEnum.CAPTURE_AMOUNT
                      }}</span>
                      <span v-else>{{
                        paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_TEXT
                      }}</span>
                    </template>
                  </x-tooltip>

                  <x-tooltip v-else>
                    <span class="text-sm">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >DUE DATE</span
                      >
                      <sup v-if="!isViewEnabled" class="text-red-500">*</sup>
                    </span>
                    <template #tooltip>
                      <span v-if="isFieldReadonly">{{
                        paymentTooltipEnum.DUE_DATE_VIEW
                      }}</span>
                      <span v-else>{{ paymentTooltipEnum.DUE_DATE }}</span>
                    </template>
                  </x-tooltip>
                </div>
                <div class="w-1/5 px-2">
                  <x-tooltip>
                    <span class="text-sm">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >DOCUMENTS</span
                      >
                      <sup
                        v-if="
                          !isViewEnabled &&
                          !isCreditApprovalView &&
                          !(
                            hasAnyCCPayment() &&
                            paymentMethodsForm.collection_type == 'insurer'
                          )
                        "
                        class="text-red-500"
                        >*</sup
                      >
                    </span>
                    <template #tooltip>
                      <span v-if="isFieldReadonly">{{
                        paymentTooltipEnum.DOCUMENTS_VIEW
                      }}</span>
                      <span v-else>{{ paymentTooltipEnum.DOCUMENTS }}</span>
                    </template>
                  </x-tooltip>
                </div>
              </div>

              <!-- Split Payment Fields Rendering -->

              <template v-if="isViewEnabled">
                <div class="flex w-full custombreak">
                  <div class="w-1/6 px-2 text-center">{{ splitPaymentNo }}</div>

                  <div class="w-1/5 px-2">
                    {{
                      getPaymentTypeLabel(paymentMethodsModels[splitPaymentNo])
                    }}
                    <p>{{ checkDetailModels[splitPaymentNo] }}</p>
                  </div>

                  <div class="w-1/5 px-2">
                    {{ formatAmount(splitAmountModels[splitPaymentNo]) }}
                  </div>

                  <div class="w-1/5 px-2">
                    {{ formatDate(dueDateModels[splitPaymentNo]) }}
                  </div>

                  <div class="w-1/5 px-2">
                    <div
                      v-for="fileData in fileUploadModels[splitPaymentNo]"
                      :key="fileData.id"
                    >
                      <span style="display: flex; align-items: center">
                        <span
                          :key="fileData.id"
                          class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                          style="
                            flex: 1;
                            text-decoration: none;
                            cursor: pointer;
                          "
                          @click="openInnerModal(fileData.id)"
                        >
                          {{ fileData.original_name }}
                        </span>
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex w-full custombreak pt-5">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >CC PAYMENT STATUS INFO</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_CC_PAYMENT_STATUS
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >CC PAYMENT GATEWAY</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_CC_GATEWAY
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >DIGITAL WALLET</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_WALLET
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >SAGE RECEIPT ID</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_SAGE_RECIPT
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                </div>
                <div class="flex w-full custombreak pt-1 pb-5">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.cc_payment_status_info !== null
                        ? splitPaymentRecord.cc_payment_status_info
                        : 'N/A'
                    }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.cc_payment_gateway !== null
                        ? splitPaymentRecord.cc_payment_gateway
                        : 'N/A'
                    }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.digital_wallet !== null
                        ? splitPaymentRecord.digital_wallet
                        : 'N/A'
                    }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.sage_reciept_id !== null
                        ? splitPaymentRecord.sage_reciept_id
                        : 'N/A'
                    }}
                  </div>
                </div>
                <div class="flex w-full custombreak">
                  <div class="w-1/6 px-2 text-center"></div>
                  <template
                    v-if="splitPaymentRecord.payment_method.code == 'CC'"
                  >
                    <div class="w-1/5 px-2">
                      <span class="text-sm"> AUTHORISED AMOUNT </span>
                    </div>
                    <div class="w-1/5 px-2">
                      <span class="text-sm"> AUTHORISED AT </span>
                    </div>
                    <div class="w-1/5 px-2">
                      <span class="text-sm">
                        {{
                          splitPaymentRecord.payment_status_id ==
                          paymentStatusEnum.PARTIALLY_PAID
                            ? 'PARTIALLY CAPTURED AT'
                            : 'CAPTURED AT'
                        }}
                      </span>
                    </div>
                  </template>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >CC PAYMENT ID</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_ID }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                </div>

                <div class="flex w-full custombreak pb-5">
                  <div class="w-1/6 px-2 text-center"></div>
                  <template
                    v-if="splitPaymentRecord.payment_method.code == 'CC'"
                  >
                    <div class="w-1/5 px-2">
                      {{
                        splitPaymentRecord.premium_authorized !== null
                          ? formatAmount(splitPaymentRecord.premium_authorized)
                          : 'N/A'
                      }}
                    </div>
                    <div class="w-1/5 px-2">
                      {{
                        splitPaymentRecord.authorized_at !== null
                          ? formatDate(splitPaymentRecord.authorized_at, true)
                          : 'N/A'
                      }}
                    </div>
                    <div class="w-1/5 px-2">
                      {{
                        splitPaymentRecord.captured_at !== null
                          ? formatDate(splitPaymentRecord.captured_at, true)
                          : 'N/A'
                      }}
                    </div>
                    <div class="w-1/5 px-2">
                      {{
                        splitPaymentRecord.cc_payment_id !== null
                          ? splitPaymentRecord.cc_payment_id
                          : 'N/A'
                      }}
                    </div>
                  </template>
                </div>
                <div class="flex w-full custombreak">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >PAYMENT STATUS</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_STATUS
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >PAYMENT ALLOCATION STATUS</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_ALLO_STATUS
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                  <div class="w-1/5 px-2">
                    <x-tooltip>
                      <span class="text-sm">
                        <span
                          class="border-b-2 border-dotted border-black text-sm"
                          >COLLECTED AMOUNT</span
                        >
                      </span>
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_TEXT
                        }}</span>
                      </template>
                    </x-tooltip>
                  </div>
                  <div class="w-1/5 px-2" v-if="isVerifiedEnabled">
                    <span class="text-sm">
                      <span class="text-sm">VERIFIED AT</span>
                    </span>
                  </div>
                </div>

                <div class="flex w-full custombreak pb-5">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    {{ formatString(splitPaymentRecord.payment_status.text) }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.payment_allocation_status !== null
                        ? formatString(
                            splitPaymentRecord.payment_allocation_status,
                          )
                        : 'N/A'
                    }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.collection_amount !== null
                        ? formatAmount(splitPaymentRecord.collection_amount)
                        : '0.00'
                    }}
                  </div>
                  <div class="w-1/5 px-2" v-if="isVerifiedEnabled">
                    {{
                      splitPaymentRecord.verified_at !== null
                        ? splitPaymentRecord.verified_at
                        : 'N/A'
                    }}
                  </div>
                </div>

                <div class="flex w-full custombreak">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    <span class="text-sm">
                      <span class="border-b-2 border-solid border-black text-sm"
                        >Receipt ID</span
                      >
                    </span>
                  </div>
                  <div class="w-1/5 px-2">
                    <span class="text-sm">
                      <span class="border-b-2 border-solid border-black text-sm"
                        >Auth Code</span
                      >
                    </span>
                  </div>
                  <div class="w-1/5 px-2">
                    <span class="text-sm">
                      <span class="border-b-2 border-solid border-black text-sm"
                        >Charge ID</span
                      >
                    </span>
                  </div>
                </div>

                <div class="flex w-full custombreak pb-5">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    {{ splitPaymentRecord.payment_receipt_id ?? 'N/A' }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{ splitPaymentRecord.payment_auth_code ?? 'N/A' }}
                  </div>
                  <div class="w-1/5 px-2">
                    {{ splitPaymentRecord?.payment_charges?.transaction_id }}
                  </div>
                </div>

                <div class="flex w-full custombreak" v-if="isVerifiedEnabled">
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    <span class="text-sm">
                      <span class="text-sm">VERIFIED BY</span>
                    </span>
                  </div>
                </div>

                <div
                  class="flex w-full custombreak pb-5"
                  v-if="isVerifiedEnabled"
                >
                  <div class="w-1/6 px-2 text-center"></div>
                  <div class="w-1/5 px-2">
                    {{
                      splitPaymentRecord.verified_by !== null
                        ? splitPaymentRecord.verified_by_user.name
                        : 'N/A'
                    }}
                  </div>
                </div>
              </template>
              <template v-else>
                <div
                  v-for="count in parseInt(paymentMethodsForm.payment_no)"
                  :key="count"
                  class="mb-2"
                >
                  <div class="flex w-full custombreak">
                    <div class="w-1/6 px-2 text-center">{{ count }}</div>
                    <div class="w-1/5 px-2">
                      <template v-if="readOnlyPayments[count]">
                        {{ getPaymentTypeLabel(paymentMethodsModels[count]) }}
                        <p>{{ checkDetailModels[count] }}</p>
                      </template>
                      <template v-else>
                        <x-tooltip v-if="isPaymentMethodEnabled">
                          <select
                            :class="{
                              'custom-select-error':
                                isPaymentMetodNotSelected[count],
                            }"
                            class="w-full custom-select"
                            v-model="paymentMethodsModels[count]"
                            disabled="true"
                          >
                            <!-- Use the title attribute to set the tooltip text -->
                            <option
                              v-for="option in handlePaymentTypes(count)"
                              :key="option.value"
                              :value="option.value"
                              :title="option.tooltip"
                            >
                              {{ option.label }}
                            </option>
                          </select>
                          <template #tooltip>
                            <span class="custom-tooltip-content">{{
                              paymentTooltipEnum.CREDIT_APPROVAL_PAYMENT_METHOD_DISABLED_MESSAGE
                            }}</span>
                          </template>
                        </x-tooltip>
                        <select
                          v-else
                          :class="{
                            'custom-select-error':
                              isPaymentMetodNotSelected[count],
                          }"
                          class="w-full custom-select"
                          v-model="paymentMethodsModels[count]"
                          @change="handlePaymentOptions(count)"
                        >
                          <!-- Use the title attribute to set the tooltip text -->
                          <option
                            v-for="option in handlePaymentTypes(count)"
                            :key="option.value"
                            :value="option.value"
                            :title="
                              isCCPaymentDisabled(option.value)
                                ? paymentTooltipEnum.CC_PAYMENT_NOT_SUPPORTED
                                : isPolicySendUpdateBooked(option.value)
                                  ? paymentTooltipEnum.CC_PAYMENT_NOT_SUPPORTED_WHEN_BOOKED
                                  : option.tooltip
                            "
                            :disabled="
                              isCCPaymentDisabled(option.value) ||
                              isPolicySendUpdateBooked(option.value)
                            "
                          >
                            {{ option.label }}
                          </option>
                        </select>
                        <p
                          v-if="isPaymentMetodNotSelected[count]"
                          class="text-sm text-red-500 dark:text-red-400 mt-1"
                        >
                          This field is required
                        </p>
                        <x-tooltip>
                          <x-input
                            class="w-full mt-2"
                            v-if="
                              isCheckDetailsEnabled[count] &&
                              (paymentMethodsModels[count] === 'CHQ' ||
                                paymentMethodsModels[count] === 'PDC')
                            "
                            v-model="checkDetailModels[count]"
                            placeholder="Cheque Number"
                            :rules="[rules.isRequired]"
                          />
                          <template #tooltip>
                            <span>{{ paymentTooltipEnum.CHECK_DETAILS }}</span>
                          </template>
                        </x-tooltip>
                      </template>
                    </div>
                    <div class="w-1/5 px-2">
                      <template
                        v-if="readOnlyPayments[count] && !isPaidEditable"
                      >
                        {{ formatAmount(splitAmountModels[count]) }}
                      </template>
                      <template v-else>
                        <x-input
                          v-model="splitAmountModels[count]"
                          class="w-full"
                          :rules="[rules.isRequired]"
                          :disabled="isPaymentLocked"
                        />
                        <sup
                          v-if="isSplitAmountInvalid[count]"
                          class="text-sm text-red-500 dark:text-red-400"
                          >{{ isSplitAmountInvalidError[count] }}</sup
                        >
                      </template>
                    </div>
                    <div class="w-1/5 px-2" v-if="isCreditApprovalView">
                      <template
                        v-if="readOnlyPayments[count] && !isCreditCardView"
                      >
                        {{ formatAmount(collectionAmountModels[count]) }}
                      </template>
                      <template v-else>
                        <x-input
                          v-if="
                            paymentMethodsModels[count] === 'CC' &&
                            authorizedPayments[count]
                          "
                          v-model="collectionAmountModels[count]"
                          class="w-full"
                          :class="{
                            'custom-select-error':
                              isCreditPaymentInvalid[count],
                          }"
                        />
                        <span v-else>{{
                          formatAmount(collectionAmountModels[count])
                        }}</span>
                        <sup
                          v-if="isCreditPaymentInvalid[count]"
                          class="text-sm text-red-500 dark:text-red-400"
                          >{{ isCreditPaymentInvalidError[count] }}</sup
                        >
                        <small
                          class="text-red-600 text-sm"
                          v-if="capturePaymentValidationErrorMessage"
                          >{{ capturePaymentValidationErrorMessage }}</small
                        >
                      </template>
                    </div>
                    <div class="w-1/5 px-2" v-else>
                      <template v-if="readOnlyPayments[count]">
                        {{ formatDate(dueDateModels[count]) }}
                      </template>
                      <template v-else>
                        <DatePicker
                          v-model="dueDateModels[count]"
                          class="w-full"
                          :rules="[rules.isRequired]"
                          placeholder="dd-mm-yyyy"
                          :disabled="isPaymentLocked"
                        />
                      </template>
                    </div>
                    <div class="w-1/5 px-2 mb-2">
                      <x-tooltip v-if="!readOnlyPayments[count]">
                        <Dropzone
                          :id="paymentProofDocument?.id"
                          :multiple="true"
                          :customDisplay="true"
                          :accept="paymentProofDocument?.accepted_files"
                          :max-files="paymentProofDocument?.max_files"
                          :max-size="paymentProofDocument?.max_size"
                          :loading="documentForm.processing"
                          @change="
                            uploadDocument(paymentProofDocument, $event, count)
                          "
                        />
                        <template #tooltip>
                          <span>{{ paymentTooltipEnum.DOCUMENTS_UPLOAD }}</span>
                        </template>
                      </x-tooltip>
                      <p
                        v-if="isDocumentNotUploaded[count]"
                        class="text-sm text-red-500 dark:text-red-400 mt-1"
                      >
                        This field is required
                      </p>
                      <div
                        v-for="fileData in fileUploadModels[count]"
                        :key="fileData?.id"
                      >
                        <span style="display: flex; align-items: center">
                          <span
                            :key="fileData?.id"
                            class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                            style="
                              flex: 1;
                              text-decoration: none;
                              cursor: pointer;
                            "
                            @click="openInnerModal(fileData?.id)"
                          >
                            {{ fileData?.original_name }}
                          </span>
                          <span
                            class="delete-pointer"
                            @click="
                              deleteDocument(
                                fileData.doc_name,
                                count,
                                fileData.id,
                                fileData.doc_uuid,
                              )
                            "
                            v-if="!readOnlyPayments[count]"
                          >
                            <x-tooltip>
                              &#10006;
                              <template #tooltip>
                                <span>{{
                                  paymentTooltipEnum.DOCUMENT_DELETE_ICON
                                }}</span>
                              </template>
                            </x-tooltip>
                          </span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <x-divider class="mb-4 mt-1" />

            <div v-if="isViewEnabled" class="p-1 mb-2">
              <h3>Notes</h3>
            </div>

            <div class="w-full grid">
              <x-field>
                <label v-if="!isViewEnabled">Notes</label>
                <p
                  v-if="
                    (isFieldReadonly && isViewEnabled) || isCreditApprovalView
                  "
                >
                  {{ paymentMethodsForm.notes }}
                </p>
                <x-input
                  v-if="!isViewEnabled && !isCreditApprovalView"
                  class="w-full"
                  v-model="paymentMethodsForm.notes"
                />
              </x-field>
            </div>

            <div
              class="flex items-center justify-center"
              v-if="
                splitPaymentRecord.verified_by !== null &&
                paymentMethodsForm.status == 'view' &&
                paymentMethodsModels[splitPaymentNo] !=
                  paymentMethodsEnums.CreditCard &&
                paymentMethodsModels[splitPaymentNo] !=
                  paymentMethodsEnums.CreditApproval
              "
            >
              <p class="text-lg font-bold text-blue-400 mr-2">
                Payment has been verified
              </p>
              <img
                style="width: 30px; height: 30px"
                src="/images/payment_verified.jpg"
              />
            </div>

            <template
              v-if="(isViewEnabled || isCreditApprovalView) && isDeclineClicked"
            >
              <div class="p-1 mb-2">
                <h3 class="text-white">PAYMENT DECLINE</h3>
              </div>
              <x-divider class="mb-4 mt-1" />
              <div class="flex w-full">
                <div class="w-full px-2">
                  <x-field label="DECLINE REASON" required>
                    <select
                      :class="{ 'custom-select-error': isDeclinedReasonError }"
                      class="custom-select"
                      v-model="paymentMethodsForm.declined_reason"
                      :rules="[rules.isRequired]"
                      @change="handleDeclinedReasonChange"
                    >
                      <template
                        v-for="option in declinedReasons"
                        :key="option.value"
                      >
                        <option :value="option.value">
                          {{ option.label }}
                        </option>
                      </template>
                    </select>
                    <p
                      v-if="isDeclinedReasonError"
                      class="text-sm text-red-500 dark:text-red-400 mt-1"
                    >
                      This field is required
                    </p>
                  </x-field>
                </div>
                <div v-if="isDeclineCustomReason" class="w-full px-2">
                  <x-field label="CUSTOM REASON" required>
                    <x-input
                      class="w-full"
                      v-model="paymentMethodsForm.declined_custom_reason"
                      :rules="[rules.isRequired]"
                    />
                  </x-field>
                </div>
              </div>
            </template>

            <template
              v-if="
                isViewEnabled &&
                isApproveClicked &&
                paymentMethodsModels[splitPaymentNo] != 'CC'
              "
            >
              <x-divider class="mb-4 mt-1" />
              <div class="w-1/2 px-2 p-1 mb-2">
                <x-tooltip class="tooltip-display">
                  <h3 class="font-bold">Payment Verification</h3>
                  <template #tooltip>
                    <span>{{
                      paymentTooltipEnum.PAYMENT_VIEW_VERIFICATION_HEADER
                    }}</span>
                  </template>
                </x-tooltip>
              </div>
              <div
                v-if="isApprovePaymentError"
                class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500"
                role="alert"
              >
                <svg
                  class="flex-shrink-0 inline w-4 h-4 mr-3"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                  />
                </svg>
                <div>
                  {{ approveErrorMessage }}
                </div>
              </div>
              <div class="flex w-full">
                <div class="w-1/2 px-2">
                  <div>
                    <x-tooltip class="tooltip-display">
                      <span
                        class="border-b-2 border-dotted border-black text-sm"
                        >COLLECTED AMOUNT
                        <sup class="text-red-500">*</sup></span
                      >
                      <template #tooltip>
                        <span>{{
                          paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_AMOUNT
                        }}</span>
                      </template>
                    </x-tooltip>
                    <x-field class="w-full">
                      <x-input
                        class="w-full"
                        v-model="paymentMethodsForm.collection_amount"
                        :rules="[rules.isRequired, rules.amount]"
                      />
                    </x-field>
                  </div>
                </div>
                <div
                  class="w-1/2 px-2"
                  v-if="
                    paymentMethodsModels[splitPaymentNo] == 'BT' ||
                    paymentMethodsModels[splitPaymentNo] == 'CHQ'
                  "
                >
                  <x-tooltip>
                    <span class="border-b-2 border-dotted border-black text-sm"
                      >BANK REFERENCE NUMBER
                      <sup
                        v-if="
                          !(
                            paymentMethodsForm.collection_type === 'insurer' &&
                            paymentMethodsModels[splitPaymentNo] === 'CHQ' &&
                            paymentMethodsForm.credit_approval != ''
                          )
                        "
                        class="text-red-500"
                        >*</sup
                      >
                    </span>
                    <template #tooltip>
                      <span>{{
                        paymentTooltipEnum.PAYMENT_VIEW_BANK_REFERENCE
                      }}</span>
                    </template>
                  </x-tooltip>
                  <x-field>
                    <x-input
                      class="w-full"
                      v-model="paymentMethodsForm.bank_reference_number"
                      :rules="[rules.isBankReferenceRequird]"
                    />
                  </x-field>
                </div>
                <div class="w-1/3 px-2">
                  <x-tooltip>
                    <span class="border-b-2 border-dotted border-black text-sm"
                      >DOCUMENT
                      <sup
                        v-if="paymentMethodsForm.collection_type === 'insurer'"
                        class="text-red-500"
                        >*</sup
                      ></span
                    >
                    <template #tooltip>
                      <span>{{
                        paymentTooltipEnum.PAYMENT_VIEW_DOCUMENTS
                      }}</span>
                    </template>
                  </x-tooltip>
                  <x-field>
                    <Dropzone
                      :id="approveProofDocument.id"
                      :customDisplay="true"
                      :multiple="true"
                      :accept="approveProofDocument.accepted_files"
                      :max-files="approveProofDocument.max_files"
                      :max-size="approveProofDocument.max_size"
                      :loading="documentForm.processing"
                      @change="
                        uploadDocument(
                          approveProofDocument,
                          $event,
                          splitPaymentNo,
                        )
                      "
                    />
                    <p
                      v-if="isApprovedDocumentNotUploaded"
                      class="text-sm text-red-500 dark:text-red-400 mt-1"
                    >
                      This field is required
                    </p>
                  </x-field>
                  <div
                    v-for="fileData in approvedDocumentModel[splitPaymentNo]"
                    :key="fileData.id"
                  >
                    <span style="display: flex; align-items: center">
                      <span
                        :key="fileData.id"
                        class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                        style="flex: 1; text-decoration: none; cursor: pointer"
                        @click="openInnerModal(fileData.id)"
                      >
                        {{ fileData.original_name }}
                      </span>
                      <span
                        class="delete-pointer"
                        @click="
                          deleteDocument(
                            fileData.doc_name,
                            splitPaymentNo,
                            fileData.id,
                            fileData.doc_uuid,
                          )
                        "
                        v-if="!readOnlyPayments[splitPaymentNo]"
                      >
                        &#10006;
                      </span>
                    </span>
                  </div>
                </div>
              </div>
            </template>

            <x-divider class="mb-4 mt-1" />

            <template v-if="isViewEnabled || isCreditApprovalView">
              <template
                v-if="
                  isCreditApprovalView ||
                  (paymentMethodsModels[splitPaymentNo] !== 'CA' &&
                    paymentMethodsModels[splitPaymentNo] !== 'CC')
                "
              >
                <div
                  v-if="
                    isCreditApprovalView ||
                    (splitPaymentRecord.payment_status_id !=
                      paymentStatusEnum.PAID &&
                      (can(permissionEnum.ApprovePayments) ||
                        (can(permissionEnum.INPL_APPROVER) &&
                          splitPaymentRecord.payment_method.code ==
                            paymentMethodsEnum?.InsureNowPayLater)))
                  "
                  class="w-full flex justify-end"
                >
                  <div v-if="isDeclineClicked" class="mr-4">
                    <x-button
                      size="sm"
                      @click="handleCancelChanges"
                      tabindex="0"
                      class="focus:outline-black"
                    >
                      Cancel
                    </x-button>
                  </div>
                  <div
                    v-if="
                      ((!isApproveClicked && !isDeclineClicked) ||
                        (isCreditApprovalView && !isDeclineClicked)) &&
                      isVerificationAllowed
                    "
                    class="mr-4"
                  >
                    <x-button
                      v-if="!isProformaPaymentRequest"
                      size="sm"
                      @click="handleDeclinedChange"
                      tabindex="0"
                      class="focus:outline-black"
                    >
                      Decline
                    </x-button>
                  </div>
                  <div
                    v-if="
                      !isApproveClicked &&
                      isDeclineClicked &&
                      isVerificationAllowed
                    "
                    class="mr-4"
                  >
                    <x-button
                      size="sm"
                      type="submit"
                      tabindex="0"
                      class="focus:outline-black"
                      :loading="paymentMethodsForm.processing"
                    >
                      Decline
                    </x-button>
                  </div>
                  <div
                    v-if="
                      !isDeclineClicked &&
                      (paymentMethodsModels[splitPaymentNo] != 'CC' ||
                        isCreditApprovalView)
                    "
                  >
                    <x-button
                      v-if="
                        !isApproveClicked &&
                        isViewEnabled &&
                        !isProformaPaymentRequest &&
                        isVerificationAllowed
                      "
                      class="mr-2 focus:outline-black"
                      size="sm"
                      color="#ff5e00"
                      @click="
                        isAmlVerified(
                          props.quoteRequest,
                          props.quoteType,
                          props.payments,
                        )
                          ? (isApproveClicked = !isApproveClicked)
                          : openAmlVerificationModal()
                      "
                      tabindex="0"
                    >
                      Approve
                    </x-button>
                    <x-button
                      v-if="
                        (isApproveClicked ||
                          (isCreditApprovalView && !isDeclineClicked)) &&
                        isTransactionCaptureButtonEnabled
                      "
                      class="mr-2 focus:outline-black"
                      size="sm"
                      color="#ff5e00"
                      type="submit"
                      tabindex="0"
                      :loading="paymentMethodsForm.processing"
                      :disabled="
                        isApproveConfirmed || !isTransactionCaptureButtonEnabled
                      "
                    >
                      <template v-if="isCreditApprovalView && isCreditCardView">
                        Capture
                      </template>
                      <template v-else-if="isVerificationAllowed">
                        Approve
                      </template>
                      <template v-else> Approve </template>
                    </x-button>
                  </div>
                </div>
              </template>
            </template>
            <template v-else>
              <div class="w-full md:col-span-4 flex justify-end">
                <div
                  v-if="
                    paymentMethodsForm.status == 'edit' &&
                    insurerPaymentLinkIndex < 0
                  "
                  class="mr-4"
                >
                  <x-button
                    @click="createPaymentModal = !createPaymentModal"
                    tabindex="0"
                    class="focus:outline-black"
                  >
                    Cancel
                  </x-button>
                </div>
                <div
                  v-if="
                    insurerPaymentLinkIndex < 0 &&
                    (paymentMethodsForm.status == 'create' ||
                      paymentMethodsForm.status == 'edit')
                  "
                >
                  <x-button
                    color="emerald"
                    type="submit"
                    tabindex="0"
                    class="focus:outline-black"
                    :loading="paymentMethodsForm.processing"
                  >
                    {{
                      paymentMethodsForm.status == 'create'
                        ? 'Add Manual Payment'
                        : 'Update'
                    }}
                  </x-button>
                </div>
                <template
                  v-if="
                    paymentMethodsModels[insurerPaymentLinkIndex] ===
                      page.props.paymentMethodsEnum?.InsurerPaymentLink &&
                    can(permissionEnum.INSURER_PAYMENT_LINK)
                  "
                >
                  <InsurerPaymentLink
                    ref="insurerPaymentComponent"
                    :modelType="props.quoteType"
                    :insurerPaymentLinkIndex="insurerPaymentLinkIndex"
                    :paymentForm="paymentMethodsForm"
                    :payments="payments"
                    @updateOnParent="
                      (e, f, g) => updateFromInsurerPaymentLink(e, f, g)
                    "
                  />
                </template>
              </div>
            </template>

            <div
              class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
              v-if="isApproveConfirmed"
            >
              <div
                class="modal-confirm-container bg-white w-full max-w-full overflow-hidden rounded-lg"
              >
                <div class="modal-confirm-header text-base text-white bg-white">
                  <div
                    class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
                  >
                    <div class="flex items-center space-x-2">
                      {{ transactionActionText }}
                    </div>
                    <div class="flex items-center space-x-2">
                      <span
                        @click="closeConfirmModal"
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
                  </div>
                </div>
                <div class="w-full h-full mt-2 flex flex-col items-center">
                  <div
                    class="text-lg font-semibold px-6 py-4 border-b flex justify-between items-start"
                  >
                    <div class="flex items-center text-center mr-2 mt-4">
                      <input
                        type="checkbox"
                        @click="isApproveNotChecked = !isApproveNotChecked"
                        class="h-6 w-6 mr-2 border border-gray-300 rounded checked:bg-blue-500 checked:border-transparent focus:ring-blue-400"
                      />
                    </div>
                    <div class="text-left">
                      <span
                        v-if="paymentMethodsForm.collection_type === 'insurer'"
                        >I certify that all details provided, including the
                        official receipt or payment confirmation, are correct
                        and in compliance with our conduct standards.</span
                      >
                      <span
                        v-if="paymentMethodsForm.collection_type === 'broker'"
                        >I verify that the information provided is accurate and
                        my actions align with our standards of conduct.</span
                      >
                    </div>
                  </div>
                  <x-tooltip v-if="isApproveNotChecked">
                    <x-button
                      size="lg"
                      color="orange"
                      class="px-4 py-2 mt-4"
                      :disabled="isApproveNotChecked"
                    >
                      <span>Confirm</span></x-button
                    >
                    <template #tooltip>
                      <span>{{
                        paymentTooltipEnum.CONFIRM_APPROVE_UNSELECT
                      }}</span>
                    </template>
                  </x-tooltip>
                  <x-button
                    v-if="!isApproveNotChecked"
                    size="lg"
                    type="submit"
                    color="orange"
                    class="px-4 py-2 mt-4"
                    :disabled="isApproveNotChecked"
                    :loading="paymentMethodsForm.processing"
                  >
                    <span>Confirm</span></x-button
                  >
                </div>
              </div>
            </div>
          </x-form>

          <!-- Image Gallery Modal -->
          <ImageGalleryModal
            v-model="isGalleryModelOpen"
            :files="filesTest"
            :initial-index="currentFileIndex"
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
          :is-processing="paymentMethodsForm.processing"
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
.custom-select {
  border: 2px solid #e5e7eb;
  padding: 7px;
  border-radius: 5px;
  background-color: #fff;
  color: #333;
  font-size: 16px;
  width: 100%;
  height: 38px;
}
.custom-select-error {
  border: 1px solid red;
  padding: 1px;
  outline: none;
  box-sizing: border-box;
  height: 45px;
}
.custom-dropdown {
  position: relative;
}
.close-icon {
  position: absolute;
  top: 8px;
  left: 0;
  margin-left: calc(100% - 39px);
  cursor: pointer;
  color: #333; /* Customize the close icon color */
  font-size: 0.7rem;
  font-weight: normal;
}
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
