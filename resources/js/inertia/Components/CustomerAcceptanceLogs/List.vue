<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Pagination from '../Pagination.vue';

const notification = useNotifications('toast'); // Fix: Use consistent notification import

const props = defineProps({
  logs: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  customerAcceptanceLogsUrl: {
    type: String,
    required: true,
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      next_page_url: null,
      prev_page_url: null,
      from: 0,
      to: 0,
    }),
  },
});

const emit = defineEmits([
  'refresh',
  'page-change',
  'view-document',
]);

// Permission management
const page = usePage();
const downloadLoader = ref(false);

// DataTable configuration
const tableHeaders = ref([
  { text: '#', value: 'id', sortable: false },
  { text: 'Date Created', value: 'created_at', sortable: true },
  { text: 'Document Hash', value: 'document_hash', sortable: true },
  { text: 'User agent', value: 'user_agent', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false },
]);

// Format data for DataTable
const tableItems = computed(() => {
  return props.logs.map(log => ({
    ...log,
    actions: log, // Pass full log object for actions
  }));
});

const handleDownloadDocument = async doc => {
  try {
    debugger;
    downloadLoader.value = true;
    
    const fullUrl = doc.document_link;
    const urlParts = fullUrl.split('/');
    const fileName = urlParts[urlParts.length - 1];
    
    // Method 1: Try direct download with fetch (handles CORS and errors better)
    try {
      const response = await fetch(props.customerAcceptanceLogsUrl+fullUrl, {
        method: 'GET',
        mode: 'cors',
      });
      
      if (!response.ok) {
        if (response.status === 403) {
          notification.error('Access denied. The document is not publicly accessible.');
          return;
        }
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      // Convert response to blob and download
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      
      const link = document.createElement('a');
      link.href = url;
      link.download = fileName;
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      // Clean up the object URL
      window.URL.revokeObjectURL(url);
      
      notification.success('Document downloaded successfully');
      
    } catch (fetchError) {
      console.warn('Fetch download failed, trying direct link:', fetchError);
      
      // Method 2: Fallback to direct link (opens in new tab)
      const link = document.createElement('a');
      link.href = fullUrl;
      link.download = fileName;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      notification.info('Document opened in new tab. Please save it manually if needed.');
    }
    
  } catch (error) {
    console.error('Download failed:', error);
    notification.error('Failed to download document. Please try again.');
  } finally {
    setTimeout(() => {
      downloadLoader.value = false;
    }, 1000);
  }
};

const handleRefresh = () => {
  emit('refresh');
};

</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex justify-end items-center mb-4">
      <div class="flex justify-end space-x-2">
        <x-button
          color="primary"
          size="sm"
          @click="handleRefresh"
          :disabled="loading"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
        </x-button>
      </div>
    </div>

    <!-- DataTable -->
    <div class="">
      <DataTable
        v-if="!loading && logs.length > 0"
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="true"
        :headers="tableHeaders"
        :items="tableItems"
        :loading="loading"
        table-class-name="min-w-full"
        header-text-direction="center"
        body-text-direction="center"
        border-cell
        alternating
      >
        <!-- Actions Column -->
        <template #item-actions="{ actions }">
          <div class="flex items-center space-x-1 space-y-1 flex-wrap">
            <!-- Hidden button to fixing the alignment issues for all button -->
            <x-button class="hidden"></x-button>

            <!-- View Document Button -->
            <x-button
              color="primary"
              size="sm"
              @click="handleDownloadDocument(actions)"
              :loading="downloadLoader"
              title="Download Document"
            >
                <span class="px-2"> Download Document </span>
            </x-button>
          </div>
        </template>
      </DataTable>

      <!-- Loading State -->
      <div v-else-if="loading" class="flex justify-center items-center py-12">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"
        ></div>
        <span class="ml-3 text-gray-600">Loading Digital Consent logs...</span>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
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
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          No Digital Consent logs found
        </h3>
      </div>
    </div>

    <!-- Pagination Controls (similar to Pet Quotes) -->
    <div class="mt-4 px-6 pb-4">
      <Pagination
        :links="{
          next: pagination.next_page_url,
          prev: pagination.prev_page_url,
          current: pagination.current_page,
          from: pagination.from,
          to: pagination.to,
        }"
        :api="true"
        @navigate="url => emit('page-change', url)"
      />
    </div>

  </div>
</template>
