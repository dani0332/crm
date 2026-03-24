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

const tableColumns = [
  { text: 'ID', value: 'id' },
  { text: 'Channel', value: 'channel' },
  { text: 'Email Template', value: 'email_template' },
  { text: 'Email Address', value: 'email_address' },
  { text: 'Status', value: 'status' },
  { text: 'Sent At', value: 'sent_at' },
  { text: 'Suppression / Review flow', value: 'review_flow_status' },
  { text: 'Reason for non-dispatch', value: 'reason_non_dispatch' },
  { text: 'Suppression expiry', value: 'suppression_expires_at' },
  { text: 'Review clicked at', value: 'review_clicked_at' },
  { text: 'Stopping source', value: 'stopping_source' },
];

const tableItems = computed(() =>
  (props.googleReviewCommunicationLogs || []).map(row => ({
    ...row,
    stopping_source: row.stopping_source ?? '—',
    channel: row.channel ?? '—',
  })),
);
</script>

<template>
  <div
    v-if="showGoogleReviewCommunicationLog"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center w-full gap-4 flex-wrap">
          <h3 class="font-semibold text-primary-800 text-lg">
            Google review communication log
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <p
          v-if="
            !googleReviewCommunicationLogs ||
            googleReviewCommunicationLogs.length === 0
          "
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
          <template #item-id="item">
            <span class="text-sm">{{ item.id ?? '—' }}</span>
          </template>
          <template #item-channel="item">
            <span
              class="text-xs font-semibold px-2 py-0.5 rounded"
              :class="
                String(item.channel || '').toLowerCase() === 'whatsapp'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-slate-100 text-slate-700'
              "
            >
              {{ item.channel || '—' }}
            </span>
          </template>
          <template #item-stopping_source="item">
            <span class="text-sm">{{ item.stopping_source || '—' }}</span>
          </template>
          <template #item-email_template="item">
            <span class="text-sm">{{ item.email_template || '—' }}</span>
          </template>
          <template #item-email_address="item">
            <span class="text-sm">{{ item.email_address || '—' }}</span>
          </template>
          <template #item-status="item">
            <span class="text-sm">{{ item.status || '—' }}</span>
          </template>
          <template #item-sent_at="item">
            <span class="text-sm">{{ item.sent_at || '—' }}</span>
          </template>
          <template #item-review_flow_status="item">
            <span class="text-sm">{{ item.review_flow_status || '—' }}</span>
          </template>
          <template #item-reason_non_dispatch="item">
            <span class="text-sm">{{ item.reason_non_dispatch || '—' }}</span>
          </template>
          <template #item-suppression_expires_at="item">
            <span class="text-sm">{{
              item.suppression_expires_at || '—'
            }}</span>
          </template>
          <template #item-review_clicked_at="item">
            <span class="text-sm">{{ item.review_clicked_at || '—' }}</span>
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
