<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import BorLogsList from './BorLogsList.vue';
import BorRequestForm from './BorRequestForm.vue';
import BorUploadDocument from './BorUploadDocument.vue';
import BorCancelModal from './BorCancelModal.vue';
import BorDoneModal from './BorDoneModal.vue';
import BorViewDocumentModal from './BorViewDocumentModal.vue';
import axios from 'axios';

const props = defineProps({
  leadId: {
    type: [String, Number],
    required: true,
  },
  isCompanyCar: {
    type: Boolean,
    default: false,
  },
  lob: {
    type: String,
    required: true,
  },
  hasPolicyIssuedStatus: {
    type: Boolean,
    default: false,
  },
  customerData: {
    type: Object,
    default: () => ({}),
  },
  autoCollapse: {
    type: Boolean,
    default: true,
  },
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  documentTypes: {
    type: Object,
    required: false,
    default: () => ({}),
  },
  insuranceProviders: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const notification = useNotifications('toast'); // Fix: Use consistent notification import

// Reactive data
const borLogs = ref([]);
const total = ref(0);
const BorStatusEnum = ref({});
const isLoading = ref(false);
const error = ref(null);
const showBorRequestForm = ref(false);
const showUploadModal = ref(false);
const showCancelModal = ref(false);
const showDoneModal = ref(false);
const showViewDocumentModal = ref(false);
const selectedBorLog = ref(null);
// Edit mode state
const isEditMode = ref(false);
const editingBorLog = ref(null);

// Auto-collapse when policy is issued
const isCollapsed = ref(props.autoCollapse && props.hasPolicyIssuedStatus);

// Override with expanded prop if provided
watch(
  () => props.expanded,
  newValue => {
    if (newValue !== undefined) {
      isCollapsed.value = !newValue;
    }
  },
  { immediate: true },
);

// Methods
const toggleSection = () => {
  isCollapsed.value = !isCollapsed.value;
};

const fetchBorLogs = async (page = 1) => {
  if (!props.leadId) return;

  isLoading.value = true;
  error.value = null;

  try {
    // Use simple axios call with pagination, similar to Pet Quotes approach
    const response = await axios.get(route('bor.requests.index'), {
      params: { page, leadId: props.leadId, lob: props.lob },
    });

    if (response.data.success) {
      // Handle Laravel pagination response
      const paginatedData = response.data.data;
      borLogs.value = paginatedData.data || [];
      total.value = response.data.total;
      BorStatusEnum.value = response.data.bor_status_enum;
      // Extract pagination info from Laravel pagination response
      pagination.value = {
        current_page: paginatedData.current_page,
        last_page: paginatedData.last_page,
        next_page_url: paginatedData.next_page_url,
        prev_page_url: paginatedData.prev_page_url,
        from: paginatedData.from,
        to: paginatedData.to,
      };
    } else {
      throw new Error(response.data.message || 'Failed to fetch BOR logs');
    }
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Failed to load BOR logs. Please try again.';

    notification.error({
      title: 'Error',
      message: 'Failed to load BOR logs. Please try again.',
      timeout: 5000,
    });
  } finally {
    isLoading.value = false;
  }
};

// Add pagination state
const pagination = ref({
  current_page: 1,
  last_page: 1,
  next_page_url: null,
  prev_page_url: null,
  from: 0,
  to: 0,
});

// Handle page changes
const handlePageChange = pageUrl => {
  // Extract page number from URL or use page number directly
  let page = 1;
  if (typeof pageUrl === 'number') {
    page = pageUrl;
  } else if (typeof pageUrl === 'string' && pageUrl) {
    const urlParams = new URLSearchParams(pageUrl.split('?')[1]);
    page = parseInt(urlParams.get('page')) || 1;
  }
  fetchBorLogs(page);
};

const openBorRequestForm = () => {
  isEditMode.value = false;
  editingBorLog.value = null;
  showBorRequestForm.value = true;
};

// Handle BOR edit
const handleEditBor = (borLog = null) => {
  isEditMode.value = borLog ? true : false;
  editingBorLog.value = borLog;
  showBorRequestForm.value = true;
};

const closeBorRequestForm = () => {
  showBorRequestForm.value = false;
  isEditMode.value = false;
  editingBorLog.value = null;

  // Ensure scroll is restored when modal closes
  setTimeout(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }, 100);
};

const handleBorRequestSuccess = newBorLog => {
  // Add the new BOR log to the list
  fetchBorLogs(1);

  // Close the form
  closeBorRequestForm();

  // Ensure form is closed and scroll is restored
  showBorRequestForm.value = false;

  // Force scroll restoration
  setTimeout(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';

    // Force a repaint to ensure scrolling works
    window.dispatchEvent(new Event('resize'));
  }, 150);

  // Expand section if collapsed
  if (isCollapsed.value) {
    isCollapsed.value = false;
  }
};

const handleUploadDocument = borLog => {
  selectedBorLog.value = borLog;
  showUploadModal.value = true;
};

const closeUploadModal = () => {
  showUploadModal.value = false;
  selectedBorLog.value = null;
  setTimeout(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }, 100);
};

