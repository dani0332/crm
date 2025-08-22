<script setup>
import { defineProps, defineEmits } from 'vue';
import moment from 'moment';
import {
  PaymentFormFields,
  PaymentFormAlerts,
  PaymentFormScheduleTable,
  PaymentFormNotes,
  PaymentFormVerified,
  PaymentFormDecline,
  PaymentFormVerification,
  PaymentFormFooter,
} from './PaymentFormComps/index.js';

const page = usePage();
const can = permission => useCan(permission);

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const permissionEnum = page.props.permissionsEnum;
const paymentLookups = page.props.paymentLookups;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const vatValue = page.props.vatValue;
const paymentMethodsEnum = page.props.paymentMethodsEnum;

const notification = useNotifications('toast');

const props = defineProps({
  totalPrice: Number,
  creditApprovalReasons: Array,
  discountReasons: Array,
  paymentDocument: Array,
  totalAmount: Number,
  planDetail: Object,
  quoteTypesToCheck: Array,
  insuranceProviders: Array,

  isPaidEditable: Boolean,
  paymentProofDocument: Object,
  isMultiPaymentsEnabled: Boolean,
  quoteType: String,
  sendUpdate: Object,
  payments: Array,
  isCCEnabled: Boolean,
  quoteRequest: Object,
  sendUpdateStatusEnum: Object,

  approveProofDocument: Object,

  createPaymentModal: Boolean,
  paymentMethods: Array,
  eCommercePriceWithLP: Number,
  isPlanDetailSectionEnabled: Boolean,
  quoteSubType: String,
  isLackingPayment: Boolean,
  planText: String,
  isTransactionCaptureButtonEnabled: Boolean,
  showInsurerReceiptNumberInputField: Boolean,
  insurerReceiptNumberCheckInProcess: Boolean,
  capturePaymentValidationErrorMessage: String,
  selectedPaymentForEdit: Object,
  isInsurerReceiptNumberExistsModalOpen: Boolean,
  isLifePlanDetailsEnabled: Boolean,
});

const fileUploadModels = ref([]);
const isApproveConfirmed = ref(false);
const paymentMethodsModels = ref([]);
const splitPaymentNo = ref(0);
const isFieldReadonly = ref(false);
const isViewEnabled = ref(false);
const isPaymentCalculationError = ref(false);
const isDowngradeFrequencyError = ref(false);
const isApproveClicked = ref(false);
const isFileError = ref(false);
const isApprovePaymentError = ref(false);
const paidAmountSum = ref(0);
const totalPaidAmount = ref(0);
const masterPaymentStatus = ref('NEW');
const isDeclineClicked = ref(false);
const isPaymentNoEnabled = ref(false);
const isCustomReasonEnabled = ref(false);
const isResetCreditApproval = ref(false);
const isCustomDiscountReasonEnabled = ref(false);
const isDiscountEnabled = ref(false);
const isDiscountReasonEnabled = ref(false);
const isCheckDetailsEnabled = ref([]);
const isUploading = ref(false);
const fileErrorMessage = ref('');
const isApproveConfirm = ref(false);
const isDeclinedReasonError = ref(false);
const isDeclineCustomReason = ref(false);
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
const splitAmountModels = ref([]);
const dueDateModels = ref([]);
const collectionAmountModels = ref([]);
const checkDetailModels = ref([]);
const readOnlyPayments = ref([]);
const authorizedPayments = ref([]);
const splitPaymentRecord = ref([]);
const filesTest = ref([]);
const isCreditPaymentInvalid = ref([]);
const isCreditPaymentInvalidError = ref([]);
const currentFileIndex = ref(0);
const oldTotalPayments = ref(0);
const isDiscountReasonError = ref(false);
const isCreditApprovalView = ref(false);
const isCreditCardView = ref(false);
const isDiscountError = ref(false);
const discountError = ref('');
const isTotalPriceUpdated = ref(false);
const trashedFilesModal = ref([]);
const isApproveNotChecked = ref(true);
const isCreditApprovalAllowed = ref(true);
const isVerificationAllowed = ref(true);
const isPaymentFrequencyNotSelected = ref(false);
const isDiscountAllowed = ref(true);
const isSplitAmountInvalid = ref([]);
const isSplitAmountInvalidError = ref([]);
const isCollectedByEnabled = ref(false);
const premiumToCapture = ref(0);
const insurerPaymentLinkChanged = ref(false);
const confirmModalClose = ref(false);
const insurerPaymentComponent = ref(null);
const homePlanText = ref();
const lifePlanText = ref();
const paymentForm = ref();

