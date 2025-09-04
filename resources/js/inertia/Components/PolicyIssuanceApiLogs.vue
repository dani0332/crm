<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    required: false,
    type: String,
  },
  id: {
    required: true,
    type: [String, Number],
  },
  quoteTypeId: {
    required: true,
    type: String,
  },
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const page = usePage();
const insuranceProviderId = ref(null);
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');
const modals = reactive({
  apiLog: false,
});
const notification = useToast();

const insuranceProviders = computed(() => {
  return page.props.insuranceProviders.map(item => ({
    value: item.value ? item.value : item.id,
    label: item.label ? item.label : item.text,
  }));
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
    { text: 'REF-ID', value: 'policy_issuance.model.uuid' },
    { text: 'Completed Step', value: 'policy_issuance.completed_step' },
    { text: 'Step', value: 'step' },
    { text: 'Status', value: 'status' },
    { text: 'Provider Name', value: 'policy_issuance.insurance_provider.text' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Action', value: 'action' },
  ],
});

const filteredLogs = computed(() => {
  if (insuranceProviderId.value != null)
    return apiLogs.data.filter(
      item => item.policy_issuance.insurance_provider.id == insuranceProviderId.value,
    );
  else return apiLogs.data;
});

const onLoadAuditLogData = async () => {
  apiLogs.loading = true;

  let url = '/policy-issuance-logs';

  let data = { 
    quoteId: props.id, 
    quoteTypeId: props.quoteTypeId,
    jsonData: true
  };

  axios
    .post(url, data)
    .then(res => {
      if (res.data.success) {
        apiLogs.data = res.data.data;
        notification.success({
          title: 'Policy Issuance API Logs Loaded Successfully',
          position: 'top',
        });
      } else {
        notification.error({
          title: res.data.message || `Failed to load Policy Issuance API logs`,
          position: 'top',
        });
      }
    })
    .catch(err => {
      notification.error({
        title: `Something went wrong. Please try again later`,
        position: 'top',
      });
      console.log('Policy Issuance Api Logs Error', err);
    })
    .finally(() => {
      apiLogs.loading = false;
    });
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Policy Issuance API Logs</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-center py-3" v-if="apiLogs.data === null">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadAuditLogData"
            :loading="apiLogs.loading"
          >
            Load Policy Issuance API Logs
          </x-button>
        </div>
        <div v-else>
          <div class="flex items-center gap-4 my-3">
            <x-select
              class="flex-1 mt-1"
              v-model="insuranceProviderId"
              :options="insuranceProviders"
              placeholder="Insurance Provider"
              filterable
            />
            <x-button
              size="sm"
              color="primary"
              @click="insuranceProviderId = null"
              class="h-10"
            >
              Reset
            </x-button>
          </div>
          <DataTable
            table-class-name="compact tablefixed"
            :headers="apiLogs.table"
            :items="filteredLogs || []"
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
                <p>
                  {{ status === 'success' ? 'PASSED' : status.toUpperCase() }}
                </p>
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
      </template>
    </Collapsible>
  </div>

  <x-modal
    v-model="modals.apiLog"
    size="lg"
    :title="`Policy Issuance Request Response Details:  ${selectedLog?.id}`"
    show-close
    backdrop
  >
    <div>
      <dl class="grid md:grid-cols-2 gap-x-1 gap-y-5">

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">REF-ID:</dt>
          <dd>{{ selectedLog.policy_issuance?.model?.uuid }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Step:</dt>
          <dd>{{ selectedLog.step }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Completed Step:</dt>
          <dd>{{ selectedLog.policy_issuance?.completed_step }}</dd>
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
              {{
                selectedLog.status === 'success'
                  ? 'PASSED'
                  : selectedLog.status.toUpperCase()
              }}
            </x-tag>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Provider Name:</dt>
          <dd>{{ selectedLog.policy_issuance?.insurance_provider?.text }}</dd>
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
        <dt class="font-medium mb-2">Payload:</dt>
        <div
          class="text-sm h-auto w-auto break-words p-3.5 bg-[#d5edfd] text-[#060404] rounded"
        >
          {{ selectedLog.payload }}
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
    </div>
    <template #actions>
      <div class="text-right space-x-4">
        <x-button
          size="sm"
          ghost
          tabindex="-1"
          @click.prevent="modals.apiLog = false"
        >
          Close
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
