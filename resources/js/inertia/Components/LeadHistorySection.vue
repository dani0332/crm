<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  quoteId: {
    type: [Number, String],
    required: true,
  },
  quoteTypeId: {
    type: [Number, String],
    required: true,
  },
});
const page = usePage();
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const historyData = ref(null);
const historyLoading = ref(false);

const defaultHeaders = [
  { text: 'Modified At', value: 'created_at' },
  { text: 'Modified By', value: 'created_by.email' },
  { text: 'Lead Status From', value: 'previous_quote_status.text' },
  { text: 'Lead Status To', value: 'current_quote_status.text' },
  { text: 'Source of Action', value: 'status_change_source' },
];

const engineeringHeaders = [{ text: 'Notes', value: 'notes' }];

const headers = computed(() => {
  if (!hasRole(rolesEnum.Engineering)) {
    return defaultHeaders;
  }

  return [...defaultHeaders, ...engineeringHeaders];
});

const endpoint = computed(() => {
  const query = new URLSearchParams();
  query.append('quoteId', props.quoteId);
  query.append('quoteTypeId', props.quoteTypeId);
  return `/quotes/status-logs?${query.toString()}`;
});

const onLoadHistoryData = async () => {
  historyLoading.value = true;

  try {
    const res = await fetch(endpoint.value);
    const finalRes = await res.json();
    historyData.value = Array.isArray(finalRes) ? finalRes : [];
  } catch (error) {
    console.error('Failed to load lead history data', error);
    historyData.value = [];
  } finally {
    historyLoading.value = false;
  }
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>

          <button
            v-if="historyData !== null"
            @click.prevent.stop="onLoadHistoryData"
            :disabled="historyLoading"
            class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-gray-100 rounded-full transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Refresh Lead History"
          >
            <svg
              :class="{ 'animate-spin': historyLoading }"
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
        <div v-if="historyData === null" class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadHistoryData"
            :loading="historyLoading"
          >
            Load History Data
          </x-button>
        </div>

        <DataTable
          v-else
          table-class-name="compact"
          :headers="headers"
          :items="historyData || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="historyData.length < 15"
        />
      </template>
    </Collapsible>
  </div>
</template>
