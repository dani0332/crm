<script setup>
defineProps({
  quote: Object,
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
  <x-collapse show-icon class="p-4 rounded shadow mb-6 bg-white">
    <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
    <template #content>
      <x-divider class="mb-4 mt-1" />
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
  </x-collapse>
  <!-- <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
      <x-divider class="mb-4 mt-1" />
    </div>

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
  </div> -->
</template>
