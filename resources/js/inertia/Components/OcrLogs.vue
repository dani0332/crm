<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  type: {
    type: String,
    required: true,
  },
  id: {
    type: Number,
    required: true,
  },
  expanded: {
    type: Boolean,
    default: false,
  },
})

const ocrLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'ID', value: 'id' },
    { text: 'Document Type', value: 'document_type_name' },
    { text: 'Status', value: 'status' },
    { text: 'Provider', value: 'provider_name' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Action', value: 'action' },
  ],
})

const selectedLog = ref({})
const modals = reactive({
  ocrLog: false,
})

const selectLog = item => {
  selectedLog.value = item
  modals.ocrLog = true
}

const onLoadOcrLogData = async () => {
  ocrLogs.loading = true
  try {
    const response = await axios.post('/ocr-logs', {
      type: props.type,
      id: props.id,
    })

    if (response.data.success) {
      ocrLogs.data = response.data.data
    } else {
      console.error('Failed to load OCR logs:', response.data.message)
    }
  } catch (error) {
    console.error('Error loading OCR logs:', error)
  } finally {
    ocrLogs.loading = false
  }
}


</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">OCR AI Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-center py-3" v-if="ocrLogs.data === null">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadOcrLogData"
            :loading="ocrLogs.loading"
          >
            Load OCR Logs
          </x-button>
        </div>
        <div v-else>
          <DataTable
            table-class-name="compact tablefixed"
            :headers="ocrLogs.table"
            :items="ocrLogs.data || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="ocrLogs.data?.length < 15"
          >
            <template #item-status="{ status }">
              <x-tag
                v-if="status"
                size="xs"
                :color="status === 'success' ? 'success' : status === 'failed' ? 'red' : status === 'processing' ? 'warning' : 'secondary'"
                class="mt-0.5 text-[10px]"
              >
                <p>
                  {{ status === 'success' ? 'PASSED' : status.toUpperCase() }}
                </p>
              </x-tag>
            </template>
            <template #item-created_at="{ created_at }">
              {{ new Date(created_at).toLocaleString() }}
            </template>
            <template #item-action="item">
              <x-button
                size="xs"
                color="primary"
                outlined
                @click.prevent="selectLog(item)"
              >
                View
              </x-button>
            </template>
          </DataTable>
        </div>
      </template>
    </Collapsible>

    <!-- Modal for log details -->
    <x-modal
      v-model="modals.ocrLog"
      size="lg"
      :title="`OCR Log Details: ${selectedLog?.id}`"
      show-close
      backdrop
    >
             <div v-if="selectedLog">
         <dl class="grid md:grid-cols-2 gap-x-1 gap-y-5">
           <div class="grid sm:grid-cols-2">
             <dt class="font-medium">REF-ID:</dt>
             <dd>{{ selectedLog.ref_id || 'N/A' }}</dd>
           </div>

           <div class="grid sm:grid-cols-2">
             <dt class="font-medium">Document Type:</dt>
             <dd>{{ selectedLog.document_type_name }}</dd>
           </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Status:</dt>
            <dd>
              <x-tag
                v-if="selectedLog.status"
                size="xs"
                :color="selectedLog.status === 'success' ? 'success' : selectedLog.status === 'failed' ? 'red' : selectedLog.status === 'processing' ? 'warning' : 'secondary'"
                class="mt-0.5 text-[10px]"
              >
                {{ selectedLog.status.toUpperCase() }}
              </x-tag>
            </dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Execution Time:</dt>
            <dd>{{ selectedLog.formatted_execution_time }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Provider:</dt>
            <dd>{{ selectedLog.provider_name || 'N/A' }}</dd>
          </div>

                     <div class="grid sm:grid-cols-2">
             <dt class="font-medium">Uploaded Through:</dt>
             <dd>{{ selectedLog.uploaded_through }}</dd>
           </div>

           <div class="grid sm:grid-cols-2">
             <dt class="font-medium">Uploaded By:</dt>
             <dd>{{ selectedLog.user_name || 'N/A' }}</dd>
           </div>

           <div class="grid sm:grid-cols-2">
             <dt class="font-medium">Created At:</dt>
             <dd>{{ new Date(selectedLog.created_at).toLocaleString() }}</dd>
           </div>
        </dl>

        <x-divider class="my-5" />
        
        <!-- Request Data -->
        <div v-if="selectedLog.request_data">
          <dl class="">
            <dt class="font-medium mb-2">Request Data:</dt>
            <div
              class="text-sm h-auto w-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
            >
              <pre class="whitespace-pre-wrap">{{ JSON.stringify(selectedLog.request_data, null, 2) }}</pre>
            </div>
          </dl>
        </div>

        <!-- Response Data -->
        <div v-if="selectedLog.response_data" class="mt-5">
          <dl class="">
            <dt class="font-medium mb-2">Response Data:</dt>
            <div
              class="text-sm h-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
            >
              <pre class="whitespace-pre-wrap">{{ JSON.stringify(selectedLog.response_data, null, 2) }}</pre>
            </div>
          </dl>
        </div>

        <!-- Error Message -->
        <div v-if="selectedLog.error_message" class="mt-5">
          <dl class="">
            <dt class="font-medium mb-2">Error Message:</dt>
            <div
              class="text-sm h-auto break-words p-3.5 bg-red-100 text-red-800 rounded"
            >
              {{ selectedLog.error_message }}
            </div>
          </dl>
        </div>
      </div>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            tabindex="-1"
            @click.prevent="modals.ocrLog = false"
          >
            Close
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template> 