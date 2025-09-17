<script setup>
// Test comment for Cursor rule - testing pre-commit hook
// Another test comment to trigger "Build Vue assets before commit" rule
// Testing git hook implementation
defineProps({
  quotes: Object,
  leadStatuses: Array,
  advisors: Array,
  renewalBatches: Array,
  isManualAllocationAllowed: Boolean,
  authorizedDays: Number,
  insurerAMLStatus: Array,
});

const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useNotifications('toast');
const { isRequired } = useRules();

const loader = reactive({
  table: false,
  export: false,
});

const quotesSelected = ref([]);

let params = useUrlSearchParams('history');
const cleanObj = obj => useCleanObj(obj);
const showFilters = ref(true);
const filtersCount = ref(0);
const serverOptions = ref({
  page: 1,
  sortBy: 'created_at',
  sortType: 'desc',
});

const tableHeader = ref([
  { text: 'Ref-ID', value: 'code', is_active: true },
  { text: 'FIRST NAME', value: 'first_name', is_active: true },
  { text: 'LAST NAME', value: 'last_name', is_active: true },
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at', is_active: true },
  { text: 'PAYMENT CAPTURED DATE', value: 'captured_at', is_active: true },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date', is_active: true },
  { text: 'LEAD STATUS', value: 'quote_status_id_text', is_active: true },
  {
    text: 'INSURER AML STATUS',
    value: 'insurer_aml_status_display',
    is_active: true,
  },
  { text: 'ADVISOR', value: 'advisor_id_text', is_active: true },
  {
    text: 'CREATED DATE',
    value: 'created_at',
    is_active: true,
    sortable: true,
  },
  {
    text: 'LAST MODIFIED DATE',
    value: 'updated_at',
    is_active: true,
    sortable: true,
  },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date',
    is_active: true,
    sortable: true,
  },
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  { text: 'PRICE', value: 'price_with_vat', is_active: true, sortable: true },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  {
    text: 'Previous Policy Number',
    value: 'previous_quote_policy_number',
    is_active: true,
  },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    is_active: true,
    sortable: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch_text', is_active: true },
  {
    text: 'Private Client',
    value: 'customer.pcp_tag_formatted',
    is_active: true,
  },
]);

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status_id: [],
  insurer_aml_status: [],
  advisors: [],
  is_renewal: '',
  previous_quote_policy_number: '',
  renewal_batches: [],
  payment_status: [],
  is_cold: false,
  is_stale: false,
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  payment_due_date: '',
  booking_date: '',
  advisor_assigned_date: null,
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
  authorize_date: '',
  captured_date: '',
  private_client: 'all',
});

// PUA Export Modal state
const puaExportModal = reactive({
  show: false,
  exportType: 'all',
  payment_date: '',
});

const canExport = ref(false);
watch(
  () => filters,
  () => {
    if (
      (filters.created_at_start && filters.created_at_end) ||
      (filters.policy_expiry_date && filters.policy_expiry_date_end) ||
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
const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  let options = [
    {
      value: '-1',
      label: 'UnAssigned',
    },
  ];

  options.push(
    ...page.props.advisors.map(advisor => ({
      value: advisor.id,
      label: advisor.name,
    })),
  );

  return options;
});

const renewalBatchOptions = computed(() => {
  return page.props?.renewalBatches?.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

const exportLoader = ref(false);
const onDataExport = (exportType = 'download') => {
  // Check date range restriction for created dates
  if (filters.created_at_start && filters.created_at_end) {
    let diff, maxLimit, maxPeriod;

    if (exportType === 'email') {
      // For email export, use months-based validation
      diff = calculateMonthsDifference(
        filters.created_at_start,
        filters.created_at_end,
      );
      maxLimit = 3;
      maxPeriod = '3 months';
    } else {
      // For download export, use days-based validation
      diff = calculateDaysDifference(
        filters.created_at_start,
        filters.created_at_end,
      );
      maxLimit = 31;
      maxPeriod = '31 days';
    }

    if (diff > maxLimit) {
      notification.error({
        message: `Maximum of ${maxPeriod} (created date) are allowed to be exported.`,
        position: 'top',
      });
      return;
    }
  }

  filters.exportType = exportType;

  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'home');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Home'),
    exportType: exportType,
    url: url + '?' + new URLSearchParams(data).toString(),
  };
  exportLoader.value = true;

  logAndExportQuotes(payload)
    .then(result => {
      if (result.data.message) {
        notification.success({
          title: result.data.message,
          position: 'top',
        });
      }
      if (result)
        setTimeout(() => {
          exportLoader.value = false;
        }, 1000);
    })
    .catch(err => {
      notification.error({
        title: err.response.data.message
          ? err.response.data.message
          : 'Unable to start an export',
        position: 'top',
      });
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
      throw err;
    });
};

const onPUAExport = () => {
  // Open the PUA export modal
  puaExportModal.show = true;
};

const onConfirmPUAExport = () => {
  // Use the single date for both authorize and capture date filters
  const filtersForExport = {
    authorize_date: puaExportModal.payment_date,
    captured_date: puaExportModal.payment_date,
  };

  const data = useObjToUrl(filtersForExport);
  const url = `/Home/pua-leads-export?${data}`;

  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Home'),
    exportType: 'download',
    url: url,
    filters: { ...filtersForExport },
  };

  exportLoader.value = true;
  puaExportModal.show = false; // Close modal

  logAndExportQuotes(payload)
    .then(result => {
      if (result.data.message) {
        notification.success({
          title: result.data.message,
          position: 'top',
        });
      }
      if (result)
        setTimeout(() => {
          exportLoader.value = false;
        }, 1000);
    })
    .catch(err => {
      notification.error({
        title: err.response.data.message
          ? err.response.data.message
          : 'Unable to start PUA export',
        position: 'top',
      });
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
      throw err;
    });
};

