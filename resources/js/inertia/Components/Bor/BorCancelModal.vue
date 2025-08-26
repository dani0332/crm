<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  borLog: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close', 'success']);
const showModal = ref(props.visible);
const notification = useNotifications('toast');

// Form handling
const form = useForm({
  reason: '',
  notes: '',
});

// Cancellation reasons
const cancellationReasons = [
  'Customer requested to cancel',
  'Rejected by UW',
  'Other reasons',
];

const isSubmitting = ref(false);

// Submit cancellation
const submitCancellation = () => {
  if (!form.reason.trim()) {
    form.setError('reason', 'Please select a cancellation reason');
    return;
  }
  if(form.reason === 'Other reasons' || form.reason === 'Rejected by UW') {
    if(!form.notes.trim()) {
      form.setError('notes', 'Please provide details about the cancellation reason');
      return;
    }
  }

  form.post(route('bor.logs.cancel', props.borLog.id), {
    preserveScroll: true,
    onBefore: () => {
      isSubmitting.value = true;
      form.clearErrors();
    },
    onSuccess: (page) => {
      // Emit success with updated BOR log
      emit('success');
      handleClose();
    },
    onError: (errors) => {
      // Show error notification
      const firstError = Object.values(errors)[0];
      if (firstError) {
        notification.error({
          title: 'Cancellation Failed',
          message: Array.isArray(firstError) ? firstError[0] : firstError,
          position: 'top',
        });
      }
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

const handleClose = () => {
  form.reset();
  setTimeout(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }, 100);
  emit('close');
};
</script>

<template>
  <x-modal
    title="Cancel BOR Request"
    v-model="showModal"
    size="md"
    backdrop
  >
    <template #default>
      <div class="p-1">
          <div class="space-y-1">
            <!-- Warning Message -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800">
                    Are you sure you want to cancel this BOR request?
                  </h3>
                  <p class="mt-1 text-sm text-yellow-700">
                    This action cannot be undone. The customer will be notified of the cancellation.
                  </p>
                </div>
              </div>
            </div>

            <!-- BOR Details -->
            <div class="bg-gray-50 rounded-md p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">BOR Request Details</h4>
              <dl class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <dt class="text-gray-500">Policy Number:</dt>
                  <dd class="text-gray-900 font-medium">{{ borLog.policy_number || 'N/A' }}</dd>
                </div>
                <div>
                <dt class="text-gray-500">Insured Name:</dt>
                  <dd class="text-gray-900 font-medium">{{ borLog.insurer_name || 'N/A' }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Customer Type:</dt>
                  <dd class="text-gray-900 font-medium">{{ borLog.customer_type || 'N/A' }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Current Status:</dt>
                  <dd class="text-gray-900 font-medium">{{ borLog.status || 'N/A' }}</dd>
                </div>
              </dl>
            </div>

            <!-- Cancellation Reason -->
            <div>
              <label for="cancellation-reason" class="block text-sm font-medium text-gray-700 mb-2">
                Cancellation Reason <span class="text-red-500">*</span>
              </label>
              <select
                id="cancellation-reason"
                v-model="form.reason"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                :disabled="isSubmitting"
                required
              >
                <option value="">Select a reason for cancellation...</option>
                <option 
                  v-for="reason in cancellationReasons" 
                  :key="reason" 
                  :value="reason"
                >
                  {{ reason }}
                </option>
              </select>
              <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">
                {{ form.errors.reason }}
              </p>
            </div>

            <!-- Custom Reason Input (if Other is selected) -->
            <div v-if="form.reason === 'Other reasons' || form.reason === 'Rejected by UW'" class="mt-3">
              <label for="custom-reason" class="block text-sm font-medium text-gray-700 mb-2">
                Please specify the reason:
              </label>
              <textarea
                id="custom-reason"
                v-model="form.notes"
                rows="3"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                placeholder="Please provide details about the cancellation reason..."
                :disabled="isSubmitting"
              ></textarea>
            </div>
          </div>
      </div>
    </template>
    <template #actions>
       <div class="flex justify-end space-x-3">
            <x-button
              color="secondary"
              @click="handleClose"
              :disabled="isSubmitting"
            >
              Keep Active
            </x-button>
            <x-button
              color="error"
              @click="submitCancellation"
              :disabled="!form.reason.trim() || ((form.reason === 'Other reasons' || form.reason === 'Rejected by UW') && !form.notes.trim()) || isSubmitting"
              :loading="isSubmitting"
            >
              {{ isSubmitting ? 'Cancelling...' : 'Cancel BOR Request' }}
            </x-button>
          </div>
    </template>
  </x-modal>
</template> 