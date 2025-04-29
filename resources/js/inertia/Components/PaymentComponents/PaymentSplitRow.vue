<script setup>
import { computed } from 'vue';
import moment from 'moment';
import { usePayment } from '../../Composables/usePayment';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;

const {
  formatDate,
  formatAmount,
  formatString,
  paymentAllocationStatusTooltip,
} = usePayment();

const props = defineProps({
  splitPayment: Object,
  parentPayment: Object,
  splitIndex: Number,
  linkedQuoteDetails: Object,
  quoteRequest: Object,
  paymentMethodsForm: Object,
  sendUpdate: {
    type: Object,
    default: null,
  },
  sendUpdateStatusEnum: {
    type: Array,
    default: null,
  },
});

const emit = defineEmits([
  'view-payment',
  'generate-cc-link',
  'delete-split-payment',
  'retry-split-payment',
  'post-prepayment',
]);

// Add can function for permission checks
const can = permission => useCan(permission);

const viewPayment = () => {
  emit(
    'view-payment',
    props.parentPayment,
    props.splitPayment.id,
    props.splitPayment.sr_no,
    0,
  );
};

const generateCCLink = () => {
  emit(
    'generate-cc-link',
    props.splitPayment.code,
    props.splitPayment.sr_no,
    props.splitPayment.payment_status_id,
  );
};

const deleteSplitPayment = () => {
  emit(
    'delete-split-payment',
    props.splitPayment.id,
    props.splitPayment.payment_status_id,
  );
};

const retrySplitPayment = () => {
  emit(
    'retry-split-payment',
    props.splitPayment.process_job?.id,
    props.splitPayment.process_job?.message,
  );
};

const postPrepayment = () => {
  emit('post-prepayment', props.splitPayment);
};

const splitPaymentTotalPrice = (srNo, amount, discountValue) => {
  let total = 0;
  if (srNo === 1 && discountValue > 0) {
    total = amount + discountValue;
  } else {
    total = amount;
  }

  return formatAmount(total);
};

// verify if split payment deletion is enabled
const isSplitDeleteEnabled = computed(() => {
  const isNotUpfront =
    props.paymentMethodsForm.frequency !== paymentFrequencyEnum.UPFRONT;
  const hasEditPermission = can(permissionEnum.PaymentsEdit);
  const isPolicyNotBooked =
    props.quoteRequest.quote_status_id !==
    page.props.quoteStatusEnum.PolicyBooked;

  if (props.sendUpdate && isNotUpfront && hasEditPermission) {
    return true;
  }

  return isNotUpfront && hasEditPermission && isPolicyNotBooked;
});

const canDeleteSplitPayment = (item, splitIndex, splitPayment) => {
  const eligibleStatuses = [
    paymentStatusEnum.PAID,
    paymentStatusEnum.CAPTURED,
    paymentStatusEnum.AUTHORISED,
    paymentStatusEnum.REFUNDED,
    paymentStatusEnum.PARTIAL_CAPTURED,
    paymentStatusEnum.PARTIALLY_PAID,
  ];

  return (
    isSplitDeleteEnabled.value &&
    item.total_payments == splitIndex + 1 &&
    !eligibleStatuses.includes(splitPayment.payment_status_id) &&
    splitPayment.sr_no > 1
  );
};

const enablePostPrepaymentButton = computed(() => {
  console.log(
    'showPostPrepaymentButton : showPrepaymentPostButton : ',
    props.splitPayment.prepayment_receipt_status?.showPrepaymentPostButton,
    ' , batchNumber : ',
    props.splitPayment.prepayment_receipt_status?.batchNumber,
    props.splitPayment.prepayment_receipt_status,
  );
  let isPolicyBooked =
    page.props.quoteStatusEnum.PolicyBooked ===
    props.quoteRequest.quote_status_id;
  let isSendUpdateBooked =
    props.sendUpdate?.status === props.sendUpdateStatusEnum?.UPDATE_BOOKED;
  let isPolicyOrSendUpdateBooked =
    (isPolicyBooked && !props.sendUpdate) ||
    (props.sendUpdate && isSendUpdateBooked);
  if (
    can(permissionEnum.CAN_POST_PREMIUM_PREPAYMENT) &&
    isPolicyOrSendUpdateBooked &&
    props.splitPayment.prepayment_receipt_status?.showPrepaymentPostButton
  ) {
    return true;
  }
  return false;
});
</script>

<template>
  <tr>
    <td class="text-center">{{ splitPayment.sr_no }}</td>
    <td class="text-center">
      {{ splitPayment.code }}-{{ splitPayment.sr_no }}
    </td>
    <td>{{ formatDate(splitPayment.due_date) }}</td>
    <td>{{ formatDate(splitPayment.due_date) }}</td>
    <td>{{ splitPayment.payment_method.name }}</td>
    <td>
      {{ formatAmount(splitPayment.price_vat_applicable) }}
    </td>
    <td>{{ formatAmount(splitPayment.price_vat) }}</td>
    <td>
      {{
        splitPaymentTotalPrice(
          splitPayment.sr_no,
          splitPayment.payment_amount,
          parentPayment.discount_value,
        )
      }}
    </td>
    <td>
      {{
        splitPayment.sr_no == 1
          ? formatAmount(parentPayment.discount_value)
          : ''
      }}
    </td>
    <td>{{ formatAmount(splitPayment.payment_amount) }}</td>
    <td>
      {{
        splitPayment.collection_amount > 0
          ? formatAmount(splitPayment.collection_amount)
          : ''
      }}
    </td>
    <td>
      {{ formatString(splitPayment.payment_status.text) }}
    </td>
    <td>
      <x-tooltip placement="top">
        <span class="border-b border-dotted border-black">
          {{
            splitPayment.payment_allocation_status !== null
              ? formatString(splitPayment.payment_allocation_status)
              : ''
          }}
        </span>
        <template #tooltip>
          <span class="custom-tooltip-content">
            {{
              paymentAllocationStatusTooltip(
                splitPayment.payment_allocation_status,
              )
            }}
          </span>
        </template>
      </x-tooltip>
    </td>
    <td>
      <div
        v-if="
          !page.props.linkedQuoteDetails ||
          quoteRequest.quote_status_id !=
            page.props.quoteStatusEnum.PolicyCancelled ||
          page.props.linkedQuoteDetails?.childLeadsCount == 0
        "
      >
        <x-button size="xs" color="primary" @click="viewPayment" outlined
          >View</x-button
        >

        <x-button
          v-if="splitPayment.payment_method.code == 'CC'"
          class="ml-2"
          size="xs"
          color="emerald"
          @click.prevent="generateCCLink"
          outlined
          >Copy Payment Link</x-button
        >

        <x-button
          v-if="canDeleteSplitPayment(parentPayment, splitIndex, splitPayment)"
          size="xs"
          color="red"
          class="ml-2"
          @click="deleteSplitPayment"
          outlined
          >Delete</x-button
        >

        <x-button
          v-if="
            can(permissionEnum.ReApprovePayments) &&
            splitPayment.process_job?.status === 'failed'
          "
          size="xs"
          color="red"
          class="ml-2"
          @click="retrySplitPayment"
          outlined
          >Retry</x-button
        >

        <x-button
          v-if="enablePostPrepaymentButton"
          size="xs"
          color="red"
          class="ml-2"
          @click="postPrepayment"
          outlined
          >Post</x-button
        >
      </div>
    </td>
  </tr>
</template>

<style scoped>
.custom-tooltip-content {
  max-width: 200px;
  white-space: normal;
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
}
</style>
