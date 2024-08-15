<script setup>
defineProps({
  quote: Object,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const page = usePage();

const historyLoading = ref(false);

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/personal-quotes/${page.props.quote.id}/audit-history`,
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Notes', value: 'NewNotes' },
  { text: 'Lead Status', value: 'NewStatus' },
];
</script>
<template>
  <!--  show lead history data -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
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
          :headers="historyDataTable"
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
