<script setup>
const props = defineProps({
  logs: Object,
  leadStatuses: Array,
  batches: Array,
  pagination: Object,
  transactionTypes: Array,
});

const page = usePage();
const notification = useNotifications('toast');

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const { isRequired } = useRules();

const objToUrl = obj => useObjToUrl(obj);
const cleanObj = obj => useCleanObj(obj);

const filters = reactive({
  quoteId: null,
  quoteType: 'Car',
  chat_initiated_at: [
    useDateFormat(new Date(), 'YYYY-MM-DD').value,
    useDateFormat(new Date(), 'YYYY-MM-DD').value,
  ],
  page: 1,
  transaction_type_id: [],
  quote_batch_id: [],
  quote_status_id: [],
  payment_status_id: [],
  sale_leads: null,
  fallback: null,
  channel: null,
  segment: null,
  mobile_no: null,
  report: null,
  email: null,
});

const params = useUrlSearchParams('history');

const reportButtonCon = computed(() => {
  let data = {
    disable: false,
    msg: null,
  };
  if (filters.report == null) {
    data.disable = true;
    data.msg = 'Please select the report type';
  } else if (filters.quoteId || filters.email || filters.mobile_no) {
    data.disable = false;
  } else if (
    filters.chat_initiated_at == null ||
    filters.chat_initiated_at == []
  ) {
    data.disable = true;
    data.msg = 'Please select the Start Date and End Date ';
  }

  return data;
});

