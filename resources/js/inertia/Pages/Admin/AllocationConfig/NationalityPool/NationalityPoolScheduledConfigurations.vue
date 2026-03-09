<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
  expanded: {
    type: Boolean,
    default: true,
  },
});

const routingLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Created Date', value: 'user.name' },
    { text: 'User', value: 'log_data' },
    { text: 'Event', value: 'event' },
    { text: 'Effective From', value: 'effective_from' },
    { text: 'Effective To', value: 'effective_to' },
    { text: 'Nationalities', value: 'nationalities' },
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
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">
           Scheduled Configurations
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="relative">
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
