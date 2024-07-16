<script setup>
const props = defineProps({
  logs: Object,
});
const filters = reactive({
  quoteId: null,
  start_date: null,
  end_date: null,
  page: 1,
});

const params = useUrlSearchParams('history');

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const { isRequired } = useRules();
const isError = ref(false);
const page = usePage();
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
  { text: 'Ref-ID', value: 'quote_id' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Actions', value: 'action' },
]);

const quoteTypes = computed(() =>
{
  return Object.entries(page.props.quoteTypeCodeEnum).map(([value, label]) => ({
    value,
    label,
  }));
});

const isQuoteTypeSelected = computed(() =>
{
  return (
    (filters.quoteType === null || filters.quoteType === '') && isError.value
  );
});

function onSubmit()
{
  if (filters.start_date)
  {
    filters.start_date = filters.start_date.split('T')[0];
  }
  if (filters.end_date)
  {
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

function onReset()
{
  router.visit(route('instant-alfred.logs'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryStringFilters()
{
  for (const [key] of Object.entries(params))
  {
    if (key.includes('[]'))
    {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else
    {
      filters[key] = params[key];
    }
  }
}

const showChat = item =>
{
  loader.view = true;
  axios
    .post('/get-alfred-chat-by-date', {
      quoteId: item.quote_id,
      quoteType: item.quote_type,
      created_at: useDateFormat(item.created_at.split(' ')[0], 'YYYY-MM-DD')
        .value,
    })
    .then(response =>
    {
      let { data } = { ...response.data };
      loader.view = false;
      chatMessages.value.created_at = item.created_at;
      chatMessages.value.data = data;
      chatMessages.value.id = item.quote_type + '-' + item.quote_id;
      showChatLogs.value = true;
    })
    .catch(error =>
    {
      loader.view = false;
    });
};

onMounted(() =>
{
  setQueryStringFilters();
});
</script>

<template>

  <Head title="InstantAlfred Chat Logs" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">InstantAlfred Chat Logs</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit"
          :auto-focus="false">
    <div class="grid sm:grid-cols-3 md:grid-cols-3 gap-4">
      <x-field label="Ref-ID">
        <x-input v-model="filters.quoteId"
                 type="search"
                 name="code"
                 class="w-full"
                 placeholder="Search by Ref-ID" />
      </x-field>
      <x-field label="Quote Type"
               required>
        <combo-box v-model="filters.quoteType"
                   :options="[
                    { label: 'Car', value: 'Car' },
                    { label: 'Health', value: 'Health' },
                    { label: 'Travel', value: 'Travel' },
                  ]"
                   placeholder="Select a Quote Type"
                   class="w-full"
                   single>
        </combo-box>
      </x-field>
      <x-field label="Start Date">
        <DatePicker v-model="filters.start_date"
                    class="w-full" />
      </x-field>
      <x-field label="End Date">
        <DatePicker v-model="filters.end_date"
                    class="w-full" />
      </x-field>
      <x-field label="Transaction Type"
               >
        <combo-box :options="[
          { label: 'New Business', value: 'Car' },
          { label: 'Existing Customer\'s Renewal', value: 'Health' },
          { label: 'Existing Customer\'s New Business', value: 'Travel' },
        ]"
                   placeholder="Search by Transaction type"
                   class="w-full">
        </combo-box>
      </x-field>
      <x-field label="Batch"
               >
        <combo-box :options="[]"
                   placeholder="Search by Batch"
                   class="w-full">
        </combo-box>
      </x-field>
      <x-field label="Lead Status"
               >
        <combo-box :options="[]"
                   placeholder="Select the Lead status"
                   class="w-full">
        </combo-box>
      </x-field>
      <x-field label="Payment status"
               >
        <x-select :options="[{ value: null, label: 'All' },
        { value: 'yes', label: 'Yes' },
        { value: 'no', label: 'No' },
        ]"
                  placeholder="Search by Payment status"
                  class="w-full" />
      </x-field>
      <x-field label="Sale leads"
               >
        <x-select :options="[{ value: null, label: 'All' },
        { value: 'yes', label: 'Yes' },
        { value: 'no', label: 'No' },
        ]"
                  placeholder="Search by Sale leads"
                  class="w-full" />
      </x-field>
      <x-field label="Fallback"
               >
        <x-select :options="[{ value: null, label: 'All' },
        { value: 'yes', label: 'Yes' },
        { value: 'no', label: 'No' },
        ]"
                  placeholder="Search by Fallback"
                  class="w-full" />
      </x-field>
      <x-field label=" Message channel"
               >
        <x-select :options="[{ value: null, label: 'All' },
        { value: 'whatsapp', label: 'Whatsapp' },
        { value: 'e-commerce', label: 'E-commerce' },
        ]"
                  placeholder="Search by Message channel"
                  class="w-full" />
      </x-field>
      <x-field label="Segment"
               >
        <x-select :options="[{ value: null, label: 'All' },
        { value: 'SIC', label: 'SIC' },
        { value: 'Non-SIC', label: 'Non-SIC' },
        { value: 'SIC renewals', label: 'SIC renewals' },
        ]"
                  placeholder="Search by SIC"
                  class="w-full" />
      </x-field>
      <x-field label="Email"
               >
        <x-input placeholder="Search by Email"
                  class="w-full" />
      </x-field>
      <x-field label="Mobile Number"
               >
        <x-input placeholder="Search by Mobile number"
                  class="w-full" />
      </x-field>
      <x-field label="Report"
               >
               <x-select :options="[{ value: null, label: 'All' },
        { value: 'Consolidated Report', label: 'Consolidated Report' },
        { value: 'Detailed Report', label: 'Detailed Report' },
        ]"
                  placeholder="Select the Report type"
                  class="w-full" />
      </x-field>
    </div>

    <div class="flex justify-end gap-3">
      <x-button size="sm"
                color="#ff5e00"
                type="submit">Search</x-button>
      <x-button size="sm"
                color="primary"
                @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>

  <chat-logs-modal :showChatLogs="showChatLogs"
                   :chatMessages="chatMessages"
                   @update:showChatLogs="showChatLogs = $event"></chat-logs-modal>

  <DataTable table-class-name="tablefixed mt-3"
             :loading="loader.table"
             :headers="tableHeader"
             :items="logs.data || []"
             border-cell
             hide-rows-per-page
             hide-footer
             fixed-checkbox>
    <template #item-quote_id="{ quote_id, quote_type }">
      <span v-if="quote_type.toLowerCase().includes('hea')">
        {{ 'HEA' + '-' + quote_id }}
      </span>
      <span v-else-if="quote_type.toLowerCase().includes('travel')">
        {{ 'TRA' + '-' + quote_id }}
      </span>
      <span v-else>
        {{ quote_type + '-' + quote_id }}
      </span>
    </template>
    <template #item-created_at="{ created_at }">
      {{ dateFormat(created_at.split(' ')[0]) }}
    </template>
    <template #item-action="item">
      <x-button size="xs"
                color="primary"
                outlined
                @click="showChat(item)"
                :loading="loader.view">
        View
      </x-button>
    </template>
  </DataTable>

  <Pagination :links="{
    next: logs.next_page_url,
    prev: logs.prev_page_url,
    current: logs.current_page,
    from: logs.from,
    to: logs.to,
  }" />
</template>
