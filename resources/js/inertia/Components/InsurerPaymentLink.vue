<script setup>
import { computed, ref } from 'vue';
const emit = defineEmits(['updateOnParent']);
const props = defineProps({
  paymentForm: Object,
  payments: Array,
});

const paymentLinkBtnDisabled = computed(() => {
    return props.paymentForm.insurerPaymentLink == ''
});

const insurerPaymentLink = computed(() => {
    return props.payments[0]?.payment_splits[0]?.insurer_payment_link ?? null;
});

const quotePaymentLinkChanged = computed(() => {
    return props.paymentForm.insurerPaymentLink != insurerPaymentLink.value && props.paymentForm.insurerPaymentLink != '' && props.paymentForm.status == 'edit';
})

const closePaymentForm = () => {
    emit('updateOnParent', { closePaymentModal: true, linkChanged: quotePaymentLinkChanged.value });
}


</script>
<template>
    <div>
        <div v-if="paymentForm.status == 'edit' && !quotePaymentLinkChanged" class="mr-4">
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
                paymentForm.status == 'create' || quotePaymentLinkChanged
                ? 'Send Insurer Payment Link'
                : 'Updates'
            }}
        </x-button>
    </div>
</template>