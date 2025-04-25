<script setup>
import { computed } from 'vue';
import UpdateTotalPrice from './../UpdateTotalPrice.vue';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;

const can = permission => useCan(permission);
const emit = defineEmits(['add-payment-modal', 'download-proforma-payment']);

const props = defineProps({
  payments: Array,
  paymentTooltipEnum: Object,
  proformaPayment: Object,
  quoteRequest: Object,
  quoteType: String,
  readOnlyMode: {
    type: Object,
    default: () => ({ isDisable: true }),
  },
});

const downloadProformaPayment = () => {
  emit('download-proforma-payment');
};

const addPaymentModal = () => {
  emit('add-payment-modal');
};

</script>

<template>
  <div class="flex items-center mb-4">
    <div class="ml-auto flex gap-2">
      <template v-if="can(permissionEnum.ENABLE_PROFORMA_PDF_DOWNLOAD_BUTTON)">
        <template v-if="proformaPayment?.payment_status_id == paymentStatusEnum.PAID">
          <x-button
            v-if="proformaPayment"
            size="sm"
            color="primary"
            target="_blank"
            @click="downloadProformaPayment"
          >
            <span class="border-b border-dotted">Download Proforma Payment Request</span>
          </x-button>
        </template>
        <template v-else>
          <x-tooltip placement="right">
            <x-button
              v-if="proformaPayment"
              size="sm"
              color="primary"
              target="_blank"
              @click="downloadProformaPayment"
            >
              <span class="border-b border-dotted">Download Proforma Payment Request</span>
            </x-button>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_DOWNLOAD_PROFORMA_PAYMENT }}</span>
            </template>
          </x-tooltip>
        </template>
      </template>
      <div
        v-if="
          !page.props.linkedQuoteDetails ||
          quoteRequest.quote_status_id != page.props.quoteStatusEnum.PolicyCancelled ||
          page.props.linkedQuoteDetails?.childLeadsCount == 0
        "
      >
        <template v-if="payments.length > 0">
          <div class="flex justify-between items-center gap-2" style="margin-left: auto">
            <UpdateTotalPrice
              v-if="
                can(permissionEnum.TEMP_UPDATE_TOTALPRICE) &&
                quoteRequest.quote_status_id === 15
              "
              :quoteId="quoteRequest.id"
              :paymentCode="payments[0].code"
              :quoteType="quoteType"
              :totalPrice="payments[0].total_price"
              :totalPaidPrice="payments[0].total_amount + payments[0].discount_value"
            />
            <div v-if="readOnlyMode.isDisable === true">
              <x-button
                v-if="can(permissionEnum.PaymentsCreate)"
                size="sm"
                color="emerald"
                @click="addPaymentModal"
              >
                Add Manual Payment
              </x-button>
            </div>
          </div>
        </template>
        <template v-else>
          <x-tooltip>
            <div v-if="readOnlyMode.isDisable === true">
              <x-button
                class="focus:ring-2 focus:ring-black"
                v-if="can(permissionEnum.PaymentsCreate)"
                size="sm"
                color="emerald"
                @click="addPaymentModal"
              >
                <span class="border-b border-dotted">Add Manual Payment</span>
              </x-button>
            </div>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_MANAGEMENT_ADD_PAYMENT }}</span>
            </template>
          </x-tooltip>
        </template>
      </div>
    </div>
  </div>
</template> 