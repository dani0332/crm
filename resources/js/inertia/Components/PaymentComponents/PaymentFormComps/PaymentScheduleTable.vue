<script setup>
import { usePayment } from '../../../Composables/usePayment';
import DatePicker from '@/inertia/Components/DatePicker.vue';
import Dropzone from '@/inertia/Components/Dropzone.vue';

const page = usePage();

const paymentStatusEnum = page.props.paymentStatusEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentFrequencyEnum = page.props.paymentFrequencyEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const isSplitFrequency = computed(
  () =>
    props.paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS,
);

const props = defineProps({
  // View mode related props
  isViewEnabled: { type: Boolean, default: false },
  isCreditApprovalView: { type: Boolean, default: false },
  isVerifiedEnabled: { type: Boolean, default: false },
  isPaymentMethodEnabled: { type: Boolean, default: false },
  isPaidEditable: { type: Boolean, default: false },
  isPaymentLocked: { type: Boolean, default: false },
  isCreditCardView: { type: Boolean, default: false },
  isFieldReadonly: { type: Boolean, default: false },
  paymentTypes: { type: Object, required: true },
  paymentTypesFiltered: { type: Array, required: true },
  isMultiPaymentsEnabled: { type: Boolean, default: false },
  quoteType: { type: String, required: true },
  sendUpdate: { type: Object, required: false },
  payments: { type: Array, required: true },
  isCCEnabled: { type: Boolean, default: false },
  quoteRequest: { type: Object, required: true },
  sendUpdateStatusEnum: { type: Object, required: true },

  // Payment form data
  paymentMethodsForm: { type: Object, required: true },
  splitPaymentNo: { type: Number, default: 1 },
  splitPaymentRecord: { type: Object, default: () => ({}) },

  // Models
  paymentMethodsModels: { type: Object, required: true },
  checkDetailModels: { type: Object, required: true },
  splitAmountModels: { type: Object, required: true },
  dueDateModels: { type: Object, required: true },
  collectionAmountModels: { type: Object, required: true },
  fileUploadModels: { type: Object, required: true },
  readOnlyPayments: { type: Object, default: () => ({}) },

  // Validation states
  isPaymentMetodNotSelected: { type: Object, default: () => ({}) },
  isSplitAmountInvalid: { type: Object, default: () => ({}) },
  isSplitAmountInvalidError: { type: Object, default: () => ({}) },
  isDocumentNotUploaded: { type: Object, default: () => ({}) },
  isCreditPaymentInvalid: { type: Object, default: () => ({}) },
  isCreditPaymentInvalidError: { type: Object, default: () => ({}) },

  // Option states
  isCheckDetailsEnabled: { type: Object, default: () => ({}) },
  authorizedPayments: { type: Object, default: () => ({}) },

  // Documents
  paymentProofDocument: { type: Object, required: true },
  documentForm: { type: Object, required: true },

  // Enums and helpers
  rules: { type: Object, required: true },

  // Error messages
  capturePaymentValidationErrorMessage: { type: String, default: '' },

  selectedPaymentForEdit: { type: Object, required: false },
});

const emit = defineEmits([
  'upload-document',
  'delete-document',
  'open-inner-modal',
  'handle-payment-options',
]);

// Methods that forward actions to parent component
const uploadDocument = (document, event, count) => {
  emit('upload-document', document, event, count);
};

const deleteDocument = (docName, count, id) => {
  emit('delete-document', docName, count, id);
};

const openInnerModal = id => {
  emit('open-inner-modal', id);
};

const handlePaymentOptions = count => {
  emit('handle-payment-options', count);
};

const { formatDate, formatAmount, formatString } = usePayment();

const minSelectableDate = computed(() => {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  return d;
});

const getPaymentTypeLabel = code => {
  const paymentType = props.paymentTypes.find(item => item.value === code);
  if (paymentType) {
    return paymentType.label;
  }
  return '';
};

const hasAnyCCPayment = () => {
  const paymentMM = Object.values(props.paymentMethodsModels);
  return paymentMM.some(item => item == 'CC');
};

