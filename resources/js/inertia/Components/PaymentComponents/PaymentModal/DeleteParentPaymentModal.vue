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
  },
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
    const res = await axios.post(
      `/payments/${props.quoteType}/delete-payment`,
      {
        payment_id: props.paymentId,
        payment_code: props.paymentCode,
      },
    );

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
  <div
    class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
    v-if="modelValue"
  >
    <div
      class="modal-retry-container bg-white w-full max-w-full overflow-hidden rounded-lg"
    >
      <div class="modal-confirm-header text-base text-white bg-white">
        <div
          class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
        >
          <div class="flex items-center space-x-2">Delete Payment</div>
          <div class="flex items-center space-x-2">
            <span
              @click="closeModal"
              class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
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
        </div>
      </div>
      <x-form @submit="deletePayment" :auto-focus="false">
        <div class="w-full h-full mt-2 flex flex-col">
          <div
            class="text-lg px-6 py-4 border-b flex justify-between items-start"
          >
            <div class="text-center w-full">
              <span>Are you sure to Delete this payment?</span>
            </div>
          </div>
        </div>
        <div class="w-full h-full mt-2 flex flex-col items-center">
          <x-button
            size="lg"
            type="submit"
            color="orange"
            class="px-4 py-2 mt-4 mb-4"
            :loading="processing"
          >
            <span>Confirm</span>
          </x-button>
        </div>
      </x-form>
    </div>
  </div>
</template>

<style scoped>
.modal-confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #33333333;
  z-index: 1040;
}

.modal-retry-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 45%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px;
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc;
}

.modal-confirm-header {
  color: #000;
}
</style>
