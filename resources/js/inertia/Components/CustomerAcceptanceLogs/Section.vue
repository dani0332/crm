<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import List from './List.vue';
import axios from 'axios';

const props = defineProps({
  leadId: {
    type: [String, Number],
    required: true,
  },
  lob: {
    type: String,
    required: true,
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
});

const page = usePage();
const notification = useNotifications('toast'); // Fix: Use consistent notification import

// Reactive data
const customerAcceptanceLogs = ref([]);
const customerAcceptanceLogsUrl = ref('');
const isLoading = ref(false);
const error = ref(null);

// Auto-collapse when policy is issued
const isCollapsed = ref(props.autoCollapse);

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

const fetchCustomerAcceptanceLogs = async (page = 1) => {
  if (!props.leadId) return;

  isLoading.value = true;
  error.value = null;

  try {
    // Use simple axios call with pagination, similar to Pet Quotes approach
    const response = await axios.get('/consent-logs/request', {
      params: { page, leadId: props.leadId, lob: props.lob },
    });

    if (response.data.success) {
      // Handle Laravel pagination response
      const paginatedData = response.data.data;
      customerAcceptanceLogs.value = paginatedData.data || [];
      customerAcceptanceLogsUrl.value = response.data.customerAcceptanceLogsUrl;
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
      throw new Error(response.data.message || 'Failed to fetch Customer Acceptance Logs');
    }
  } catch (err) {
    console.error('Error fetching Customer Acceptance Logs:', err);
    error.value =
      err.response?.data?.message ||
      'Failed to load Customer Acceptance Logs. Please try again.';

    notification.error({
      title: 'Error',
      message: 'Failed to load Customer Acceptance Logs. Please try again.',
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
  fetchCustomerAcceptanceLogs(page);
};

// Watch for lead ID changes
watch(
  () => props.leadId,
  newLeadId => {
    if (newLeadId) {
      fetchCustomerAcceptanceLogs();
    }
  },
  { immediate: false },
);

// Lifecycle
onMounted(() => {
  // Always fetch Customer Acceptance Logs independently when component mounts
  fetchCustomerAcceptanceLogs(1);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="!isCollapsed">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Digital Consent
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
            Loading Digital Consent logs...
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
                  Error loading Digital Consent logs
                </h3>
                <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                <x-button
                  @click="fetchCustomerAcceptanceLogs"
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
        <div v-else-if="customerAcceptanceLogs.length === 0" class="text-center py-8">
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
              No digital consent logs yet
            </h3>
          </div>
        </div>

        <!-- Customer Acceptance Logs List -->
        <div v-else>
          <List
            :logs="customerAcceptanceLogs"
            :loading="isLoading"
            :pagination="pagination"
            :customerAcceptanceLogsUrl="customerAcceptanceLogsUrl"
            @refresh="fetchCustomerAcceptanceLogs"
            @page-change="handlePageChange"
          />
        </div>
      </template>
    </Collapsible>
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
