<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  title: String,
  message: String,
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  confirmColor: {
    type: String,
    default: 'orange',
  },
  isLoading: Boolean,
  isProcessing: Boolean,
  isCheckboxRequired: {
    type: Boolean,
    default: false,
  },
  checkboxText: String,
  tooltipText: String,
});

const isChecked = ref(false);
const emit = defineEmits(['close', 'confirm', 'cancel']);

const closeModal = () => {
  if (emit('cancel')) {
    // If the new cancel event is handled, use it
    return;
  }
  // Otherwise, fallback to old close event
  emit('close');
};

const confirm = () => {
  emit('confirm');
};

// Computed property to determine if the modal is in a loading state
const isModalLoading = computed(() => {
  // Support both old isLoading and new isProcessing props
  return props.isLoading || props.isProcessing;
});
</script>

<template>
  <div
    v-if="isOpen"
    class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
  >
    <div
      class="modal-confirm-container bg-white w-full max-w-full overflow-hidden rounded-lg"
    >
      <div class="modal-confirm-header text-base text-white bg-white">
        <div
          class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
        >
          <div class="flex items-center space-x-2">
            {{ title }}
          </div>
          <div class="flex items-center space-x-2">
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
        </div>
      </div>
      <div class="w-full h-full mt-2 flex flex-col items-center">
        <!-- Checkbox section if required -->
        <div v-if="isCheckboxRequired" class="text-lg font-semibold px-6 py-4 border-b flex justify-between items-start">
          <div class="flex items-center text-center mr-2 mt-4">
            <input
              type="checkbox"
              v-model="isChecked"
              class="h-6 w-6 mr-2 border border-gray-300 rounded checked:bg-blue-500 checked:border-transparent focus:ring-blue-400"
            />
          </div>
          <div class="text-left">
            <span>{{ checkboxText }}</span>
          </div>
        </div>
        
        <!-- Message display (for new structure) -->
        <div v-if="message" class="text-center p-6">
          <p class="mb-4">{{ message }}</p>
          <p class="font-semibold">This action cannot be undone.</p>
        </div>
        
        <!-- Slot for custom content (for old structure) -->
        <slot></slot>
        
        <div class="flex gap-4 pb-6 pt-4">
          <!-- Tooltip-wrapped disabled button for checkbox requirement -->
          <x-tooltip v-if="isCheckboxRequired && !isChecked">
            <x-button
              size="lg"
              :color="confirmColor"
              class="px-4 py-2"
              :disabled="isCheckboxRequired && !isChecked"
            >
              <span>{{ confirmText }}</span>
            </x-button>
            <template #tooltip>
              <span>{{ tooltipText }}</span>
            </template>
          </x-tooltip>
          
          <!-- Confirm button (when checkbox not required or is checked) -->
          <x-button
            v-if="!isCheckboxRequired || isChecked"
            size="lg"
            type="button"
            :color="confirmColor"
            class="px-4 py-2"
            :loading="isModalLoading"
            @click="confirm"
          >
            <span>{{ confirmText }}</span>
          </x-button>
          
          <!-- Cancel button -->
          <x-button
            size="lg"
            type="button"
            color="gray"
            class="px-4 py-2"
            @click="closeModal"
          >
            <span>{{ cancelText }}</span>
          </x-button>
        </div>
      </div>
    </div>
  </div>
</template> 