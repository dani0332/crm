<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
  type: {
    type: String,
    required: true,
  },
  teamCategory: {
    type: String,
  },
  quoteRequestId: {
    type: Number,
  },
  expanded: {
    type: Boolean,
    default: true,
  },
});

const routingLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'User', value: 'user.name' },
    ...(props.type == 'CONFIGURATION'
      ? [{ text: 'Team Category', value: 'team_category' }]
      : []),
    ...(props.type == 'ROUTING' ? [{ text: 'Source', value: 'source' }] : []),
    { text: 'Log Data', value: 'log_data' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Action', value: 'action' },
  ],
});

const eligibleProviders = computed(
  () => page.props.eligibleOcrProviders?.providers || {},
);

const dynamicQuoteTypeNames = computed(
  () => page.props.eligibleOcrProviders?.quoteTypeNames || {},
);

const selectedLog = ref({});
const modals = reactive({
  ocrLog: false,
});

const selectLog = item => {
  selectedLog.value = item;
  modals.log = true;
};

const onLoadLogData = async () => {
  routingLogs.loading = true;
  try {
    const response = await axios.post('/health-routing-logs', {
      type: props.type,
      team_category: props.teamCategory,
      quote_request_id: props.quoteRequestId,
    });

    if (response.data.success) {
      routingLogs.data = response.data.data;
    } else {
      console.error('Failed to load OCR logs:', response.data.message);
    }
  } catch (error) {
    console.error('Error loading OCR logs:', error);
  } finally {
    routingLogs.loading = false;
  }
};

// Format the providers data for display in the tooltip
const formattedProviders = computed(() => {
  const formatted = [];

  // Group providers by name
  const providerMap = {};

  Object.entries(eligibleProviders.value).forEach(([quoteType, providers]) => {
    const quoteTypeName = dynamicQuoteTypeNames.value[quoteType] || quoteType;

    providers.forEach(provider => {
      if (!providerMap[provider.name]) {
        providerMap[provider.name] = [];
      }

      if (!providerMap[provider.name].includes(quoteTypeName)) {
        providerMap[provider.name].push(quoteTypeName);
      }
    });
  });

  // Convert to array format for display
  Object.entries(providerMap).forEach(([providerName, quoteTypes]) => {
    // Replace underscores with spaces in provider names
    const formattedName = providerName.replace(/_/g, ' ');

    formatted.push({
      name: formattedName,
      types: quoteTypes.join(', '),
    });
  });

  return formatted.sort((a, b) => a.name.localeCompare(b.name));
});

watch(
  () => props.teamCategory,
  () => {
    onLoadLogData();
  },
);
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">
            Health Routing Logs
          </h3>
          <!-- Refresh Icon - Only visible after logs are loaded -->
          <button
            v-if="routingLogs.data !== null"
            @click.prevent.stop="onLoadLogData"
            :disabled="routingLogs.loading"
            class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-gray-100 rounded-full transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Refresh OCR Logs"
          >
            <svg
              :class="{ 'animate-spin': routingLogs.loading }"
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
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              ></path>
            </svg>
          </button>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-center py-3" v-if="routingLogs.data === null">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadLogData"
            :loading="routingLogs.loading"
          >
            Load Health Routing Logs
          </x-button>
        </div>
        <div v-else class="relative">
          <!-- Loading Overlay -->
          <div
            v-if="routingLogs.loading"
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
            :headers="routingLogs.table"
            :items="routingLogs.data || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="routingLogs.data?.length < 15"
          >
            <template #item-created_at="{ created_at }">
              {{ new Date(created_at).toLocaleString() }}
            </template>
            <template #item-log_data="{ log_data }">
              {{ JSON.stringify(log_data) }}
            </template>
            <template #item-action="item">
              <div style="width: 60px">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  class="w-full"
                  @click.prevent="selectLog(item)"
                >
                  View
                </x-button>
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Collapsible>

    <!-- Modal for log details -->
    <x-modal
      v-model="modals.log"
      size="lg"
      :title="`Health Routing Log Details${selectedLog.type == 'ROUTING' ? ': ' + selectedLog.uuid : ''}`"
      show-close
      backdrop
    >
      <div v-if="selectedLog">
        <dl class="grid md:grid-cols-2 gap-x-1 gap-y-5">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">User:</dt>
            <dd>{{ selectedLog.user.name }}</dd>
          </div>
          <div
            class="grid sm:grid-cols-2"
            v-if="selectedLog.type == 'CONFIGURATION'"
          >
            <dt class="font-medium">Team Category:</dt>
            <dd>{{ selectedLog.team_category }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created At:</dt>
            <dd>{{ new Date(selectedLog.created_at).toLocaleString() }}</dd>
          </div>
          <div class="grid sm:grid-cols-2" v-if="selectedLog.type == 'ROUTING'">
            <dt class="font-medium">Source:</dt>
            <dd>{{ selectedLog.source }}</dd>
          </div>
        </dl>
        <x-divider class="my-5" />

        <!-- Log Data -->
        <div>
          <dl class="">
            <dt class="font-medium mb-2">Log Data:</dt>
            <div
              class="text-sm h-auto w-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
            >
              <pre class="whitespace-pre-wrap">{{ selectedLog.log_data }}</pre>
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
            @click.prevent="modals.log = false"
          >
            Close
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
