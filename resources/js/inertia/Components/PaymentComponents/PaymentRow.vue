<script setup>
import { computed } from 'vue';
import moment from 'moment';
import { usePayment } from '../../Composables/usePayment';
import { useAMLKYC } from '../../Composables/useAMLKYC';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentGatewayEnum = page.props.paymentGatewayEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;

const { formatDate,
   formatAmount,
   formatString,
   filterCCPayments,
   getCaptureValidStatuses,
   filterCAPayments,
   verifyCreditApproved,
   hasAnyCCSplitPayment
  } = usePayment();

const { isAmlVerified, isKycVerified, isInsurerAmlVerified } = useAMLKYC();

const props = defineProps({
  quoteRequest: Object,
  payments: Array,
  payment: Object,
  index: Number,
  isExpanded: Boolean,
  isChildPaymentDeletable: Boolean,
  isLackingPayment: Boolean,
  isApproveConfirmed: Boolean,
  capturePaymentValidationInProcess: Boolean,
  quoteType: String,
  isFuncsEnabled: {
    type: Array,
    default: [],
  },
  bookPolicyDetails: {
    type: Array,
    default: [],
  },
  sendUpdate: {
    type: Object,
    default: null,
  },
  isCapBtnEnabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'edit-payment',
  'delete-payment',
  'capture-payment',
  'approve-payment',
  'void-payment',
  'alert-capture',
  'open-aml-verification'
]);

const editPayment = () => {
  emit('edit-payment', props.payment, 0, 0, 0);
};

const deletePayment = () => {
  emit('delete-payment', props.payment);
};

const capturePayment = () => {
  emit('capture-payment', props.payment, 0, 0, 1);
};

const approvePayment = () => {
  if (!props.sendUpdate && (!isAmlVerified(props.quoteRequest, props.quoteType, props.payments) || !isKycVerified(props.quoteRequest, props.quoteType, props.payments))) {
    emit('open-aml-verification');
  } else {
    const isValid = getCaptureValidation.value;
    if (isValid) {
      emit('edit-payment', props.payment, 0, 0, 2);
    } else {
      emit('alert-capture', props.payment);
    }
  }
};

const voidPayment = () => {
  emit('void-payment', props.payment);
};

// Add can function for permission checks
const can = permission => useCan(permission);

const getCaptureOption = computed(() => {
  const payment = props.payment;
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
    (isCaptureButtonEnabled && isCreditCardPayment)
  ) {
    return 'capture';
  }
  return 'approve';
});
/**
 * Validates if payment can be captured based on payment type and status
 * Returns true if payment is valid for capture, false otherwise
 */
const getCaptureValidation = computed(() => {
  const payment = props.payment;
  if (shouldProcessUpdate()) {
    const isTravelQuote = props.quoteType == quoteTypeCodeEnum.Travel;
    const isInsurerApiStatus = props.quoteRequest.insurer_api_status;
    const isAllianceProvider = props.isAllianceProvider;
    const ispaymentApproveCapture = isTravelQuote && isAllianceProvider && isInsurerApiStatus == null;

    if (payment.is_approved === 1 || ispaymentApproveCapture) return false;
    
    let paymentRecord = payment;
    if (paymentRecord.frequency === paymentFrequencyEnum.UPFRONT) {
      return validateUpfrontCapture(paymentRecord);
    } else if (
      paymentRecord.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
    ) {
      return validateSplitPaymentsCapture(paymentRecord);
    } else {
      return validateNonUpfrontAndSplitCapture(paymentRecord);
    }
  }
  return false;
});

// Will check if the payment is ready for capture
const shouldProcessUpdate = () => {
  const payment = props.payment;
  const totalPriceRounded = Math.round(payment.total_price * 100) / 100;
  const calculatedTotal =
    Math.round((payment.total_amount + payment.discount_value) * 100) / 100;
  const hasPayments = props.payments.length > 0;
  const isTotalPriceMatching = totalPriceRounded === calculatedTotal;
  
  // Check if it's a renewal upload condition
  if (props.isCapBtnEnabled &&
      props.quoteType === quoteTypeCodeEnum.Car &&
      (page.props?.bookPolicyDetails?.isGIGProvider || false) &&
      isAmlVerified(props.quoteRequest, props.quoteType, props.payments) &&
      isKycVerified(props.quoteRequest, props.quoteType, props.payments) &&
      isTotalPriceMatching &&
      hasAnyCCSplitPayment(payment) &&
      !props.sendUpdate &&
      hasPayments &&
      payment?.collection_type == 'insurer') {
    return true;
  }
  
  // Check if capture option is approve
  const captureOption = getCaptureOption.value;
  if (captureOption === 'approve') {
    return hasPayments;
  }

  // All other cases
  return (
    hasPayments &&
    isTotalPriceMatching &&
    // Check if AML & KYC verification and insurer AML are complete
    (isAmlVerified(props.quoteRequest, props.quoteType, props.payments) || props.quoteType === quoteTypeCodeEnum.Travel || props.sendUpdate) &&
    isInsurerAmlVerified(props.quoteRequest, props.quoteType, props.payments)
  );
};

