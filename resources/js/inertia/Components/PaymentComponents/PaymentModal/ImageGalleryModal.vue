<script setup>
import { ref, computed, watch, nextTick } from 'vue';

/**
 * Component props definition
 * - modelValue: Controls the visibility of the modal (for v-model directive)
 * - files: Array of file objects to display in the gallery
 * - initialIndex: Starting index for the gallery viewer
 * - storageUrl: Base URL for accessing stored files
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  files: {
    type: Array,
    required: true,
    default: () => [],
  },
  initialIndex: {
    type: Number,
    required: true,
    default: 0,
  },
  storageUrl: {
    type: String,
    required: true,
  },
});

// Define emits for two-way binding with v-model
const emit = defineEmits(['update:modelValue']);

// Reactive state for the component
const currentFileIndex = ref(props.initialIndex);
const zoomLevel = ref(1);
const modalRef = ref(null);

/**
 * Returns the currently displayed file object
 * Based on the currentFileIndex in the files array
 */
const currentFile = computed(() => {
  return props.files[currentFileIndex.value];
});

/**
 * Determines if there is a next file available in the gallery
 * Used to enable/disable the "Next" navigation button
 */
const hasNextFile = computed(() => {
  return currentFileIndex.value < props.files.length - 1;
});

/**
 * Determines if there is a previous file available in the gallery
 * Used to enable/disable the "Previous" navigation button
 */
const hasPreviousFile = computed(() => {
  return currentFileIndex.value > 0;
});

/**
 * Closes the modal by emitting update:modelValue event with false
 * Resets zoom level to default when closing
 */
const closeModal = () => {
  zoomLevel.value = 1;
  emit('update:modelValue', false);
};

/**
 * Navigates to the next file in the gallery
 * Resets zoom level when navigating to a new file
 */
const nextFile = () => {
  if (hasNextFile.value) {
    currentFileIndex.value++;
    zoomLevel.value = 1;
  }
};

/**
 * Navigates to the previous file in the gallery
 * Resets zoom level when navigating to a new file
 */
const previousFile = () => {
  if (hasPreviousFile.value) {
    currentFileIndex.value--;
    zoomLevel.value = 1;
  }
};

/**
 * Increases zoom level for the current image
 * Limited to maximum zoom of 3x (300%)
 */
const zoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 0.25, 3);
};

/**
 * Decreases zoom level for the current image
 * Limited to minimum zoom of 0.25x (25%)
 */
const zoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25);
};

/**
 * Handles keyboard navigation in the gallery
 * - Escape: Close the modal
 * - ArrowLeft: Navigate to previous file
 * - ArrowRight: Navigate to next file
 */
const handleKeyDown = event => {
  if (event.key === 'Escape') {
    closeModal();
  } else if (event.key === 'ArrowLeft' && hasPreviousFile.value) {
    previousFile();
  } else if (event.key === 'ArrowRight' && hasNextFile.value) {
    nextFile();
  }
};

/**
 * Watches for changes in initialIndex prop to update the local currentFileIndex ref
 * This ensures synchronization when the parent component changes the initial index
 */
watch(
  () => props.initialIndex,
  newIndex => {
    currentFileIndex.value = newIndex;
  },
);

/**
 * Watches for the modal visibility state
 * When modal opens, focuses the modal element for keyboard navigation
 */
watch(
  () => props.modelValue,
  isOpen => {
    if (isOpen) {
      nextTick(() => {
        if (modalRef.value) {
          modalRef.value.focus();
        }
      });
    }
  },
);
</script>

<template>
  <div
    class="modal-overlay fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center"
    v-if="modelValue"
    @keydown.esc="closeModal"
  >
    <div
      class="modal-container bg-white w-full max-w-full overflow-hidden rounded-lg"
      tabindex="0"
      ref="modalRef"
      @keydown="handleKeyDown"
    >
      <div class="modal-header text-base text-white bg-gray-800">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            {{ currentFile?.original_name }}
          </div>
          <div class="flex items-center space-x-2">
            <span
              @click="closeModal"
              class="text-gray-300 font-bold cursor-pointer"
              tabindex="0"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="w-4 h-4"
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
        <div class="flex items-center justify-between">
          <button
            class="flex items-center space-x-2 cursor-pointer text-gray-300"
            @click="previousFile"
            :disabled="!hasPreviousFile"
            :class="{ 'opacity-50 cursor-not-allowed': !hasPreviousFile }"
            tabindex="0"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              class="w-6 h-6"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              ></path>
            </svg>
            <span>Previous</span>
          </button>
          <div
            class="flex items-center space-x-2"
            v-if="currentFile?.doc_mime_type != 'application/pdf'"
          >
            <button
              class="flex items-center space-x-2 cursor-pointer text-gray-300"
              @click="zoomOut"
              :disabled="zoomLevel <= 0.25"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-6 w-6"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 12H4"
                />
              </svg>
            </button>
            <div class="flex flex-initial w-24 justify-center">
              <span class="text-gray-300 font-bold"
                >{{ Math.round(zoomLevel * 100) }}%</span
              >
            </div>
            <button
              class="flex items-center space-x-2 cursor-pointer text-gray-300"
              @click="zoomIn"
              :disabled="zoomLevel >= 3"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-6 w-6"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
              </svg>
            </button>
          </div>
          <button
            class="flex items-center space-x-2 cursor-pointer text-gray-300"
            @click="nextFile"
            :disabled="!hasNextFile"
            :class="{ 'opacity-50 cursor-not-allowed': !hasNextFile }"
            tabindex="0"
          >
            <span>Next</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              class="w-6 h-6"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              ></path>
            </svg>
          </button>
        </div>
      </div>
      <div class="modal-body w-full h-full mt-2">
        <div
          v-if="
            currentFile?.doc_mime_type === 'image/jpeg' ||
            currentFile?.doc_mime_type === 'image/png'
          "
          class="flex items-center justify-center"
        >
          <div class="overflow-auto items-center justify-center">
            <img
              :src="storageUrl + currentFile?.doc_url"
              :style="{ transform: `scale(${zoomLevel})` }"
              class="max-w-full max-h-full"
              alt="Document Image"
            />
          </div>
        </div>
        <div
          v-else-if="currentFile?.doc_mime_type === 'application/pdf'"
          class="w-full h-80vh"
        >
          <embed
            :src="storageUrl + currentFile?.doc_url"
            type="application/pdf"
            class="w-full h-full"
          />
        </div>
        <div v-else class="text-center p-10 text-gray-500">
          Preview not available for this file type.
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}

.modal-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  background-color: hsl(0, 4%, 9%);
  border-radius: 4px;
  padding: 5px;
  z-index: 1050;
}

.modal-header {
  background-color: hsl(0, 4%, 9%);
}

.modal-body {
  padding: 10px 0;
}

.h-80vh {
  height: 85vh;
}
</style>
