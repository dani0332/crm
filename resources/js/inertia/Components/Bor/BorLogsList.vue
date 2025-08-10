<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BorCancelModal from './BorCancelModal.vue'
import BorDoneModal from './BorDoneModal.vue'
import BorViewDocumentModal from './BorViewDocumentModal.vue'
import Pagination from '../Pagination.vue'

const notification = useNotifications('toast'); // Fix: Use consistent notification import

const props = defineProps({
  logs: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  borStatusEnum: {
    type: Object,
    required: true
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
    })
  }
})

const emit = defineEmits(['upload-document', 'update-status', 'cancel-bor', 'mark-done', 'view-document', 'refresh', 'page-change', 'edit-bor', 'update-log'])

// Permission management
const page = usePage()
const can = permission => useCan(permission)
const permissionsEnum = page.props.permissionsEnum

// Modal state management
const showCancelModal = ref(false)
const showDoneModal = ref(false)
const showViewDocumentModal = ref(false)
const selectedLog = ref(null)

// DataTable configuration
const tableHeaders = ref([
  { text: '#', value: 'bor_reference', sortable: false },
  { text: 'Date Created', value: 'date_created', sortable: true },
  { text: 'Date Signed', value: 'date_signed', sortable: true },
  { text: 'Date Uploaded', value: 'date_uploaded', sortable: true },
  { text: 'Document Id', value: 'document_id', sortable: true },
  { text: 'User agent', value: 'user_agent', sortable: true },
  { text: 'Email Sent to UW', value: 'email_sent', sortable: false },
  { text: 'Status', value: 'status', sortable: false },
  { text: 'Actions', value: 'actions', sortable: false }
])

// Format data for DataTable
const tableItems = computed(() => {
  return props.logs.map(log => ({
    ...log,
    email_sent: log.email_sent ? 'Yes' : 'No',
    status_badge: getStatusBadge(log.status),
    actions: log // Pass full log object for actions
  }))
})

// Status badge configuration
const getStatusBadge = (status) => {
  const statusConfig = {
    // New BorStatusEnum statuses
    'SIGNATURE_REQUESTED': { class: 'bg-yellow-100 text-yellow-800', text: 'Signature Requested' },
    'SENT_TO_INSURER': { class: 'bg-blue-100 text-blue-800', text: 'Sent to Insurer' },
    'DOCUMENT_SIGNED': { class: 'bg-indigo-100 text-indigo-800', text: 'Document Signed' },
    'DOCUMENT_UPLOADED': { class: 'bg-purple-100 text-purple-800', text: 'Document Uploaded' },
    'CANCELLED': { class: 'bg-gray-100 text-gray-800', text: 'Cancelled' },
    'COMPLETED': { class: 'bg-green-100 text-green-800', text: 'Completed' },
  }
  return statusConfig[status] || { class: 'bg-gray-100 text-gray-800', text: status }
}

// Action handlers
const handleUploadDocument = (log) => {
  emit('upload-document', log)
}

const handleUpdateStatus = (log, newStatus) => {
  emit('update-status', log.id, newStatus)
}

const handleCancelBor = (log) => {
  selectedLog.value = log
  showCancelModal.value = true
}

const handleMarkDone = (log) => {
  selectedLog.value = log
  showDoneModal.value = true
}

const handleViewDocument = (log) => {
  selectedLog.value = log
  showViewDocumentModal.value = true
}

const handleEditBor = (log = null) => {
  emit('edit-bor', log)
}

const handleCopyLink = async (log) => {
  // Copy the BOR link to clipboard
  const borGenerateLink = route('bor.requests.generate-link', log.id)
  const response = await axios.get(borGenerateLink)
  const borLink = response.data.data;
  console.log(borLink);
  try {
    if (response.data.success) {
        const el = document.createElement('textarea');
        el.value = borLink;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);

        notification.success({
          title: 'Bor Request Link copied to clipboard',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Bor Request Link Generation Failed',
          position: 'top',
        });
    }
  } catch (err) {
    notification.error({
      title: 'Bor Request Link Generation Failed',
      position: 'top',
    });
  }
}

// Modal event handlers
const onCancelModalClose = () => {
  showCancelModal.value = false
  selectedLog.value = null
}

const onDoneModalClose = () => {
  showDoneModal.value = false
  selectedLog.value = null
}

const onViewDocumentModalClose = () => {
  showViewDocumentModal.value = false
  selectedLog.value = null

  setTimeout(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }, 100);
}

const onCancelSuccess = (updatedBorLog) => {
  // Update the specific BOR log in the list
  emit('refresh');
  onCancelModalClose();
}

const onDoneSuccess = (updatedBorLog) => {
  // Update the specific BOR log in the list
  const index = props.logs.findIndex(log => log.id === updatedBorLog.id);
  if (index !== -1) {
    // Update the log in the parent component
    emit('update-log', index, updatedBorLog);
  }
  onDoneModalClose();
}

const handleRefresh = () => {
  emit('refresh')
}

