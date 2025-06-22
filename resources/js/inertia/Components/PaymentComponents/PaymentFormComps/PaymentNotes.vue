<script setup>
const props = defineProps({
  isViewEnabled: { type: Boolean, default: false },
  isFieldReadonly: { type: Boolean, default: false },
  isCreditApprovalView: { type: Boolean, default: false },
  paymentMethodsForm: { type: Object, required: true },
});
</script>

<template>
  <div v-if="isViewEnabled" class="p-1 mb-2">
    <h3>Notes</h3>
  </div>

  <div class="w-full grid">
    <x-field>
      <label v-if="!isViewEnabled">Notes</label>
      <p v-if="(isFieldReadonly && isViewEnabled) || isCreditApprovalView">
        {{ paymentMethodsForm.notes }}
      </p>
      <x-input
        v-if="!isViewEnabled && !isCreditApprovalView"
        class="w-full"
        v-model="paymentMethodsForm.notes"
      />
    </x-field>
  </div>
</template>
