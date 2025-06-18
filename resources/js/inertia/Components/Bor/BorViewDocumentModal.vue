<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';

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

const emit = defineEmits(['close']);

const isLoading = ref(false);
const error = ref(null);
const documentData = ref(null);

// Computed properties
const hasSignedDocument = computed(() => {
  return documentData.value?.signed_pdf_path || props.borLog.signed_pdf_path;
});

const hasUploadedDocuments = computed(() => {
  return documentData.value?.uploaded_documents?.length > 0;
});

const signedDocumentUrl = computed(() => {
  return documentData.value?.signed_pdf_path || props.borLog.signed_pdf_path;
});

const uploadedDocuments = computed(() => {
  return documentData.value?.uploaded_documents || [];
});

// Fetch document details
const fetchDocumentDetails = async () => {
  if (!props.borLog?.id) return;

  isLoading.value = true;
  error.value = null;

  try {
    const response = await axios.get(route('bor.logs.view-document', props.borLog.id));
    documentData.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch document details:', err);
    error.value = 'Failed to load document details. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

// View document in new tab
const viewDocument = (documentUrl) => {
  if (documentUrl) {
    window.open(documentUrl, '_blank');
  }
};

// Download document
const downloadDocument = (documentUrl, filename) => {
  if (documentUrl) {
    const link = document.createElement('a');
    link.href = documentUrl;
    link.download = filename || 'document.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
};

// Handle close
const handleClose = () => {
  documentData.value = null;
  error.value = null;
  emit('close');
};

// Watch for modal visibility changes
watch(() => props.visible, (newVisible) => {
  if (newVisible) {
    fetchDocumentDetails();
  }
});

// Format file size
const formatFileSize = (bytes) => {
  if (!bytes) return 'Unknown size';
  
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
};

// Get file type icon
const getFileTypeIcon = (filename) => {
  if (!filename) return 'document';
  
  const extension = filename.split('.').pop()?.toLowerCase();
  
  switch (extension) {
    case 'pdf':
      return 'pdf';
    case 'doc':
    case 'docx':
      return 'document-text';
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
      return 'photograph';
    default:
      return 'document';
  }
};
</script>

<template>
  <x-modal
    :visible="visible"
    @close="handleClose"
    size="lg"
  >
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900 flex items-center">
        <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        View BOR Documents
      </h3>
    </template>

    <template #body>
      <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="ml-3 text-gray-600">Loading documents...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="py-4">
          <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Error loading documents</h3>
                <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                <x-button 
                  @click="fetchDocumentDetails"
                  color="error"
                  size="sm"
                  class="mt-2"
                >
                  Try again
                </x-button>
              </div>
            </div>
          </div>
        </div>

        <!-- Document Content -->
        <div v-else class="space-y-6">
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
                <dt class="text-gray-500">Status:</dt>
                <dd class="text-gray-900 font-medium">{{ borLog.status || 'N/A' }}</dd>
              </div>
            </dl>
          </div>

          <!-- Signed PDF Document -->
          <div v-if="hasSignedDocument" class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-gray-900 flex items-center">
                <svg class="w-4 h-4 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                </svg>
                Signed BOR Document
              </h4>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Signed
              </span>
            </div>
            
            <div class="bg-white border border-dashed border-gray-300 rounded-lg p-6 text-center">
              <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
              </svg>
              <h3 class="text-sm font-medium text-gray-900 mb-2">Signed BOR Document</h3>
              <p class="text-sm text-gray-500 mb-4">
                This is the official signed Broker on Record document.
              </p>
              <div class="flex justify-center space-x-3">
                <x-button
                  color="primary"
                  size="sm"
                  @click="viewDocument(signedDocumentUrl)"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  View Document
                </x-button>
                <x-button
                  color="secondary"
                  size="sm"
                  @click="downloadDocument(signedDocumentUrl, `BOR_Signed_${borLog.policy_number || borLog.id}.pdf`)"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                  </svg>
                  Download
                </x-button>
              </div>
            </div>
          </div>

          <!-- Uploaded Documents -->
          <div v-if="hasUploadedDocuments" class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
              <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              Additional Uploaded Documents
            </h4>
            
            <div class="space-y-3">
              <div 
                v-for="document in uploadedDocuments" 
                :key="document.id"
                class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg v-if="getFileTypeIcon(document.filename) === 'pdf'" class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="getFileTypeIcon(document.filename) === 'photograph'" class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ document.filename || 'Unknown file' }}</p>
                    <p class="text-xs text-gray-500">
                      {{ formatFileSize(document.file_size) }} • Uploaded {{ new Date(document.created_at).toLocaleDateString() }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <x-button
                    color="primary"
                    size="xs"
                    @click="viewDocument(document.url)"
                  >
                    View
                  </x-button>
                  <x-button
                    color="secondary"
                    size="xs"
                    @click="downloadDocument(document.url, document.filename)"
                  >
                    Download
                  </x-button>
                </div>
              </div>
            </div>
          </div>

          <!-- No Documents State -->
          <div v-if="!hasSignedDocument && !hasUploadedDocuments" class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No documents available</h3>
            <p class="text-gray-500">
              Documents will appear here once the BOR process progresses.
            </p>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end">
        <x-button
          color="secondary"
          @click="handleClose"
        >
          Close
        </x-button>
      </div>
    </template>
  </x-modal>
</template> 