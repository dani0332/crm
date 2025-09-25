<script setup>
import { ref, computed, watch, onMounted, reactive } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Dropzone from '@/inertia/Components/Dropzone.vue';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  borLog: {
    type: Object,
    required: true,
  },
  documentTypes: {
    type: Object,
    required: false,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'success']);

const page = usePage();
const notification = useToast();

// Reactive data
const showModal = ref(props.visible);
const uploadingStatus = ref({});
const errorMsg = ref({});
const successStatus = ref({});

// Computed properties
const modalTitle = computed(() => {
  return `Upload Document - BOR #${props.borLog?.id || 'N/A'}`;
});

// Get the appropriate document type for this BOR's LOB
const borDocumentType = computed(() => {
  // Map LOB to document type code (same logic as in BorController)
  const lobToDocTypeMap = {
    'car': 'BAL',
    'health': 'BAL_HLTH',
    'travel': 'BAL_TRVL',
    'bike': 'BAL_Bike',
    'business': 'BAL_BS',
    'home': 'BAL_HOME',
    'life': 'BAL_Life',
    'pet': 'BAL_PET',
    'yacht': 'BAL_YCHT',
    'cycle': 'BAL_CYCLE',
  };
  
  // Get LOB from the BOR log's related quote or from the parent component
  const lob = props.borLog.quote?.quote_type || 
             props.borLog.lob || 
             'car'; // fallback to car
  const docTypeCode = lobToDocTypeMap[lob.toLowerCase()] || 'BAL';
  
  // Find the document type configuration
  // Since documentTypes is nested by categories, we need to search through all categories
  for (const category of Object.values(props.documentTypes)) {
    const docType = category.find(dt => dt.code === docTypeCode);
    if (docType) {
      return docType;
    }
  }
  
  // Fallback document type if not found in props
  return {
    id: 'bor_default',
    code: docTypeCode,
    text: 'Broker Appointment Letter',
    accepted_files: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
    max_size: 25,
    max_files: 10,
    is_required: true,
  };
});

// Watch for visibility changes
watch(() => props.visible, (newVal) => {
  showModal.value = newVal;
  if (newVal) {
    resetForm();
  }
});

// Methods
const resetForm = () => {
  uploadingStatus.value = {};
  errorMsg.value = {};
  successStatus.value = {};
};

const closeModal = () => {
  showModal.value = false;
  emit('close');
  resetForm();
};

const uploadFile = (documentType, filesWithInfo) => {
  const docTypeId = documentType.id;
  successStatus.value[docTypeId] = false;
  errorMsg.value[docTypeId] = '';
  
  const { files, rejectReason } = filesWithInfo;
  
  if (files.length === 0) {
    notification.error({
      title: 'File upload failed',
      position: 'top',
    });
    errorMsg.value[docTypeId] = useFileUploadErrorMessage(documentType, rejectReason);
    return false;
  }

  const url = route('bor.documents.upload', props.borLog.id);
  const formData = new FormData();
  
  // Add the file to form data
  files.forEach(file => {
    formData.append('file', file.file); // Note: BOR upload expects 'file', not 'files[]'
  });

  uploadingStatus.value[docTypeId] = true;

  axios
    .post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    .then(response => {
      successStatus.value[docTypeId] = true;
      notification.success({
        title: 'Document uploaded successfully',
        position: 'top',
      });
      
      // Emit success with updated borLog data
      emit('success', response.data.borLog || response.data);
      
      // Auto-close modal after successful upload
      setTimeout(() => {
        closeModal();
      }, 1500);
    })
    .catch(error => {
      const errorMessage = error.response?.data?.message || 'File upload failed';
      errorMsg.value[docTypeId] = errorMessage;
      
      notification.error({
        title: 'File upload failed',
        position: 'top',
      });
      
      // Handle validation errors
      const errorMessages = error.response?.data?.errors;
      if (errorMessages) {
        Object.keys(errorMessages).forEach(function (key) {
          notification.error({
            title: errorMessages[key][0] ?? errorMessages[key],
            position: 'top',
          });
        });
      }
    })
    .finally(() => {
      uploadingStatus.value[docTypeId] = false;
    });
};

