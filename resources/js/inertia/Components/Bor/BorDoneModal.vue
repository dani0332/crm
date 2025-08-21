<script setup>
import { ref } from 'vue';
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
  notes: '',
});

const isSubmitting = ref(false);

// Submit completion
const submitCompletion = () => {
  form.post(route('bor.logs.mark-done', props.borLog.id), {
    preserveScroll: true,
    onBefore: () => {
      isSubmitting.value = true;
      form.clearErrors();
    },
    onSuccess: (page) => {
      // Show success notification
      
      // Emit success with updated BOR log
      emit('success', page.props.updatedBorLog || props.borLog);
      handleClose();
    },
    onError: (errors) => {
      // Show error notification
      const firstError = Object.values(errors)[0];
      if (firstError) {
        notification.error({
          title: 'Completion Failed',
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
    v-model="showModal"
    size="md"
    title="Mark BOR as Complete"
  >
    <template #default>
      <div class="space-y-4">
        <!-- Success Message -->
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                Ready to mark this BOR request as complete?
              </h3>
              <p class="mt-1 text-sm text-green-700">
                This will finalize the BOR process and notify all relevant parties that the transfer is complete.
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

        <!-- Completion Checklist -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-3">Completion Checklist</h4>
          <div class="space-y-2">
            <div class="flex items-center text-sm" v-if="borLog.status === 'Document Signed'">
              <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span class="text-blue-700">Document has been signed by the customer</span>
            </div>
            <div class="flex items-center text-sm" v-if="borLog.status === 'Document Uploaded'">
              <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span class="text-blue-700">Signed document has been uploaded to the system</span>
            </div>
          <div class="flex items-center text-sm" v-if="borLog.email === 1">
              <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span class="text-blue-700">All necessary parties have been informed</span>
            </div>
          </div>
        </div>

        <!-- Optional Notes -->
        <div>
          <label for="completion-notes" class="block text-sm font-medium text-gray-700 mb-2">
            Completion Notes (Optional)
          </label>
          <textarea
            id="completion-notes"
            v-model="form.notes"
            rows="3"
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
            placeholder="Add any additional notes about the completion process..."
            :disabled="isSubmitting"
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">
            These notes will be saved with the BOR record for future reference.
          </p>
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
          Cancel
        </x-button>
        <x-button
          color="success"
          @click="submitCompletion"
          :disabled="isSubmitting"
          :loading="isSubmitting"
        >
          {{ isSubmitting ? 'Completing...' : 'Mark as Complete' }}
        </x-button>
      </div>
    </template>
  </x-modal>
</template> 