const leadStatus = computed(() => {
  return props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadBatches = computed(() => {
  return props.batches.map(status => ({
    value: status.id,
    label: status.name,
  }));
});

const transactionTypes = computed(() => {
  return props.transactionTypes.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const paymentStatus = computed(() => {
  return [...Object.keys(page.props.paymentStatusEnum)].map(
    (status, index) => ({
      value: page.props.paymentStatusEnum[status],
      label: status,
    }),
  );
});

const quoteSegments = page.props.quoteSegments;

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const isError = ref(false);

const loader = reactive({
  table: false,
  view: false,
});
const showChatLogs = ref(false);

const chatMessages = ref({
  created_at: '',
  data: [],
  id: null,
});

const tableHeader = reactive([
  { text: 'Ref-ID', value: 'code' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Actions', value: 'action' },
]);

const quoteTypes = computed(() => {
  return Object.entries(page.props.quoteTypeCodeEnum).map(([value, label]) => ({
    value,
    label,
  }));
});

const isQuoteTypeSelected = computed(() => {
  return (
    (filters.quoteType === null || filters.quoteType === '') && isError.value
  );
});

function onSubmit() {
  if (filters.quoteId || filters.email || filters.mobile_no) {
    filters.chat_initiated_at = [];
  }

  filters.page = 1;
  router.visit(route('instant-alfred.index'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('instant-alfred.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

const createQueryParams = item => {
  return {
    quoteId: item.code.split('-')[1],
    quoteType: filters.quoteType.toUpperCase(),
    created_at: useDateFormat(
      item.code.includes('CAR')
        ? item.chat_initiated_at.split(' ')[0]
        : item.chat_initiated_at.split(' ')[0],
      'YYYY-MM-DD',
    ).value,
  };
};

const resetChatMessageObject = () => {
  chatMessages.value.created_at = '';
  chatMessages.value.data = [];
  chatMessages.value.id = null;
};
const showChat = item => {
  loader.view = true;
  resetChatMessageObject();
  axios
    .post('/instant-alfred/chats', {
      ...createQueryParams(item),
    })
    .then(response => {
      let { data } = { ...response.data };
      loader.view = false;
      chatMessages.value.data = data.length > 0 ? data : [];
      chatMessages.value.id = item.code;
      showChatLogs.value = true;
    })
    .catch(error => {
      loader.view = false;
    });
};

onMounted(() => {
  setQueryStringFilters();
});

const downloadReport = () => {
  const data = useObjToUrl(useCleanObj(filters));
  const url = route('exportChatData');
  window.open(url + '?' + new URLSearchParams(data).toString());
};
</script>

<template>
  <Head title="InstantAlfred Chat Logs" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">InstantAlfred Chat Logs</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-3 md:grid-cols-3 gap-4">
      <x-field label="Ref-ID">
        <x-input
          v-model="filters.quoteId"
          type="search"
          name="code"
          class="w-full"
          placeholder="Search by Ref-ID"
        />
      </x-field>
      <x-field label="Quote Type" required>
        <combo-box
          v-model="filters.quoteType"
          :options="[
            { label: 'Car', value: 'Car' },
            { label: 'Health', value: 'Health' },
            { label: 'Travel', value: 'Travel' },
            { label: 'Bike', value: 'Bike' },
          ]"
          placeholder="Select a Quote Type"
          class="w-full"
          single
        >
        </combo-box>
      </x-field>
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm decoration-primary-600"
          >
            Select Start & End Date <span class="text-red-500">*</span>
          </label>
          <template #tooltip> Maximum 30 days are allowed </template>
        </x-tooltip>
        <DatePicker
          class="py-1"
          v-model="filters.chat_initiated_at"
          placeholder="Select Start & End Date"
          range
          :max-range="31"
          size="md"
          model-type="yyyy-MM-dd"
          :rules="
            filters.quoteId || filters.email || filters.mobile_no
              ? []
              : [isRequired]
          "
          :onlySelect="true"
        />
      </div>
      <x-field label="Transaction Type">
        <combo-box
          v-model="filters.transaction_type_id"
          :options="transactionTypes"
          placeholder="Search by Transaction type"
          class="w-full"
        >
        </combo-box>
      </x-field>
      <x-field label="Batch">
        <combo-box
          v-model="filters.quote_batch_id"
          :options="leadBatches"
          placeholder="Search by Batch"
          class="w-full"
        >
        </combo-box>
      </x-field>
      <x-field label="Lead Status">
        <combo-box
          v-model="filters.quote_status_id"
          :options="leadStatus"
          placeholder="Select the Lead status"
          class="w-full"
        >
        </combo-box>
      </x-field>
      <x-field label="Payment Status">
        <combo-box
          v-model="filters.payment_status_id"
          :options="paymentStatus"
          placeholder="Search by Payment status"
          class="w-full"
        />
      </x-field>
      <x-field label="Sale leads">
        <x-select
          v-model="filters.sale_leads"
          :options="[
            { value: null, label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          placeholder="Search by Sale leads"
          class="w-full"
        />
      </x-field>
      <x-field label="Segment">
        <x-select
          v-model="filters.segment"
          :options="quoteSegments"
          placeholder="Search by SIC"
          class="w-full"
        />
      </x-field>
      <x-field label="Report Category">
        <x-select
          v-model="filters.report"
          :options="[
            { value: null, label: 'All', tooltip: null },
            {
              value: 'Summary',
              label: 'Summary',
              suffix:
                'A summary of InstantAlfred\'s interactions for each lead',
            },
            {
              value: 'Detailed',
              label: 'Detailed',
              suffix:
                ' Detailed InstantAlfred\'s interactions across all channels for each lead',
            },
          ]"
          placeholder="Select the Report type"
          class="w-full"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.label != 'All'">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{
                  item.label == 'Detailed'
                    ? "Detailed InstantAlfred's interactions across all channels for each lead"
                    : "A summary of InstantAlfred's interactions for each lead"
                }}
              </template>
            </x-tooltip>
          </template>
        </x-select>
      </x-field>
      <x-field label="Email">
        <x-input
          v-model="filters.email"
          placeholder="Search by Email"
          class="w-full"
        />
      </x-field>
      <x-field label="Mobile Number">
        <x-input
          v-model="filters.mobile_no"
          placeholder="Search by Mobile number"
          class="w-full"
        />
      </x-field>
    </div>

    <div class="flex justify-between gap-3">
      <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
        <x-tooltip v-if="reportButtonCon.disable" position="right">
          <x-button size="sm" color="emerald">Export Excel</x-button>
          <template #tooltip v-if="reportButtonCon.msg">
            <span class="font-medium">
              {{ reportButtonCon.msg }}
            </span>
          </template>
        </x-tooltip>

        <x-button
          :disabled="reportButtonCon.disable"
          v-else
          size="sm"
          color="emerald"
          @click.prevent="downloadReport"
          >Export Excel</x-button
        >
      </div>

      <div class="flex justify-end gap-3">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </div>
  </x-form>

  <chat-logs-modal
    :showChatLogs="showChatLogs"
    :chatMessages="chatMessages"
    @update:showChatLogs="showChatLogs = $event"
  ></chat-logs-modal>

  <DataTable
    table-class-name="tablefixed mt-3"
    :loading="loader.table"
    :headers="tableHeader"
    :items="logs.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-created_at="item">
      <span>
        {{ dateFormat(item.chat_initiated_at.split(' ')[0]) }}
      </span>
    </template>
    <template #item-action="item">
      <x-button
        size="xs"
        color="primary"
        outlined
        @click="showChat(item)"
        :loading="loader.view"
      >
        View
      </x-button>
    </template>
  </DataTable>

  <Pagination
    :links="{
      next: logs.next_page_url,
      prev: logs.prev_page_url,
      current: logs.current_page,
      from: logs.from,
      to: logs.to,
    }"
  />
</template>
