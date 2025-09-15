<script setup>
const page = usePage();

const props = defineProps({
  paymentMethodsForm: Object,
  rules: Object,
  isDeclineClicked: Boolean,
  isViewEnabled: Boolean,
  isCreditApprovalView: Boolean,
  isDeclinedReasonError: Boolean,
  declinedReasons: Array,
  isDeclineCustomReason: Boolean,
});

const emit = defineEmits(['handle-declined-reason-change']);
</script>

<template>
  <template v-if="(isViewEnabled || isCreditApprovalView) && isDeclineClicked">
    <div class="p-1 mb-2">
      <h3 class="text-white">PAYMENT DECLINE</h3>
    </div>
    <x-divider class="mb-4 mt-1" />
    <div class="flex w-full">
      <div class="w-full px-2">
        <x-field label="DECLINE REASON" required>
          <select
            :class="{ 'custom-select-error': isDeclinedReasonError }"
            class="custom-select"
            v-model="paymentMethodsForm.declined_reason"
            :rules="[rules.isRequired]"
            @change="emit('handle-declined-reason-change')"
          >
            <template v-for="option in declinedReasons" :key="option.value">
              <option :value="option.value">
                {{ option.label }}
              </option>
            </template>
          </select>
          <p
            v-if="isDeclinedReasonError"
            class="text-sm text-red-500 dark:text-red-400 mt-1"
          >
            This field is required
          </p>
        </x-field>
      </div>
      <div v-if="isDeclineCustomReason" class="w-full px-2">
        <x-field label="CUSTOM REASON" required>
          <x-input
            class="w-full"
            v-model="paymentMethodsForm.declined_custom_reason"
            :rules="[rules.isRequired]"
          />
        </x-field>
      </div>
    </div>
  </template>
</template>
