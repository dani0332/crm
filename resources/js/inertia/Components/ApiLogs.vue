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
const modals = reactive({
  apiLog: false,
});

const selectedLog = ref({});
const selectLog = (item) => {
    selectedLog.value = item;    
  	modals.apiLog = true;
}

const apiLogs = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'ID', value: 'id' },
    { text: 'REF-ID', value: 'quote_uuid' },
    { text: 'Call Type', value: 'call_type' },
    { text: 'Status', value: 'status' },
    { text: 'Provider Name', value: 'insurance_provider.text' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Action', value: 'action' },
  ],
});

const onLoadAuditLogData = async () => {
  apiLogs.loading = true;

  let data = {
    auditableType: props.type,
    auditableId: props.id,
    jsonData: true,
  };

  let url = '/insurer-logs';

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
      
      <template #item-status="{ status }">
        <x-tag v-if="status" size="xs" :color="status === 'failed' ? 'red' : 'primary'" class="mt-0.5 text-[10px]">
            <p>{{ status.toUpperCase() }}</p>
        </x-tag>					
      </template>    
      <template #item-created_at="{ created_at }">
        {{ dateFormat(created_at).value }}
      </template>
      <template #item-action="item">
        <x-button size="xs" color="primary" outlined @click.prevent="selectLog(item)">
							View
				</x-button>						
			</template>
    </DataTable>
  </div> 
  
   
  <x-modal v-model="modals.apiLog" size="lg" show-close backdrop>
    <template #header>
        Insurance Request Response Details: {{ selectedLog.id }}
    </template>
    
    <div class="space-x-4">
        <table class="table">
            <tr class="mb-10">
                <td class="font-medium">REF-ID:</td>
                <td>{{ selectedLog.quote_uuid }}</td>
            </tr>
            <tr class="mb-10">
                <td class="font-medium">Call Type:</td>
                <td>{{ selectedLog.call_type }}</td>
            </tr>
            <tr>
                <td class="font-medium">Status:</td>
                <td>
                  <x-tag v-if="selectedLog.status" size="xs" :color="selectedLog.status === 'failed' ? 'red' : 'primary'" class="mt-0.5 text-[10px]">
                      {{ selectedLog.status.toUpperCase() }}
                  </x-tag>					
                </td>
            </tr>
            <tr>
                <td class="font-medium">Provider Name:</td>
                <td>{{ selectedLog.insurance_provider.text }}</td>
            </tr>
            <tr>
                <td class="font-medium" colspan="2">Request:</td>               
            </tr>
            <tr>
                <td colspan="2">
                    <div style="font-size:14px; background-color: #d5edfd; color: rgb(6, 4, 4); height: 200px; width: 700px; overflow-y: auto; padding: 10px;">
                      {{ selectedLog.request }}
                    </div>
                </td>
            </tr>
            <tr>
                <td class="font-medium" colspan="2">Response:</td>               
            </tr>
            <tr>                
                <td colspan="2">
                  <div style="font-size:14px; background-color: #d5edfd; color: rgb(6, 4, 4); height: 200px; width: 700px; overflow-y: auto; padding: 10px;">
                    {{ selectedLog.response }}
                  </div>
                </td>
            </tr>
            <tr>
                <td class="font-medium">Created At:</td>
                <td>{{ dateFormat(selectedLog.created_at).value }}</td>
            </tr>
            <tr>
                <td class="font-medium">Updated At:</td>
                <td>{{ dateFormat(selectedLog.updated_at).value }}</td>
            </tr>
        </table>
    </div>
    <div class="text-right space-x-4 mt-12">
        <x-button size="sm" @click.prevent="modals.apiLog = false">
            Close
        </x-button>
    </div>
</x-modal>  
</template>
