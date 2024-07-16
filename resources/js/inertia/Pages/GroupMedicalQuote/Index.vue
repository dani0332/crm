<script setup>
defineProps({
  model: String,
  leadStatuses: Array,
  advisors: Array,
  isManagerORDeputy: Boolean,
  quotes: Object,
  isManualAllocationAllowed: Boolean,
});

const canExport = ref(false);
const page = usePage();
const notification = useNotifications('toast');
const { isRequired } = useRules();

const created_at_rule = v => {
  if (filters.created_at_end) {
    return isRequired(v);
  }
  return true;
};

const created_at_end_rule = v => {
  if (filters.created_at_start) {
    return isRequired(v);
  }
  return true;
};

const quotesSelected = ref([]);

const loader = reactive({
  table: false,
  export: false,
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  leadStatus: [],
  advisor_id: '',
  page: 1,
  previous_quote_policy_number: '',
  renewal_batch: '',
  payment_due_date:"",
  booking_date: ""
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});
const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'leadStatus' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'PRICE', value: 'premium' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CREATED AT', value: 'created_at' },
  { text: 'Updated AT', value: 'updated_at' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  { text: 'Renewal Batch', value: 'renewal_batch' },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  router.visit(route('amt.index'), {
    method: 'get',
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      filters.page = 1;
      loader.table = true;
    },
  });
}

function filterQuotes(isValid) {
  if (!isValid) {
    return;
  }
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }
  if (filters.created_at_start) {
    filters.created_at_start = filters.created_at_start.split('T')[0];
  }
  if (filters.created_at_end) {
    filters.created_at_end = filters.created_at_end.split('T')[0];
  }
  router.visit(route('amt.index'), {
    method: 'get',
    data: {
      ...filters,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      filters.page = 1;
      loader.table = true;
    },
  });
}

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'business',
  selectTmLeadId: '',
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);

    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post(route('manualLeadAssign', { quoteType: 'business' }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: res => {
          quotesSelected.value = [];
        },
      });
  }
}

function displayNotification() {
  const session = usePage().props.flash;
  for (const key in session) {
    notification[key]({
      title: session[key],
      position: 'top',
      timeout: 0,
    });
  }
}

function setQueryFilters() {
  let urlParams = new URLSearchParams(window.location.search);
  for (const [key, value] of urlParams) {
    if (key.includes('[')) {
      let index = key.replace('[]', '');
      filters[index] = urlParams.getAll(key).map(item => parseInt(item));
    } else {
      filters[key] = value.match(/^\d+$/) ? parseInt(value) : value;
    }
  }
}

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'amt');
  window.open(url + '?' + new URLSearchParams(data).toString());
};

watch(
  () => filters,
  () => {
    if (filters.created_at_start && filters.created_at_end) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

onMounted(() => {
  setQueryFilters();
});
const resetDateFilters = (filterName) => {
  if (filterName === 'payment_due_date') {
    filters.created_at_start = '';
    filters.created_at_end = '';
    filters.booking_date = '';
  } else if (filterName === 'booking_date') {
    filters.payment_due_date = '';
    filters.created_at_start = '';
    filters.created_at_end = '';
  } else if (filterName === 'created_at_start' || filterName === 'created_at_end') {
    filters.booking_date = '';
    filters.payment_due_date = '';
  }
};
watch(() => filters.payment_due_date, (newValue) => {
  if (newValue) {
    resetDateFilters('payment_due_date');
  }
});

watch(() => filters.booking_date, (newValue) => {
  if (newValue) {
    resetDateFilters('booking_date');
  }
});

watch(() => filters.created_at_start, (newValue) => {
  if (newValue) {
    resetDateFilters('created_at_start');
  }
});

watch(() => filters.created_at_end, (newValue) => {
  if (newValue) {
    resetDateFilters('created_at_end');
  }
});
</script>

<template>
  <div>
    <Head title="AMT List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link :href="route('amt.cardsView')">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>

        <Link :href="route('amt.create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="filterQuotes" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <x-tooltip position="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Ref-ID
            </label>
            <template #tooltip> Reference ID </template>
          </x-tooltip>
          <x-input
            v-model="filters.code"
            type="search"
            name="code"
            class="w-full"
            placeholder="Search by Ref-ID"
          />
        </div>
        <x-field label="First Name">
          <x-input
            v-model="filters.first_name"
            type="search"
            name="first_name"
            class="w-full"
            placeholder="Search by First Name"
          />
        </x-field>
        <x-field label="Last Name">
          <x-input
            v-model="filters.last_name"
            type="search"
            name="last_name"
            class="w-full"
            placeholder="Search by Last Name"
          />
        </x-field>
        <x-field label="Email">
          <x-input
            v-model="filters.email"
            type="search"
            name="email"
            class="w-full"
            placeholder="Search by Email"
          />
        </x-field>
        <x-field label="Mobile Number">
          <x-input
            v-model="filters.mobile_no"
            type="search"
            name="mobile_no"
            class="w-full"
            placeholder="Search by Mobile Number"
          />
        </x-field>
        <x-field label="Created Date Start">
          <DatePicker
            v-model="filters.created_at_start"
            name="created_at_start"
            :rules="[created_at_rule]"
          />
        </x-field>
        <x-field label="Created Date End">
          <DatePicker
            v-model="filters.created_at_end"
            name="created_at_end"
            :rules="[created_at_end_rule]"
          />
        </x-field>
        <x-field label="Lead Status">
          <ComboBox
            v-model="filters.leadStatus"
            placeholder="Search by Lead Status"
            :options="leadStatusOptions"
          />
        </x-field>
        <x-field label="Advisor">
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptions"
            class="w-full"
          />
        </x-field>

        <x-input
          v-model="filters.previous_quote_policy_number"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
        />

        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />
        <DatePicker
          v-model="filters.payment_due_date"
          label="Payment Due Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
        <DatePicker
          v-model="filters.booking_date"
          label="Booking Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            @click.prevent="onDataExport"
            class="justify-self-start"
          >
            Export
          </x-button>
          <x-tooltip v-else position="right">
            <x-button tag="div" size="sm" color="emerald"> Export </x-button>
            <template #tooltip>
              <span class="font-medium">
                Created dates are required to export data.
              </span>
            </template>
          </x-tooltip>
        </div>
        <div v-else />
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <Transition name="fade">
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <div
          class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50"
          v-if="isManualAllocationAllowed == true"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <ComboBox
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                :single="true"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :error="assignForm.errors.assigned_to_id_new"
              />
              <div class="mb-3 md:pt-6">
                <x-button
                  color="orange"
                  size="sm"
                  type="submit"
                  :loading="assignForm.processing"
                >
                  Assign
                </x-button>
              </div>
            </div>
          </x-form>
        </div>
      </div>
    </Transition>

    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-code="{ code, uuid }">
        <a
          :href="route('amt.show', uuid)"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </a>
      </template>

      <template #item-source="{ source }">
        <a
          :href="source && source.includes('http') ? source : '#'"
          :target="source && source.includes('http') ? '_blank' : '_self'"
          class="text-primary-500 hover:underline"
        >
          {{ source }}
        </a>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
    />
  </div>
</template>