// Format file size for display
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Cleanup on unmount
onMounted(() => {
  // Reset form when component mounts
  resetForm();
});
</script>

<template>
  <x-modal 
    v-model="showModal"
    size="xl"
  >
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            {{ modalTitle }}
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            Upload supporting documentation for this BOR request
          </p>
        </div>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- BOR Details -->
      <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="font-medium text-gray-700">Customer Type:</span>
            <span class="ml-2 text-gray-900">{{ borLog.customer_type }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Policy Number:</span>
            <span class="ml-2 text-gray-900">{{ borLog.policy_number || 'N/A' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Insurer:</span>
            <span class="ml-2 text-gray-900">{{ borLog.insurer_name || 'N/A' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Status:</span>
            <span class="ml-2 text-gray-900">{{ borLog.status }}</span>
          </div>
        </div>
      </div>

      <!-- Document Upload Section -->
      <div class="mb-6">
        <div class="grid md:grid-cols-2 gap-4 border-b pb-4">
          <!-- Document Type Info -->
          <div class="flex flex-col gap-2">
            <h5 class="text-sm font-semibold">
              {{ borDocumentType.text }}
              <span class="text-red-500">{{ borDocumentType.is_required ? '*' : '' }}</span>
            </h5>
            <p class="text-xs text-gray-600">Max files: {{ borDocumentType.max_files }}</p>
            <p class="text-xs text-gray-600">
              Supported: {{ borDocumentType.accepted_files }}
            </p>
            <p class="text-xs text-gray-600">
              Max file size: {{ borDocumentType.max_size }} MB
            </p>

            <!-- Success Message -->
            <x-alert
              v-if="successStatus[borDocumentType.id]"
              type="success"
              color="success"
              light
              class="mt-2"
            >
              <p class="text-sm">File uploaded successfully! The modal will close automatically.</p>
            </x-alert>

            <!-- Error Message -->
            <x-alert
              v-if="errorMsg[borDocumentType.id]"
              type="error"
              color="error"
              light
              class="mt-2"
            >
              <p class="text-sm">{{ errorMsg[borDocumentType.id] }}</p>
            </x-alert>
          </div>

          <!-- Dropzone Upload Area -->
          <div class="space-y-3">
            <Dropzone
              :id="borDocumentType.id"
              :accept="borDocumentType.accepted_files"
              :max-files="1"
              :max-size="borDocumentType.max_size"
              :loading="uploadingStatus[borDocumentType.id]"
              :document-type-code="borDocumentType.code"
              :multiple="false"
              @change="uploadFile(borDocumentType, $event)"
            />

            <!-- Show existing documents if any -->
            <template v-if="borLog.documents && borLog.documents.length > 0">
              <div class="mt-3">
                <h6 class="text-xs font-medium text-gray-700 mb-2">Uploaded Documents:</h6>
                <div
                  v-for="document in borLog.documents"
                  :key="document.id"
                  class="block px-3 py-2 border rounded-md text-xs bg-green-50 border-green-200 text-green-800"
                >
                  <div class="flex items-center justify-between">
                    <span class="truncate">{{ document.original_name || document.doc_name }}</span>
                    <svg class="w-4 h-4 text-green-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Upload Guidelines -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 class="text-sm font-medium text-blue-900 mb-2">Upload Guidelines</h4>
        <ul class="text-xs text-blue-800 space-y-1">
          <li>• Ensure the document is clear and legible</li>
          <li>• Include all relevant pages and information</li>
          <li>• File size must be under {{ borDocumentType.max_size }}MB</li>
          <li>• Supported formats: {{ borDocumentType.accepted_files.replace(/\./g, '').toUpperCase() }}</li>
          <li>• Document will be automatically processed after upload</li>
        </ul>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end space-x-3">
        <x-button
          color="gray"
          @click="closeModal"
          :disabled="uploadingStatus[borDocumentType.id]"
        >
          {{ successStatus[borDocumentType.id] ? 'Close' : 'Cancel' }}
        </x-button>
        
        <!-- Note: Upload happens automatically when file is selected via Dropzone -->
        <div v-if="!successStatus[borDocumentType.id]" class="text-sm text-gray-500">
          Select a file above to upload automatically
        </div>
      </div>
    </div>
  </x-modal>
</template> 