<script setup>
import { computed, ref } from 'vue';
const emit = defineEmits(['updateOnParent']);
const props = defineProps({
  paymentForm: Object,
  payments: Array,
  insurerPaymentLinkIndex: Number,
});
const notification = useNotifications('toast');

const isLinkChanged = ref(false);

const paymentLinkBtnDisabled = computed(() => {
  return !props.paymentForm.insurerPaymentLink || props.paymentForm.insurerPaymentLink === '';
});

const quotePaymentLinkChanged = () => {
    const result = props.paymentForm.insurerPaymentLink != insurerPaymentLink.value && props.paymentForm.insurerPaymentLink != '';
    emit('updateOnParent', false, result );
    return result;
}

const insurerPaymentLink = computed(() => {
    var currentIndexLink = props.insurerPaymentLinkIndex - 1;
    return props.payments[0]?.payment_splits[currentIndexLink]?.insurer_payment_link ?? null;
});

const closePaymentForm = (closeModal = false) => {
    emit('updateOnParent', true, isLinkChanged.value, closeModal);
}

// Watch for changes in paymentForm.insurerPaymentLink
watch(() => props.paymentForm.insurerPaymentLink, (newVal, oldVal) => {
  isLinkChanged.value = quotePaymentLinkChanged();
});

const closeNotification = () => {
  notification.error({
    title: 'Are you sure?',
    message: 'payment link has not been saved & not shared with customer',
    position: 'top',
    timeout: 0,
    action: {
      label: 'Confirm',
      onClick: () => {
        closePaymentForm(true)
      },
    },
  })
}

onMounted(() => {
    isLinkChanged.value = quotePaymentLinkChanged();
});

defineExpose({
    isLinkChanged, // Now accessible via the parent ref
    closeNotification, // Now accessible via the parent ref
    quotePaymentLinkChanged,
});


</script>
<template>
    <div>
        <div v-if="paymentForm.status == 'edit' && !isLinkChanged" class="mr-4">
            <x-button
            @click="closePaymentForm()"
            tabindex="0"
            class="focus:outline-black"
            >
            Cancel
            </x-button>
        </div>
    </div>
    <div
        v-if="
        paymentForm.status == 'create' ||
        paymentForm.status == 'edit'
        "
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
                <span>
                    Enter Insurer Payment Link to be sent.
                </span>
            </template>
        </x-tooltip>
        <x-button
            :color=" isLinkChanged ? 'orange' : 'emerald'"
            type="submit"
            tabindex="0"
            class="focus:outline-black"
            :loading="paymentForm.processing"
            v-else
        >
            {{
                isLinkChanged
                ? 'Send Insurer Payment Link'
                : 'Updates'
            }}
        </x-button>
    </div>
</template>