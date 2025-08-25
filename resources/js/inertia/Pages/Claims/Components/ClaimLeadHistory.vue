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

const claimLeadHistory = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Modified At', value: 'ModifiedAt' },
    { text: 'Modified By', value: 'ModifiedBy' },
    { text: 'Claim Status From', value: 'oldStatus' }, 
    { text: 'Claim Status To', value: 'NewStatus' },   
    { text: 'Notes', value: 'Notes' },   
  ],
});

const onLoadHistoryData = async () => {
  claimLeadHistory.loading = true;
  try {
    const res = await fetch(route('claims.lead-history', props.claim.uuid));
    const finalRes = await res.json();
    claimLeadHistory.data = finalRes;
  } catch (error) {
    console.error('Error loading claim lead history:', error);
    claimLeadHistory.data = [];
  } finally {
    claimLeadHistory.loading = false;
  }
};
</script>

<template>
  <!--  show claim lead history data -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Claim Lead History</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div v-if="claimLeadHistory.data === null" class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadHistoryData"
            :loading="claimLeadHistory.loading"
          >
            Load Lead History Data
          </x-button>
        </div>
        <DataTable
          v-else
          table-class-name="compact tablefixed"
          :headers="claimLeadHistory.table"
          :items="claimLeadHistory.data || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="claimLeadHistory.data?.length < 15"
        >
          <template #item-ModifiedAt="{ ModifiedAt }">
            {{ ModifiedAt }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
