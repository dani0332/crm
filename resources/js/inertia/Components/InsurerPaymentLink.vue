<script setup>
import { computed, ref } from 'vue';
const emit = defineEmits(['updateOnParent']);
const props = defineProps({
  paymentForm: Object,
  payments: Array,
});
const notification = useNotifications('toast');

const isLinkChanged = ref(false);

const paymentLinkBtnDisabled = computed(() => {
    isLinkChanged.value = quotePaymentLinkChanged();
    return props.paymentForm.insurerPaymentLink == ''
});

const quotePaymentLinkChanged = () => {
    const result = props.paymentForm.insurerPaymentLink != insurerPaymentLink.value && props.paymentForm.insurerPaymentLink != '';
    emit('updateOnParent', false, result );
    return result;
}

const insurerPaymentLink = computed(() => {
    return props.payments[0]?.payment_splits[0]?.insurer_payment_link ?? null;
});

const closePaymentForm = (closeModal = false) => {
    emit('updateOnParent', true, isLinkChanged.value, closeModal);
}

const closeNotification = () => {
  notification.error({
    title: 'Are you sure?',
    message: 'your changes will be lost',
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
            color="orange"
            type="submit"
            tabindex="0"
            class="focus:outline-black font-bold text-md"
            :loading="paymentForm.processing"
            v-else
        >
            {{
                paymentForm.status == 'create' || isLinkChanged
                ? 'Send Insurer Payment Link'
                : 'Updates'
            }}
        </x-button>
    </div>
</template>