function onSubmit(isValid) {
  if (isValid) {
    if (validateDateRange()) {
      notification.error({
        title:
          'The selected date range exceeds one month. Please select a range within one month.',
        position: 'top',
      });
      return;
    }
    serverOptions.value.page = 1;

    const filtersCleaned = cleanObj(filters);

    filtersCount.value = Object.keys(filtersCleaned).length;

    router.visit(route('home-quotes-list'), {
      method: 'get',
      data: {
        ...filtersCleaned,
        ...serverOptions.value,
      },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onFinish: () => (loader.table = false),
    });
  } else {
  }
}

function onReset() {
  removedSavedParams();
  router.visit(route('home-quotes-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const handleSelectedFilters = selectedFilters => {
  if (selectedFilters.created_at_start && selectedFilters.created_at_end) {
    filters.created_at_start = selectedFilters.created_at_start;
    filters.created_at_end = selectedFilters.created_at_end;
  }

  if (selectedFilters.quote_status) {
    filters.quote_status = selectedFilters.quote_status;
  }

  if (selectedFilters.payment_status) {
    filters.payment_status = selectedFilters.payment_status;
  }

  filters.is_cold = selectedFilters.cold;
  filters.is_stale = selectedFilters.stale;

  onSubmit(true);
};

function setQueryStringFilters() {
  // Define which fields should have integer values
  const integerFields = [
    'quote_status_id',
    'insurer_aml_status',
    'advisors',
    'renewal_batches',
    'payment_status',
    'page',
  ];

  // Group array parameters
  const arrayParams = {};
  const singleParams = {};

  for (const [key, value] of Object.entries(params)) {
    // Check for indexed array format like authorize_date[0], authorize_date[1]
    const arrayMatch = key.match(/^(.+)\[(\d+)\]$/);

    if (arrayMatch) {
      const [, fieldName, index] = arrayMatch;
      if (!arrayParams[fieldName]) {
        arrayParams[fieldName] = [];
      }
      arrayParams[fieldName][parseInt(index)] = value;
    } else if (key.includes('[]')) {
      // Handle simple array format like quote_status_id[]
      const fieldName = key.substring(0, key.length - 2);
      arrayParams[fieldName] = Array.isArray(value) ? value : [value];
    } else {
      // Single parameters
      singleParams[key] = value;
    }
  }

  // Process array parameters
  for (const [fieldName, values] of Object.entries(arrayParams)) {
    // Filter out undefined values and convert to correct type
    const cleanValues = values.filter(v => v !== undefined);

    if (integerFields.includes(fieldName)) {
      filters[fieldName] = cleanValues
        .map(v => parseInt(v))
        .filter(v => !isNaN(v));
    } else {
      filters[fieldName] = cleanValues;
    }
  }

  // Process single parameters
  for (const [key, value] of Object.entries(singleParams)) {
    if (integerFields.includes(key) && !isNaN(parseInt(value))) {
      filters[key] = parseInt(value);
    } else if (key === 'is_cold' && (value === '0' || value === '1')) {
      // Boolean-like fields
      filters[key] = parseInt(value);
    } else if (key === 'is_stale' && (value === '0' || value === '1')) {
      // Boolean-like fields
      filters[key] = parseInt(value);
    } else {
      // Keep as string for dates, text fields, enums, etc.
      filters[key] = value;
    }
  }
}

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'Home',
  selectTmLeadId: '',
  isManualAllocationAllowed: page.props.isManualAllocationAllowed,
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post(route('manualLeadAssign', { quoteType: 'home' }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          quotesSelected.value = [];
        },
      });
  }
}

const readOnlyMode = reactive({
  isDisable: true,
});