const handlePaymentTypes = count => {
  var paymentTypesWithoutCheck = props.paymentTypesFiltered;
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
    frequenciesToFilterForCount.includes(props.paymentMethodsForm.frequency)
  ) {
    paymentTypesWithoutCheck = filterPaymentTypes(props.paymentTypesFiltered, [
      page.props.paymentMethodsEnum.Cheque,
    ]);
  }

  if (
    props.paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT ||
    props.paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
  ) {
    paymentTypesWithoutCheck = filterPaymentTypes(paymentTypesWithoutCheck, [
      page.props.paymentMethodsEnum.PostDatedCheque,
    ]);
  }

  if (props.paymentMethodsForm.collection_type === 'insurer') {
    let isMultiPaymentEnabled = props.isMultiPaymentsEnabled;
    if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
      isMultiPaymentEnabled = props?.selectedPaymentForEdit
        ? props?.selectedPaymentForEdit?.isMultiplePaymentsEnabled
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
      if (frequenciesToFilter.includes(props.paymentMethodsForm.frequency)) {
        paymentTypesWithoutCheck = filterPaymentTypes(
          paymentTypesWithoutCheck,
          [page.props.paymentMethodsEnum.CreditCard],
        );
      }
    }
  }
  return paymentTypesWithoutCheck;
};

const filterPaymentTypes = (paymentTypes, methodsToExclude) => {
  return paymentTypes.filter(item => !methodsToExclude.includes(item.value));
};

const isCCPaymentDisabled = option => {
  let isCreditCardEnabled = props.isCCEnabled;
  if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
    isCreditCardEnabled = props.paymentMethodsForm.isCreditCardEnabled;
  }
  return (
    !isCreditCardEnabled &&
    props.paymentMethodsForm.collection_type === 'insurer' &&
    option == 'CC'
  );
};

const isPolicySendUpdateBooked = option => {
  const isInsurerCollection =
    props.paymentMethodsForm.collection_type === 'insurer';
  const isCCOption = option === 'CC';
  const isPolicyBooked =
    props.quoteRequest.quote_status_id ===
    page.props.quoteStatusEnum.PolicyBooked;
  const isUpdateBooked =
    props.sendUpdate &&
    props.sendUpdate.status === props.sendUpdateStatusEnum?.UPDATE_BOOKED;
  const isCCAndInsurer = props.isCCEnabled && isInsurerCollection && isCCOption;

  if (isUpdateBooked && isCCAndInsurer) {
    return true;
  }
  return isCCAndInsurer && isPolicyBooked && !props.sendUpdate;
};
</script>

