<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useDocumentTempUrl } from '@/inertia/Composables/useDocumentTempUrl.js';

const notification = useNotifications('toast');
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
  currentFileURL: {
    type: String,
    required: true,
  },
});

// Define emits for two-way binding with v-model
const emit = defineEmits(['update:modelValue', 'update:currentFileURL']);

// Reactive state for the component
const currentFileIndex = ref(props.initialIndex);
const zoomLevel = ref(1);
const modalRef = ref(null);
/** URL actually shown in viewer; cleared when navigating to avoid showing previous file */
const displayUrl = ref(props.currentFileURL);
/** True while fetching next/previous file URL so we show loading instead of stale content */
const isLoadingNext = ref(false);

const { getTempUrl } = useDocumentTempUrl();

/**
 * Returns the currently displayed file object
 * Based on the currentFileIndex in the files array
 */
const currentFile = computed(() => {
  return props.files[currentFileIndex.value];
});

/**
 * PDF MIME type variants and URL fallback for when doc_mime_type is missing or non-standard.
 */
const PDF_MIME_PREFIX = 'application/pdf';
const PDF_MIME_ALIASES = ['application/pdf', 'application/x-pdf'];

function isPdfMimeOrUrl(file) {
  if (!file) return false;
  const mime = (file.doc_mime_type || '').trim().toLowerCase();
  if (PDF_MIME_ALIASES.includes(mime)) return true;
  if (mime.startsWith(PDF_MIME_PREFIX)) return true;
  const url = (file.doc_url || '').toLowerCase();
  return url.endsWith('.pdf');
}

/**
 * Whether the current file should be rendered as a PDF (embed).
 */
const isCurrentFilePdf = computed(() => isPdfMimeOrUrl(currentFile.value));

/**
 * Whether the current file is a previewable image (jpeg/png).
 */
const isCurrentFileImage = computed(() => {
  const file = currentFile.value;
  if (!file) return false;
  const mime = (file.doc_mime_type || '').trim().toLowerCase();
  return (
    ['image/jpeg', 'image/jpg', 'image/png'].includes(mime) ||
    mime.startsWith('image/')
  );
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

/** Show loading when fetching URL (next/prev) or when we have a file but URL not ready yet (e.g. initial open) */
const isFileLoading = computed(
  () => isLoadingNext.value || (currentFile.value && !displayUrl.value),
);

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
 * Clears display and shows loading until the new URL is ready to avoid showing previous file.
 */
const nextFile = async () => {
  if (!hasNextFile.value) return;
  isLoadingNext.value = true;
  displayUrl.value = '';
  currentFileIndex.value++;
  zoomLevel.value = 1;
  try {
    const documentURL = await getTempUrl(currentFile.value.doc_url);
    emit('update:currentFileURL', documentURL);
  } catch {
    isLoadingNext.value = false;
    displayUrl.value = props.currentFileURL;
  }
};

/**
 * Navigates to the previous file in the gallery
 * Clears display and shows loading until the new URL is ready to avoid showing previous file.
 */
const previousFile = async () => {
  if (!hasPreviousFile.value) return;
  isLoadingNext.value = true;
  displayUrl.value = '';
  currentFileIndex.value--;
  zoomLevel.value = 1;
  try {
    const documentURL = await getTempUrl(currentFile.value.doc_url);
    emit('update:currentFileURL', documentURL);
  } catch {
    isLoadingNext.value = false;
    displayUrl.value = props.currentFileURL;
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

/** Sync display URL from parent when it updates (after next/previous fetch); clear loading state */
watch(
  () => props.currentFileURL,
  url => {
    displayUrl.value = url;
    isLoadingNext.value = false;
  },
);

/**
 * Watches for the modal visibility state.
 * When modal opens: sync display URL and focus.
 * When modal closes: reset all state and pagination so next open starts fresh.
 */
watch(
  () => props.modelValue,
  isOpen => {
    if (isOpen) {
      displayUrl.value = props.currentFileURL;
      nextTick(() => {
        if (modalRef.value) {
          modalRef.value.focus();
        }
      });
    } else {
      currentFileIndex.value = props.initialIndex;
      zoomLevel.value = 1;
      displayUrl.value = '';
      isLoadingNext.value = false;
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
          <div class="flex items-center space-x-2" v-if="!isCurrentFilePdf">
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
      <div class="modal-body flex-1 min-h-0 w-full mt-2 overflow-auto">
        <div
          v-if="isFileLoading"
          class="flex flex-1 items-center justify-center min-h-[200px] text-gray-400"
        >
          <div class="flex flex-col items-center gap-2">
            <x-spinner class="w-10 h-10 text-gray-400" />
            <span class="text-sm">File is being loaded…</span>
          </div>
        </div>
        <template v-else-if="displayUrl">
          <div
            v-if="isCurrentFileImage"
            class="flex items-center justify-center"
          >
            <div class="overflow-auto items-center justify-center">
              <img
                :src="displayUrl"
                :style="{ transform: `scale(${zoomLevel})` }"
                class="max-w-full max-h-full"
                alt="Document Image"
              />
            </div>
          </div>
          <div v-else-if="isCurrentFilePdf" class="w-full h-80vh">
            <embed
              :src="displayUrl"
              type="application/pdf"
              class="w-full h-full"
            />
          </div>
          <div v-else class="text-center p-10 text-gray-500">
            Preview not available for this file type.
          </div>
        </template>
        <div v-else class="text-center p-10 text-gray-500">
          No document to display.
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
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  min-width: 100%;
  min-height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}

/* Centered modal box with max dimensions so it never goes full screen */
.modal-container {
  max-width: 72rem; /* max-w-6xl */
  max-height: 90vh;
  width: 100%;
  height: 100%;
  background-color: hsl(0, 4%, 9%);
  border-radius: 4px;
  padding: 5px;
  z-index: 1050;
  display: flex;
  flex-direction: column;
  overflow: hidden;
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
