<script setup>
const props = defineProps({
  claim: Object,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

const claimSubStatusLogs = reactive({
  loading: false,
  data: null,
  processedData: null,
  table: [
    { text: 'Modified At', value: 'ModifiedAt' },
    { text: 'Modified By', value: 'ModifiedBy' },
    { text: 'Sub-Status From', value: 'OldSubStatus' },
    { text: 'Sub-Status To', value: 'NewSubStatus' },
    { text: 'Notes', value: 'Notes' },
  ],
});

// Process raw data to add old sub-status from previous entry
const processSubStatusData = (rawData) => {
  if (!rawData || rawData.length === 0) return [];
  
  // Sort by created_at ascending to ensure chronological order
  const sortedData = [...rawData].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
  
  // Process each entry to add old sub-status from previous entry
  const processedData = sortedData.map((item, index) => ({
    ...item,
    OldSubStatus: index > 0 ? sortedData[index - 1].NewSubStatus : null
  }));
  
  // Return in descending order for display (newest first)
  return processedData.reverse();
};

const onLoadLogsData = async () => {
  claimSubStatusLogs.loading = true;
  try {
    const res = await fetch(route('claims.sub-status-logs', props.claim.uuid));
    const finalRes = await res.json();
    claimSubStatusLogs.data = finalRes;
    claimSubStatusLogs.processedData = processSubStatusData(finalRes);
  } catch (error) {
    console.error('Error loading claim sub-status logs:', error);
    claimSubStatusLogs.data = [];
    claimSubStatusLogs.processedData = [];
  } finally {
    claimSubStatusLogs.loading = false;
  }
};
</script>

<template>
  <!--  show claim sub-status logs data -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Claim Sub-status Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div v-if="claimSubStatusLogs.processedData === null" class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadLogsData"
            :loading="claimSubStatusLogs.loading"
          >
            Load Sub-status Logs Data
          </x-button>
        </div>
        <DataTable
          v-else
          table-class-name="compact tablefixed"
          :headers="claimSubStatusLogs.table"
          :items="claimSubStatusLogs.processedData || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="claimSubStatusLogs.processedData?.length < 15"
        >
          <template #item-ModifiedAt="{ ModifiedAt }">
            {{ ModifiedAt }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
