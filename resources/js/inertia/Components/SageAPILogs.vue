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
  if (response.data?.success)
    console.log('fetchLatestSageError:', response.data);
  if (response?.data?.error && isPolicyOrEndorsementBookingFailed) {
    notification.error({
      title: 'Sage API Error',
      message: response?.data?.error,
      position: 'top',
      timeout: 30000,
    });
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
    <x-modal v-model="sageLogModel" size="xl" backdrop show-close>
      <template #header>
        <span>Sage API Logs </span>
      </template>
      <DataTable
        table-class-name="compact tablefixed"
        :headers="sageAPILogs.table"
        :items="sageAPILogs.data || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="sageAPILogs.data?.length < 15"
      >
        <template #item-user="{ user }">
          {{ user?.name }}
        </template>
        <template #item-sage_request_type="{ sage_request_type }">
          {{ sage_request_type?.substr(0, 10) }}
          <x-icon
            v-if="sage_request_type"
            @click.prevent="copyToClipboard(sage_request_type)"
            icon="copy"
            class="text-primary"
            size="md"
          />
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
