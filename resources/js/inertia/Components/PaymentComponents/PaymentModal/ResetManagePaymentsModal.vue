<script setup>
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  quoteType: {
    type: String,
    required: true,
  },
  quoteCode: {
    type: String,
    required: true,
  },
  /** Primary key on the quote request row; optional but preferred for lookup (numeric id for getQuoteObject). */
  quoteRequestId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const notification = useNotifications('toast');
const reason = ref('');
const reasonError = ref(false);
const processing = ref(false);

const close = () => {
  emit('update:modelValue', false);
};

watch(
  () => props.modelValue,
  open => {
    if (open) {
      reason.value = '';
      reasonError.value = false;
    }
  },
);

const confirm = async () => {
  reasonError.value = false;
  processing.value = true;

  try {
    const payload = {
      reason: reason.value,
      quote_request_id: props.quoteRequestId,
      quote_code: props.quoteCode,
    };

    const res = await axios.post(
      `/payments/${props.quoteType}/reset-manage-payments`,
      payload,
    );

    processing.value = false;

    if (!res.data?.success) {
      notification.error({
        title: res.data?.message ?? 'Reset failed.',
        position: 'top',
      });

      return;
    }

    notification.success({
      title: res.data?.message ?? 'Payments reset.',
      position: 'top',
    });
    close();
    router.reload({ preserveState: false });
  } catch (err) {
    processing.value = false;
    const msg =
      err.response?.data?.message ??
      err.response?.data?.errors?.reason?.[0] ??
      'Failed to reset payments.';

    notification.error({
      title: msg,
      position: 'top',
    });
  }
};
</script>

<template>
  <x-modal v-model="isOpen" size="md" title="Reset Manage Payments" show-close>
    <p class="text-sm text-gray-600 mb-4">
      This will remove all payment records for this lead. Enter a reason to
      confirm.
    </p>
    <x-label class="flex flex-col gap-2 mb-4">
      <span>Reason</span>
      <x-textarea
        v-model="reason"
        class="w-full"
        rows="4"
        placeholder="Enter reason for resetting payments"
        :error="reasonError ? 'Reason is required (min. 5 characters)' : ''"
      />
    </x-label>
    <template #actions>
      <div class="flex gap-2 justify-end">
        <x-button
          size="sm"
          color="error"
          :loading="processing"
          @click.prevent="confirm"
        >
          Confirm reset
        </x-button>
        <x-button size="sm" ghost :disabled="processing" @click.prevent="close">
          Cancel
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
