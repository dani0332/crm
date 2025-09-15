<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  quoteTypeId: {
    type: [Number, String],
    required: true,
  },
  quoteRequestId: {
    type: [Number, String],
    required: true,
  },
  isProcessing: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const permissionEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const emit = defineEmits(['update:modelValue']);

const closeModal = () => {
  emit('update:modelValue', false);
};
</script>

<template>
  <x-modal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    size="lg"
  >
    <div class="flex items-center justify-end space-x-2">
      <span
        @click="closeModal"
        class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
      >
        <!-- Cross icon -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          tabindex="0"
          viewBox="0 0 24 24"
          stroke="currentColor"
          class="w-4 h-4 text-gray-800"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          ></path>
        </svg>
      </span>
    </div>
    <x-form :auto-focus="false">
      <div class="text-lg text-center">
        <span>Please complete the AML screening to proceed.</span>
      </div>
      <div class="mt-2 text-center">
        <Link :href="`/kyc/aml/${quoteTypeId}/details/${quoteRequestId}`">
          <x-tooltip>
            <x-button
              v-if="can(permissionEnum.AMLList)"
              size="lg"
              color="orange"
              class="px-4 py-4 mt-4"
              :loading="isProcessing"
            >
              <span>Go to AML & KYC page</span></x-button
            >
            <template #tooltip>
              <span>{{ paymentTooltipEnum.GOTO_AML_AND_KYC_PAGE }}</span>
            </template>
          </x-tooltip>
        </Link>
      </div>
    </x-form>
  </x-modal>
</template>
