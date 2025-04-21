<script setup>
import { computed } from 'vue';
import moment from 'moment';
import ToolTip from './../../Components/ToolTip.vue';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentMethodsEnums = page.props.paymentMethodsEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentAllocationStatus = page.props.paymentAllocationStatus;

const props = defineProps({
  payment: Object,
  index: Number,
  storageUrl: String,
  can: Object,
  paymentTooltipEnum: Object,
  isChildPaymentDeletable: Boolean,
  vatValue: {
    type: Number,
    default: 0
  },
});

const emit = defineEmits([
  'void-payment-model',
  'delete-payment-model',
  'capture-payment',
  'alert-capture',
  'edit-payment',
  'copy-payment-link',
  'open-inner-modal',
  'retry-process',
  'trigger-post-prepayment',
  'capture-transaction',
  'download-offline-payment-url',
]);

const getCaptureOption = computed(() => {
  return props.payment.captureOption;
});

const getCaptureValidation = computed(() => {
  return props.payment.captureValidation;
});

const isVoidPaymentEnabled = computed(() => {
  return props.payment.isVoidPaymentEnabled;
});

const formatDate = dateString => {
  return dateString ? moment(dateString).format('DD/MM/YYYY') : '';
};

const formatPrice = (price, decimals = 2) => {
  return Number(price).toFixed(decimals);
};

const useCan = permission => {
  return props.can(permission);
};

const voidPaymentModel = () => {
  emit('void-payment-model', props.payment);
};

const deletePaymentModel = () => {
  emit('delete-payment-model', props.payment);
};

const capturePayment = () => {
  emit('capture-payment', props.payment);
};

const alertCapture = () => {
  emit('alert-capture', props.payment);
};

const editPayment = (payment, status) => {
  emit('edit-payment', payment, status);
};

const copyPaymentLink = (paymentLink, paymentStatus) => {
  emit('copy-payment-link', paymentLink, paymentStatus);
};

const openInnerModal = fileId => {
  emit('open-inner-modal', fileId);
};

const retryProcess = (paymentId, jobId) => {
  emit('retry-process', paymentId, jobId);
};

const triggerPostPrepayment = splitPayment => {
  emit('trigger-post-prepayment', splitPayment);
};

const captureTransaction = splitPayment => {
  emit('capture-transaction', splitPayment);
};

const downloadOfflinePaymentUrl = () => {
  emit('download-offline-payment-url');
};
</script>

