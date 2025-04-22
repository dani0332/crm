<script setup>
import { computed } from 'vue';
import moment from 'moment';
import { usePayment } from '../../Composables/usePayment';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;

const { formatDate, formatAmount, formatString, filterCCPayments } = usePayment();

const props = defineProps({
  payment: Object,
  index: Number,
  isExpanded: Boolean,
  isChildPaymentDeletable: Boolean,
  is_lacking_payment: Boolean,
  amlAndKycTooltip: String,
  disableMainPaymentApproval: Boolean,
  isApproveConfirmed: Boolean,
  capturePaymentValidationInProcess: Boolean
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
  if (!props.sendUpdate && (!isAmlVerified() || !isKycVerified())) {
    emit('open-aml-verification');
  } else {
    const isValid = props.getCaptureValidation?.(props.payment) ?? false;
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

// We're assuming these functions are provided or can be mocked
const isAmlVerified = () => true;
const isKycVerified = () => true;

// Add can function for permission checks
const can = (permission) => {
  return useCan ? useCan(permission) : true;
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
      (isCaptureButtonEnabled && isCreditCardPayment)
    ) {
      return 'capture';
    }
    return 'approve';
  };
});


const getCaptureValidation = computed(() => {
  return props.payment.captureValidation || false;
});

const isVoidPaymentEnabled = payment => {
  return (
    props.isFuncsEnabled.tapIntegration &&
    can(permissionEnum.PAYMENTS_VOID) &&
    payment.payment_status_id === page.props.paymentStatusEnum.AUTHORISED &&
    payment.payment_gateway_id === props.paymentGatewayEnum.PAYMENT_GATEWAY_TAP
  );
};

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
        <template v-if="is_lacking_payment">
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
            @click="capturePayment"
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
              @click="approvePayment"
              :disabled="isApproveConfirmed"
            >
              Approve
            </x-button>
          </template>
        </template>
        <template v-if="isVoidPaymentEnabled">
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