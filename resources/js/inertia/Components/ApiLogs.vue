<script setup>
const props = defineProps({
  type: {
    required: false,
    type: String,
  },
  id: {
    required: true,
    type: [String, Number],
  },
  quoteType: {
    required: false,
    type: String,
  },
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

const apiLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'ID', value: 'id' },
    { text: 'REF-ID', value: 'name' },
    { text: 'Call Type', value: 'event' },
    { text: 'Status', value: 'old_values' },
    { text: 'Provider Name', value: 'new_values' },
    { text: 'Created At', value: 'ip_address' },
    { text: 'Action', value: 'created_at' },
  ],
});

const onLoadAuditLogData = async () => {
  apiLogs.loading = true;

  let data = {
    auditableType: props.type,
    auditableId: props.id,
    jsonData: true,
  };

  let url = '/apilogs';

  if (props.quoteType != undefined) {
    data = {
      auditable_id: props.id,
      quote_type: props.quoteType,
      jsonData: true,
    };    
  }

  axios
    .post(url, data)
    .then(res => {
      apiLogs.data = res.data;
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      apiLogs.loading = false;
    });
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">API Logs</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <div class="text-center py-3" v-if="apiLogs.data === null">
      <x-button
        size="sm"
        color="primary"
        outlined
        @click.prevent="onLoadAuditLogData"
        :loading="apiLogs.loading"
      >
        Load API Logs
      </x-button>
    </div>
    <DataTable
      v-else
      table-class-name="compact tablefixed"
      :headers="apiLogs.table"
      :items="apiLogs.data || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="15"
      :hide-footer="apiLogs.data?.length < 15"
    >
      <template #item-created_at="{ created_at }">
        {{ dateFormat(created_at).value }}
      </template>
    </DataTable>
  </div>
</template>
