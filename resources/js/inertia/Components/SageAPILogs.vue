<script setup>
import NProgress from 'nprogress';
const { copy, copied } = useClipboard();
const props = defineProps({
  quoteType: {
    required: true,
    type: String,
  },
  record: {
    required: true,
    type: Object,
  },
  modelClass: {
    required: true,
    type: String,
  },
  permissionsEnum: {
    required: true,
    type: Object,
  },
});
const page = usePage();
const notification = useToast();
const can = permission => useCan(permission);
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

const sageLogModel = ref(false);
const lastRefreshed = ref(new Date());
const sageAPILogs = reactive({
  data: [],
  loader: false,
  table: [
    { text: 'ID', value: 'id' },
    { text: 'user', value: 'user' },
    { text: 'Request Type', value: 'sage_request_type' },
    { text: 'API End Point', value: 'sage_end_point' },
    { text: 'Request Payload', value: 'sage_payload' },
    { text: 'Request Response', value: 'response' },
    { text: 'Request Status', value: 'status' },
    { text: 'Logged At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
  ],
});

const isSageLogButtonEnable = computed(() => {
  return can(props.permissionsEnum.VIEW_SAGE_API_LOGS);
});

const fetchLatestSageError = async () => {
  const { modelClass, record } = props;
  const { sendUpdateLogStatusEnum, quoteStatusEnum } = page.props;

  let isPolicyOrEndorsementBookingFailed =
    modelClass === 'App\\Models\\SendUpdateLog'
      ? record?.status === sendUpdateLogStatusEnum.UPDATE_BOOKING_FAILED
      : record?.quote_status_id === quoteStatusEnum.POLICY_BOOKING_FAILED;
  if (isPolicyOrEndorsementBookingFailed) {
    NProgress.start();
    const response = await axios.get(
      route('sage-api-logs-latest-error', [record.id]),
      {
        params: {
          modelClass: modelClass,
        },
      },
    );
    NProgress.done();
    if (response?.data?.error) {
      notification.error({
        title: 'Sage API Error',
        message: response?.data?.error,
        position: 'top',
        timeout: 30000,
      });
    }
    return;
  }
};

const fetchSageAPILogs = async () => {
  NProgress.start();
  sageAPILogs.loader = true;
  const response = await axios.get(route('sage-api-logs', [props.record.id]), {
    params: {
      modelClass: props.modelClass,
    },
  });
  sageAPILogs.loader = false;
  NProgress.done();
  if (response.data?.success) {
    sageAPILogs.data = response?.data?.sageApiLogs;
    lastRefreshed.value = new Date();
    return true;
  } else {
    return false;
  }
};

const showSageAPILogs = async () => {
  try {
    sageAPILogs.loader = false;
    let sageLogs = await fetchSageAPILogs();
    if (sageLogs) {
      if (sageAPILogs.data.length === 0) {
        notification.error({
          title: 'No Sage API Logs Found',
          position: 'top',
        });
        return;
      }
      sageLogModel.value = true;
    } else {
      notification.error({
        title: 'Something went wrong. Please try again.',
        position: 'top',
      });
    }
  } catch (err) {
    sageAPILogs.loader = false;
    console.log(err);
  }
};

const copyToClipboard = item => {
  if (item.endpoint) delete item.endpoint;
  if (item.payload) delete item.payload;
  console.log(item);
  copy(item);
  if (copied)
    notification.success({
      title: 'Copied to clipboard!',
      position: 'top',
    });
};

onBeforeMount(() => {
  fetchLatestSageError();
});
</script>

<template>
  <x-tooltip
    v-if="sageAPILogs?.data?.find(item => item.status === 'fail')?.length > 0"
  >
    <x-button
      v-if="isSageLogButtonEnable"
      class="focus:ring-2 focus:ring-black"
      size="sm"
      color="primary"
      outlined
      @click="showSageAPILogs"
      :loading="sageAPILogs.loader"
    >
      Sage API Logs
    </x-button>
    <template #tooltip>
      <span class="custom-tooltip-content">
        Booking Failed! Check sage logs and Try again booking this Policy!.
      </span>
    </template>
  </x-tooltip>
  <template v-else>
    <x-button
      v-if="isSageLogButtonEnable"
      class="focus:ring-2 focus:ring-black"
      size="sm"
      color="primary"
      outlined
      @click="showSageAPILogs"
      :loading="sageAPILogs.loader"
    >
      Sage API Logs
    </x-button>
  </template>

  <div>
    <x-modal v-model="sageLogModel" size="xxl" backdrop>
      <template #header>
        <div class="flex items-center justify-between w-full px-6 py-4">
          <div class="flex items-center space-x-3">
            <div>
              <h2 class="text-xl font-bold text-gray-900 tracking-tight">
                Sage API Logs
              </h2>
              <p class="text-sm text-gray-600 uppercase font-medium">
                {{ quoteType }} - Record #{{ record.id }}
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <div
              class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg border"
            >
              {{ sageAPILogs.data?.length || 0 }}
              {{ sageAPILogs.data?.length === 1 ? 'entry' : 'entries' }}
            </div>
          </div>
        </div>
      </template>

      <template #actions>
        <div
          class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200"
        >
          <div class="text-sm text-gray-500">
            Last refreshed: {{ lastRefreshed.toLocaleTimeString() }}
          </div>
          <div class="flex items-center space-x-3">
            <button
              @click="fetchSageAPILogs"
              :disabled="sageAPILogs.loader"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg shadow-sm hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <x-icon icon="reset" class="mr-2" size="sm" />
              Refresh
            </button>
            <button
              @click="sageLogModel = false"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
            >
              <x-icon icon="xmark" class="mr-2" size="sm" />
              Close
            </button>
          </div>
        </div>
      </template>

      <DataTable
        table-class-name="compact tablefixed"
        :headers="sageAPILogs.table"
        :items="sageAPILogs.data || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="20"
        :hide-footer="sageAPILogs.data?.length < 20"
      >
        <template #item-user="{ user }">
          {{ user?.name }}
        </template>
        <template #item-sage_request_type="{ sage_request_type }">
          {{ sage_request_type }}
        </template>
        <template #item-sage_end_point="{ sage_end_point }">
          {{ sage_end_point?.substr(0, 10) }}
          <x-icon
            v-if="sage_end_point"
            @click.prevent="copyToClipboard(sage_end_point)"
            icon="copy"
            class="text-primary"
            size="md"
          />
        </template>
        <template #item-sage_payload="{ sage_payload }">
          {{ sage_payload?.substr(0, 20) }}
          <x-icon
            @click.prevent="copyToClipboard(sage_payload)"
            icon="copy"
            class="text-primary"
            size="md"
          />
        </template>
        <template #item-response="{ response }">
          {{ response?.substr(0, 20) }}
          <x-icon
            @click.prevent="copyToClipboard(response)"
            icon="copy"
            class="text-primary"
            size="md"
          />
        </template>
        <template #item-created_at="{ created_at }">
          {{ dateFormat(created_at)?.value }}
        </template>
        <template #item-updated_at="{ updated_at }">
          {{ dateFormat(updated_at)?.value }}
        </template>
      </DataTable>
    </x-modal>
  </div>
</template>
