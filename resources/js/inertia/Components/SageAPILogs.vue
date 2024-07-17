<script setup>
import NProgress from 'nprogress';
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

const notification = useToast();
const can = permission => useCan(permission);
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

const sageLogModel = ref(false);
const sageAPILogs = reactive({
  data: [],
  loader: false,
  table: [
    { text: 'Id', value: 'id' },
    { text: 'Request Type', value: 'sage_request_type' },
    { text: 'API End Point', value: 'sage_end_point' },
    { text: 'Request Payload', value: 'sage_payload' },
    { text: 'Request Response', value: 'response' },
    { text: 'Request Status', value: 'status' },
    { text: 'Logged At', value: 'created_at' },
  ],
});

const isSageLogButtonEnable = computed(() => {
  return (
    can(props.permissionsEnum.VIEW_SAGE_API_LOGS) && sageAPILogs.data.length > 0
  );
});

const fetchSageAPILogs = async () => {
  NProgress.start();
  sageAPILogs.loader = true;
  const response = await axios.get(route('sage.api.logs', [props.record.id]), {
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

onBeforeMount(() => {
  fetchSageAPILogs();
});
</script>

<template>
  <x-tooltip>
    <x-button
      v-if="isSageLogButtonEnable"
      size="sm"
      color="primary"
      outlined
      @click="showSageAPILogs"
      :loading="sageAPILogs.loader"
    >
      Sage API Logs
    </x-button>
    <template #tooltip>
      <span
        class="custom-tooltip-content"
        v-if="
          sageAPILogs?.data?.filter(item => item.status === 'fail')?.length > 0
        "
      >
        Booking Failed! Check sage logs and Try again booking this Policy!.
      </span>
    </template>
  </x-tooltip>

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
        <template #item-created_at="{ created_at }">
          {{ dateFormat(created_at).value }}
        </template>
      </DataTable>
    </x-modal>
  </div>
</template>
