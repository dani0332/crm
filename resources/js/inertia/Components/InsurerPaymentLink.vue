<script setup>
import { computed, ref } from 'vue';
const emit = defineEmits(['updateOnParent']);
const props = defineProps({
  paymentForm: Object,
  payments: Array,
  insurerPaymentLinkIndex: Number,
  modelType: String,
});

const notification = useNotifications('toast');
const page = usePage();

const isLinkChanged = ref(false);
const isCancelPaymentLink = ref(false);

const paymentMethodsForm = useForm({
  quoteId: page.props.quote.id,
  quoteType: props.modelType,
});

const paymentLinkBtnDisabled = computed(() => {
  return (
    !props.paymentForm.insurerPaymentLink ||
    props.paymentForm.insurerPaymentLink === ''
  );
});

const quotePaymentLinkChanged = () => {
  const result =
    props.paymentForm.insurerPaymentLink != insurerPaymentLink.value &&
    props.paymentForm.insurerPaymentLink != '';
  emit('updateOnParent', false, result);
  return result;
};

const insurerPaymentLink = computed(() => {
  var currentIndexLink = props.insurerPaymentLinkIndex - 1;
  return (
    props.payments[0]?.payment_splits[currentIndexLink]?.insurer_payment_link ??
    null
  );
});

const closePaymentForm = (closeModal = false) => {
  emit('updateOnParent', true, isLinkChanged.value, closeModal);
};

const updatePaymentStatus = () => {
  // TODO: need to warn user and change the status of the lead to 'in negotiation'
  isCancelPaymentLink.value = true;
};

const cancelPaymentLink = () => {
  try {
    // First request - Lead Status Update
    paymentMethodsForm.post(
      '/payments/' + props.modelType + '/remove-insurer-payment-link',
      {
        preserveScroll: true,
        onSuccess: response => {
          const flash_messages = response.props.flash;
          isCancelPaymentLink.value = false;
          router.reload({ only: ['quoteRequest'] });
        },
        onError: error => {
          notification.error({
            title: 'Payment Update Failed',
            position: 'top',
          });
        },
      },
    );
  } catch (error) {
    console.error('One or more requests failed:', error);
  }
};

// Watch for changes in paymentForm.insurerPaymentLink
watch(
  () => props.paymentForm.insurerPaymentLink,
  (newVal, oldVal) => {
    isLinkChanged.value = quotePaymentLinkChanged();
  },
);

const closeNotification = () => {
  notification.error({
    title: 'Are you sure?',
    message: 'payment link has not been saved & not shared with customer',
    position: 'top',
    timeout: 0,
    action: {
      label: 'Confirm',
      onClick: () => {
        closePaymentForm(true);
      },
    },
  });
};

onMounted(() => {
  isLinkChanged.value = quotePaymentLinkChanged();
});

defineExpose({
  isLinkChanged, // Now accessible via the parent ref
  closeNotification, // Now accessible via the parent ref
  quotePaymentLinkChanged,
});

const ButtonCondition = computed(() => {
  if (
    isLinkChanged.value ||
    (page.props.quote.source == 'Renewal_upload' &&
      props.paymentForm.status == 'edit' &&
      (props.paymentForm.insurerPaymentLink != '' ||
        props.paymentForm.insurerPaymentLink != null))
  ) {
    return 'Send Insurer Payment Link';
  } else {
    return 'Updates';
  }
});
</script>
<template>
  <!-- Cancel the payment link Modal -->
  <x-modal
    ref="cancelPaymentLinkModal"
    v-model="isCancelPaymentLink"
    size="lg"
    title="Cancel Payment Link Shared with Customer"
    backdrop
  >
    <x-form :auto-focus="false">
      <div class="text-lg text-center">
        <span
          >The lead status will change to
          <strong class="font-bold">"In Negotiation"</strong>, and a new insurer
          payment link will need to be sent once the customer finalises a
          plan.</span
        >
      </div>
      <div class="mt-2 text-center flex justify-center">
        <div class="mr-4">
          <x-button
            size="sm"
            class="focus:outline-black mt-4"
            @click="isCancelPaymentLink = false"
          >
            <span>Cancel</span>
          </x-button>
        </div>
        <div>
          <x-button
            size="sm"
            color="orange"
            class="mt-4 text-center"
            @click="cancelPaymentLink()"
          >
            <span>Confirm</span>
          </x-button>
        </div>
      </div>
    </x-form>
  </x-modal>
  <!-- Buttons for the payment link -->
  <div>
    <div v-if="paymentForm.status == 'edit' && !isLinkChanged" class="mr-4">
      <x-button
        @click="closePaymentForm()"
        tabindex="0"
        class="focus:outline-black"
      >
        Cancel
      </x-button>
      <x-button
        color="orange"
        tabindex="0"
        class="focus:outline-black ml-4"
        @click="updatePaymentStatus()"
        :loading="paymentMethodsForm.processing"
      >
        Cancel Payment Link Shared with Customer
      </x-button>
    </div>
  </div>
  <div
    v-if="paymentForm.status == 'create' || paymentForm.status == 'edit'"
    class="font-bold"
  >
    <x-tooltip v-if="paymentForm.status == 'create' && paymentLinkBtnDisabled">
      <x-button
        color="orange"
        type="submit"
        tabindex="0"
        class="focus:outline-black font-bold text-md"
        :loading="paymentForm.processing"
        :disabled="paymentLinkBtnDisabled"
      >
        Send Insurer Payment Link
      </x-button>
      <template #tooltip v-if="paymentLinkBtnDisabled">
        <span> Enter Insurer Payment Link to be sent. </span>
      </template>
    </x-tooltip>
    <x-button
      v-else
      :color="isLinkChanged ? 'orange' : 'emerald'"
      type="submit"
      tabindex="0"
      class="focus:outline-black"
      :loading="paymentForm.processing"
    >
      {{ ButtonCondition }}
    </x-button>
  </div>
</template>
