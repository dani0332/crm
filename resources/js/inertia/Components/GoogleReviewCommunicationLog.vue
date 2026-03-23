<script setup>
const props = defineProps({
  googleReviewCommunicationLogs: {
    type: Array,
    default: () => [],
  },
  showGoogleReviewCommunicationLog: {
    type: Boolean,
    default: false,
  },
  expanded: {
    type: Boolean,
    default: true,
  },
});

/** Column keys match CRM spreadsheet: Google Review Communication Log */
const tableColumns = [
  { text: 'ID', value: 'id' },
  { text: 'Email Template', value: 'email_template' },
  { text: 'Email Address', value: 'email_address' },
  { text: 'Status', value: 'status' },
  { text: 'Sent At', value: 'sent_at' },
  { text: 'Suppression / Review flow', value: 'review_flow_status' },
  { text: 'Reason for non-dispatch', value: 'reason_non_dispatch' },
  { text: 'Suppression expiry', value: 'suppression_expires_at' },
  { text: 'Review clicked at', value: 'review_clicked_at' },
  { text: 'Channel', value: 'channel' },
  // { text: 'Touchpoint', value: 'touchpoint' }, // TODO: include when touchpoint data is wired
];

const tableItems = computed(() => props.googleReviewCommunicationLogs || []);
</script>

<template>
  <div
    v-if="showGoogleReviewCommunicationLog"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <Collapsible :expanded="expanded">
      <template #header>
        <div
          class="flex justify-between items-center w-full gap-4 flex-wrap"
        >
          <h3 class="font-semibold text-primary-800 text-lg">
            Google review communication log
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <p
          v-if="!googleReviewCommunicationLogs || googleReviewCommunicationLogs.length === 0"
          class="text-sm text-gray-500 mb-4"
        >
          No Google review courtesy activity recorded for this line of business
          yet.
        </p>
        <DataTable
          v-else
          table-class-name="tablefixed compact"
          :headers="tableColumns"
          :items="tableItems"
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-status="item">
            <span class="text-sm">{{ item.status || '—' }}</span>
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
