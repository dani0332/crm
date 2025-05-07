<script setup>
import { defineProps, defineEmits, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  paymentId: {
    type: [Number, String],
    required: true,
  },
  paymentCode: {
    type: String,
    required: true,
  },
  quoteType: {
    type: String,
    required: true,
  },
  quoteId: {
    type: [Number, String],
    required: true,
  }
});

const emit = defineEmits(['update:modelValue']);

const notification = useNotifications('toast');
const processing = ref(false);

const closeModal = () => {
  emit('update:modelValue', false);
};

/**
 * Handles the delete payment process
 * Makes an API call to delete the payment
 */
const deletePayment = async () => {
  processing.value = true;
  
  try {
    const res = await axios.post(`/payments/${props.quoteType}/delete-payment`, {
      payment_id: props.paymentId,
      payment_code: props.paymentCode,
    });
    
    processing.value = false;
    
    if (res.data.status === false) {
      notification.error({
        title: res.data.message,
        position: 'top',
      });
      closeModal();
      return;
    }
    
    notification.success({
      title: 'Payment deleted successfully',
      position: 'top',
    });
    closeModal();
    
    // Reload to reflect changes
    setTimeout(() => {
      router.reload({
        only: ['payments'],
      });
    }, 500);
  } catch (err) {
    processing.value = false;
    
    let errorMessage = 'Delete payment process failed';
    if (err.response) {
      if (err.response.data.message) {
        errorMessage = err.response.data.message;
      } else if (err.response.data[0]) {
        errorMessage = err.response.data[0];
      }
    }
    
    notification.error({
      title: errorMessage,
      position: 'top',
    });
    closeModal();
  }
};
</script>

<template>
  <x-modal
    :model-value="modelValue"
    @update:model-value="closeModal"
    size="lg"
    title="Delete Payment"
    show-close
    backdrop
  >
    <x-form @submit="deletePayment" :auto-focus="false">
      <div class="text-lg text-center">
        <span>Are you sure to Delete this payment?</span>
      </div>
      <div class="mt-2 text-center">
        <x-button
          size="sm"
          color="orange"
          class="mt-4 text-center"
          :loading="processing"
          type="submit"
        >
          <span>Delete</span>
        </x-button>
      </div>
    </x-form>
  </x-modal>
</template> 