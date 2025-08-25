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
  table: [
    { text: 'Modified At', value: 'ModifiedAt' },
    { text: 'Modified By', value: 'ModifiedBy' },
    { text: 'Sub-Status From', value: 'OldSubStatus' },
    { text: 'Sub-Status To', value: 'NewSubStatus' },
    { text: 'Notes', value: 'Notes' },  
  ],
});

const onLoadLogsData = async () => {
  claimSubStatusLogs.loading = true;
  try {
    const res = await fetch(route('claims.sub-status-logs', props.claim.uuid));
    const finalRes = await res.json();
    claimSubStatusLogs.data = finalRes;
  } catch (error) {
    console.error('Error loading claim sub-status logs:', error);
    claimSubStatusLogs.data = [];
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
          <p class="text-sm text-gray-600">Logs all updates to the Claims Sub-status made by the Claims Manager for tracking and audit purposes.</p>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div v-if="claimSubStatusLogs.data === null" class="text-center py-3">
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
          :items="claimSubStatusLogs.data || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="claimSubStatusLogs.data?.length < 15"
        >
          <template #item-ModifiedAt="{ ModifiedAt }">
            {{ ModifiedAt }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
