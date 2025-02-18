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

const ftcLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Id', value: 'id' },
    { text: 'Date Created', value: 'created_at' },
    { text: 'Date Signed', value: 'date_signed' },
    { text: 'Date Uploaded', value: 'date_uploaded' },
    { text: 'Document Id(Hash Key)', value: 'document_id' },
    { text: 'User Agent', value: 'user_agent' },
    { text: 'Email Sent To UW', value: 'email_sent_to_uw' },
    { text: 'Action', value: 'action' },
  ],
});

const onLoadAuditLogData = async () => {
  ftcLogs.loading = true;

  let data = {
    auditableType: props.type,
    auditableId: props.id,
    code: props.quoteCode,
    jsonData: true,
  };

  let url = props.url ?? '/ftcLogs';

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
      ftcLogs.data = res.data;
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      ftcLogs.loading = false;
    });
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">FTC tracking</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <DataTable
          table-class-name="compact tablefixed"
          :headers="ftcLogs.table"
          :items="ftcLogs.data || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="ftcLogs.data?.length < 15"
        >
          <template #item-created_at="{ created_at }">
            {{ dateFormat(created_at).value }}
          </template>
          <template #item-action="{ action }">
                
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
