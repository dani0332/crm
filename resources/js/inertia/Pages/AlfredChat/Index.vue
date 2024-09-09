<script setup>
import { computed } from 'vue';

const props = defineProps({
  logs: Object,
  leadStatuses: Array,
  batches: Array,
});

const page = usePage();

const objToUrl = obj => useObjToUrl(obj);
const cleanObj = obj => useCleanObj(obj);

const filters = reactive({
  quoteId: null,
  quoteType: 'Car',
  start_date: null,
  end_date: null,
  page: 1,
  transaction_type: [],
  batch: [],
  lead_status: [],
  payment_status_id: [],
  sale_leads: null,
  fallback: null,
  message_channel: null,
  segment: null,
  mobile_number: null,
  report: null,
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
  } else if (
    filters.report == 'Consolidated Report' &&
    filters.quoteId == null
  ) {
    data.disable = true;
    data.msg = 'Please select the Quote ID / Ref-ID';
  } else if (
    (filters.start_date == null || filters.end_date == null) &&
    filters.report == 'Detailed Report'
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

const paymentStatus = computed(() => {
  return [...Object.keys(page.props.paymentStatusEnum)].map(status => ({
    value: status,
    label: status,
  }));
});

const quoteSegments = page.props.quoteSegments;

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const { isRequired } = useRules();
const isError = ref(false);

const loader = reactive({
  table: false,
  view: false,
});
const showChatLogs = ref(false);

const chatMessages = ref({
  created_at: '',
  data: [],
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
  if (filters.start_date) {
    filters.start_date = filters.start_date.split('T')[0];
  }
  if (filters.end_date) {
    filters.end_date = filters.end_date.split('T')[0];
  }

  filters.page = 1;
  router.visit(route('instant-alfred.logs'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('instant-alfred.logs'), {
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
        ? item.car_quote_request_detail.chat_initiated_at.split(' ')[0]
        : item.health_quote_request_detail.chat_initiated_at.split(' ')[0],
      'YYYY-MM-DD',
    ).value,
  };
};

const showChat = item => {
  loader.view = true;
  axios
    .post('/get-alfred-chat-by-date', { ...createQueryParams(item) })
    .then(response => {
      let { data } = { ...response.data };
      loader.view = false;
      // chatMessages.value.created_at = item.created_at;
      // chatMessages.value.created_at = '2024-09-02';
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
  router.visit(route('exportChatData'), {
    method: 'get',
    data: cleanObj(filters),
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
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
          ]"
          placeholder="Select a Quote Type"
          class="w-full"
          single
        >
        </combo-box>
      </x-field>
      <x-field label="Start Date">
        <DatePicker v-model="filters.start_date" class="w-full" />
      </x-field>
      <x-field label="End Date">
        <DatePicker v-model="filters.end_date" class="w-full" />
      </x-field>
      <x-field label="Transaction Type">
        <combo-box
          v-model="filters.transaction_type"
          :options="[
            { label: 'New Business', value: 'Car' },
            { label: 'Existing Customer\'s Renewal', value: 'Health' },
            { label: 'Existing Customer\'s New Business', value: 'Travel' },
          ]"
          placeholder="Search by Transaction type"
          class="w-full"
        >
        </combo-box>
      </x-field>
      <x-field label="Batch">
        <combo-box
          v-model="filters.batch"
          :options="leadBatches"
          placeholder="Search by Batch"
          class="w-full"
        >
        </combo-box>
      </x-field>
      <x-field label="Lead Status">
        <combo-box
          v-model="filters.lead_status"
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
            { value: 'yes', label: 'Yes' },
            { value: 'no', label: 'No' },
          ]"
          placeholder="Search by Sale leads"
          class="w-full"
        />
      </x-field>
      <x-field label="Fallback">
        <x-select
          v-model="filters.fallback"
          :options="[
            { value: null, label: 'All' },
            { value: 'yes', label: 'Yes' },
            { value: 'no', label: 'No' },
          ]"
          placeholder="Search by Fallback"
          class="w-full"
        />
      </x-field>
      <x-field label=" Message channel">
        <x-select
          v-model="filters.message_channel"
          :options="[
            { value: null, label: 'All' },
            { value: 'WHATSAPP', label: 'Whatsapp' },
            { value: 'e-commerce', label: 'E-commerce' },
            { value: 'WEBSITE', label: 'Website' },
          ]"
          placeholder="Search by Message channel"
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
      <x-field label="Email">
        <x-input
          v-model="filters.email"
          placeholder="Search by Email"
          class="w-full"
        />
      </x-field>
      <x-field label="Mobile Number">
        <x-input
          v-model="filters.mobile_number"
          placeholder="Search by Mobile number"
          class="w-full"
        />
      </x-field>
      <x-field label="Report">
        <x-select
          v-model="filters.report"
          :options="[
            { value: null, label: 'All' },
            { value: 'Consolidated Report', label: 'Consolidated Report' },
            { value: 'Detailed Report', label: 'Detailed Report' },
          ]"
          placeholder="Select the Report type"
          class="w-full"
        />
      </x-field>
    </div>

    <div class="flex justify-between gap-3">
      <x-tooltip v-if="reportButtonCon.disable" position="right">
        <x-button
          :disabled="reportButtonCon.disable"
          size="sm"
          color="emerald"
          :href="`export-chat-data?${objToUrl(filters)}`"
          >Export Excel</x-button
        >
        <template #tooltip v-if="reportButtonCon.msg">
          <span class="font-medium">
            {{ reportButtonCon.msg }}
          </span>
        </template>
      </x-tooltip>

      <!-- @click.prevent="downloadReport" -->
      <x-button
        v-else
        :disabled="reportButtonCon.disable"
        size="sm"
        color="emerald"
        :href="`export-chat-data?${objToUrl(cleanObj(filters))}`"
        >Export Excel</x-button
      >
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
    <!-- <template #item-quote_id="{ quote_id, quote_type }">
      <span v-if="quote_type.toLowerCase().includes('hea')">
        {{ 'HEA' + '-' + quote_id }}
      </span>
      <span v-else-if="quote_type.toLowerCase().includes('travel')">
        {{ 'TRA' + '-' + quote_id }}
      </span>
      <span v-else>
        {{ quote_type + '-' + quote_id }}
      </span>
    </template> -->
    <template #item-created_at="item">
      <span v-if="item.code.includes('CAR')">
        {{
          dateFormat(
            item.car_quote_request_detail.chat_initiated_at.split(' ')[0],
          )
        }}
      </span>

      <span v-if="item.code.includes('HEA')">
        {{
          dateFormat(
            item.health_quote_request_detail.chat_initiated_at.split(' ')[0],
          )
        }}
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
