<script setup>
import { usePayment } from '../../../Composables/usePayment';

const page = usePage();
const can = permission => useCan(permission);
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const permissionEnum = page.props.permissionsEnum;
const { formatDate, formatAmount, formatString } = usePayment();

const emit = defineEmits([
  'handle-collection-type-change',
  'handle-frequency-change',
  'calculate-payment-breakup',
  'reset-credit-approval',
  'handle-approval-reason-change',
  'reset-discount',
  'handle-discount-change',
  'handle-discount-reason-change',
  'upload-document',
  'open-inner-modal',
  'delete-document',
  'calculate-total-amount',
  'handle-discount-value-change',
  'validate-insurer-payment-link',
]);

const lookupsEnum = page.props.lookupsEnum;

const props = defineProps({
  isFieldReadonly: Boolean,
  paymentMethodsForm: Object,
  rules: Object,
  totalPrice: Number,
  collectionTypes: Array,
  isCollectedByEnabled: Boolean,
  insuranceProviderName: String,
  frequencyTypes: Array,
  isPaymentFrequencyNotSelected: Boolean,
  getPlanName: String,
  isPaymentNoEnabled: Boolean,
  totalPayments: Array,
  masterPaymentStatus: String,
  creditApprovalReasons: Array,
  isCreditApprovalAllowed: Boolean,
  isPaymentLocked: Boolean,
  isTotalPriceUpdated: Boolean,
  isCustomReasonEnabled: Boolean,
  showDiscountOptions: Boolean,
  isDiscountAllowed: Boolean,
  discountTypeLabel: String,
  isDiscountReasonEnabled: Boolean,
  discountReasons: Array,
  discountTypes: Array,
  isDiscountReasonError: Boolean,
  isCustomDiscountReasonEnabled: Boolean,
  isDiscountEnabled: Boolean,
  paymentDocument: Array,
  isDiscountDocumentNotUploaded: Boolean,
  discountDocumentModel: Array,
  isDiscountError: Boolean,
  discountValue: Number,
  discountError: String,
  totalAmount: Number,
  documentForm: Object,
  insurerPaymentLinkIndex: Number,
  paymentMethodsModels: Array,
  payments: Array,
  paymentStatusEnum: Object,
  quoteType: String,
  quoteTypeCodeEnum: Object,
  isLifePlanDetailsEnabled: Boolean,
});

const totalPriceFormat = computed(() => {
  return formatAmount(props.totalPrice);
});

const providerName = computed(() => {
  return props.insuranceProviderName;
});

const getPlanName = computed(() => {
  return props.getPlanName;
});

const masterPaymentStatusFormat = computed(() => {
  return formatString(props.masterPaymentStatus);
});

const discountProofDocument =
  props.paymentDocument.find(item => item.text === 'Discount Proof') || {};

const totalAmountFormat = computed(() => {
  return formatAmount(props.totalAmount);
});

// Computed property to check if frequency should be readonly/disabled for life quotes
const isLifeQuoteFrequencyReadonly = computed(() => {
  return (
    props.quoteType === props.quoteTypeCodeEnum.Life &&
    !props.isLifePlanDetailsEnabled
  );
});

const localDiscountValue = ref(props.discountValue);

watch(
  () => props.discountValue,
  (newVal, oldVal) => {
    localDiscountValue.value = newVal;
  },
);

watch(localDiscountValue, (newVal, oldVal) => {
  emit('handle-discount-value-change', newVal);
});

const isMasterPaymentPaid = computed(() => {
  if (
    props.payments.length > 0 &&
    props.payments[0].payment_status_id === page.props.paymentStatusEnum.PAID
  ) {
    return true;
  }
  return false;
});
</script>