<template>
  <tr>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ payment.number }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ payment.code }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatDate(payment.collection_date) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatDate(payment.due_date) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ payment.payment_method_name }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatPrice(payment.price_without_vat, 2) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ payment.vat_value }}%
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatPrice(payment.total_price, 2) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatPrice(payment.discount_value, 2) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatPrice(payment.total_amount, 2) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ formatPrice(payment.collected_amount, 2) }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      <div
        class="px-2 py-1 rounded-md text-center"
        :class="{
          'bg-emerald-100 text-emerald-600':
            payment.payment_status_id === paymentStatusEnum.PAID || 
            payment.payment_status_id === paymentStatusEnum.ALLOCATED || 
            payment.payment_status_id === paymentStatusEnum.ONHOLD_ALLOCATED,
          'bg-orange-100 text-orange-600':
            payment.payment_status_id === paymentStatusEnum.AUTHORISED,
          'bg-red-100 text-red-600':
            payment.payment_status_id === paymentStatusEnum.DECLINED ||
            payment.payment_status_id === paymentStatusEnum.VOID,
          'bg-blue-100 text-blue-600':
            payment.payment_status_id === paymentStatusEnum.CREDIT_APPROVED || 
            payment.payment_status_id === paymentStatusEnum.PARTIALLY_PAID,
          'bg-gray-100 text-gray-600':
            payment.payment_status_id === paymentStatusEnum.PENDING || 
            payment.payment_status_id === paymentStatusEnum.NEW || 
            payment.payment_status_id === paymentStatusEnum.OVERDUE || 
            payment.payment_status_id === paymentStatusEnum.DRAFT,
        }"
      >
        <span
          v-if="
            payment.payment_status_id === paymentStatusEnum.ALLOCATED ||
            payment.payment_status_id === paymentStatusEnum.ONHOLD_ALLOCATED
          "
        >
          ALLOCATED
          <x-tooltip>
            <span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-4 h-4 inline-block"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                />
              </svg>
            </span>
            <template #tooltip>
              <span>
                <span v-if="payment.allocation_status === paymentAllocationStatus.ALLOCATED">
                  Allocated to Sage - {{ payment.posted_date }}
                </span>
                <span v-else-if="payment.allocation_status === paymentAllocationStatus.ON_HOLD_ALLOCATED">
                  On-Hold Payment Allocated to Sage - {{ payment.posted_date }}
                </span>
              </span>
            </template>
          </x-tooltip>
        </span>
        <span v-else>
          {{ payment.payment_status_name }}
        </span>
      </div>
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      {{ payment.decline_reason || 'N/A' }}
    </td>
    <td
      v-if="payment.payment_splits && payment.payment_splits.length > 0"
      class="inner-td-class"
    >
      <div class="flex gap-2 justify-center">
        <!-- Payment Actions -->
        <div
          v-if="
            payment.frequency === 'custom' &&
            useCan(permissionEnum.PAYMENTS_EDIT) &&
            (payment.payment_status_id === paymentStatusEnum.NEW ||
              payment.payment_status_id === paymentStatusEnum.PENDING ||
              payment.payment_status_id === paymentStatusEnum.DRAFT ||
              payment.payment_status_id === paymentStatusEnum.OVERDUE)
          "
        >
          <button
            type="button"
            @click="editPayment(payment, 'edit')"
            class="px-3 py-1 text-primary-600 border border-primary-600 rounded-lg hover:bg-primary-500 hover:text-white transition duration-150 focus:outline-none"
          >
            Edit
          </button>
        </div>

        <div v-if="payment.payment_link && payment.is_approved === 0">
          <button
            @click="copyPaymentLink(payment.payment_link, payment.payment_status_id)"
            class="px-3 py-1 text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-500 hover:text-white transition duration-150 focus:outline-none"
          >
            Copy Link
          </button>
        </div>

        <div
          v-if="
            index > 0 &&
            isChildPaymentDeletable &&
            useCan(permissionEnum.PAYMENTS_DELETE)
          "
        >
          <button
            @click="deletePaymentModel(payment)"
            class="px-3 py-1 text-red-600 border border-red-600 rounded-lg hover:bg-red-500 hover:text-white transition duration-150 focus:outline-none"
          >
            Delete
          </button>
        </div>

        <template v-if="getCaptureValidation">
          <div v-if="isVoidPaymentEnabled">
            <button
              type="button"
              @click="voidPaymentModel(payment)"
              class="px-3 py-1 text-red-600 border border-red-600 rounded-lg hover:bg-red-500 hover:text-white transition duration-150 focus:outline-none"
            >
              Void
            </button>
          </div>
          <div>
            <button
              type="button"
              @click="capturePayment(payment)"
              class="px-3 py-1 text-emerald-600 border border-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition duration-150 focus:outline-none"
            >
              {{ getCaptureOption === 'capture' ? 'Capture' : 'Approve' }}
            </button>
          </div>
        </template>
        <template v-else-if="payment.payment_status_id !== paymentStatusEnum.PAID">
          <div>
            <button
              type="button"
              @click="alertCapture(payment)"
              class="px-3 py-1 text-slate-400 border border-slate-400 rounded-lg cursor-not-allowed transition duration-150 focus:outline-none"
              disabled
            >
              {{ getCaptureOption === 'capture' ? 'Capture' : 'Approve' }}
            </button>
          </div>
        </template>

        <template
          v-if="
            payment.process_status === 'failed' &&
            payment.job_id &&
            payment.is_approved === 1
          "
        >
          <div>
            <button
              type="button"
              @click="retryProcess(payment.id, payment.job_id)"
              class="px-3 py-1 text-emerald-600 border border-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition duration-150 focus:outline-none"
            >
              Retry Process
            </button>
          </div>
        </template>
      </div>
    </td>
  </tr>

  <!-- Payment Splits Table -->
  <template v-for="(split, splitIndex) in payment.payment_splits" :key="splitIndex">
    <tr>
      <td class="inner-td-class pl-10">
        <div class="flex gap-1 items-center">
          <div class="font-bold">{{ splitIndex + 1 }}</div>
        </div>
      </td>
      <td class="inner-td-class"></td>
      <td class="inner-td-class"></td>
      <td class="inner-td-class">
        {{ formatDate(split.due_date) }}
      </td>
      <td class="inner-td-class">
        <div class="flex gap-1 items-center">
          <div>{{ split.payment_method.text }}</div>
          <div>
            <template
              v-if="
                split.payment_method.code === paymentMethodsEnums?.BankTransfer ||
                split.payment_method.code === paymentMethodsEnums?.Cheque ||
                split.payment_method.code === paymentMethodsEnums?.PostDatedCheque ||
                split.payment_method.code === paymentMethodsEnums?.InsureNowPayLater ||
                split.payment_method.code === paymentMethodsEnums?.CreditApproval ||
                split.payment_method.code === paymentMethodsEnums?.InsurerPayment
              "
            >
              <template v-if="split.file && split.file.length > 0">
                <div
                  @click="openInnerModal(split.file[0].id)"
                  class="cursor-pointer text-blue-500 hover:text-blue-700"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="w-6 h-6"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                    />
                  </svg>
                </div>
              </template>
            </template>
          </div>
        </div>
      </td>
      <td class="inner-td-class"></td>
      <td class="inner-td-class"></td>
      <td class="inner-td-class"></td>
      <td class="inner-td-class"></td>
      <td class="inner-td-class">
        {{ formatPrice(split.amount, 2) }}
      </td>
      <td class="inner-td-class">
        {{ formatPrice(split.collected_amount, 2) }}
      </td>
      <td class="inner-td-class">
        <div
          class="px-2 py-1 rounded-md text-center"
          :class="{
            'bg-emerald-100 text-emerald-600': split.payment_status_id === paymentStatusEnum.PAID,
            'bg-orange-100 text-orange-600': split.payment_status_id === paymentStatusEnum.AUTHORISED,
            'bg-red-100 text-red-600': split.payment_status_id === paymentStatusEnum.DECLINED || 
                                      split.payment_status_id === paymentStatusEnum.VOID,
            'bg-blue-100 text-blue-600': split.payment_status_id === paymentStatusEnum.CREDIT_APPROVED || 
                                        split.payment_status_id === paymentStatusEnum.PARTIALLY_PAID || 
                                        split.payment_status_id === paymentStatusEnum.CAPTURED,
            'bg-gray-100 text-gray-600': split.payment_status_id === paymentStatusEnum.PENDING || 
                                        split.payment_status_id === paymentStatusEnum.NEW || 
                                        split.payment_status_id === paymentStatusEnum.OVERDUE || 
                                        split.payment_status_id === paymentStatusEnum.DRAFT,
          }"
        >
          {{ split.payment_status.text }}
        </div>
      </td>
      <td class="inner-td-class">
        {{ split.decline_reason || 'N/A' }}
      </td>
      <td class="inner-td-class">
        <div class="flex gap-2 justify-center">
          <template
            v-if="
              split.payment_method.code === paymentMethodsEnums?.CreditCard &&
              useCan(permissionEnum.PRE_PAYMENT_CAPTURE) &&
              split.payment_status_id === paymentStatusEnum.AUTHORISED &&
              split.is_capturable === true
            "
          >
            <div>
              <button
                type="button"
                @click="captureTransaction(split)"
                class="px-3 py-1 text-emerald-600 border border-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition duration-150 focus:outline-none"
              >
                Capture
              </button>
            </div>
          </template>
          <template
            v-if="
              useCan(permissionEnum.PREPAYMENT_POST_TO_SAGE) &&
              split.payment_status_id === paymentStatusEnum.PAID &&
              split.is_prepayment_posted !== true
            "
          >
            <div>
              <button
                type="button"
                @click="triggerPostPrepayment(split)"
                class="px-3 py-1 text-emerald-600 border border-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition duration-150 focus:outline-none"
              >
                Post To Sage
              </button>
            </div>
          </template>
          <template
            v-if="
              useCan(permissionEnum.OFFLINE_PAYMENT_QR_CODE) &&
              split.payment_method.code === paymentMethodsEnums?.OfflinePayment &&
              (split.payment_status_id === paymentStatusEnum.PENDING ||
                split.payment_status_id === paymentStatusEnum.NEW)
            "
          >
            <div>
              <button
                type="button"
                @click="downloadOfflinePaymentUrl()"
                class="px-3 py-1 text-emerald-600 border border-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition duration-150 focus:outline-none"
              >
                Download QR
              </button>
            </div>
          </template>
        </div>
      </td>
    </tr>
  </template>
</template> 