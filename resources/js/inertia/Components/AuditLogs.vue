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
  url: {
    type: String,
  },
  quoteCode: {
    required: false,
    type: String,
  },
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

const auditLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Id', value: 'id' },
    { text: 'User', value: 'name' },
    { text: 'Event', value: 'event' },
    { text: 'Old Values', value: 'old_values' },
    { text: 'New Values', value: 'new_values' },
    { text: 'Ip Address', value: 'ip_address' },
    { text: 'Logged At', value: 'created_at' },
  ],
});

const onLoadAuditLogData = async () => {
  auditLogs.loading = true;

  let data = {
    auditableType: props.type,
    auditableId: props.id,
    code: props.quoteCode,
    jsonData: true,
  };

  let url = props.url ?? '/auditlogs';

  if (props.quoteType != undefined) {
    data = {
      auditable_id: props.id,
      quote_type: props.quoteType,
      code: props.quoteCode,
      jsonData: true,
    };
    url = '/audits/get-quote-audits';
  }

  axios
    .post(url, data)
    .then(res => {
      auditLogs.data = res.data;
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      auditLogs.loading = false;
    });
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Audit Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-center py-3" v-if="auditLogs.data === null">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadAuditLogData"
            :loading="auditLogs.loading"
          >
            Load Audit Logs
          </x-button>
        </div>
        <DataTable
          v-else
          table-class-name="compact tablefixed"
          :headers="auditLogs.table"
          :items="auditLogs.data || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="auditLogs.data?.length < 15"
        >
          <template #item-created_at="{ created_at }">
            {{ dateFormat(created_at).value }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
