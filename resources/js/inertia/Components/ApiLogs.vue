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

const insuranceProviderId = ref(null);
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');
const modals = reactive({
  apiLog: false,
});

const selectedLog = ref({});
const selectLog = item => {
  selectedLog.value = item;
  modals.apiLog = true;
};

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

const onLoadAuditLogData = async (hasinsuranceId = true) => {
  if (!hasinsuranceId) insuranceProviderId.value = null;
  apiLogs.loading = true;

  let url = '/insurer-logs';

  let data = {
    ...(props.quoteType === undefined
      ? { auditableType: props.type, auditableId: props.id }
      : { quote_type: props.quoteType, auditable_id: props.id }),
    jsonData: true,
    insurance_provider: insuranceProviderId.value ?? null,
  };
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
    <div v-else>
      <div class="flex items-center gap-4 my-3">
        <x-field class="flex-1" label="Insurance Provider">
          <ComboBox
            :single="true"
            class="w-full"
            v-model="insuranceProviderId"
            :options="
              $page.props.insuranceProviders.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
          />
        </x-field>
        <x-button
          size="sm"
          color="orange"
          @click="onLoadAuditLogData"
          :loading="apiLogs.loading"
          class="h-10 mt-3"
        >
          Search
        </x-button>
        <x-button
          size="sm"
          color="primary"
          @click="onLoadAuditLogData(false)"
          class="h-10 mt-3"
        >
          Reset
        </x-button>
      </div>
      <DataTable
        table-class-name="compact tablefixed"
        :headers="apiLogs.table"
        :items="apiLogs.data || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="apiLogs.data?.length < 15"
      >
        <template #item-status="{ status }">
          <x-tag
            v-if="status"
            size="xs"
            :color="status === 'failed' ? 'red' : 'success'"
            class="mt-0.5 text-[10px]"
          >
            <p>{{ status.toUpperCase() }}</p>
          </x-tag>
        </template>
        <template #item-created_at="{ created_at }">
          {{ dateFormat(created_at).value }}
        </template>
        <template #item-action="item">
          <x-button
            size="xs"
            color="primary"
            outlined
            @click.prevent="selectLog(item)"
          >
            View
          </x-button>
        </template>
      </DataTable>
    </div>
  </div>

  <x-modal v-model="modals.apiLog" size="lg" show-close backdrop>
    <template #header>
      Insurance Request Response Details: {{ selectedLog.id }}
    </template>

    <div>
      <dl class="grid md:grid-cols-2 gap-x-1 gap-y-5">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">REF-ID:</dt>
          <dd>{{ selectedLog.quote_uuid }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Call Type:</dt>
          <dd>{{ selectedLog.call_type }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Status:</dt>
          <dd>
            <x-tag
              v-if="selectedLog.status"
              size="xs"
              :color="selectedLog.status === 'failed' ? 'red' : 'success'"
              class="mt-0.5 text-[10px]"
            >
              {{ selectedLog.status.toUpperCase() }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Provider Name:</dt>
          <dd>{{ selectedLog.insurance_provider.text }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At:</dt>
          <dd>{{ dateFormat(selectedLog.created_at).value }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At:</dt>
          <dd>{{ dateFormat(selectedLog.updated_at).value }}</dd>
        </div>
      </dl>

      <x-divider class="my-5" />
      <dl class="">
        <dt class="font-medium mb-2">Request:</dt>
        <div
          class="text-sm h-auto w-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
        >
          {{ selectedLog.request }}
        </div>
      </dl>
      <dl class="mt-5">
        <dt class="font-medium mb-2">Response:</dt>
        <div
          class="text-sm h-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
        >
          {{ selectedLog.response }}
        </div>
      </dl>
      <!-- <table class="table">
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
            <x-tag
              v-if="selectedLog.status"
              size="xs"
              :color="selectedLog.status === 'failed' ? 'red' : 'success'"
              class="mt-0.5 text-[10px]"
            >
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
            <div
              class="text-sm h-[200px] w-[700px] overflow-y-auto p-2.5 bg-[#d5edfd] text-[#060404]"
            >
              {{ selectedLog.request }}
            </div>
          </td>
        </tr>
        <tr>
          <td class="font-medium" colspan="2">Response:</td>
        </tr>
        <tr>
          <td colspan="2">
            <div
              class="text-sm h-[200px] w-[700px] overflow-y-auto p-2.5 bg-[#d5edfd] text-[#060404]"
            >
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
      </table> -->
    </div>
    <div class="text-right space-x-4 mt-12">
      <x-button size="sm" @click.prevent="modals.apiLog = false">
        Close
      </x-button>
    </div>
  </x-modal>
</template>
