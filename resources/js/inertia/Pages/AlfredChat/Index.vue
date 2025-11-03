<script setup>
const props = defineProps({
  logs: Object,
  leadStatuses: Array,
  batches: Array,
  pagination: Object,
  transactionTypes: Array,
  renewalBatches: Array,
});

const page = usePage();
const notification = useNotifications('toast');

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const { isRequired, maxDateRangeArray } = useRules();

const objToUrl = obj => useObjToUrl(obj);
const cleanObj = obj => useCleanObj(obj);

const filters = reactive({
  quoteId: null,
  quoteType: 'Car',
  chat_initiated_at: [
    useDateFormat(new Date(), 'YYYY-MM-DD').value,
    useDateFormat(new Date(), 'YYYY-MM-DD').value,
  ],
  lead_created_at: [],
  page: 1,
  transaction_type_id: [],
  quote_batch_id: [],
  renewal_batch: null,
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

const serverOptions = ref({
  page: 1,
  sortType: 'desc',
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

const renewalBatches = computed(() => {
  return props.renewalBatches.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

// Determine if the current quote type is non-motor
const isNonMotor = computed(() => {
  const nonMotorTypes = ['Bike', 'Home', 'Health', 'Travel'];
  return nonMotorTypes.includes(filters.quoteType);
});

// Reset renewal_batch when quote type changes
watch(
  () => filters.quoteType,
  () => {
    filters.renewal_batch = isNonMotor.value ? [] : null;
  },
);

const quoteSegments = page.props.quoteSegments;

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const isError = ref(false);

const loader = reactive({
  table: false,
  view: false,
  exportLoader: false,
});
const showChatLogs = ref(false);

const chatMessages = ref({
  created_at: '',
  data: [],
  id: null,
});

const tableHeader = reactive([
  { text: 'Ref-ID', value: 'code' },
  {
    text: 'AI Interaction Start Date',
    value: 'chat_initiated_at',
    sortable: true,
  },
  // { text: 'Lead Created At', value: 'lead_created_at', sortable: true },
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

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) onSubmit(true);
  },
);

function onSubmit(isValid) {
  if (!isValid) return;
  if (filters.quoteId || filters.email || filters.mobile_no) {
    filters.chat_initiated_at = [];
  }

  filters.page = 1;
  router.visit(route('instant-alfred.index'), {
    method: 'get',
    data: { ...useGenerateQueryString(filters), ...serverOptions.value },
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
  for (const [key, value] of Object.entries(params)) {
    if (key.startsWith('chat_initiated_at[')) {
      // Extract the index from 'chat_initiated_at[0]', 'chat_initiated_at[1]'
      const index = parseInt(key.match(/\[(\d+)\]/)?.[1], 10);
      if (!isNaN(index)) {
        filters.chat_initiated_at[index] = value.split('T')[0]; // Remove time part
      }
    } else if (key.startsWith('lead_created_at[')) {
      const index = parseInt(key.match(/\[(\d+)\]/)?.[1], 10);
      if (!isNaN(index)) {
        filters.lead_created_at[index] = useDateFormat(
          value,
          'YYYY-MM-DD hh:mm:ss',
        ).value; // Remove time part
      }
    } else if (Array.isArray(value)) {
      filters[key] = value;
    } else {
      filters[key] = isNaN(parseInt(value)) ? value : parseInt(value);
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

  let filtersCleaned = cleanObj({ ...filters, ...serverOptions.value });

  if (filtersCleaned.sortType) {
    serverOptions.value.sortType = filtersCleaned.sortType;
    delete filtersCleaned.sortType;
  }

  if (filtersCleaned.page) {
    serverOptions.value.page = filtersCleaned.page;
    delete filtersCleaned.page;
  }
});

const exportReport = async (exportType = 'download') => {
  try {
    loader.exportLoader = true;

    // Validate required fields for email export
    if (!filters.report) {
      notification.error({
        position: 'top',
        title: 'Export Error!',
        text: 'Please select a report type before exporting.',
      });
      return;
    }

    // Check chat_initiated_at date range
    if (filters.chat_initiated_at && filters.chat_initiated_at.length === 2) {
      const chatDays = calculateDaysDifference(
        filters.chat_initiated_at[0],
        filters.chat_initiated_at[1],
      );

      if (chatDays > 31) {
        notification.error({
          position: 'top',
          title: 'Export Error!',
          message:
            'Maximum 31 days are allowed for Chat Initiated At date range.',
        });
        return;
      }
    }

    // Check lead_created_at date range
    if (filters.lead_created_at && filters.lead_created_at.length === 2) {
      const leadDays = calculateDaysDifference(
        filters.lead_created_at[0],
        filters.lead_created_at[1],
      );

      if (leadDays > 31) {
        notification.error({
          position: 'top',
          title: 'Export Error',
          message:
            'Maximum 31 days are allowed for Lead Created At date range.',
        });
        return;
      }
    }

    const data = {
      ...useCleanObj({ ...filters, ...serverOptions.value }),
      ...(exportType === 'email'
        ? { recipientEmail: page.props.auth.user.email }
        : {}),
      report: filters.report,
    };

    const payload =
      exportType === 'download'
        ? {
            type: 'instant-alfred-chat',
            quote_type_id: null,
            exportType: 'download',
            url: `${route('exportChatData')}?${new URLSearchParams(useObjToUrl(data)).toString()}`,
          }
        : {
            type: 'instant-alfred-chat',
            quote_type_id: null,
            exportType: 'email',
            url: route('instant-alfred.export-email'),
            data: data,
            method: 'post',
          };

    const result = await logAndExportQuotes(payload);

    if (result.data.success !== false) {
      notification.success({
        title:
          exportType === 'download'
            ? 'Export Initiated'
            : 'Your export has been queued and will be sent to your email shortly.',
        position: 'top',
      });
    } else {
      notification.error({
        title:
          result.data.message || 'Failed to initiate export. Please try again.',
        position: 'top',
      });
    }
  } catch (error) {
    notification.error({
      position: 'top',
      title: 'Export Error',
      text:
        error.response?.data?.message ||
        'An error occurred while initiating the export. Please try again.',
    });
  } finally {
    loader.exportLoader = false;
  }
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
      <x-input
        v-model="filters.quoteId"
        type="search"
        name="code"
        class="w-full"
        placeholder="Search by Ref-ID"
        label="Ref-ID"
      />
      <x-select
        v-model="filters.quoteType"
        :options="[
          { label: 'Car', value: 'Car' },
          { label: 'Health', value: 'Health' },
          { label: 'Travel', value: 'Travel' },
          { label: 'Bike', value: 'Bike' },
          { label: 'Home', value: 'Home' },
        ]"
        placeholder="Select a Quote Type"
        class="w-full"
        single
        label="Quote Type"
        required
      />

      <DatePicker
        label="Interaction Start and End Date"
        v-model="filters.chat_initiated_at"
        placeholder="Select Start & End Date"
        range
        size="md"
        model-type="yyyy-MM-dd"
        :rules="
          filters.quoteId || filters.email || filters.mobile_no
            ? []
            : [maxDateRangeArray(31)]
        "
        :onlySelect="true"
        tooltip="Date range of customer interaction with InstantAlfred (Maximum 31 days allowed)"
      />
      <DatePicker
        v-model="filters.lead_created_at"
        placeholder="Select Lead Created Start & End Date"
        range
        size="md"
        label="Lead Created Start & End Date"
        text-input
        time-picker-inline
        enableTimePicker
        withTime
        :rules="[maxDateRangeArray(31)]"
        tooltip="Lead creation date range (Maximum 31 days allowed)"
      />

      <x-select
        v-model="filters.transaction_type_id"
        :options="transactionTypes"
        placeholder="Search by Transaction type"
        class="w-full"
        multiple
        truncate
        filterable
        filterPlaceholder="Filter Transaction Type...."
        label="Transaction Type"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.transaction_type_id = transactionTypes.map(
                item => item.value,
              )
            "
            @clear="filters.transaction_type_id = []"
          />
        </template>
      </x-select>

      <x-select
        v-model="filters.quote_batch_id"
        :options="leadBatches"
        placeholder="Search by Batch"
        class="w-full"
        multiple
        truncate
        filterable
        filterPlaceholder="Filter Batch...."
        label="Batch"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.quote_batch_id = leadBatches.map(item => item.value)
            "
            @clear="filters.quote_batch_id = []"
          />
        </template>
      </x-select>

      <!-- Renewal Batch - Dropdown for Non-Motor (Bike, Home, Health, Travel) -->
      <x-select
        v-if="isNonMotor"
        v-model="filters.renewal_batch"
        :options="renewalBatches"
        placeholder="Search by Renewal Batch"
        class="w-full"
        label="Renewal Batch"
        filterable
        filterPlaceholder="Filter Renewal Batch...."
        multiple
        truncate
        virtual-list
        :virtual-list-item-height="34"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.renewal_batch = renewalBatches.map(item => item.id)
            "
            @clear="filters.renewal_batch = []"
          />
        </template>
      </x-select>

      <!-- Renewal Batch - Input field for Car -->
      <x-input
        v-else
        v-model="filters.renewal_batch"
        placeholder="Search by Renewal Batch"
        type="text"
        class="w-full"
        label="Renewal Batch"
      />

      <x-select
        v-model="filters.quote_status_id"
        :options="leadStatus"
        placeholder="Select the Lead status"
        class="w-full"
        multiple
        truncate
        filterable
        filterPlaceholder="Filter Lead Status...."
        label="Lead Status"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.quote_status_id = leadStatus.map(item => item.value)
            "
            @clear="filters.quote_status_id = []"
          />
        </template>
      </x-select>

      <x-select
        v-model="filters.payment_status_id"
        :options="paymentStatus"
        placeholder="Search by Payment status"
        class="w-full"
        multiple
        truncate
        filterable
        filterPlaceholder="Filter Payment Status...."
        label="Payment Status"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.payment_status_id = paymentStatus.map(item => item.value)
            "
            @clear="filters.payment_status_id = []"
          />
        </template>
      </x-select>

      <x-select
        v-model="filters.sale_leads"
        :options="[
          { value: null, label: 'All' },
          { value: 'Yes', label: 'Yes' },
          { value: 'No', label: 'No' },
        ]"
        placeholder="Search by Sale leads"
        class="w-full"
        label="Sale leads"
      >
      </x-select>

      <x-select
        v-model="filters.segment"
        :options="quoteSegments"
        placeholder="Search by SIC"
        class="w-full"
        label="Segment"
      />

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
      <div v-if="can(permissionsEnum.DATA_EXTRACTION)" class="flex gap-2">
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
          @click.prevent="exportReport('download')"
          :loading="loader.exportLoader"
          >Export Excel</x-button
        >

        <x-tooltip v-if="reportButtonCon.disable" position="right">
          <x-button size="sm" color="emerald" :loading="loader.exportLoader">
            Export via Email
          </x-button>
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
          color="blue"
          :loading="loader.exportLoader"
          @click.prevent="exportReport('email')"
          >Export via Email</x-button
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
    v-model:server-options="serverOptions"
    table-class-name="tablefixed mt-3"
    :loading="loader.table"
    :headers="tableHeader"
    :items="logs.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-chat_initiated_at="item">
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
