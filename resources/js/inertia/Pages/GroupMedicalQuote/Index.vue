<script setup>
defineProps({
  model: String,
  leadStatuses: Array,
  advisors: Array,
  isManagerORDeputy: Boolean,
  quotes: Object,
  isManualAllocationAllowed: Boolean,
  authorizedDays: Number,
});

const canExport = ref(false);
const page = usePage();
const notification = useNotifications('toast');
const cleanObj = obj => useCleanObj(obj);
const { isRequired } = useRules();

const serverOptions = ref({
  page: 1,
  sortType: 'desc',
});

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
  created_at_start: new Date().toISOString() || '',
  created_at_end: new Date().toISOString() || '',
  leadStatus: [],
  advisor_id: '',
  page: 1,
  previous_quote_policy_number: '',
  renewal_batch: '',
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  company_name: '',
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
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
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at' },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date' },
  { text: 'LEAD STATUS', value: 'leadStatus' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'PRICE', value: 'premium' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CREATED AT', value: 'created_at' },
  { text: 'Updated AT', value: 'updated_at' },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date',
    sortable: true,
  },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
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
  if (validateDateRange()) {
    notification.error({
      title:
        'The selected date range exceeds one month. Please select a range within one month.',
      position: 'top',
    });
    return;
  }
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }
  // if (filters.created_at_start) {
  //   filters.created_at_start = filters.created_at_start.split('T')[0];
  // }
  // if (filters.created_at_end) {
  //   filters.created_at_end = filters.created_at_end.split('T')[0];
  // }

  serverOptions.value.page = 1;
  router.visit(route('amt.index'), {
    method: 'get',
    data: {
      ...filters,
      ...serverOptions.value,
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
    if (key == 'created_at_start' || key == 'created_at_end') {
      filters[key] = useDateFormat(urlParams[key], 'YYYY-MM-DD').value;
    } else if (key.includes('[')) {
      let index = key.replace('[]', '');
      filters[index] = urlParams.getAll(key).map(item => parseInt(item));
    } else {
      filters[key] = value.match(/^\d+$/) ? parseInt(value) : value;
    }
  }
}

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const exportLoader = ref(false);
const onDataExport = () => {
  let diff = calculateDaysDifference(
    filters.created_at_start,
    filters.created_at_end,
  );

  if (diff > 31) {
    notification.error({
      message: 'Maximum of 31 days (created date) are allowed to be exported.',
      position: 'top',
    });
    return;
  }

  filters.created_at_start = useDateFormat(
    filters.created_at_start,
    'YYYY-MM-DD',
  ).value;

  filters.created_at_end = useDateFormat(
    filters.created_at_end,
    'YYYY-MM-DD',
  ).value;

  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'amt');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Business'),
    url: url + '?' + new URLSearchParams(data).toString(),
  };

  exportLoader.value = true;
  logAndExportQuotes(payload).then(result => {
    if (result)
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
  });
};

watch(
  () => filters,
  () => {
    if (
      (filters.created_at_start && filters.created_at_end) ||
      filters.payment_due_date ||
      filters.booking_date
    ) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  setQueryFilters();

  let filtersCleaned = cleanObj(filters);

  if (filtersCleaned.sortBy) {
    serverOptions.value.sortBy = filtersCleaned.sortBy;
    delete filtersCleaned.sortBy;
  }

  if (filtersCleaned.sortType) {
    serverOptions.value.sortType = filtersCleaned.sortType;
    delete filtersCleaned.sortType;
  }

  if (filtersCleaned.page) {
    serverOptions.value.page = filtersCleaned.page;
    delete filtersCleaned.page;
  }

  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
const resetDateFilters = filterName => {
  const filterMappings = {
    payment_due_date: ['created_at_start', 'created_at_end', 'booking_date'],
    booking_date: ['payment_due_date', 'created_at_start', 'created_at_end'],
    created_at: ['booking_date', 'payment_due_date'],
  };

  const filtersToReset =
    filterMappings[filterName] ||
    (filterName.startsWith('created_at') ? filterMappings.created_at : []);

  filtersToReset.forEach(filter => {
    filters[filter] = '';
  });
};

[
  'payment_due_date',
  'booking_date',
  'created_at_start',
  'created_at_end',
].forEach(filterName => {
  watch(
    () => filters[filterName],
    newValue => {
      if (newValue) {
        resetDateFilters(filterName);
      }
    },
  );
});
function daysAgoFromAuthorizedDate(authorizedDate) {
  if (!authorizedDate) {
    return;
  }
  const [day, month, year] = authorizedDate.split('-').map(Number);
  const parsedDate = new Date(year, month - 1, day);

  if (isNaN(parsedDate.getTime())) {
    return 'Invalid date';
  }

  // Reset time to 00:00:00 to consider only the date
  parsedDate.setHours(0, 0, 0, 0);

  // Add `page.props.authorizedDays` to the parsed date
  const authorizedDays = page.props.authorizedDays || 8; // Default to 8 if not defined
  const newDate = new Date(parsedDate);
  newDate.setDate(parsedDate.getDate() + authorizedDays);

  // Reset time for newDate as well
  newDate.setHours(0, 0, 0, 0);

  const currentDate = new Date();
  currentDate.setHours(0, 0, 0, 0); // Reset time for current date

  // Calculate the difference in days
  const differenceInTime = newDate.getTime() - currentDate.getTime();
  const differenceInDays = Math.ceil(differenceInTime / (1000 * 3600 * 24));

  // Return appropriate message
  if (differenceInDays <= 0) {
    return 'Expired';
  }

  return differenceInDays === 1
    ? `${differenceInDays} day`
    : `${differenceInDays} days`;
}
const validateDateRange = () => {
  const { policy_expiry_date, policy_expiry_date_end } = filters;
  if (policy_expiry_date && policy_expiry_date_end) {
    const startDate = new Date(policy_expiry_date);
    const endDate = new Date(policy_expiry_date_end);
    const oneMonthLater = new Date(startDate);
    oneMonthLater.setMonth(oneMonthLater.getMonth() + 1);
    // Adjust for months with fewer than 31 days
    if (oneMonthLater.getDate() < startDate.getDate()) {
      oneMonthLater.setDate(0);
    }
    if (endDate > oneMonthLater) {
      return true;
    }
  }
  return false;
};
const formatDate = dateString =>
  useDateFormat(useConvertDate(dateString), 'DD-MMM-YYYY').value;

watch(() => {
  if (filters.company_name) {
    filters.created_at_start = '';
    filters.created_at_end = '';
  }
});

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) filterQuotes(true);
  },
  { deep: true },
);
</script>

