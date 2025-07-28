<script setup>
import Dropzone from '@/inertia/Components/Dropzone.vue';

const page = usePage();
const paymentTooltipEnum = page.props.paymentTooltipEnum;

const props = defineProps({
  isViewEnabled: { type: Boolean, default: false },
  isApproveClicked: { type: Boolean, default: false },
  paymentMethodsModels: { type: Object, required: true },
  splitPaymentNo: { type: [Number, String], required: true },
  paymentMethodsForm: { type: Object, required: true },
  isApprovePaymentError: { type: Boolean, default: false },
  approveErrorMessage: { type: String, default: '' },
  approveProofDocument: { type: Object, required: true },
  documentForm: { type: Object, required: true },
  isApprovedDocumentNotUploaded: { type: Boolean, default: false },
  approvedDocumentModel: { type: Object, required: true },
  readOnlyPayments: { type: Object, default: () => ({}) },
  rules: { type: Object, required: true },
  showInsurerReceiptNumberInputField: { type: Boolean, default: false },
});

const emit = defineEmits([
  'upload-document',
  'open-inner-modal',
  'delete-document',
]);

const uploadDocument = (document, event, count) => {
  emit('upload-document', document, event, count);
};

const openInnerModal = id => {
  emit('open-inner-modal', id);
};

const deleteDocument = (docName, count, docId) => {
  emit('delete-document', docName, count, docId);
};
</script>

<template>
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
          <span>{{ paymentTooltipEnum.PAYMENT_VIEW_VERIFICATION_HEADER }}</span>
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
            <span class="border-b-2 border-dotted border-black text-sm"
              >COLLECTED AMOUNT <sup class="text-red-500">*</sup></span
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
      <div class="w-1/2 px-2" v-if="showInsurerReceiptNumberInputField">
        <div>
          <x-tooltip class="tooltip-display">
            <span class="border-b-2 border-dotted border-black text-sm"
              >INSURER RECEIPT NUMBER <sup class="text-red-500">*</sup></span
            >
            <template #tooltip>
              <span>{{
                paymentTooltipEnum.PAYMENT_VIEW_INSURER_RECEIPT_NUMBER
              }}</span>
            </template>
          </x-tooltip>
          <x-field class="w-full">
            <x-input
              class="w-full"
              v-model="paymentMethodsForm.insurer_receipt_number"
              :rules="[rules.isRequired]"
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
            <span>{{ paymentTooltipEnum.PAYMENT_VIEW_BANK_REFERENCE }}</span>
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
            <span>{{ paymentTooltipEnum.PAYMENT_VIEW_DOCUMENTS }}</span>
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
              uploadDocument(approveProofDocument, $event, splitPaymentNo)
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
                deleteDocument(fileData.doc_name, splitPaymentNo, fileData.id)
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
</template>

<style scoped>
.delete-pointer {
  cursor: pointer;
  color: red;
  margin-left: 5px;
}

.tooltip-display {
  display: block;
}
</style>