<template>
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
              item => item.value === paymentMethodsForm.collection_type,
            ).label
          }}
        </span>
        <select
          v-if="!isFieldReadonly"
          class="custom-select"
          v-model="paymentMethodsForm.collection_type"
          :rules="[rules.isRequired]"
          @change="emit('handle-collection-type-change')"
          :disabled="isCollectedByEnabled"
        >
          <template v-for="option in collectionTypes" :key="option.value">
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
        :tooltip="
          isLifeQuoteFrequencyReadonly
            ? 'To make changes, please update the payment term in the Available Plan section.'
            : paymentTooltipEnum.FREQUENCY
        "
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
          v-if="!isFieldReadonly && !isLifeQuoteFrequencyReadonly"
          :class="{
            'custom-select-error': isPaymentFrequencyNotSelected,
          }"
          class="custom-select"
          v-model="paymentMethodsForm.frequency"
          :rules="[rules.isRequired]"
          @change="emit('handle-frequency-change')"
        >
          <template v-for="option in frequencyTypes" :key="option.value">
            <option :value="option.value" :title="option.tooltip">
              {{ option.label }}
            </option>
          </template>
        </select>
        <input
          v-if="!isFieldReadonly && isLifeQuoteFrequencyReadonly"
          class="custom-select cursor-not-allowed bg-gray-100"
          :value="
            frequencyTypes.find(
              item => item.value === paymentMethodsForm.frequency,
            )?.label || paymentMethodsForm.frequency
          "
          readonly
          :title="'To make changes, please update the payment term in the Available Plan section.'"
        />
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
          @change="emit('calculate-payment-breakup')"
        >
          <template v-for="option in totalPayments" :key="option.value">
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
            @mousedown.stop="emit('reset-credit-approval')"
          >
            &#10006;
          </span>
          <select
            class="custom-select"
            v-model="paymentMethodsForm.credit_approval"
            @change="emit('handle-approval-reason-change')"
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
              item => item.value === paymentMethodsForm.credit_approval,
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
      <span v-if="isFieldReadonly">{{ paymentMethodsForm.custom_reason }}</span>
    </x-field>
    <div v-if="showDiscountOptions && isDiscountAllowed && !isFieldReadonly">
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
            @mousedown.stop="emit('reset-discount')"
          >
            &#10006;
          </span>
          <select
            class="custom-select"
            v-model="paymentMethodsForm.discount"
            @change="emit('handle-discount-change')"
          >
            <template v-for="option in discountTypes" :key="option.value">
              <option
                :value="option.value"
                :title="option.tooltip"
                v-if="option.value !== lookupsEnum.SYSTEM_ADJUSTED_DISCOUNT"
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
      v-if="isDiscountReasonEnabled && isDiscountAllowed && !isFieldReadonly"
      class=""
    >
      <ToolTip
        title="DISCOUNT REASON"
        :tooltip="
          isFieldReadonly
            ? discountReasons.find(
                item => item.value === paymentMethodsForm.discount_reason,
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
          @change="emit('handle-discount-reason-change')"
        >
          <template v-for="option in discountReasons" :key="option.value">
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
                item => item.value === paymentMethodsForm.discount_reason,
              )?.tooltip
            : paymentTooltipEnum.DISCOUNT_REASON
        "
        :required="!isFieldReadonly"
      />
      <x-field class="w-full">
        <span v-if="isFieldReadonly">
          {{
            discountReasons.find(
              item => item.value === paymentMethodsForm.discount_reason,
            )?.label
          }}
        </span>
      </x-field>
    </div>
    <x-field
      v-if="
        isCustomDiscountReasonEnabled && isDiscountAllowed && !isFieldReadonly
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
                emit('upload-document', discountProofDocument, $event, 0)
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
                <span data-v-d0063695="" class="custom-tooltip-content">
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
      <div v-for="fileData in discountDocumentModel[0]" :key="fileData.id">
        <span style="display: flex; align-items: center">
          <span
            :key="fileData.id"
            class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
            style="flex: 1; text-decoration: none; cursor: pointer"
            @click="emit('open-inner-modal', fileData.id)"
          >
            {{ fileData.original_name }}
          </span>
          <span
            class="delete-pointer"
            @click="emit('delete-document', fileData.doc_name, 0, fileData.id)"
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
      <div v-for="fileData in discountDocumentModel[0]" :key="fileData.id">
        <span style="display: flex; align-items: center">
          <span
            :key="fileData.id"
            class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
            style="flex: 1; text-decoration: none; cursor: pointer"
            @click="emit('open-inner-modal', fileData.id)"
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
          v-model="localDiscountValue"
          name="discount_value"
          @keyup="emit('calculate-total-amount', true)"
        />
        <sup
          v-if="isDiscountError"
          class="text-sm text-red-500 dark:text-red-400"
          >{{ discountError }}</sup
        >
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
        insurerPaymentLinkIndex >= 0 && can(permissionEnum.INSURER_PAYMENT_LINK)
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
            'custom-select-error': paymentMethodsForm.errors.insurerPaymentLink,
          }"
          placeholder="Enter valid url e.g: https://imcrm.alfred.ae/login"
          v-model="paymentMethodsForm.insurerPaymentLink"
          :disabled="isMasterPaymentPaid"
          @input="emit('validate-insurer-payment-link')"
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
</template>

<style>
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
.custom-tooltip-content {
  max-width: 200px; /* Adjust the max-width as needed */
  white-space: normal; /* Allow the text to wrap */
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
}
</style>
