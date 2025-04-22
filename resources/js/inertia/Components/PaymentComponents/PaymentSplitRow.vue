<script setup>
import { computed } from 'vue';
import moment from 'moment';
import { usePayment } from '../../Composables/usePayment';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const { formatDate, formatAmount, formatString } = usePayment();

const props = defineProps({
  splitPayment: Object,
  parentPayment: Object,
  splitIndex: Number,
  linkedQuoteDetails: Object,
  quoteRequest: Object,
  paymentAllocationStatusTooltip: {
    type: Function,
    default: (status) => status
  }
});

const emit = defineEmits([
  'view-payment',
  'generate-cc-link',
  'delete-split-payment',
  'retry-split-payment',
  'post-prepayment'
]);

// Add can function for permission checks
const can = (permission) => {
  return useCan ? useCan(permission) : true;
};

const viewPayment = () => {
  emit('view-payment', props.parentPayment, props.splitPayment.id, props.splitPayment.sr_no, 0);
};

const generateCCLink = () => {
  emit('generate-cc-link', props.splitPayment.code, props.splitPayment.sr_no, props.splitPayment.payment_status_id);
};

const deleteSplitPayment = () => {
  emit('delete-split-payment', props.splitPayment.id, props.splitPayment.payment_status_id);
};

const retrySplitPayment = () => {
  emit('retry-split-payment', props.splitPayment.process_job?.id, props.splitPayment.process_job?.message);
};

const postPrepayment = () => {
  emit('post-prepayment', props.splitPayment);
};

const splitPaymentTotalPrice = (srNo, amount, discountValue) => {
  // This is a mock of the original function - in the real app, you'd implement the actual logic
  return formatAmount(amount);
};

const canDeleteSplitPayment = computed(() => {
  // This is a mock implementation - in the real app, you'd need to implement the actual logic
  return props.canDeleteSplitPayment && props.canDeleteSplitPayment(
    props.parentPayment, 
    props.splitIndex, 
    props.splitPayment
  );
});

const enablePostPrepaymentButton = computed(() => {
  return props.enablePostPrepaymentButton && props.enablePostPrepaymentButton(props.splitPayment);
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
              ? formatString(
                  splitPayment.payment_allocation_status,
                )
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
          page.props.linkedQuoteDetails?.childLeadsCount ==
            0
        "
      >
        <x-button
          size="xs"
          color="primary"
          @click="viewPayment"
          outlined
        >View</x-button>
        
        <x-button
          v-if="splitPayment.payment_method.code == 'CC'"
          class="ml-2"
          size="xs"
          color="emerald"
          @click.prevent="generateCCLink"
          outlined
        >Copy Payment Link</x-button>
        
        <x-button
          v-if="canDeleteSplitPayment"
          size="xs"
          color="red"
          class="ml-2"
          @click="deleteSplitPayment"
          outlined
        >Delete</x-button>
        
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
        >Retry</x-button>

        <x-button
          v-if="enablePostPrepaymentButton"
          size="xs"
          color="red"
          class="ml-2"
          @click="postPrepayment"
          outlined
        >Post</x-button>
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