const handleUploadSuccess = updatedBorLog => {
  // Update the BOR log in the list
  const index = borLogs.value.findIndex(log => log.id === updatedBorLog.id);
  if (index !== -1) {
    borLogs.value[index] = updatedBorLog;
  }

  // Close the modal
  closeUploadModal();
};

const handleUpdateStatus = (borLogId, newStatus) => {
  // Use Inertia to update status
  router.put(
    route('bor.logs.update-status', borLogId),
    {
      status: newStatus,
    },
    {
      preserveScroll: true,
      onSuccess: page => {
        // Update the BOR log in the list
        const index = borLogs.value.findIndex(log => log.id === borLogId);
        if (index !== -1 && page.props.updatedBorLog) {
          borLogs.value[index] = page.props.updatedBorLog;
        }

        notification.success({
          title: 'Success',
          message: 'BOR status updated successfully',
          position: 'top',
        });
      },
      onError: errors => {
        notification.error({
          title: 'Error',
          message: 'Failed to update status',
          timeout: 5000,
        });
      },
    },
  );
};

// New action handlers for the modals
const handleCancelBor = borLog => {
  selectedBorLog.value = borLog;
  showCancelModal.value = true;
};

const handleMarkDone = borLog => {
  selectedBorLog.value = borLog;
  showDoneModal.value = true;
};

const handleViewDocument = borLog => {
  selectedBorLog.value = borLog;
  showViewDocumentModal.value = true;
};

// Modal close handlers
const closeCancelModal = () => {
  showCancelModal.value = false;
  selectedBorLog.value = null;
};

const closeDoneModal = () => {
  showDoneModal.value = false;
  selectedBorLog.value = null;
};

const closeViewDocumentModal = () => {
  showViewDocumentModal.value = false;
  selectedBorLog.value = null;

  setTimeout(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }, 100);
};

// Success handlers for modal actions
const handleActionSuccess = updatedBorLog => {
  // Update the BOR log in the list
  const index = borLogs.value.findIndex(log => log.id === updatedBorLog.id);
  if (index !== -1) {
    borLogs.value[index] = updatedBorLog;
  }

  // Show success notification
  notification.success({
    title: 'Success',
    message: 'BOR action completed successfully',
    timeout: 5000,
  });
};

const handleUpdateLog = (index, updatedBorLog) => {
  // Update the specific BOR log in the list by index
  if (index >= 0 && index < borLogs.value.length) {
    borLogs.value[index] = updatedBorLog;
  }
};

// Watch for lead ID changes
watch(
  () => props.leadId,
  newLeadId => {
    if (newLeadId) {
      fetchBorLogs();
    }
  },
  { immediate: false },
);

// Lifecycle
onMounted(() => {
  // Always fetch BOR logs independently when component mounts
  fetchBorLogs(1);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="!isCollapsed">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            BOR (Broker on Record)
            <x-tag size="sm">{{ total || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />

        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-8">
          <div class="inline-flex items-center">
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-orange-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Loading BOR logs...
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="py-4">
          <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg
                  class="h-5 w-5 text-red-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  Error loading BOR logs
                </h3>
                <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                <x-button
                  @click="fetchBorLogs"
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

        <!-- Empty State -->
        <div v-else-if="borLogs.length === 0" class="text-center py-8">
          <div class="max-w-sm mx-auto">
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
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
              No BOR requests yet
            </h3>
            <p class="text-gray-500 mb-4">
              Create a Broker on Record request to transfer policy ownership and
              receive important documents.
            </p>
            <x-button @click="openBorRequestForm" color="orange">
              Create First BOR Request
            </x-button>
          </div>
        </div>

        <!-- BOR Logs List -->
        <div v-else>
          <BorLogsList
            :logs="borLogs"
            :loading="isLoading"
            :pagination="pagination"
            :bor-status-enum="BorStatusEnum"
            @upload-document="handleUploadDocument"
            @update-status="handleUpdateStatus"
            @cancel-bor="handleCancelBor"
            @mark-done="handleMarkDone"
            @view-document="handleViewDocument"
            @refresh="fetchBorLogs"
            @page-change="handlePageChange"
            @edit-bor="handleEditBor"
            @update-log="handleUpdateLog"
          />
        </div>
      </template>
    </Collapsible>

    <!-- BOR Request Form Modal -->
    <BorRequestForm
      v-if="showBorRequestForm"
      :visible="showBorRequestForm"
      :isCompanyCar="isCompanyCar"
      :lead-id="leadId"
      :lob="lob"
      :customer-data="customerData"
      :insurance-providers="insuranceProviders"
      :bor-log="editingBorLog"
      :bor-status-enum="BorStatusEnum"
      @close="closeBorRequestForm"
      @success="handleBorRequestSuccess"
    />

    <!-- Upload Document Modal -->
    <BorUploadDocument
      v-if="showUploadModal"
      :visible="showUploadModal"
      :bor-log="selectedBorLog"
      :document-types="documentTypes"
      @close="closeUploadModal"
      @success="handleUploadSuccess"
    />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