<template>
  <div>
    <Head title="AMT List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link :href="route('amt.cardsView')">
          <x-button
            size="sm"
            color="#1d83bc"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Cards View
          </x-button>
        </Link>

        <Link :href="route('amt.create')">
          <x-button
            size="sm"
            color="#ff5e00"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Create Lead
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="filterQuotes" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <x-tooltip placement="bottom">
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
        <x-field label="Company Name">
          <x-input
            v-model="filters.company_name"
            type="search"
            name="company_name"
            class="w-full"
            placeholder="Search by Company Name"
          />
        </x-field>
        <x-field label="Created Date Start">
          <DatePicker
            v-model="filters.created_at_start"
            name="created_at_start"
            :rules="
              filters.previous_quote_policy_number ||
              filters.code ||
              filters.email ||
              filters.renewal_batch ||
              filters.payment_due_date ||
              filters.booking_date ||
              filters.company_name
                ? []
                : [isRequired]
            "
          />
        </x-field>
        <x-field label="Created Date End">
          <DatePicker
            v-model="filters.created_at_end"
            name="created_at_end"
            :rules="
              filters.previous_quote_policy_number ||
              filters.code ||
              filters.email ||
              filters.renewal_batch ||
              filters.payment_due_date ||
              filters.booking_date ||
              filters.company_name
                ? []
                : [isRequired]
            "
          />
        </x-field>
        <x-field label="Lead Status">
          <ComboBox
            v-model="filters.leadStatus"
            placeholder="Search by Lead Status"
            :options="leadStatusOptions"
          />
        </x-field>
        <x-field label="Policy Expiry Start Date">
          <DatePicker
            v-model="filters.policy_expiry_date"
            name="policy_expiry_date"
          />
        </x-field>
        <x-field label="Policy Expiry End Date">
          <DatePicker
            v-model="filters.policy_expiry_date_end"
            name="policy_expiry_date_end"
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
        <x-input
          v-if="can(permissionsEnum.SEARCH_INSURER_TAX_INVOICE_NUMBER)"
          v-model="filters.insurer_tax_invoice_number"
          type="text"
          name="insurer_tax_invoice_number"
          label="Insurer Tax Invoice No"
          class="w-full"
          placeholder="Insurer Tax Invoice No"
        />
        <x-input
          v-if="
            can(permissionsEnum.SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER)
          "
          v-model="filters.insurer_commission_tax_invoice_number"
          type="text"
          name="insurer_commission_tax_invoice_number"
          label="Insurer Commission Tax Invoice No"
          class="w-full"
          placeholder="Insurer Commission Tax Invoice No"
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
            :loading="exportLoader"
          >
            Export
          </x-button>
          <x-tooltip v-else placement="right">
            <x-button tag="div" size="sm" color="emerald"> Export </x-button>
            <template #tooltip>
              <span class="font-medium">
                Created dates or payment due date or booking date are required
                to export data.
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
                v-if="readOnlyMode.isDisable === true"
              />
              <div class="mb-3 md:pt-6">
                <x-button
                  color="orange"
                  size="sm"
                  type="submit"
                  :loading="assignForm.processing"
                  v-if="readOnlyMode.isDisable === true"
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
      v-model:server-options="serverOptions"
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid }">
        <Link
          :href="route('amt.show', uuid)"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
      </template>
      <template #item-authorized_at="item">
        <p v-if="item.payment_status_id_text === 'AUTHORISED'">
          {{ item.authorized_at }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p v-if="item.payment_status_id_text === 'AUTHORISED'">
          {{ daysAgoFromAuthorizedDate(item.authorized_at) }}
        </p>
      </template>
      <template
        #item-previous_policy_expiry_date="{
          previous_policy_expiry_date,
          source,
        }"
      >
        {{
          source === 'Renewal_upload'
            ? formatDate(previous_policy_expiry_date)
            : ''
        }}
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
