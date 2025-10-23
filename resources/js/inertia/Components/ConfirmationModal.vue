<script setup>
/**
 * Reusable confirmation modal component
 *
 * @example
 * <ConfirmationModal
 *   v-model="showModal"
 *   title="Confirm Action"
 *   message="Are you sure you want to proceed?"
 *   :loading="isProcessing"
 *   @confirm="handleConfirm"
 *   @cancel="handleCancel"
 * />
 */

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Are you sure?',
  },
  message: {
    type: String,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const isActive = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const handleConfirm = () => {
  emit('confirm');
};

const handleCancel = () => {
  emit('cancel');
};
</script>

<template>
  <x-modal v-model="isActive" size="lg" :title="title" show-close backdrop>
    <div class="items-center">
      <div v-if="message" class="ml-2">
        <p>{{ message }}</p>
      </div>
      <div class="ml-2 mt-2">
        <p class="font-semibold">Do you want to proceed?</p>
      </div>
    </div>
    <template #actions>
      <div class="text-right space-x-4">
        <x-button
          size="sm"
          ghost
          :disabled="loading"
          @click.prevent="handleCancel"
        >
          Cancel
        </x-button>

        <x-button
          size="sm"
          color="error"
          :loading="loading"
          @click.prevent="handleConfirm"
        >
          Confirm
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
