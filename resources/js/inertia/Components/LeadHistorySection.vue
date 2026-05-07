<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  title: {
    type: String,
    required: false,
    default: 'Lead History',
  },
  modelType: {
    type: String,
    required: true,
  },
  recordId: {
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
  { text: 'Notes', value: 'notes' },
];

const engineeringHeaders = [
  { text: 'Status Change Source', value: 'status_change_source' },
  { text: 'Status Change Action', value: 'status_change_action' },
];

const headers = computed(() => {
  if (!hasRole(rolesEnum.Engineering)) {
    return defaultHeaders;
  }

  return [...defaultHeaders, ...engineeringHeaders];
});

const endpoint = computed(() => {
  const query = new URLSearchParams({
    modelType: String(props.modelType),
    recordId: String(props.recordId),
    quoteTypeId: String(props.quoteTypeId),
  });

  return `/quotes/lead-history?${query.toString()}`;
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
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">{{ title }}</h3>
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
