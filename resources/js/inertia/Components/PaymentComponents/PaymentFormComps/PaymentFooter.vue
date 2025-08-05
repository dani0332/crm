<script setup>
import { defineProps, defineEmits } from 'vue';
import { useAMLKYC } from '../../../Composables/useAMLKYC';
import InsurerPaymentLink from '../../InsurerPaymentLink.vue';

const { isAmlVerified } = useAMLKYC();

const can = permission => useCan(permission);

const props = defineProps({
  isViewEnabled: {
    type: Boolean,
    default: false,
  },
  isCreditApprovalView: {
    type: Boolean,
    default: false,
  },
  isCreditCardView: {
    type: Boolean,
    default: false,
  },
  splitPaymentNo: {
    type: Number,
    default: 0,
  },
  paymentMethodsModels: {
    type: Object,
    default: () => ({}),
  },
  splitPaymentRecord: {
    type: Object,
    default: () => ({}),
  },
  paymentStatusEnum: {
    type: Object,
    default: () => ({}),
  },
  permissionEnum: {
    type: Object,
    default: () => ({}),
  },
  paymentMethodsEnum: {
    type: Object,
    default: () => ({}),
  },
  isDeclineClicked: {
    type: Boolean,
    default: false,
  },
  isApproveClicked: {
    type: Boolean,
    default: false,
  },
  isVerificationAllowed: {
    type: Boolean,
    default: false,
  },
  isProformaPaymentRequest: {
    type: Boolean,
    default: false,
  },
  isTransactionCaptureButtonEnabled: {
    type: Boolean,
    default: false,
  },
  isApproveConfirmed: {
    type: Boolean,
    default: false,
  },
  processing: {
    type: Boolean,
    default: false,
  },
  formStatus: {
    type: String,
    default: '',
  },
  quoteRequest: {
    type: Object,
    default: () => ({}),
  },
  quoteType: {
    type: String,
    default: '',
  },
  payments: {
    type: Array,
    default: () => [],
  },
  insurerPaymentLinkIndex: {
    type: Number,
    default: 0,
  },
  paymentMethodsForm: {
    type: Object,
    default: () => ({}),
  },
  insurerReceiptNumberCheckInProcess: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const paymentMethodsEnums = page.props.paymentMethodsEnum;

const emit = defineEmits([
  'cancel',
  'decline',
  'approve',
  'cancel-modal',
  'aml-verification',
  'update-from-insurer-payment-link',
  'check-insurer-receipt-number',
]);

const handleCancelClick = () => {
  emit('cancel');
};

const handleDeclineClick = () => {
  emit('decline');
};

const handleApproveClick = () => {
  // Check AML verification first
  if (isAmlVerified(props.quoteRequest, props.quoteType, props.payments)) {
    emit('approve');
  } else {
    emit('aml-verification');
  }
};

const handleCancelModalClick = () => {
  emit('cancel-modal');
};
</script>

<template>
  <div>
    <template v-if="isViewEnabled || isCreditApprovalView">
      <template
        v-if="
          isCreditApprovalView ||
          (paymentMethodsModels[splitPaymentNo] !== 'CA' &&
            paymentMethodsModels[splitPaymentNo] !== 'CC')
        "
      >
        <div
          v-if="
            isCreditApprovalView ||
            (splitPaymentRecord.payment_status_id != paymentStatusEnum.PAID &&
              !(
                splitPaymentRecord.verified_by !== null &&
                paymentMethodsForm.status == 'view' &&
                paymentMethodsModels[splitPaymentNo] !=
                  paymentMethodsEnums.CreditCard &&
                paymentMethodsModels[splitPaymentNo] !=
                  paymentMethodsEnums.CreditApproval
              ) &&
              (can(permissionEnum.ApprovePayments) ||
                (can(permissionEnum.INPL_APPROVER) &&
                  splitPaymentRecord.payment_method.code ==
                    paymentMethodsEnum?.InsureNowPayLater)))
          "
          class="w-full flex justify-end"
        >
          <div v-if="isDeclineClicked" class="mr-4">
            <x-button
              size="sm"
              @click="handleCancelClick"
              tabindex="0"
              class="focus:outline-black"
            >
              Cancel
            </x-button>
          </div>
          <div
            v-if="
              ((!isApproveClicked && !isDeclineClicked) ||
                (isCreditApprovalView && !isDeclineClicked)) &&
              isVerificationAllowed
            "
            class="mr-4"
          >
            <x-button
              v-if="!isProformaPaymentRequest"
              size="sm"
              @click="handleDeclineClick"
              tabindex="0"
              class="focus:outline-black"
            >
              Decline
            </x-button>
          </div>
          <div
            v-if="
              !isApproveClicked && isDeclineClicked && isVerificationAllowed
            "
            class="mr-4"
          >
            <x-button
              size="sm"
              type="submit"
              tabindex="0"
              class="focus:outline-black"
              :loading="processing"
            >
              Decline
            </x-button>
          </div>
          <div
            v-if="
              !isDeclineClicked &&
              (paymentMethodsModels[splitPaymentNo] != 'CC' ||
                isCreditApprovalView)
            "
          >
            <x-button
              v-if="
                !isApproveClicked &&
                isViewEnabled &&
                !isProformaPaymentRequest &&
                isVerificationAllowed
              "
              class="mr-2 focus:outline-black"
              size="sm"
              color="#ff5e00"
              @click="handleApproveClick"
              tabindex="0"
            >
              Approve
            </x-button>
            <!-- <x-button
              v-if="
                (isApproveClicked ||
                  (isCreditApprovalView && !isDeclineClicked)) &&
                isTransactionCaptureButtonEnabled
              "
              class="mr-2 focus:outline-black"
              size="sm"
              color="#ff5e00"
              type="submit"
              tabindex="0"
              :loading="processing"
              :disabled="
                isApproveConfirmed || !isTransactionCaptureButtonEnabled
              "
            >
              <template v-if="isCreditApprovalView && isCreditCardView">
                Capture
              </template>
              <template v-else-if="isVerificationAllowed"> Approve </template>
              <template v-else> Approve </template>
            </x-button> -->
            <template
              v-if="
                (isApproveClicked ||
                  (isCreditApprovalView && !isDeclineClicked)) &&
                isTransactionCaptureButtonEnabled
              "
            >
              <x-button
                v-if="isCreditApprovalView && isCreditCardView"
                class="mr-2 focus:outline-black"
                size="sm"
                color="#ff5e00"
                type="submit"
                tabindex="0"
                :loading="processing"
                :disabled="
                  isApproveConfirmed || !isTransactionCaptureButtonEnabled
                "
              >
                Capture
              </x-button>
              <x-button
                v-else
                class="mr-2 focus:outline-black"
                size="sm"
                color="#ff5e00"
                @click="emit('check-insurer-receipt-number')"
                tabindex="0"
                :loading="processing || insurerReceiptNumberCheckInProcess"
                :disabled="
                  isApproveConfirmed || !isTransactionCaptureButtonEnabled
                "
              >
                Approve
              </x-button>
            </template>
          </div>
        </div>
      </template>
    </template>
    <template v-else>
      <div class="w-full md:col-span-4 flex justify-end">
        <div
          v-if="formStatus === 'edit' && insurerPaymentLinkIndex < 0"
          class="mr-4"
        >
          <x-button
            @click="handleCancelModalClick"
            tabindex="0"
            class="focus:outline-black"
          >
            Cancel
          </x-button>
        </div>
        <div
          v-if="
            insurerPaymentLinkIndex < 0 &&
            (formStatus === 'create' || formStatus === 'edit')
          "
        >
          <x-button
            color="emerald"
            type="submit"
            tabindex="0"
            class="focus:outline-black"
            :loading="processing"
          >
            {{ formStatus === 'create' ? 'Add Manual Payment' : 'Update' }}
          </x-button>
        </div>
        <template
          v-if="
            paymentMethodsModels[insurerPaymentLinkIndex] ===
              paymentMethodsEnum?.InsurerPaymentLink &&
            can(permissionEnum.INSURER_PAYMENT_LINK)
          "
        >
          <InsurerPaymentLink
            ref="insurerPaymentComponent"
            :modelType="quoteType"
            :insurerPaymentLinkIndex="insurerPaymentLinkIndex"
            :paymentForm="paymentMethodsForm"
            :payments="payments"
            @updateOnParent="
              (e, f, g) => emit('update-from-insurer-payment-link', e, f, g)
            "
          />
        </template>
      </div>
    </template>
  </div>
</template>
