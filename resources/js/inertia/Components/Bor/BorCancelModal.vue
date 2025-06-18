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

// Form handling
const form = useForm({
  reason: '',
});

// Cancellation reasons
const cancellationReasons = [
  'Customer request',
  'Policy cancelled',
  'Incorrect information provided',
  'Duplicate request',
  'Insurer rejection',
  'Technical issues',
  'Other',
];

const isSubmitting = ref(false);

// Submit cancellation
const submitCancellation = () => {
  if (!form.reason.trim()) {
    return;
  }

  isSubmitting.value = true;

  form.post(route('bor.logs.cancel', props.borLog.id), {
    preserveScroll: true,
    onSuccess: (page) => {
      emit('success', page.props.updatedBorLog || props.borLog);
      emit('close');
    },
    onError: (errors) => {
      console.error('BOR cancellation failed:', errors);
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

const handleClose = () => {
  form.reset();
  emit('close');
};
</script>

<template>
  <x-modal
    :visible="visible"
    @close="handleClose"
    size="md"
    :close-on-escape="!isSubmitting"
    :close-on-backdrop="!isSubmitting"
  >
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900 flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        Cancel BOR Request
      </h3>
    </template>

    <template #body>
      <div class="space-y-4">
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
              <dt class="text-gray-500">Insurer:</dt>
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
        <div v-if="form.reason === 'Other'" class="mt-3">
          <label for="custom-reason" class="block text-sm font-medium text-gray-700 mb-2">
            Please specify the reason:
          </label>
          <textarea
            id="custom-reason"
            v-model="form.custom_reason"
            rows="3"
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
            placeholder="Please provide details about the cancellation reason..."
            :disabled="isSubmitting"
          ></textarea>
        </div>
      </div>
    </template>

    <template #footer>
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
          :disabled="!form.reason.trim() || isSubmitting"
          :loading="isSubmitting"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          {{ isSubmitting ? 'Cancelling...' : 'Cancel BOR Request' }}
        </x-button>
      </div>
    </template>
  </x-modal>
</template> 