<script setup>
const props = defineProps({
  showCommissionPercentageExceedsLimitAlert: {
    type: Boolean,
    default: false,
  },
  bpForm: {
    type: Object,
    default: {},
  },
});

const emit = defineEmits(['modalClosed']);

const closeModal = () => {
  emit('modalClosed');
};
</script>
<template>
  <div
    class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
    v-if="showCommissionPercentageExceedsLimitAlert"
  >
    <div
      class="modal-confirm-container bg-white w-full max-w-full overflow-hidden rounded-lg"
    >
      <div class="modal-confirm-header text-base text-white bg-white">
        <div
          class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
        >
          <div class="flex items-center space-x-2">
            Commission inputted is outside the permitted commission range.
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
        <div
          class="text-lg font-semibold px-6 py-4 flex justify-between items-start"
        >
          <div class="text-center">
            <span>Do you want to proceed?</span>
          </div>
        </div>
        <x-button
          size="lg"
          type="submit"
          color="orange"
          class="px-4 py-2 mt-4"
          :loading="bpForm.processing"
        >
          <span>Confirm</span></x-button
        >
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
/* Modal container */
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
/* Modal header */
.modal-header {
  background-color: hsl(0, 4%, 9%);
}
/* Modal body */
.modal-body {
  padding: 10px 0;
}
.modal-confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #33333333;
  z-index: 1040;
}
.modal-confirm-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50%;
  height: 37%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px; /* Adjust the radius for desired roundness */
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc; /* Grey color for the border */
}
.modal-retry-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 45%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px; /* Adjust the radius for desired roundness */
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc; /* Grey color for the border */
}
/* Modal header */
.modal-confirm-header {
  color: #000;
}
</style>