const totalPayments = ref([{ value: '1', label: '1' }]);
const isApproveLowerAmountConfirmed = ref(true);
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
// Define frequency types
const frequencyTypes = ref(
  paymentLookups.paymentFrequencyTypes.map(item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  })),
);

const emit = defineEmits([
  'cancel-modal',
  'aml-verification',
  'update-plan-detail',
]);

const isCarQuote = props.quoteType === quoteTypeCodeEnum.Car;
const isTravelQuote = props.quoteType === quoteTypeCodeEnum.Travel;

const familyEmployeDiscount = [
  quoteTypeCodeEnum.Car,
  quoteTypeCodeEnum.Health,
  quoteTypeCodeEnum.Home,
  quoteTypeCodeEnum.Travel,
  quoteTypeCodeEnum.SAVINGS,
];

const documentForm = useForm({
  quote_id: props.quoteRequest.id || null,
  quote_uuid: props.quoteRequest.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

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
  payment_no: '0',
});

const insurerPaymentLinkIndex = computed(() => {
  var insurerPaymentIndex = paymentMethodsModels.value.findIndex(item => {
    return item === page.props.paymentMethodsEnum?.InsurerPaymentLink;
  });
  return insurerPaymentIndex;
});

// Here we define the computed properties
const isisUpfrontFrequency = computed(
  () => paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT,
);

const isPaymentMethodEnabled = computed(() => {
  return (
    isCreditApprovalApplied.value &&
    isCustomFrequency.value &&
    isSinglePayment.value
  );
});

const isCustomFrequency = computed(
  () => paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM,
);
const isSinglePayment = computed(() => paymentMethodsForm.payment_no == 1);

