<script setup>
import { useForm } from '@inertiajs/vue3';
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
  quoteUuid: {
    type: String,
    required: true,
  },
  quoteTypeId: {
    type: [Number, String],
    required: true,
  },
  sendUpdateId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const notification = useNotifications('toast');
const processing = ref(false);

const closeModal = () => {
  emit('update:modelValue', false);
};

/**
 * Handles the void payment process
 * Makes an API call to void the payment
 */
const voidPayment = async () => {
  processing.value = true;

  const data = {
    quote_type_id: props.quoteTypeId,
    quote_id: props.quoteId,
    quote_uuid: props.quoteUuid,
    payment_id: props.paymentId,
    payment_code: props.paymentCode,
    send_update_log_id: props.sendUpdateId,
  };

  try {
    const res = await axios.post(
      `/payments/${props.quoteType}/void-payment`,
      data,
    );
    processing.value = false;
    closeModal();

    if (res.data.status === false) {
      notification.error({
        title: res.data.message,
        position: 'top',
      });
      return;
    }

    notification.success({
      title: 'Processed',
      position: 'top',
    });
    reloadPage();
  } catch (err) {
    processing.value = false;

    if (err.response && err.response.data) {
      notification.error({
        title: err.response.data[0] || 'Void authorized payment process failed',
        position: 'top',
      });
    } else {
      notification.error({
        title: 'Void authorized payment process failed',
        position: 'top',
      });
    }
  }
};

// Reload the page to refresh the payments table after voiding a payment
// On prod we need to reload the page to refresh the payments table

const reloadPage = () => {
  router.reload({
    only: ['payments'],
  });
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
          <div class="flex items-center space-x-2">Void Authorized Payment</div>
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
      <x-form @submit="voidPayment" :auto-focus="false">
        <div class="w-full h-full mt-2 flex flex-col">
          <div
            class="text-lg px-6 py-4 border-b flex justify-between items-start"
          >
            <div class="text-center w-full">
              <span>Are you sure to void this payment?</span>
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