// Validate the upfront capture logic
const validateUpfrontCapture = paymentRecord => {
  let paymentSplitRec = paymentRecord.payment_splits[0];
  if (paymentSplitRec.payment_method.code === 'CC')
    return getCaptureValidStatuses(paymentSplitRec);
  const isIPPending =
    paymentSplitRec.payment_method.code === 'IP' &&
    paymentSplitRec.payment_status_id === paymentStatusEnum.PENDING;
  const isCAPayment =
    paymentSplitRec.payment_method.code === 'CA' &&
    paymentSplitRec.payment_status_id === paymentStatusEnum.CREDIT_APPROVED;
  const isPaidPayment =
    paymentSplitRec.payment_status_id === paymentStatusEnum.PAID;
  return isIPPending || isCAPayment || isPaidPayment;
};

// Check if the void payment is enabled based on the payment status and payment gateway
const isVoidPaymentEnabled = payment => {
  return (
    props.isFuncsEnabled.tapIntegration &&
    can(permissionEnum.PAYMENTS_VOID) &&
    payment.payment_status_id === paymentStatusEnum.AUTHORISED &&
    payment.payment_gateway_id === paymentGatewayEnum.PAYMENT_GATEWAY_TAP
  );
};

// Validate the split payments capture 
const validateSplitPaymentsCapture = paymentRecord => {
  const paymentMethodCC = filterCCPayments(paymentRecord);
  const creditApprovedPayments = filterCAPayments(paymentRecord);
  if (paymentMethodCC.length > 0 && creditApprovedPayments.length == 0) {
    let totalSplitPayments = paymentRecord.payment_splits.length;
    let paidPaymentStatus = paymentRecord.payment_splits.filter(
      item =>
        item.payment_status_id === paymentStatusEnum.PAID ||
        item.payment_status_id === paymentStatusEnum.PARTIALLY_PAID,
    );
    let ccPaymentStatus = paymentMethodCC.filter(
      item => item.payment_status_id === paymentStatusEnum.AUTHORISED,
    );
    return (
      totalSplitPayments == ccPaymentStatus.length + paidPaymentStatus.length
    );
  } else {
    let ipPaymentStatus = paymentRecord.payment_splits.filter(
      item => item.payment_method.code === 'IP',
    );
    if (ipPaymentStatus.length > 0) {
      let ipPending = ipPaymentStatus.filter(
        item =>
          item.payment_status_id === paymentStatusEnum.PENDING ||
          item.payment_status_id === paymentStatusEnum.PAID,
      );
      return ipPending.length === ipPaymentStatus.length;
    } else {
      if (verifyCreditApproved(paymentRecord)) return true;
      let paidPaymentStatus = paymentRecord.payment_splits.filter(
        item => item.payment_status_id === paymentStatusEnum.PAID,
      );
      return paidPaymentStatus.length === paymentRecord.payment_splits.length;
    }
  }
};

// Validate the non upfront and split capture 
const validateNonUpfrontAndSplitCapture = paymentRecord => {
  if (paymentRecord.payment_status_id === paymentStatusEnum.CREDIT_APPROVED) {
    if (verifyCreditApproved(paymentRecord)) return true;
  } else if (
    (paymentRecord.payment_splits[0].payment_method.code === 'IP' ||
      paymentRecord.payment_splits[0].payment_method.code === 'PDC') &&
    paymentRecord.payment_splits[0].payment_status_id ===
      paymentStatusEnum.PENDING
  ) {
    return true;
  }
  return getCaptureValidStatuses(paymentRecord.payment_splits[0]);
};

// Disable the main payment approval if the sendUpdate is true or if the AML status is failed
const disableMainPaymentApproval = computed(() => {
  if (props.sendUpdate) {
    return false;
  }
  let isAmlFailed =
    props.quoteRequest.aml_status ===
    page.props.amlStatusEnum.AMLScreeningFailed;
  if (isAmlFailed) {
    return true;
  }

  return (
    isAmlVerified(props.quoteRequest, props.quoteType, props.payments) &&
    (!isKycVerified(props.quoteRequest, props.quoteType, props.payments) || !isInsurerAmlVerified(props.quoteRequest, props.quoteType, props.payments) || !isTotalAmountMismatched())
  );
});

// Check if the total price is mismatched based on the total amount and discount value
const isTotalAmountMismatched = () => {
  const totalPriceRounded =
    Math.round(props.payments[0]?.total_price * 100) / 100;
  const calculatedTotal =
    Math.round(
      (props.payments[0]?.total_amount + props.payments[0]?.discount_value) *
        100,
    ) / 100;

  return totalPriceRounded === calculatedTotal;
};

// AML & KYC tooltip message based on the status of the payment
const amlAndKycTooltip = computed(() => {
  if (!isAmlVerified(props.quoteRequest, props.quoteType, props.payments)) {
    return page.props.paymentTooltipEnum.PENDING_AML_CLEARANCE;
  } else if (!isInsurerAmlVerified(props.quoteRequest, props.quoteType, props.payments)) {
    return page.props.paymentTooltipEnum.PENDING_INSURER_AML_CLEARANCE;
  } else if (!isKycVerified(props.quoteRequest, props.quoteType, props.payments)) {
    return page.props.paymentTooltipEnum.PENDING_KYC_CLEARANCE;
  } else if (!isTotalAmountMismatched()) {
    return page.props.paymentTooltipEnum.TOTAL_AMOUNT_MISMATCHED;
  }
});


