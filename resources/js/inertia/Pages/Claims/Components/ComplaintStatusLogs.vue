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

const complaintStatusLogs = reactive({
  loading: false,
  data: null,
  table: [ 
    { text: 'OLD STATUS', value: 'old_complaint_status', width: '140px' },
    { text: 'NEW STATUS', value: 'new_complaint_status', width: '140px' },
    { text: 'OLD DATETIME', value: 'old_complaint_datetime', width: '160px' },
    { text: 'NEW DATETIME', value: 'new_complaint_datetime', width: '160px' },
    { text: 'OLD NOTES', value: 'old_complaint_notes', width: '180px' },
    { text: 'NEW NOTES', value: 'new_complaint_notes', width: '180px' },
    { text: 'LOGGED BY', value: 'logged_by', width: '100px' },
    { text: 'LOGGED AT', value: 'logged_at', width: '130px' },
  ],
});

const onLoadComplaintStatusLogsData = async () => {
  complaintStatusLogs.loading = true;
  try {
    const res = await fetch(route('claims.complaint-status-logs', props.claim.uuid));
    const finalRes = await res.json();
    complaintStatusLogs.data = finalRes || [];
  } catch (error) {
    console.error('Error loading complaint status logs:', error);
    complaintStatusLogs.data = [];
  } finally {
    complaintStatusLogs.loading = false;
  }
};
</script>

<template>
  <!--  show complaint status logs data -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Complaint Status Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div v-if="complaintStatusLogs.data === null" class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadComplaintStatusLogsData"
            :loading="complaintStatusLogs.loading"
          >
            Load Complaint Status Logs Data
          </x-button>
        </div>
        <div v-else-if="complaintStatusLogs.data.length === 0" class="text-center py-8">
          <div class="text-gray-400">
            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 font-medium">No Complaint Status Changes Found</p>
            <p class="text-gray-400 text-sm mt-1">No complaint status, datetime, or notes modifications have been recorded yet.</p>
          </div>
        </div>
        <DataTable
          v-else
          table-class-name="compact tablefixed"
          :headers="complaintStatusLogs.table"
          :items="complaintStatusLogs.data"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="complaintStatusLogs.data?.length < 15"
        > 
          <template #item-old_complaint_status="{ old_complaint_status }">
            {{ old_complaint_status || '-' }}
          </template>
          <template #item-new_complaint_status="{ new_complaint_status }">
            {{ new_complaint_status || '-' }}
          </template>
          <template #item-old_complaint_datetime="{ old_complaint_datetime }">
           {{ old_complaint_datetime ? formattedDateDmyWithTime(old_complaint_datetime) : '-' }}
          </template>
          <template #item-new_complaint_datetime="{ new_complaint_datetime }">
            {{ new_complaint_datetime ? formattedDateDmyWithTime(new_complaint_datetime) : '-' }}
          </template>
          <template #item-old_complaint_notes="{ old_complaint_notes }">
            {{ old_complaint_notes || '-' }}
          </template>
          <template #item-new_complaint_notes="{ new_complaint_notes }">
            {{ new_complaint_notes || '-' }}
          </template>
          <template #item-logged_by="{ logged_by }">
            {{ logged_by || '-' }}
          </template>
          <template #item-logged_at="{ logged_at }">
            {{ logged_at ? formattedDateDmyWithTime(logged_at) : '-' }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
