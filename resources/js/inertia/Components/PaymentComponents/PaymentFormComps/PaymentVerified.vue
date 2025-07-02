<script setup>
const page = usePage();
const paymentMethodsEnums = page.props.paymentMethodsEnum;

const props = defineProps({
  splitPaymentRecord: { type: Object, required: true },
  paymentMethodsForm: { type: Object, required: true },
  paymentMethodsModels: { type: Object, required: true },
  splitPaymentNo: { type: Number, required: true },
});
</script>

<template>
  <div
    class="flex items-center justify-center"
    v-if="
      splitPaymentRecord.verified_by !== null &&
      paymentMethodsForm.status == 'view' &&
      paymentMethodsModels[splitPaymentNo] != paymentMethodsEnums.CreditCard &&
      paymentMethodsModels[splitPaymentNo] != paymentMethodsEnums.CreditApproval
    "
  >
    <p class="text-lg font-bold text-blue-400 mr-2">
      Payment has been verified
    </p>
    <img style="width: 30px; height: 30px" src="/images/payment_verified.jpg" />
  </div>
</template>
