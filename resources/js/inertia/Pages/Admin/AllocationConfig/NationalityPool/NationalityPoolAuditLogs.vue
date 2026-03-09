<script setup>
import { reactive, onMounted } from 'vue';

const props = defineProps({
  expanded: {
    type: Boolean,
    default: true,
  },
});

const routingLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Created Date', value: 'created_at' },
    { text: 'User', value: 'user.name' },
    { text: 'Effective From', value: 'effective_from' },
    { text: 'Effective To', value: 'effective_to' },
    { text: 'Nationalities', value: 'nationalities' },
  ],
});

const loadData = async () => {
  routingLogs.loading = true;
 
  axios.get(route('admin.nationality-pool-audit-logs', 'audit')).then(response => {
    routingLogs.data = response.data.data;
  }).catch(error => {
    console.error('Error loading audit logs:', error);
  }).finally(() => {
    routingLogs.loading = false;
  });
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">
           Audit Logs
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="relative">
          <DataTable
            table-class-name="compact tablefixed"
            :headers="routingLogs.table"
            :items="routingLogs.data || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="routingLogs.data?.length < 15"
          >
          </DataTable>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