// Status options for dropdown (supporting both legacy and new statuses)
const statusOptions = [
  // Legacy statuses
  { value: 'pending', label: 'Pending' },
  { value: 'sent', label: 'Sent' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
  { value: 'cancelled', label: 'Cancelled' },
  
  // New BorStatusEnum statuses
  { value: 'SIGNATURE_REQUESTED', label: 'Signature Requested' },
  { value: 'SENT_TO_INSURER', label: 'Sent to Insurer' },
  { value: 'DOCUMENT_SIGNED', label: 'Document Signed' },
  { value: 'DOCUMENT_UPLOADED', label: 'Document Uploaded' },
  { value: 'CANCELLED', label: 'Cancelled' },
  { value: 'COMPLETED', label: 'Completed' },
  { value: 'PENDING_BOR_REQUEST', label: 'Pending BOR Request' }
]

// Helper function to check if an action is allowed based on log data
const canPerformAction = (log, action) => {
  // Fallback logic for determining action availability
  const status = log.status
  const editAndCopyLinkCondition = ![props.borStatusEnum.COMPLETED, props.borStatusEnum.CANCELLED].includes(status);
  const uploadAndDoneCondition = ![props.borStatusEnum.COMPLETED, props.borStatusEnum.CANCELLED, props.borStatusEnum.DOCUMENT_SIGNED, props.borStatusEnum.DOCUMENT_UPLOADED].includes(status);


  switch (action) {
    case 'edit':
      return editAndCopyLinkCondition
    case 'upload':
      return uploadAndDoneCondition && can(permissionsEnum.BOR_DOCUMENT_UPLOAD)
    case 'cancel':
      return ![props.borStatusEnum.CANCELLED, props.borStatusEnum.COMPLETED].includes(status)
    case 'done':
      return [props.borStatusEnum.DOCUMENT_SIGNED, props.borStatusEnum.DOCUMENT_UPLOADED].includes(status)
    case 'view_document':
      return true;
      return [props.borStatusEnum.DOCUMENT_SIGNED, props.borStatusEnum.DOCUMENT_UPLOADED, props.borStatusEnum.CANCELLED, props.borStatusEnum.COMPLETED].includes(status)
    case 'copy_link':
      return editAndCopyLinkCondition
    default:
      return false
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex justify-end items-center mb-4">
      <div class="flex justify-end space-x-2">
        <x-button
          size="sm"
          color="orange"
          @click="handleEditBor()"
          :disabled="isLoading"
        >
          Request BOR
        </x-button>
        <x-button 
          color="primary" 
          size="sm" 
          @click="handleRefresh"
          :disabled="loading"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
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
        <template #item-bor_reference="{ bor_reference }">
          <span class="flex w-40">
            {{ bor_reference }}
          </span>
        </template>
        <!-- Status Column -->
        <template #item-status="{ status }">
          <span 
            :class="getStatusBadge(status).class"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          >
            {{ getStatusBadge(status).text }}
          </span>
        </template>

        <!-- Email Sent Column -->
        <template #item-email_sent="{ email_sent }">
          <span class="inline-flex items-center">
            <svg 
              v-if="email_sent === 'Yes'" 
              class="w-4 h-4 text-green-500 mr-1" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <svg 
              v-else 
              class="w-4 h-4 text-red-500 mr-1" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            {{ email_sent }}
          </span>
        </template>

        <!-- Actions Column -->
        <template #item-actions="{ actions }">
          <div class="flex items-center space-x-1 space-y-1 flex-wrap">
            <!-- Upload Document Button -->
            <x-button
              v-if="canPerformAction(actions, 'upload')"
              color="orange"
              class="mt-1"
              size="sm"
              @click="handleUploadDocument(actions)"
              title="Upload signed document"
            >
              Upload
            </x-button>

            <!-- Cancel Button -->
            <x-button
              v-if="canPerformAction(actions, 'cancel')"
              color="error"
              size="sm"
              @click="handleCancelBor(actions)"
              title="Cancel BOR request"
            >
              Cancel
            </x-button>

            <!-- View Document Button -->
            <x-button
              v-if="canPerformAction(actions, 'view_document')"
              color="primary"
              size="sm"
              @click="handleViewDocument(actions)"
              title="View documents"
            >
              View
            </x-button>

            <x-button
              v-if="canPerformAction(actions, 'done')"
              color="emerald"
              size="sm"
              @click="handleMarkDone(actions)"
              title="Done"
            >
              Done
            </x-button>

            <x-button
              v-if="canPerformAction(actions, 'edit')"
              color="primary"
              size="sm"
              @click="handleEditBor(actions)"
              title="Edit BOR request"
            >
              Edit
            </x-button>

            <x-button
              v-if="canPerformAction(actions, 'copy_link')"
              color="primary"
              size="sm"
              @click="handleCopyLink(actions)"
              title="Copy link"
            >
              Copy
            </x-button>
          </div>
        </template>
      </DataTable>

      <!-- Loading State -->
      <div v-else-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
        <span class="ml-3 text-gray-600">Loading BOR logs...</span>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No BOR logs found</h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by creating a new BOR request.
        </p>
      </div>
    </div>

    <!-- Pagination Controls (similar to Pet Quotes) -->
    <div  class="mt-4 px-6 pb-4">
      <Pagination
        :links="{
          next: pagination.next_page_url,
          prev: pagination.prev_page_url,
          current: pagination.current_page,
          from: pagination.from,
          to: pagination.to,
        }"
        :api="true"
        @navigate="(url) => emit('page-change', url)"
      />
    </div>

    <!-- Modal Components -->
    <BorCancelModal
      v-if="selectedLog"
      :visible="showCancelModal"
      :bor-log="selectedLog"
      @close="onCancelModalClose"
      @success="onCancelSuccess"
    />

    <BorDoneModal
      v-if="selectedLog"
      :visible="showDoneModal"
      :bor-log="selectedLog"
      @close="onDoneModalClose"
      @success="onDoneSuccess"
    />

    <BorViewDocumentModal
      v-if="selectedLog"
      :visible="showViewDocumentModal"
      :bor-log="selectedLog"
      @close="onViewDocumentModalClose"
    />
  </div>
</template> 