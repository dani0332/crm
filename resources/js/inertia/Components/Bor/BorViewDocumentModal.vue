<script setup>
import { ref, computed, watch } from 'vue';
import { formatDate } from '../../Composables/utilities';
import axios from 'axios';

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
const showModal = ref(props.visible);
const downloadLoader = ref(false);

// Computed properties - use embedded document data from getBorLogs
const hasSignedDocument = computed(() => {
  return props.borLog.has_signed_pdf && props.borLog.signed_pdf?.length > 0;
});

const closeModal = () => {
  // Reset form state
  showModal.value = false;
  emit('close');
};

const hasUploadedDocuments = computed(() => {
  return (
    props.borLog.has_uploaded_documents &&
    props.borLog.uploaded_documents?.length > 0
  );
});

const signedDocuments = computed(() => {
  return props.borLog.signed_pdf || [];
});

const uploadedDocuments = computed(() => {
  return props.borLog.uploaded_documents || [];
});

// Status badge configuration for display
const getStatusBadge = status => {
  const statusConfig = {
    SIGNATURE_REQUESTED: {
      class: 'bg-yellow-100 text-yellow-800',
      text: 'Signature Requested',
    },
    DOCUMENT_SIGNED: {
      class: 'bg-indigo-100 text-indigo-800',
      text: 'Document Signed',
    },
    DOCUMENT_UPLOADED: {
      class: 'bg-purple-100 text-purple-800',
      text: 'Document Uploaded',
    },
    CANCELLED: { class: 'bg-gray-100 text-gray-800', text: 'Cancelled' },
    COMPLETED: { class: 'bg-green-100 text-green-800', text: 'Completed' },
  };
  return (
    statusConfig[status] || { class: 'bg-gray-100 text-gray-800', text: status }
  );
};

// Download document using the same method as BorRequestForm
const downloadFile = doc => {
  const save = document.createElement('a');
  if (typeof save.download !== 'undefined') {
    save.href =
      window.location.protocol +
      '//' +
      window.location.host +
      '/bor/logs/' +
      props.borLog.id +
      '/download?path=' +
      doc.doc_url;
    save.target = '_blank';
    save.download = doc.doc_name;
    save.dispatchEvent(new MouseEvent('click'));
  } else {
    window.location.href =
      window.location.protocol +
      '//' +
      window.location.host +
      '/bor/logs/' +
      props.borLog.id +
      '/download?path=' +
      doc.doc_url;
  }

  downloadLoader.value = true;
  setTimeout(() => {
    downloadLoader.value = false;
  }, 1300);
};

// View BOR PDF document (generates PDF on-the-fly - base64 preview only)
const viewSignedPdf = async () => {
  try {
    downloadLoader.value = true;

    const response = await axios.get(
      route('bor.logs.view-signed-pdf', {
        borLogId: props.borLog.id,
      }),
    );

    if (response.data.success) {
      // Open PDF in new window for viewing only
      const newWindow = window.open();
      newWindow.document.write(`
        <html>
          <head>
            <title>View: ${response.data.name}</title>
            <style>
              body { margin: 0; padding: 0; }
              iframe { width: 100%; height: 100vh; border: none; }
            </style>
          </head>
          <body>
            <iframe src="${response.data.data}" type="application/pdf"></iframe>
          </body>
        </html>
      `);
      newWindow.document.close();
    } else {
      throw new Error(response.data.message || 'Failed to load document');
    }
  } catch (error) {
    const notification = useNotifications('toast');
    notification.error({
      title: 'View Error',
      message: 'Failed to view signed document. Please try again.',
      position: 'top',
    });
  } finally {
    downloadLoader.value = false;
  }
};

// Handle close
const handleClose = () => {
  error.value = null;
  emit('close');
};

// Watch for modal visibility changes
watch(
  () => props.visible,
  newVisible => {
    showModal.value = newVisible;
  },
);
</script>

