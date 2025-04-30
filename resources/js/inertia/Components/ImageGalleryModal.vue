<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
  modelValue: { // For v-model
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

const emit = defineEmits(['update:modelValue']); 

const currentFileIndex = ref(props.initialIndex);
const zoomLevel = ref(1);
const modalRef = ref(null);

const currentFile = computed(() => {
  return props.files[currentFileIndex.value];
});

const hasNextFile = computed(() => {
  return currentFileIndex.value < props.files.length - 1;
});

const hasPreviousFile = computed(() => {
  return currentFileIndex.value > 0;
});

const closeModal = () => {
  zoomLevel.value = 1;
  emit('update:modelValue', false);
};

const nextFile = () => {
  if (hasNextFile.value) {
    currentFileIndex.value++;
    zoomLevel.value = 1;
  }
};

const previousFile = () => {
  if (hasPreviousFile.value) {
    currentFileIndex.value--;
    zoomLevel.value = 1;
  }
};

const zoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 0.25, 3);
};

const zoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25);
};

const handleKeyDown = event => {
  if (event.key === 'Escape') {
    closeModal();
  } else if (event.key === 'ArrowLeft' && hasPreviousFile.value) {
    previousFile();
  } else if (event.key === 'ArrowRight' && hasNextFile.value) {
    nextFile();
  }
};

// Watch for changes in the initialIndex prop to update the local ref
watch(() => props.initialIndex, (newIndex) => {
  currentFileIndex.value = newIndex;
});

// Watch for the modal opening to set focus
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      if (modalRef.value) {
        modalRef.value.focus();
      }
    });
  }
});
</script>

<template>
  <div
    class="modal-overlay fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center"
    v-if="modelValue"
    @keydown.esc="closeModal"
  >
    <div
      class="modal-container bg-white w-full max-w-full overflow-hidden rounded-lg "
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
              class="text-gray-300 font-bold cursor-pointer pr-1"
              tabindex="0"
            >
              <!-- SVG for Close Modal -->
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
            class="flex items-center space-x-2 cursor-pointer text-gray-300 px-2 py-1 rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="previousFile"
            :disabled="!hasPreviousFile"
            tabindex="0"
            aria-label="Previous File"
          >
            <!-- SVG for Previous -->
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
              class="flex items-center space-x-2 cursor-pointer text-gray-300 p-1 rounded hover:bg-gray-700 disabled:opacity-50"
              @click="zoomOut"
              :disabled="zoomLevel <= 0.25"
              tabindex="0"
              aria-label="Zoom Out"
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
              <span class="text-gray-300 font-bold">{{ Math.round(zoomLevel * 100) }}%</span>
            </div>
            <button
              class="flex items-center space-x-2 cursor-pointer text-gray-300 p-1 rounded hover:bg-gray-700 disabled:opacity-50"
              @click="zoomIn"
              :disabled="zoomLevel >= 3"
              tabindex="0"
              aria-label="Zoom In"
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
            class="flex items-center space-x-2 cursor-pointer text-gray-300 px-2 py-1 rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="nextFile"
            :disabled="!hasNextFile"
            tabindex="0"
            aria-label="Next File"
          >
            <span>Next</span>
            <!-- SVG for Next -->
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
          class="flex items-center justify-center h-80vh"
        >
          <div class="overflow-auto flex items-center justify-center max-w-full max-h-full">
            <img
              :src="storageUrl + currentFile?.doc_url"
              :style="{ transform: `scale(${zoomLevel})`, transformOrigin: 'center center' }"
              class="max-w-none max-h-none object-contain transition-transform duration-150"
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
/* Ensure overlay and container take up space */
.modal-overlay {
  z-index: 1040; /* Ensure it's above other content */
}
.modal-container {
  z-index: 1050; /* Ensure modal is above overlay */
  background-color: #1f2937; /* Dark background for contrast */
}
.modal-header {
  padding: 0.75rem 1rem; /* Adjust padding as needed */
}
.modal-body {
  background-color: #374151; /* Slightly lighter background for body */
  /* padding: 1rem; */
}
.h-80vh {
  height: 80vh; /* Ensure body has height */
}

/* Styling for buttons */
button {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0;
  font: inherit;
  outline: inherit;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

button:focus-visible {
  outline: 2px solid orange; /* Or your preferred focus style */
  outline-offset: 2px;
}

/* Ensure images don't overflow their container */
img {
  display: block; /* Prevent extra space below image */
}

/* Center the image within the zoom container */
.overflow-auto {
  display: flex;
  align-items: center;
  justify-content: center;
}

</style>