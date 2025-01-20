<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  paymentForm: Object,
  modelType: String,
  quote: Object,
  documentType: Object,
  cdn: String,
});
const paymentLinkBtnDisabled = computed(() => {
    return props.paymentForm.insurerPaymentLink == ''
});



</script>
<template>
<div
    v-if="
    paymentForm.status == 'create' ||
    paymentForm.status == 'edit'
    "
    class="font-bold"
>
    <x-button
        color="orange"
        type="submit"
        tabindex="0"
        class="focus:outline-black font-bold text-md"
        :loading="paymentForm.processing"
        :disabled="paymentLinkBtnDisabled"
    >
    {{
        paymentForm.status == 'create'
        ? 'Send Insurer Payment Link'
        : 'Update'
    }}
    </x-button>
</div>
</template>