<template>
  <x-modal
    v-model="showModal"
    :title="'View BOR Documents - ' + borLog.bor_reference"
    size="xl"
    backdrop
  >
    <template #default>
      <div class="space-y-6">
        <!-- BOR Status Display -->
        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-lg font-semibold text-blue-900 mb-2">
                BOR Request Status
              </h4>
              <div class="flex items-center space-x-3">
                <span class="text-sm text-blue-700">Current Status:</span>
                <span
                  :class="getStatusBadge(borLog.status).class"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusBadge(borLog.status).text }}
                </span>
              </div>
              <div class="mt-2 text-sm text-blue-600">
                <strong>BOR Reference:</strong> {{ borLog.bor_reference }}
              </div>
              <div class="text-sm text-blue-600">
                <strong>Created:</strong> {{ formatDate(borLog.created_at) }}
              </div>
              <div v-if="borLog.date_uploaded" class="text-sm text-blue-600">
                <strong>Uploaded:</strong>
                {{ formatDate(borLog.date_uploaded) }}
              </div>
              <div v-if="borLog.date_signed" class="text-sm text-blue-600">
                <strong>Signed:</strong> {{ formatDate(borLog.date_signed) }}
              </div>
            </div>
          </div>
        </div>

        <!-- BOR Request Information -->
        <div class="bg-gray-50 p-6 rounded-lg">
          <h4
            class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
          >
            <svg
              class="w-5 h-5 mr-2 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            BOR Request Information
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-600"
                >Customer Type</label
              >
              <p class="text-sm text-gray-900">
                {{ borLog.customer_type || 'N/A' }}
              </p>
            </div>

            <div v-if="borLog.customer_type === 'Individual'">
              <label class="text-sm font-medium text-gray-600"
                >Customer Name</label
              >
              <p class="text-sm text-gray-900">
                {{ borLog.insurer_name || 'N/A' }}
              </p>
            </div>

            <div
              v-if="borLog.customer_type === 'Entity'"
              class="min-w-0 flex-1"
            >
              <label class="text-sm font-medium text-gray-600 block"
                >Company Name</label
              >
              <p class="text-sm text-gray-900 break-words overflow-hidden">
                {{ borLog.company_name || 'N/A' }}
              </p>
            </div>

            <div v-if="borLog.insurance_provider">
              <label class="text-sm font-medium text-gray-600"
                >Insurance Provider</label
              >
              <p class="text-sm text-gray-900">
                {{ borLog.insurance_provider?.text || 'N/A' }}
              </p>
            </div>

            <div v-if="borLog.policy_number">
              <label class="text-sm font-medium text-gray-600"
                >Policy Number</label
              >
              <p class="text-sm text-gray-900">{{ borLog.policy_number }}</p>
            </div>

            <div v-if="borLog.policy_expiry">
              <label class="text-sm font-medium text-gray-600"
                >Policy Expiry</label
              >
              <p class="text-sm text-gray-900">{{ borLog.policy_expiry }}</p>
            </div>

            <div v-if="borLog.chassis_number">
              <label class="text-sm font-medium text-gray-600"
                >Chassis Number</label
              >
              <p class="text-sm text-gray-900">{{ borLog.chassis_number }}</p>
            </div>

            <div>
              <label class="text-sm font-medium text-gray-600"
                >Email Sent</label
              >
              <p class="text-sm text-gray-900">
                {{ borLog.email_sent ? 'Yes' : 'No' }}
              </p>
            </div>

            <div>
              <label class="text-sm font-medium text-gray-600"
                >Total Documents</label
              >
              <p class="text-sm text-gray-900">
                {{ borLog.total_documents || 0 }}
              </p>
            </div>

            <div v-if="borLog.cancellation_reason">
              <label class="text-sm font-medium text-gray-600"
                >Cancellation Reason</label
              >
              <p class="text-sm text-gray-900">
                {{ borLog.cancellation_reason }}
              </p>
            </div>

            <div v-if="borLog.additional_notes">
              <label class="text-sm font-medium text-gray-600"
                >Additional Notes</label
              >
              <p class="text-sm text-gray-900">{{ borLog.additional_notes }}</p>
            </div>
          </div>
        </div>

        <!-- Uploaded Documents (Broker on Record Letters) -->
        <div v-if="hasUploadedDocuments" class="bg-gray-50 p-4 rounded-lg">
          <h4
            class="text-lg font-semibold text-gray-900 mb-3 flex items-center"
          >
            <svg
              class="w-5 h-5 mr-2 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            Broker on Record Letters
          </h4>
          <div class="space-y-2">
            <div
              v-for="document in uploadedDocuments"
              :key="document.id"
              class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md"
            >
              <div class="flex items-center space-x-3">
                <svg
                  class="w-8 h-8 text-blue-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ document.doc_name }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ document.doc_mime_type }}
                  </p>
                  <p class="text-xs text-gray-400">
                    Uploaded: {{ formatDate(document.updated_at) }}
                  </p>
                  <p class="text-xs text-gray-400">
                    Type: {{ document.document_type_text }}
                  </p>
                </div>
              </div>
              <x-button
                size="xs"
                color="primary"
                :loading="downloadLoader"
                @click.prevent="downloadFile(document)"
              >
                View
              </x-button>
            </div>
          </div>
        </div>

        <!-- Signed PDF Documents -->
        <div v-if="hasSignedDocument" class="bg-gray-50 p-4 rounded-lg">
          <h4
            class="text-lg font-semibold text-gray-900 mb-3 flex items-center"
          >
            <svg
              class="w-5 h-5 mr-2 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            Signed BOR Documents
            <span
              class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
              Signed
            </span>
          </h4>
          <div class="space-y-2">
            <div
              v-for="document in signedDocuments"
              :key="document.id"
              class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md"
            >
              <div class="flex items-center space-x-3">
                <svg
                  class="w-8 h-8 text-red-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                  />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ document.doc_name }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ document.doc_mime_type }}
                  </p>
                  <p class="text-xs text-gray-400">
                    Uploaded: {{ formatDate(document.updated_at) }}
                  </p>
                  <p class="text-xs text-gray-400">
                    Type: {{ document.document_type_text }}
                  </p>
                </div>
              </div>
              <x-button
                size="xs"
                color="primary"
                :loading="downloadLoader"
                @click.prevent="viewSignedPdf()"
              >
                View PDF
              </x-button>
            </div>
          </div>
        </div>

        <!-- No Documents State -->
        <div
          v-if="!hasSignedDocument && !hasUploadedDocuments"
          class="text-center py-8"
        >
          <svg
            class="w-12 h-12 text-gray-400 mx-auto mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
            />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            No documents available
          </h3>
          <p class="text-gray-500">
            Documents will appear here once they are uploaded and signed.
          </p>
        </div>
      </div>
    </template>
    <template #actions>
      <div class="flex justify-end space-x-3">
        <x-button
          @click="closeModal"
          :disabled="isSubmitting"
          color="primary"
          ghost
        >
          Close
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
