<script setup>
import { getQuoteType } from '@/inertia/Composables/utilities.js';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
  quoteUuid: {
    type: String,
    required: true,
  },
  quoteTypeId: {
    type: Number,
    required: true,
  },
  expanded: {
    type: Boolean,
    default: false,
  },
});

const quoteTypeShortCode = computed(() => {
  return getQuoteType(props.quoteTypeId, 'code') || 'N/A';
});

const uaePassLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Request ID', value: 'request_id' },
    { text: 'API Name', value: 'api_name' },
    { text: 'Status', value: 'status' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Response Status', value: 'response_status' },
    { text: 'Action', value: 'action' },
  ],
});

const selectedLog = ref({});
const modals = reactive({
  uaePassLog: false,
});

const selectLog = item => {
  selectedLog.value = item;
  modals.uaePassLog = true;
};

const onLoadUaePassLogData = async () => {
  uaePassLogs.loading = true;
  try {
    const response = await axios.post('/uae-signing-pass-logs', {
      quote_uuid: props.quoteUuid,
      quote_type_id: props.quoteTypeId,
    });

    if (response.data.success) {
      uaePassLogs.data = response.data.data;
    } else {
      console.error('Failed to load UAE Signing Pass logs:', response.data.message);
    }
  } catch (error) {
    console.error('Error loading UAE Signing Pass logs:', error);
  } finally {
    uaePassLogs.loading = false;
  }
};

const tryParseJson = value => {
  if (!value) return null;

  if (typeof value !== 'string') {
    return value;
  }

  try {
    return JSON.parse(value);
  } catch {
    return value;
  }
};

const formatJson = value => {
  const parsed = tryParseJson(value);
  if (!parsed) return null;

  if (typeof parsed === 'string') {
    return parsed;
  }

  return JSON.stringify(parsed, null, 2);
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">UAE Signing Pass Logs</h3>

          <!-- Refresh Icon - Only visible after logs are loaded -->
          <button
            v-if="uaePassLogs.data !== null"
            @click.prevent.stop="onLoadUaePassLogData"
            :disabled="uaePassLogs.loading"
            class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-gray-100 rounded-full transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Refresh UAE Pass Logs"
          >
            <svg
              :class="{ 'animate-spin': uaePassLogs.loading }"
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8 0 01-15.357-2m15.357 2H15"
              ></path>
            </svg>
          </button>
        </div>
      </template>

      <template #body>
        <x-divider class="my-4" />

        <div class="text-center py-3" v-if="uaePassLogs.data === null">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadUaePassLogData"
            :loading="uaePassLogs.loading"
          >
            Load UAE Signing Pass Logs
          </x-button>
        </div>

        <div v-else class="relative">
          <!-- Loading Overlay -->
          <div
            v-if="uaePassLogs.loading"
            class="absolute inset-0 bg-white/75 backdrop-blur-sm z-10 flex items-center justify-center rounded-lg"
          >
            <div class="flex flex-col items-center gap-3">
              <svg
                class="animate-spin w-8 h-8 text-primary-600"
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
              <span class="text-sm text-gray-600 font-medium"
                >Refreshing logs...</span
              >
            </div>
          </div>

          <DataTable
            table-class-name="compact tablefixed"
            :headers="uaePassLogs.table"
            :items="uaePassLogs.data || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="uaePassLogs.data?.length < 15"
          >
            <template #item-status="item">
              <x-tag
                size="xs"
                :color="item.status_tag_color"
                class="mt-0.5 text-[10px]"
              >
                {{ item.status_display }}
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

    <x-modal
      v-model="modals.uaePassLog"
      size="lg"
      :title="`UAE Signing Pass Log Details: ${quoteTypeShortCode}-${quoteUuid || 'N/A'}`"
      show-close
      backdrop
    >
      <div v-if="selectedLog">
        <dl class="grid md:grid-cols-2 gap-x-1 gap-y-5">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Request ID:</dt>
            <dd>{{ selectedLog.request_id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Proof Of Presentation ID:</dt>
            <dd class="truncate max-w-xs" :title="selectedLog.proof_of_presentation_id">
              {{ selectedLog.proof_of_presentation_id }}
            </dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Status:</dt>
            <dd>
              <x-tag
                size="xs"
                :color="selectedLog.status_tag_color"
                class="mt-0.5 text-[10px]"
              >
                {{ selectedLog.status_display }}
              </x-tag>
            </dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created At:</dt>
            <dd>{{ new Date(selectedLog.created_at).toLocaleString() }}</dd>
          </div>
        </dl>

        <x-divider class="my-5" />

        <div v-if="selectedLog.request_payload">
          <dl class="">
            <dt class="font-medium mb-2">Request Payload:</dt>
            <div
              class="text-sm h-auto w-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
            >
              <pre class="whitespace-pre-wrap">{{
                formatJson(selectedLog.request_payload)
              }}</pre>
            </div>
          </dl>
        </div>

        <div v-if="selectedLog.presentation_response_payload" class="mt-5">
          <dl class="">
            <dt class="font-medium mb-2">Presentation Response Payload:</dt>
            <div
              class="text-sm h-auto w-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
            >
              <pre class="whitespace-pre-wrap">{{
                formatJson(selectedLog.presentation_response_payload)
              }}</pre>
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
            @click.prevent="modals.uaePassLog = false"
          >
            Close
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>