<script setup>
import { reactive, onMounted } from 'vue';

const props = defineProps({
  expanded: {
    type: Boolean,
    default: true,
  },
});
const notification = useToast();
const tooltip = ref({
  visible: false,
  text: '',
  x: 0,
  y: 0,
});

const routingLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Created Date', value: 'created_at' },
    { text: 'User', value: 'user' },
    { text: 'Effective From', value: 'effective_from' },
    { text: 'Effective To', value: 'effective_to' },
    { text: 'Nationalities', value: 'nationalities' },
  ],
});

const loadData = async () => {
  routingLogs.loading = true;

  axios
    .get(route('admin.nationality-pool-audit-logs', 'audit'))
    .then(response => {
      routingLogs.data = response.data.data;
    })
    .catch(error => {
      notification.error({
        title: 'Error loading audit logs',
        position: 'top',
      });
    })
    .finally(() => {
      routingLogs.loading = false;
    });
};

const showTooltip = (event, nationalities) => {
  const rect = event.target.getBoundingClientRect();

  tooltip.value = {
    visible: true,
    text: nationalities.join(', '),
    x: rect.left + rect.width / 2,
    y: rect.top,
  };
};

const hideTooltip = () => {
  tooltip.value.visible = false;
};

defineExpose({
  loadData,
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">Audit Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-center py-3" v-if="routingLogs.data === null">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="loadData"
            :loading="routingLogs.loading"
          >
            Load Logs
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
                >Loading logs...</span
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
            <template #item-nationalities="{ nationalities }">
              <span>
                {{ nationalities.slice(0, 7).join(', ') }}
              </span>
              <span
                v-if="nationalities.length > 7"
                class="text-primary cursor-pointer ml-1"
                @mouseenter="showTooltip($event, nationalities)"
                @mouseleave="hideTooltip"
              >
                +{{ nationalities.length - 7 }} more
              </span>
            </template>
          </DataTable>
        </div>

        <!-- Tooltip -->
        <Teleport to="body">
          <div
            v-if="tooltip.visible"
            :style="{
              position: 'fixed',
              left: tooltip.x + 'px',
              top: tooltip.y - 10 + 'px',
              transform: 'translate(-50%, -100%)',
            }"
            class="bg-black text-white text-xs rounded p-2 shadow-lg z-[9999] max-w-xs whitespace-normal break-words"
          >
            {{ tooltip.text }}
          </div>
        </Teleport>
      </template>
    </Collapsible>
  </div>
</template>