</script>

<template>
  <tr>
    <td class="text-center">
      <span class= "expand-pointer" @click="emit('toggle-expand', index)">
        {{ isExpanded ? '∧' : '∨' }}
      </span>
    </td>
    <td>{{ payment.code }}</td>
    <td>{{ formatDate(payment.collection_date) }}</td>
    <td>{{ formatDate(payment.payment_splits[0]?.due_date) }}</td>
    <td>{{ payment.payment_method?.name }}</td>
    <td>{{ formatAmount(payment.price_vat_applicable) }}</td>
    <td>{{ formatAmount(payment.price_vat) }}</td>
    <td>{{ formatAmount(payment.total_price) }}</td>
    <td>{{ formatAmount(payment.discount_value) }}</td>
    <td>{{ formatAmount(payment.total_amount) }}</td>
    <td>{{ formatAmount(payment.captured_amount) }}</td>
    <td>{{ formatString(payment.payment_status?.text) }}</td>
    <td>
      <x-tooltip placement="left">
        <span class="border-b border-dotted border-black">
          {{ payment.payment_allocation_status !== null
              ? formatString(payment.payment_allocation_status)
              : '' }}
        </span>
        <template #tooltip>
          <span class="custom-tooltip-content">
            {{ paymentAllocationStatusTooltip
                ? paymentAllocationStatusTooltip(payment.payment_allocation_status)
                : payment.payment_allocation_status }}
          </span>
        </template>
      </x-tooltip>
    </td>
    <td>
      <div class="flex gap-2">
        <template v-if="isLackingPayment">
          <x-tooltip placement="left">
            <x-badge
              size="xs"
              color="error"
              outlined
              offset-x="-8"
              offset-y="-10"
            >
              <x-button
                v-if="can(permissionEnum.PaymentsEdit)"
                size="xs"
                color="primary"
                outlined
                @click="editPayment"
              >
                Edit
              </x-button>
              <template #content>!</template>
            </x-badge>
            <template #tooltip>
              {{ isEditPaymentEnabled && isEditPaymentEnabled(payment)
                  ? paymentTooltipEnum.PAYMENT_TOTAL_PRICE_EXCEEDS_AUTHORISED_AMOUNT
                  : paymentTooltipEnum.PAYMENT_REVISED_ACTION_NEEDED }}
            </template>
          </x-tooltip>
        </template>
        <template v-else>
          <x-button
            v-if="can(permissionEnum.PaymentsEdit)"
            size="xs"
            color="primary"
            outlined
            @click="editPayment"
          >
            Edit
          </x-button>
        </template>
        <template v-if="index == 1 && isChildPaymentDeletable">
          <x-button
            size="xs"
            color="orange"
            outlined
            @click="deletePayment"
          >
            Delete
          </x-button>
        </template>
        <template v-if="can(permissionEnum.ApprovePayments) && 
                        (!isChildPaymentDeletable || index > 0)">
          <x-button
            v-if="getCaptureOption === 'capture' && getCaptureValidation"
            size="xs"
            color="orange"
            outlined
            @click="
              getCaptureValidation
                ? $emit('edit-payment', payment, 0, 0, 1)
                : $emit('alert-capture', payment)
            "
            :disabled="isApproveConfirmed"
            :loading="capturePaymentValidationInProcess"
          >
            Capture
          </x-button>

          <template v-if="disableMainPaymentApproval">
            <x-tooltip placement="right">
              <x-button
                v-if="getCaptureOption === 'approve' && getCaptureValidation"
                size="xs"
                color="orange"
                outlined
                :disabled="isApproveConfirmed || disableMainPaymentApproval"
              >
                Approve
              </x-button>
              <template #tooltip>
                <span>{{ amlAndKycTooltip }}</span>
              </template>
            </x-tooltip>
          </template>
          <template v-else>
            <x-button
              v-if="getCaptureOption === 'approve' && getCaptureValidation"
              size="xs"
              color="orange"
              outlined
              @click="
                  !props.sendUpdate &&
                  (!isAmlVerified(props.quoteRequest, props.quoteType, props.payments) || !isKycVerified(props.quoteRequest, props.quoteType, props.payments))
                    ? $emit('open-aml-verification')
                    : getCaptureValidation
                      ? $emit('edit-payment', payment, 0, 0, 2)
                      : alertCapture()
                "
              :disabled="isApproveConfirmed"
            >
              Approve
            </x-button>
          </template>
        </template>
        <template v-if="isVoidPaymentEnabled(payment)">
          <x-button
            size="xs"
            color="orange"
            outlined
            @click="voidPayment"
          >
            Void
          </x-button>
        </template>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.expand-pointer {
  cursor: pointer;
  font-size: 20px;
  font-weight: bold;
  color: #1d83bc;
}
</style> 