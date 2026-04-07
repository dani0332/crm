<script setup>
const props = defineProps({
  communicationEventLogs: {
    type: Array,
    default: () => [],
  },
  expanded: {
    type: Boolean,
    default: true,
  },
});

const eventLogTable = reactive({
  columns: [
    { text: 'Event Channel', value: 'event_channel' },
    { text: 'Communication Type', value: 'communication_type' },
    { text: 'Action / Event', value: 'action_event' },
    { text: 'Created Date', value: 'created_at' },
  ],
});

function formatActionEvent(value) {
  if (value == null || value === '') {
    return '—';
  }

  return String(value)
    .split('_')
    .filter(Boolean)
    .map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
    .join(' ');
}
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Event Tracking</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="max-h-[min(28rem,55vh)] overflow-y-auto overflow-x-auto">
          <DataTable
            table-class-name="tablefixed compact"
            :headers="eventLogTable.columns"
            :items="communicationEventLogs || []"
            border-cell
            hide-rows-per-page
            hide-footer
          >
            <template #item-action_event="item">
              <span class="text-sm">{{
                formatActionEvent(item.action_event)
              }}</span>
            </template>
          </DataTable>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