onMounted(() => {
  params = getSavedQueryParams() || params;

  setQueryStringFilters();

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

  filtersCount.value = Object.keys(filtersCleaned).length;
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
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

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) onSubmit(true);
  },
);

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

const resetDateFilters = filterName => {
  const filterMappings = {
    payment_due_date: ['created_at_start', 'created_at_end', 'booking_date'],
    booking_date: ['payment_due_date', 'created_at_start', 'created_at_end'],
    created_at: ['booking_date', 'payment_due_date'],
    previous_quote_policy_number: ['created_at_start', 'created_at_end'],
  };

  const filtersToReset =
    filterMappings[filterName] ||
    (filterName.startsWith('created_at') ? filterMappings.created_at : []);

  filtersToReset.forEach(filter => {
    filters[filter] = '';
  });
};

[
  'email',
  'mobile_no',
  'code',
  'created_at_start',
  'created_at_end',
  'renewal_batch',
  'previous_quote_policy_number',
  'payment_due_date',
  'booking_date',
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

const yesterday = computed(() => {
  const date = new Date();
  date.setDate(date.getDate() - 1);
  return date;
});

const canExportPUA = computed(() => {
  return puaExportModal.payment_date;
});

const insurerAMLStatusOption = computed(() => {
  return Object.entries(page.props.insurerAMLStatus).map(([key, value]) => ({
    value: key,
    label: value,
  }));
});

const formatDate = dateString =>
  useDateFormat(useConvertDate(dateString), 'DD-MMM-YYYY').value;
</script>

<template>
  <div>
    <Head title="Home List" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Home List</h2>
      </template>
      <template #default>
        <ColumnSelection
          v-model:columns="tableHeader"
          storage-key="home-list"
        />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />

        <Link :href="route('home-quotes-card')">
          <x-button
            size="sm"
            color="#1d83bc"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Cards View
          </x-button>
        </Link>

        <Link :href="route('home-quotes-create')">
          <x-button
            size="sm"
            color="#ff5e00"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Create Lead
          </x-button>
        </Link>
      </template>
    </StickyHeader>
    <x-divider class="my-4" />
    <x-form v-show="showFilters" @submit="onSubmit" :auto-focus="false">
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
        <x-input
          v-model="filters.first_name"
          type="search"
          name="first_name"
          class="w-full"
          placeholder="Search by First Name"
          label="First Name"
        />
        <x-input
          v-model="filters.last_name"
          type="search"
          name="last_name"
          class="w-full"
          placeholder="Search by Last Name"
          label="Last Name"
        />
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          class="w-full"
          placeholder="Search by Email"
          label="Email"
        />
        <x-input
          v-model="filters.mobile_no"
          type="search"
          name="mobile_no"
          class="w-full"
          placeholder="Search by Mobile Number"
          label="Mobile Number"
        />
        <DatePicker
          v-model="filters.created_at_start"
          name="created_at_start"
          label="Created Date Start"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
        />
        <DatePicker
          v-model="filters.advisor_assigned_date"
          name="created_at_start"
          label="Advisor Assigned Date"
          range
          format="dd-MM-yyyy"
        />
        <x-select
          v-model="filters.quote_status_id"
          name="quote_status_id"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
          class="w-full"
          filterable
          label="Lead Status"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.quote_status_id = leadStatusOptions.map(
                  item => item.value,
                )
              "
              @clear="filters.quote_status_id = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.insurer_aml_status"
          name="insurer_aml_status"
          placeholder="Search by Insurer AML Status"
          :options="insurerAMLStatusOption"
          class="w-full"
          filterable
          label="Insurer AML Status"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.insurer_aml_status = insurerAMLStatusOption.map(
                  item => item.value,
                )
              "
              @clear="filters.insurer_aml_status = []"
            />
          </template>
        </x-select>
        <DatePicker
          v-model="filters.policy_expiry_date"
          name="policy_expiry_date"
          label="Policy Expiry Start Date"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_end"
          name="policy_expiry_date_end"
          label="Policy Expiry End Date"
        />
        <x-select
          v-if="
            !hasAnyRole([rolesEnum.HomeAdvisor, rolesEnum.HomeRenewalAdvisor])
          "
          v-model="filters.advisors"
          name="advisor_id"
          placeholder="Search by Advisor"
          :options="advisorOptions"
          class="w-full"
          filterable
          label="Advisor"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.advisors = advisorOptions.map(item => item.value)
              "
              @clear="filters.advisors = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.is_renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
          label="Renewal"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
        />
        <x-select
          v-model="filters.renewal_batches"
          placeholder="Search by Renewal Batch"
          label="Renewal Batch"
          :options="renewalBatchOptions"
          multiple
          truncate
          filterable
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.renewal_batches = renewalBatchOptions.map(
                  item => item.value,
                )
              "
              @clear="filters.renewal_batches = []"
            />
          </template>
        </x-select>

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
        <DatePicker
          v-model="filters.authorize_date"
          label="Payment Authorised Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
        <DatePicker
          v-model="filters.captured_date"
          label="Payment Captured Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
        />
        <DatePicker
          v-if="hasRole(rolesEnum.HomeManager)"
          v-model="filters.advisor_assigned_date"
          name="created_at_start"
          label="Advisor Assigned Date"
          range
          format="dd-MM-yyyy"
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
        <ComboBox
          v-model="filters.private_client"
          label="Private Client"
          placeholder="Search by private client tag"
          :options="[
            { value: 'all', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 'no', label: 'No' },
            { value: 0, label: 'Ex-Pc' },
          ]"
          class="w-full"
          :single="true"
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click.prevent="onDataExport"
            class="justify-self-start mr-3"
          >
            Export
          </x-button>
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click.prevent="onDataExport('email')"
            class="justify-self-start mr-3"
          >
            Export via email
          </x-button>
          <x-tooltip v-else placement="right">
            <x-button tag="div" size="sm" color="emerald" class="mr-3">
              Export
            </x-button>
            <x-button tag="div" size="sm" color="emerald" class="mr-3"
              >Export via email</x-button
            >
            <template #tooltip>
              <span class="font-medium">
                Created dates or policy expiry dates or payment due date or
                booking date are required to export data.
              </span>
            </template>
          </x-tooltip>

          <x-button
            v-if="can(permissionsEnum.EXPORT_HOME_PUA_UPDATES)"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click="onPUAExport"
            class="justify-self-start mr-3"
          >
            Export PUA Updates
          </x-button>
        </div>
        <div v-else />
        <div class="flex justify-self-end gap-3">
          <x-button
            size="sm"
            color="#ff5e00"
            type="submit"
            :loading="loader.table"
          >
            Search
          </x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <section
      v-if="quotesSelected.length > 0 && !can(permissionsEnum.VIEW_ALL_LEADS)"
      class="mb-4"
    >
      <div
        class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50"
        v-if="isManualAllocationAllowed == true"
      >
        <h3 class="font-semibold text-primary-800">Assign Leads</h3>
        <x-divider class="mb-4 mt-1" />
        <x-form @submit="onAssignLead" :auto-focus="false">
          <div class="w-full flex flex-col md:flex-row gap-4">
            <x-select
              v-model="assignForm.assigned_to_id_new"
              :options="advisorOptions"
              placeholder="Select Advisor"
              class="flex-1 w-full"
              :rules="[isRequired]"
              label="Assign Advisor"
              filterable
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
    </section>
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
      <template #item-code="{ code, uuid, stale_at, price_with_vat }">
        <Link
          :href="route('home-quotes-show', uuid)"
          class="text-primary-500 hover:underline flex items-center space-x-1"
        >
          <span>{{ code }}</span>
          <StaleLeadsBadge :date="stale_at" :align="`left`" />
        </Link>
      </template>
      <template #item-authorized_at="item">
        <p v-if="item?.payments[0]?.payment_status_id === 4">
          {{ item?.payments[0]?.authorized_at }}
        </p>
      </template>
      <template #item-captured_at="item">
        <p v-if="item?.payments[0]?.captured_at">
          {{ item?.payments[0]?.captured_at }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p v-if="item?.payments[0]?.payment_status_id === 4">
          {{ daysAgoFromAuthorizedDate(item.payments[0].authorized_at) }}
        </p>
      </template>
      <template #item-previous_policy_expiry_date="item">
        {{
          item?.source === 'Renewal_upload'
            ? formatDate(item?.previous_policy_expiry_date)
            : ''
        }}
      </template>
      <template #item-quote_status_id_text="item">
        {{ item?.quote_status?.text }}
      </template>
      <template #item-advisor_id_text="item">
        {{ item?.advisor?.name }}
      </template>
      <template #item-renewal_batch_text="item">
        <p>
          {{ item.renewal_batch_text }}
        </p>
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

    <!-- PUA Export Modal -->
    <x-modal
      v-model="puaExportModal.show"
      size="lg"
      title="Export Home PUA Updates"
      show-close
      backdrop
      persistent
    >
      <div class="grid grid-cols-1 gap-4">
        <DatePicker
          v-model="puaExportModal.payment_date"
          label="Payment Date"
          class="w-full"
          :max-date="yesterday"
        />
      </div>

      <template #secondary-action>
        <x-button
          ghost
          tabindex="-1"
          size="sm"
          @click.prevent="puaExportModal.show = false"
        >
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <x-button
          v-if="canExportPUA"
          size="sm"
          color="emerald"
          :loading="exportLoader"
          @click="onConfirmPUAExport"
        >
          Export Data
        </x-button>
      </template>
    </x-modal>
  </div>
</template>