<template>
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
            v-if="!isViewEnabled && !isCreditApprovalView && !hasAnyCCPayment()"
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
            <span class="border-b-2 border-dotted border-black text-sm"
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
            <span v-else>{{ paymentTooltipEnum.PAYMENT_METHOD }}</span>
          </template>
        </x-tooltip>
      </div>
      <div class="w-1/5 px-2">
        <x-tooltip>
          <span class="text-sm">
            <span class="border-b-2 border-dotted border-black text-sm"
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
            <span class="border-b-2 border-dotted border-black text-sm"
              >CAPTURE AMOUNT</span
            >
            <sup class="text-red-500">*</sup>
          </span>
          <span v-else class="text-sm">
            <span class="border-b-2 border-dotted border-black text-sm"
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
            <span class="border-b-2 border-dotted border-black text-sm"
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
            <span class="border-b-2 border-dotted border-black text-sm"
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

    <!-- View Mode -->
    <template v-if="isViewEnabled">
      <div class="flex w-full custombreak">
        <div class="w-1/6 px-2 text-center">{{ splitPaymentNo }}</div>

        <div class="w-1/5 px-2">
          {{ getPaymentTypeLabel(paymentMethodsModels[splitPaymentNo]) }}
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
                style="flex: 1; text-decoration: none; cursor: pointer"
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
              <span class="border-b-2 border-dotted border-black text-sm"
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
              <span class="border-b-2 border-dotted border-black text-sm"
                >CC PAYMENT GATEWAY</span
              >
            </span>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_CC_GATEWAY }}</span>
            </template>
          </x-tooltip>
        </div>
        <div class="w-1/5 px-2">
          <x-tooltip>
            <span class="text-sm">
              <span class="border-b-2 border-dotted border-black text-sm"
                >DIGITAL WALLET</span
              >
            </span>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_WALLET }}</span>
            </template>
          </x-tooltip>
        </div>
        <div class="w-1/5 px-2">
          <x-tooltip>
            <span class="text-sm">
              <span class="border-b-2 border-dotted border-black text-sm"
                >SAGE RECEIPT ID</span
              >
            </span>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_SAGE_RECIPT }}</span>
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
        <template v-if="splitPaymentRecord.payment_method?.code == 'CC'">
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
              <span class="border-b-2 border-dotted border-black text-sm"
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
        <template v-if="splitPaymentRecord.payment_method?.code == 'CC'">
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
              <span class="border-b-2 border-dotted border-black text-sm"
                >PAYMENT STATUS</span
              >
            </span>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_STATUS }}</span>
            </template>
          </x-tooltip>
        </div>
        <div class="w-1/5 px-2">
          <x-tooltip>
            <span class="text-sm">
              <span class="border-b-2 border-dotted border-black text-sm"
                >PAYMENT ALLOCATION STATUS</span
              >
            </span>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_ALLO_STATUS }}</span>
            </template>
          </x-tooltip>
        </div>
        <div class="w-1/5 px-2">
          <x-tooltip>
            <span class="text-sm">
              <span class="border-b-2 border-dotted border-black text-sm"
                >COLLECTED AMOUNT</span
              >
            </span>
            <template #tooltip>
              <span>{{ paymentTooltipEnum.PAYMENT_VIEW_COLLECTED_TEXT }}</span>
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
          {{ formatString(splitPaymentRecord.payment_status?.text) }}
        </div>
        <div class="w-1/5 px-2">
          {{
            splitPaymentRecord.payment_allocation_status !== null
              ? formatString(splitPaymentRecord.payment_allocation_status)
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
          <x-tooltip>
            <span class="text-sm">
              <span class="border-b-2 border-dotted border-black text-sm"
                >INSURER RECEIPT NUMBER</span
              >
            </span>
            <template #tooltip>
              <span>{{
                paymentTooltipEnum.PAYMENT_VIEW_INSURER_RECEIPT_NUMBER
              }}</span>
            </template>
          </x-tooltip>
        </div>
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
          {{
            splitPaymentRecord.insurer_receipt_number !== null
              ? splitPaymentRecord.insurer_receipt_number
              : 'N/A'
          }}
        </div>
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

      <div class="flex w-full custombreak pb-5" v-if="isVerifiedEnabled">
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

    <!-- Edit Mode -->
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
                    'custom-select-error': isPaymentMetodNotSelected[count],
                  }"
                  class="w-full custom-select"
                  v-model="paymentMethodsModels[count]"
                  disabled="true"
                >
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
                  <span class="custom-tooltip-content">
                    {{
                      paymentTooltipEnum.CREDIT_APPROVAL_PAYMENT_METHOD_DISABLED_MESSAGE
                    }}
                  </span>
                </template>
              </x-tooltip>
              <select
                v-else
                :class="{
                  'custom-select-error': isPaymentMetodNotSelected[count],
                }"
                class="w-full custom-select"
                v-model="paymentMethodsModels[count]"
                @change="handlePaymentOptions(count)"
              >
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
            <template v-if="readOnlyPayments[count] && !isPaidEditable">
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
              >
                {{ isSplitAmountInvalidError[count] }}
              </sup>
            </template>
          </div>
          <div class="w-1/5 px-2" v-if="isCreditApprovalView">
            <template v-if="readOnlyPayments[count] && !isCreditCardView">
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
                  'custom-select-error': isCreditPaymentInvalid[count],
                }"
              />
              <span v-else>{{
                formatAmount(collectionAmountModels[count])
              }}</span>
              <sup
                v-if="isCreditPaymentInvalid[count]"
                class="text-sm text-red-500 dark:text-red-400"
              >
                {{ isCreditPaymentInvalidError[count] }}
              </sup>
              <small
                class="text-red-600 text-sm"
                v-if="capturePaymentValidationErrorMessage"
              >
                {{ capturePaymentValidationErrorMessage }}
              </small>
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
                :rules="[rules.isRequired, rules.dateOnOrAfterToday]"
                placeholder="dd-mm-yyyy"
                :disabled="isPaymentLocked"
                :min-date="minSelectableDate"
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
                @change="uploadDocument(paymentProofDocument, $event, count)"
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
                  style="flex: 1; text-decoration: none; cursor: pointer"
                  @click="openInnerModal(fileData?.id)"
                >
                  {{ fileData?.original_name }}
                </span>
                <span
                  class="delete-pointer"
                  @click="deleteDocument(fileData.doc_name, count, fileData.id)"
                  v-if="!readOnlyPayments[count]"
                >
                  <x-tooltip>
                    &#10006;
                    <template #tooltip>
                      <span>{{ paymentTooltipEnum.DOCUMENT_DELETE_ICON }}</span>
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
</template>

<style scoped>
.custombreak {
  page-break-inside: avoid;
}
.custom-select {
  padding: 0.5rem;
  border-radius: 0.25rem;
  border-width: 1px;
  width: 100%;
}
.custom-select-error {
  border-color: #ef4444;
}
.delete-pointer {
  cursor: pointer;
  margin-left: 0.5rem;
  color: red;
}
.custom-tooltip-content {
  max-width: 200px; /* Adjust the max-width as needed */
  white-space: normal; /* Allow the text to wrap */
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
}
</style>
