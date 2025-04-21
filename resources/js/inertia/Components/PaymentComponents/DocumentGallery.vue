<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  currentIndex: Number,
  files: Array,
});

const emit = defineEmits(['close-modal', 'zoom-in', 'zoom-out', 'next-file', 'previous-file', 'handle-key-down']);

const modalRef = ref(null);

const currentFile = computed(() => {
  return props.files[props.currentIndex];
});

const hasPreviousFile = computed(() => {
  return props.currentIndex > 0;
});

const hasNextFile = computed(() => {
  return props.currentIndex < props.files.length - 1;
});

const closeInnerModal = () => {
  emit('close-modal');
};

const zoomIn = () => {
  emit('zoom-in');
};

const zoomOut = () => {
  emit('zoom-out');
};

const nextFile = () => {
  emit('next-file');
};

const previousFile = () => {
  emit('previous-file');
};

const handleKeyDown = (event) => {
  emit('handle-key-down', event);
};
</script>

<template>
  <div 
    v-if="isOpen"
    class="modal-overlay fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center"
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
              @click="closeInnerModal"
              class="text-gray-300 font-bold cursor-pointer pr-1"
            >
              <!-- SVG for Close Modal -->
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                tabindex="0"
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
          <div
            class="flex items-center space-x-2 cursor-pointer"
            @click="previousFile"
            :class="{ 'opacity-50 cursor-not-allowed': !hasPreviousFile }"
          >
            <!-- SVG for Previous -->
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              class="w-6 h-6 text-gray-300"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              ></path>
            </svg>
            Previous
          </div>
          <div
            class="flex items-center space-x-2"
            v-if="currentFile?.doc_mime_type != 'application/pdf'"
          >
            <div
              class="flex items-center space-x-2 cursor-pointer"
              @click="zoomOut"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-6 w-6 text-gray-300"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 12H4"
                />
              </svg>
            </div>
            <div class="flex flex-initial w-24 justify-center">
              <span class="text-gray-300 font-bold">{{ $parent.zoomLevel * 100 }}%</span>
            </div>
            <div
              class="flex items-center space-x-2 cursor-pointer"
              @click="zoomIn"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-6 w-6 text-gray-300"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
              </svg>
            </div>
          </div>
          <div
            class="flex items-center space-x-2 cursor-pointer"
            @click="nextFile"
            :class="{ 'opacity-50 cursor-not-allowed': !hasNextFile }"
          >
            <!-- SVG for Next -->
            Next
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              class="w-6 h-6 text-gray-300"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              ></path>
            </svg>
          </div>
        </div>
      </div>
      <div class="modal-body w-full h-full mt-2">
        <div
          v-if="
            currentFile?.doc_mime_type.startsWith('image/') ||
            currentFile?.doc_mime_type === 'image/jpeg' ||
            currentFile?.doc_mime_type === 'image/png' ||
            currentFile?.doc_mime_type === 'image/jpg'
          "
          class="flex justify-center p-4"
          style="min-height: 500px"
        >
          <img
            :src="$parent.storageUrl + '/' + currentFile?.doc_path"
            :style="{
              transform: 'scale(' + $parent.zoomLevel + ')',
              transformOrigin: 'center',
              transition: 'transform 0.2s ease-in-out',
            }"
            class="max-w-full max-h-full"
            alt="Document Image"
          />
        </div>
        <div v-else-if="currentFile?.doc_mime_type === 'application/pdf'">
          <iframe
            :src="$parent.storageUrl + '/' + currentFile?.doc_path"
            class="w-full"
            style="height: 80vh"
          ></iframe>
        </div>
        <div v-else class="text-center p-4">
          <p>
            This file cannot be previewed.
            <a
              :href="$parent.storageUrl + '/' + currentFile?.doc_path"
              target="_blank"
              class="text-blue-600 hover:text-blue-800"
              >Download</a
            >
            to view it.
          </p>
        </div>
      </div>
    </div>
  </div>
</template> 