const isCreditApprovalApplied = computed(
  () => paymentMethodsForm.credit_approval !== '',
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

// Define a computed property to calculate the initial total price without VAT
const initialTotalPriceWithoutVat = computed(() => {
  if (props.quoteType === quoteTypeCodeEnum.Health) {
    return props.eCommercePriceWithLP; // premium with loading price,excluding vat
  }
  const vatRate = vatValue ? vatValue / 100 : 0;
  return props.totalPrice / (1 + vatRate);
});

// Define payment decline reasons
const declinedReasons = paymentLookups.paymentDeclineReasons.map(item => ({
  value: item.id,
  label: item.text,
}));
declinedReasons.unshift({ value: '', label: 'Select Reason' });

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

const isProformaPaymentRequest = computed(() => {
  return (
    paymentMethodsForm.payment_method ===
    page.props.paymentMethodsEnum?.ProformaPaymentRequest
  );
});

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

const transactionActionText = computed(() => {
  if (paymentMethodsForm.approvalModal === 'child') {
    return 'PAYMENT VERIFICATION';
  } else if (isCreditApprovalView.value && isCreditCardView.value) {
    return 'CAPTURE TRANSACTION';
  } else {
    return 'APPROVE TRANSACTION';
  }
});

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

const addPayment = isValid => {
  emit('close-insurer-receipt-number-exists-modal');
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
    isApproveLowerAmountConfirmed.value = true;
  } else if (paymentMethodsForm.status !== 'view') {
    if (validatePaymentOption()) return;
    if (props.isPaidEditable === true) {
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

  let data = {
    code: paymentMethodsForm.payment_method,
    modelType: props.quoteType,
    quote_id: props.quoteRequest.id,
    plan_id: props.planDetail?.id ?? null, // handling null exception when plan is not found
    captured_amount: paymentMethodsForm.amount,
    insurance_provider_id: providerId.value,
    sendFTCEmail: insurerPaymentLinkChanged?.value ?? false,
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
    total_amount: props.totalAmount, // after discount calculation
    total_price: props.totalPrice,
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
      plan_id: props.planDetail?.id || 0,
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
            // createPaymentModal.value = false;
            emit('update-create-payment-modal', false);
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
      plan_id: props.planDetail?.id || 0,
      customer_id: props.quoteRequest.customer_id,
      collection_amount: paymentMethodsForm.collection_amount,
      actual_amount: parseFloat(
        splitAmountModels.value?.[splitPaymentNo.value],
      ),
      bank_reference_number: paymentMethodsForm.bank_reference_number,
      splitPaymentId: paymentMethodsForm.splitPaymentId,
      is_declined: isDeclineClicked.value,
      is_approved: isApproveClicked.value,
      declined_reason: paymentMethodsForm.declined_reason,
      approved_document_model: approvedDocumentModel.value,
      declined_custom_reason: declinedCustomReason,
      send_update_id: props.sendUpdate?.id || null,
      collection_type: paymentMethodsForm.collection_type,
      insurer_receipt_number: paymentMethodsForm.insurer_receipt_number,
    };

    paymentMethodsForm
      .transform(data => viewData)
      .post('/payments/' + props.quoteType + '/split-payment-approve-decline', {
        preserveScroll: true,
        onSuccess: () => {
          // createPaymentModal.value = false;
          emit('update-create-payment-modal', false);
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
      props.isPaidEditable === false
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
      isPaidEditable: props.isPaidEditable,
    };
    paymentMethodsForm
      .transform(data => editData)
      .post('/payments/' + props.quoteType + '/update-new', {
        preserveScroll: true,
        onSuccess: () => {
          // createPaymentModal.value = false;
          emit('update-create-payment-modal', false);
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
        // createPaymentModal.value = false;
        emit('update-create-payment-modal', false);
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

const providerId = computed(() => {
  const plan = props.planDetail;
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
  const plan = props.planDetail;
  if (props.quoteType == quoteTypeCodeEnum.Home) {
    return props.quoteRequest.insurance_provider?.text || 'Not Available';
  }
  if (props.quoteType == quoteTypeCodeEnum.SAVINGS) {
    return props.quoteRequest.insurance_provider?.text || 'Not Available';
  }
  const ecomQuoteType = [...props.quoteTypesToCheck, quoteTypeCodeEnum.Bike];
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
        parseFloat(props.totalAmount).toFixed(2) ||
      discountValue.value > 0
    ) {
      isPaymentCalculationError.value = false;
    } else {
      isPaymentCalculationError.value = true;
      issueFound = true;
    }
  } else if (
    totalSplitAmount.toFixed(2) !== parseFloat(props.totalAmount).toFixed(2)
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
    if (parseFloat(discountValue.value) > parseFloat(props.totalPrice)) {
      issueFound = true;
      isDiscountError.value = true;
      // totalAmount.value = totalPrice.value;
      emit('update-total-amount', props.totalPrice);
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
  // isGalleryModelOpen.value = false;
  emit('update-gallery-model-open', false);
  authorizedPayments.value = [];
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
  // isAmlApprovalRequired.value = false;
  emit('update-is-aml-approval-required', false);
  isApproveLowerAmountConfirmed.value = true;
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
  // Special handling for life quotes - map payment term to frequency during edit
  if (
    props.quoteType === quoteTypeCodeEnum.Life &&
    props.quoteRequest?.life_quote?.payment_term
  ) {
    // Map numeric payment term to frequency enum
    const paymentTermToFrequency = {
      12: paymentFrequencyEnum.MONTHLY,
      4: paymentFrequencyEnum.QUARTERLY,
      2: paymentFrequencyEnum.SEMI_ANNUAL,
      1: paymentFrequencyEnum.UPFRONT,
    };
    paymentMethodsForm.frequency =
      paymentTermToFrequency[props.quoteRequest?.life_quote?.payment_term] ||
      paymentFrequencyEnum.UPFRONT;
  } else {
    paymentMethodsForm.frequency = payment.frequency;
  }
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
    let checkCondition = isCarQuote || isTravelQuote || !isIPLPermission;
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

const handleDeclinedReasonChange = () => {
  if (getCustomReasonIndex(paymentMethodsForm.declined_reason) === 6) {
    isDeclineCustomReason.value = true;
  } else {
    isDeclineCustomReason.value = false;
  }
};

const calculateTotalAmount = async (avoidDelay = false) => {
  const discount = discountValue.value;
  if (
    discount > 50 &&
    paymentMethodsForm.discount_reason === 'refer_a_friend'
  ) {
    // totalAmount.value = totalPrice.value;
    emit('update-total-amount', props.totalPrice);
  } else {
    // totalAmount.value = totalPrice.value - discount;
    emit('update-total-amount', props.totalPrice - discount);
  }
  if (avoidDelay) {
    await new Promise(resolve => setTimeout(resolve, 200));
  }
  calculatePaymentBreakup(false);
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

const processPaymentSplits = payment => {
  const paidStatusIds = [
    paymentStatusEnum.PAID,
    paymentStatusEnum.PARTIALLY_PAID,
    paymentStatusEnum.AUTHORISED,
    paymentStatusEnum.CAPTURED,
    paymentStatusEnum.PARTIAL_CAPTURED,
  ];

  for (let i = 1; i <= payment.total_payments; i++) {
    const split = payment.payment_splits[i - 1];
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
    // totalPrice.value = payment.total_price;
    emit('update-total-price', payment.total_price);
    // totalAmount.value = payment.total_price - payment.discount_value;
    emit('update-total-amount', payment.total_price - payment.discount_value);
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
      isTotalPriceUpdated.value = props?.isLackingPayment;
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
      let planDetailValue =
        payment?.travel_plan ??
        props.sendUpdate?.travel_plan ??
        props.quoteRequest?.plan ??
        null;
      if (!(props.quoteRequest.insly_migrated || props.quoteRequest.insly_id)) {
        planDetailValue['insurance_provider'] =
          payment?.travel_plan?.insurance_provider ??
          props.sendUpdate?.insurance_provider;
      }
      emit('update-plan-detail', planDetailValue);
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

  // createPaymentModal.value = true;
  emit('update-create-payment-modal', true);
};

const setFrequencyTypes = () => {
  let allFrequencyTypes = paymentLookups.paymentFrequencyTypes.map(item => ({
    value: item.code,
    label: item.text,
    tooltip: item.description,
  }));
  frequencyTypes.value = allFrequencyTypes;
};

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
  // totalAmount.value = totalPrice.value;
  emit('update-total-amount', props.totalPrice);
  discountValue.value = 0;
  paymentMethodsForm.discount_reason = '';
  if (callDiscountChang) {
    handleDiscountChange();
  }
  handleDiscountReasonChange();
  calculateTotalAmount();
};

const handleDeclinedChange = () => {
  isDeclineClicked.value = true;
  isApproveClicked.value = false;
  isDeclineCustomReason.value = false;
  isApproveNotChecked.value = false;
  handleDeclinedReasonChange();
  return true;
};

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

const calculatePaymentBreakup = (changeMethod = true) => {
  isDocumentNotUploaded.value = [];
  isDowngradeFrequencyError.value = false;
  var perInstallmentPrice = parseFloat(
    (
      (props.totalAmount - paidAmountSum.value) /
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
        only: props.sendUpdate ? ['quoteDocuments'] : ['quote'],
        onError: errors => {
          documentForm.setError(errors.error);
          notification.error({
            title: 'File upload failed',
            position: 'top',
          });
          reject(errors);
        },
        onSuccess: data => {
          let quoteTypes = props.quoteTypesToCheck.filter(
            quoteType =>
              quoteType !== quoteTypeCodeEnum.Home &&
              quoteType !== quoteTypeCodeEnum.Life,
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
  // isGalleryModelOpen.value = true;
  emit('update-gallery-model-open', true);
};

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

const handleCancelChanges = () => {
  isDeclineClicked.value = !isDeclineClicked.value;
  isApproveClicked.value = false;
  isDeclinedReasonError.value = false;
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

/**
 * Determines if the user is allowed to approve a lower payment amount.
 * Approval is allowed if:
 * - There is a sendUpdate and its status is UPDATE_BOOKED, or
 * - There is no sendUpdate and the quote status is PolicyBooked, CancellationPending, PolicyCancelledReissued, or PolicyCancelled.
 *
 * @returns {boolean}
 */
const isAllowedToApproveLowerAmount = () => {
  const isUpdateBooked =
    props.sendUpdate &&
    props.sendUpdate.status === props.sendUpdateStatusEnum?.UPDATE_BOOKED;

  const isPolicyBooked =
    !props.sendUpdate &&
    [
      page.props.quoteStatusEnum?.PolicyBooked,
      page.props.quoteStatusEnum?.CancellationPending,
      page.props.quoteStatusEnum?.PolicyCancelledReissued,
      page.props.quoteStatusEnum?.PolicyCancelled,
    ].includes(props.quoteRequest?.quote_status_id);

  return isUpdateBooked || isPolicyBooked;
};

const validateViewPayment = isValid => {
  let amountExceeded = false;
  if (
    parseFloat(splitAmountModels.value[splitPaymentNo.value]) >
    parseFloat(paymentMethodsForm.collection_amount)
  ) {
    if (
      isAllowedToApproveLowerAmount() &&
      can(permissionEnum.PAYMENT_VERIFICATION_LOWER_AMOUNT)
    ) {
      isApproveLowerAmountConfirmed.value = false;
    } else {
      approveErrorMessage.value =
        'The entered amount is smaller than the total amount.';
      isApprovePaymentError.value = true;
      return true;
    }
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

// use in insurer payment link
const updateFromInsurerPaymentLink = (
  closePaymentModal = false,
  paymentLinkChanged = false,
  closeModal = false,
) => {
  closePaymentModal == true &&
    emit('update-create-payment-modal', !props?.createPaymentModal);
  confirmModalClose.value = closeModal;
  insurerPaymentLinkChanged.value = paymentLinkChanged;
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

const resetTotalPayments = () => {
  totalPayments.value = [];
  for (let i = 1; i <= 20; i++) {
    totalPayments.value.push({ value: i.toString(), label: i.toString() });
  }
};

const getPlanName = computed(() => {
  const plan = props.planDetail;
  if (props.quoteType === quoteTypeCodeEnum.Bike) {
    return plan ? props.quoteRequest.car_plan.text : 'Not Available';
  }
  if (props.sendUpdate) {
    return props.planText || 'Not Available';
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

  if (
    !props.isLifePlanDetailsEnabled &&
    props.quoteType === quoteTypeCodeEnum.Life
  ) {
    if (props.quoteRequest?.insurance_provider_plan?.text && plan) {
      lifePlanText.value = props.quoteRequest.insurance_provider_plan.text;
    }
    return lifePlanText.value || 'Not Available';
  }

  return props.quoteTypesToCheck.includes(props.quoteType) && plan
    ? plan.text
    : 'Not Available';
});

const getCustomReasonIndex = value => {
  const index = declinedReasons.findIndex(reason => reason.value === value);
  return index !== -1 ? index : null;
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

const submitPaymentForm = () => {
  paymentForm.value?.$el?.requestSubmit();
};

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

// Close confirmation modal function remains unchanged
const closeConfirmModal = () => {
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
  isApproveConfirm.value = false;
  isApproveLowerAmountConfirmed.value = true;
};

const closeApprovalLowerAmountModal = () => {
  isApproveConfirmed.value = false;
  isApproveNotChecked.value = true;
  isApproveConfirm.value = false;
  isApproveLowerAmountConfirmed.value = true;
};

const updatePaymentForm = (updates = {}) => {
  Object.entries(updates).forEach(([key, value]) => {
    paymentMethodsForm[key] = value;
  });
};

const resetPaymentMethodsForm = () => {
  paymentMethodsForm.reset();
};

const resetPaymentMethodsModal = () => {
  paymentMethodsModels.value = [];
};

const resetFileUploadModals = () => {
  fileUploadModels.value = [];
};

const resetIsPaymentCalculationError = () => {
  isPaymentCalculationError.value = false;
};

const resetIsDiscountEnabled = () => {
  isDiscountEnabled.value = false;
};

const resetIsDiscountReasonEnabled = () => {
  isDiscountReasonEnabled.value = false;
};

const resetShowDiscountOptions = () => {
  showDiscountOptions.value = true;
};

const resetDiscountDocumentModel = () => {
  discountDocumentModel.value = [];
};

const resetIsDiscountDocumentNotUploaded = () => {
  isDiscountDocumentNotUploaded.value = false;
};

const updateTotalPayments = value => {
  totalPayments.value = value;
};

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

const resetIsPaymentMetodNotSelected = () => {
  isPaymentMetodNotSelected.value[1] = false;
};

const resetIsDocumentNotUploaded = () => {
  isDocumentNotUploaded.value = [];
};

const resetSplitAmountModels = () => {
  splitAmountModels.value = [];
};

const resetDueDateModels = () => {
  dueDateModels.value = [];
};

const resetCheckDetailModels = () => {
  checkDetailModels.value = [];
};

const resetIsDiscountReasonError = () => {
  isDiscountReasonError.value = false;
};

const resetIsDiscountError = () => {
  isDiscountError.value = false;
};

const resetDiscountError = () => {
  discountError.value = '';
};

const updatePremiumToCapture = value => {
  premiumToCapture.value = value;
};

// Expose functions and properties
defineExpose({
  resetPaymentMethodsForm,
  resetPaymentForm,
  handleCollectionTypeChange,
  calculatePaymentBreakup,
  applyPermissions,
  initializePaymentForm,
  handleCollectionTypeChange,
  handleFrequencyChange,
  handleApprovalReasonChange,
  handleDiscountChange,
  handleDeclinedReasonChange,
  calculateTotalAmount,
  applyPermissions,
  processPaymentSplits,
  finalizePaymentForm,
  setFrequencyTypes,
  paymentMethodsForm,
  isApproveConfirmed,
  isViewEnabled,
  filesTest,
  currentFileIndex,
  isCreditApprovalView,
  isCreditCardView,
  resetPaymentMethodsModal,
  resetSplitAmountModels,
  resetDueDateModels,
  resetFileUploadModals,
  resetCheckDetailModels,
  resetIsDiscountReasonEnabled,
  resetIsDiscountEnabled,
  resetIsPaymentCalculationError,
  resetShowDiscountOptions,
  resetIsDiscountReasonError,
  resetIsPaymentMetodNotSelected,
  resetIsDocumentNotUploaded,
  resetIsDiscountError,
  resetDiscountError,
  resetIsDiscountDocumentNotUploaded,
  resetDiscountDocumentModel,
  isBrokerHavePermission,
  updateTotalPayments,
  updatePaymentForm,
  updatePremiumToCapture,
  submitPaymentForm,
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
// Watch for changes in the modal's state
watch(props.createPaymentModal, async (newVal, oldVal) => {
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
        // createPaymentModal.value = true;
        emit('update-create-payment-modal', true);
        await nextTick();
        if (insurerPaymentComponent.value) {
          insurerPaymentComponent.value.closeNotification();
        }
      }
      return;
    }
  }
});
</script>

<template>
  <x-form @submit="addPayment" :auto-focus="false" ref="paymentForm">
    <PaymentFormFields
      :isFieldReadonly="isFieldReadonly"
      :paymentMethodsForm="paymentMethodsForm"
      :rules="rules"
      :totalPrice="totalPrice"
      :collectionTypes="collectionTypes"
      :isCollectedByEnabled="isCollectedByEnabled"
      :insuranceProviderName="providerName"
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
      :insurerPaymentLinkIndex="insurerPaymentLinkIndex"
      :paymentMethodsModels="paymentMethodsModels"
      :payments="payments"
      :paymentStatusEnum="paymentStatusEnum"
      :quoteType="quoteType"
      :quoteTypeCodeEnum="quoteTypeCodeEnum"
      :isLifePlanDetailsEnabled="isLifePlanDetailsEnabled"
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
      @handle-discount-value-change="value => (discountValue = value)"
      @validate-insurer-payment-link="validateInsurerPaymentLink"
    />

    <x-divider class="mb-4 mt-10" />

    <PaymentFormAlerts
      :isDowngradeFrequencyError="isDowngradeFrequencyError"
      :isPaymentCalculationError="isPaymentCalculationError"
      :isPaymentLocked="isPaymentLocked"
      :paymentMethodsForm="paymentMethodsForm"
      :fileErrorMessage="fileErrorMessage"
      :isFileError="isFileError"
    />

    <PaymentFormScheduleTable
      :isViewEnabled="isViewEnabled"
      :isCreditApprovalView="isCreditApprovalView"
      :isVerifiedEnabled="isVerifiedEnabled"
      :isPaymentMethodEnabled="isPaymentMethodEnabled"
      :isPaidEditable="isPaidEditable"
      :isPaymentLocked="isPaymentLocked"
      :isCreditCardView="isCreditCardView"
      :isFieldReadonly="isFieldReadonly"
      :paymentMethodsForm="paymentMethodsForm"
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
      :documentForm="documentForm"
      :rules="rules"
      :capturePaymentValidationErrorMessage="
        capturePaymentValidationErrorMessage
      "
      :paymentTypes="paymentTypes"
      :paymentTypesFiltered="paymentTypesFiltered"
      :isMultiPaymentsEnabled="isMultiPaymentsEnabled"
      :quoteType="quoteType"
      :sendUpdate="sendUpdate"
      :payments="payments"
      :isCCEnabled="isCCEnabled"
      :quoteRequest="quoteRequest"
      :sendUpdateStatusEnum="sendUpdateStatusEnum"
      :selectedPaymentForEdit="selectedPaymentForEdit"
      @upload-document="uploadDocument"
      @delete-document="deleteDocument"
      @open-inner-modal="openInnerModal"
      @handle-payment-options="handlePaymentOptions"
    />

    <x-divider class="mb-4 mt-1" />

    <PaymentFormNotes
      :isViewEnabled="isViewEnabled"
      :isFieldReadonly="isFieldReadonly"
      :isCreditApprovalView="isCreditApprovalView"
      :paymentMethodsForm="paymentMethodsForm"
    />

    <PaymentFormVerified
      :splitPaymentRecord="splitPaymentRecord"
      :paymentMethodsForm="paymentMethodsForm"
      :paymentMethodsModels="paymentMethodsModels"
      :splitPaymentNo="splitPaymentNo"
    />

    <PaymentFormDecline
      :paymentMethodsForm="paymentMethodsForm"
      :rules="rules"
      :isDeclineClicked="isDeclineClicked"
      :isViewEnabled="isViewEnabled"
      :isCreditApprovalView="isCreditApprovalView"
      :isDeclinedReasonError="isDeclinedReasonError"
      :declinedReasons="declinedReasons"
      :isDeclineCustomReason="isDeclineCustomReason"
      @handle-declined-reason-change="handleDeclinedReasonChange"
    />

    <PaymentFormVerification
      :isViewEnabled="isViewEnabled"
      :isApproveClicked="isApproveClicked"
      :paymentMethodsModels="paymentMethodsModels"
      :splitPaymentNo="splitPaymentNo"
      :paymentMethodsForm="paymentMethodsForm"
      :isApprovePaymentError="isApprovePaymentError"
      :approveErrorMessage="approveErrorMessage"
      :approveProofDocument="approveProofDocument"
      :documentForm="documentForm"
      :isApprovedDocumentNotUploaded="isApprovedDocumentNotUploaded"
      :approvedDocumentModel="approvedDocumentModel"
      :readOnlyPayments="readOnlyPayments"
      :showInsurerReceiptNumberInputField="showInsurerReceiptNumberInputField"
      :rules="rules"
      @upload-document="uploadDocument"
      @open-inner-modal="openInnerModal"
      @delete-document="deleteDocument"
    />

    <x-divider class="mb-4 mt-1" />

    <PaymentFormFooter
      :isViewEnabled="isViewEnabled"
      :isCreditApprovalView="isCreditApprovalView"
      :isCreditCardView="isCreditCardView"
      :splitPaymentNo="splitPaymentNo"
      :paymentMethodsModels="paymentMethodsModels"
      :splitPaymentRecord="splitPaymentRecord"
      :paymentStatusEnum="paymentStatusEnum"
      :permissionEnum="permissionEnum"
      :paymentMethodsEnum="paymentMethodsEnum"
      :isDeclineClicked="isDeclineClicked"
      :isApproveClicked="isApproveClicked"
      :isVerificationAllowed="isVerificationAllowed"
      :isProformaPaymentRequest="isProformaPaymentRequest"
      :isTransactionCaptureButtonEnabled="isTransactionCaptureButtonEnabled"
      :isApproveConfirmed="isApproveConfirmed"
      :processing="paymentMethodsForm.processing"
      :formStatus="paymentMethodsForm.status"
      :quoteRequest="props.quoteRequest"
      :quoteType="props.quoteType"
      :payments="props.payments"
      :insurerPaymentLinkIndex="insurerPaymentLinkIndex"
      :paymentMethodsForm="paymentMethodsForm"
      :insurerReceiptNumberCheckInProcess="insurerReceiptNumberCheckInProcess"
      @cancel="handleCancelChanges"
      @decline="handleDeclinedChange"
      @approve="isApproveClicked = !isApproveClicked"
      @cancel-modal="emit('cancel-modal')"
      @aml-verification="emit('aml-verification')"
      @update-from-insurer-payment-link="
        (e, f, g) => updateFromInsurerPaymentLink(e, f, g)
      "
      @check-insurer-receipt-number="emit('check-insurer-receipt-number')"
    />
    <div
      class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
      v-if="isApproveConfirmed && !isApproveLowerAmountConfirmed"
    >
      <div
        class="modal-confirm-container bg-white w-[400px] max-w-[60%] md:max-h-[37vh] lg:max-h-[27vh] p-5 rounded-lg shadow-lg"
      >
        <div class="modal-confirm-header text-base text-white bg-white">
          <div
            class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
          >
            <div class="flex items-center space-x-2">Confirm Approval</div>
            <div class="flex items-center space-x-2">
              <span
                @click="closeApprovalLowerAmountModal"
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
            <div class="text-left text-sm">
              <span
                >The entered amount is less than the total amount. Do you want
                to proceed with approval?</span
              >
            </div>
          </div>
          <div class="flex flex-row space-x-4 mt-4">
            <x-button
              size="md"
              type="submit"
              color="orange"
              class="px-4 py-2"
              @click="isApproveLowerAmountConfirmed = true"
            >
              <span>Yes</span>
            </x-button>
            <x-button
              size="md"
              type="submit"
              color="gray"
              class="px-4 py-2"
              @click="closeApprovalLowerAmountModal"
            >
              <span>No</span>
            </x-button>
          </div>
        </div>
      </div>
    </div>
    <div
      class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
      v-if="isApproveConfirmed && isApproveLowerAmountConfirmed"
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
              <span v-if="paymentMethodsForm.collection_type === 'insurer'"
                >I certify that all details provided, including the official
                receipt or payment confirmation, are correct and in compliance
                with our conduct standards.</span
              >
              <span v-if="paymentMethodsForm.collection_type === 'broker'"
                >I verify that the information provided is accurate and my
                actions align with our standards of conduct.</span
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
              <span>{{ paymentTooltipEnum.CONFIRM_APPROVE_UNSELECT }}</span>
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
    <div
      class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
      v-if="isInsurerReceiptNumberExistsModalOpen"
    >
      <div
        class="modal-confirm-container receipt-number-exists-modal-container bg-white w-full max-w-full overflow-hidden rounded-lg"
      >
        <div class="modal-confirm-header text-base text-white bg-white">
          <div
            class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
          >
            <div class="flex items-center space-x-2">
              Duplicate Insurer Receipt Number Detected
            </div>
            <div class="flex items-center space-x-2">
              <span
                @click="emit('close-insurer-receipt-number-exists-modal')"
                class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
              >
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
            <div class="text-left text-md">
              <span class="text-sm">
                The insurer receipt number you entered already exists in the
                system. Do you want to proceed with using the same receipt
                number again?
              </span>
            </div>
          </div>
          <div class="w-full mt-4 flex justify-center gap-4">
            <x-button
              size="lg"
              @click="emit('close-insurer-receipt-number-exists-modal')"
              class="px-6 py-2 bg-white text-orange-600 border border-orange-500 hover:bg-orange-50"
            >
              <span>No</span>
            </x-button>

            <x-button size="lg" type="submit" color="orange" class="px-6 py-2">
              <span>Yes</span>
            </x-button>
          </div>
        </div>
      </div>
    </div>
  </x-form>
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
