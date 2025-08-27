<script setup>
import { formattedDateDmyWithTime } from '@/inertia/Composables/utilities.js';

const props = defineProps({
  claim: Object,
  expanded: {
    type: Boolean,
    required: false,
    default: false,
  },
});
 

const nextFollowUpLogs = reactive({
  loading: false,
  data: null,
  table: [  
    { text: 'OLD FOLLOW-UP DATE', value: 'old_follow_up_date', width: '160px' }, 
    { text: 'OLD NOTES', value: 'old_notes', width: '180px' },
    { text: 'NEW FOLLOW-UP DATE', value: 'new_follow_up_date', width: '160px' },
    { text: 'NEW NOTES', value: 'new_notes', width: '180px' },
    { text: 'LOGGED BY', value: 'logged_by', width: '100px' },
    { text: 'LOGGED AT', value: 'logged_at', width: '130px' },
  ],
});
 

const onLoadFollowUpLogsData = async () => {
  nextFollowUpLogs.loading = true;
  try {
    const res = await fetch(route('claims.next-follow-up-logs', props.claim.uuid));
    const finalRes = await res.json();
    // Process data to add change type information
    nextFollowUpLogs.data = finalRes;
  } catch (error) {
    console.error('Error loading next follow-up logs:', error);
    nextFollowUpLogs.data = [];
  } finally {
    nextFollowUpLogs.loading = false;
  }
};
</script>

<template>
  <!--  show next follow-up logs data -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Next Follow-up Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div v-if="nextFollowUpLogs.data === null" class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadFollowUpLogsData"
            :loading="nextFollowUpLogs.loading"
          >
            Load Follow-up Logs Data
          </x-button>
        </div>
        <div v-else-if="nextFollowUpLogs.data.length === 0" class="text-center py-8">
          <div class="text-gray-400">
            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <p class="text-gray-500 font-medium">No Follow-up Changes Found</p>
            <p class="text-gray-400 text-sm mt-1">No follow-up date or notes modifications have been recorded yet.</p>
          </div>
        </div>
        <DataTable
          v-else
          table-class-name="compact tablefixed"
          :headers="nextFollowUpLogs.table"
          :items="nextFollowUpLogs.data"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="nextFollowUpLogs.data?.length < 15"
        >
          <template #item-old_follow_up_date="{ old_follow_up_date }">
            {{ old_follow_up_date ? formattedDateDmyWithTime(old_follow_up_date) : '-' }}
          </template>
          <template #item-new_follow_up_date="{ new_follow_up_date }">
            {{ new_follow_up_date ? formattedDateDmyWithTime(new_follow_up_date) : '-' }}
          </template>
          <template #item-logged_at="{ logged_at }">
            {{ logged_at ? formattedDateDmyWithTime(logged_at) : '-' }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
