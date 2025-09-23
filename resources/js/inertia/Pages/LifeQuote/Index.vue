<script setup>
defineProps({
  quotes: Object,
  leadStatuses: Array,
  renewalBatches: Array,
  advisors: Array,
  authorizedDays: Number,
  typesOfInsurance: Array,
  numberOfYears: Array,
  currency: Array,
  insurerAMLStatus: Array,
  planSubTypes: Array,
  quoteSegments: Object,
});

const page = usePage();
let params = useUrlSearchParams('history');

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const cleanObj = obj => useCleanObj(obj);
const showFilters = ref(true);
const filtersCount = ref(0);
const quoteSegments = page.props.quoteSegments;
const quoteSegmentsLife = [
  { value: 'all', label: 'All' },
  ...quoteSegments.filter(
    segment => segment.value === 'fic' || segment.value === 'non-fic',
  ),
];

const rules = {
  isRequired: v => !!v || 'This field is required',
  created_at_end: v => {
    if (filters.created_at_start && !v) {
      return 'This field is required';
    }
    return true;
  },
  created_at_start: v => {
    if (filters.created_at_end && !v) {
      return 'This field is required';
    }
    return true;
  },
};

const serverOptions = ref({
  page: 1,
  sortType: 'desc',
});

const role = [rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.LifeManager];
const roleLeadPool = [rolesEnum.LeadPool];
const hasAnyRole = role => useHasAnyRole(role);

const isManualAllocationAllowed = ref(false);
const isLeadPool = ref(false);

isManualAllocationAllowed.value = hasAnyRole(role);
isLeadPool.value = hasAnyRole(roleLeadPool);
const notification = useNotifications('toast');

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
  advisor_id: [],
  renewal_batch_id: [],
  is_ecommerce: '',
  payment_status_id: '',
  previous_quote_policy_number_text: '',
  page: 1,
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  last_modified_date: null,
  advisor_assigned_date: null,
  insurer_tax_number: '',
  insurer_commmission_invoice_number: '',
  tenure_of_insurance_id: '',
  sum_insured_currency_id: null,
  sum_insured_range: '',
  plan_type: '',
  segment_filter: '',
  private_client: 'all',
  authorize_date: '',
  captured_date: '',
});

const filterButtonStatuses = [
  {
    text: 'Policy Booked',
    value: 1,
    quoteCodes: ['Policy Booked'],
    tooltip: '',
  },
  {
    text: 'In Negotiation',
    value: 2,
    quoteCodes: ['In Negotiation'],
    tooltip: '',
  },
  {
    text: 'Application Submitted',
    value: 3,
    quoteCodes: ['Application Submitted'],
    tooltip: '',
  },
  {
    text: 'Followed Up',
    value: 4,
    quoteCodes: ['Followed Up'],
    tooltip: '',
  },
  {
    text: 'Quoted',
    value: 5,
    quoteCodes: ['Quoted'],
    tooltip: '',
  },
];

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = ref([
  { text: 'Ref-ID', value: 'code', is_active: true },
  { text: 'FIRST NAME', value: 'first_name', is_active: true },
  { text: 'LAST NAME', value: 'last_name', is_active: true },
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at', is_active: true },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date', is_active: true },
  { text: 'LEAD STATUS', value: 'quote_status', is_active: true },
  {
    text: 'INSURER AML STATUS',
    value: 'insurer_aml_status_display',
    is_active: true,
  },
  { text: 'ADVISOR', value: 'advisor', is_active: true },
  {
    text: 'BRANCH',
    value: 'advisor.primary_branch.branch.name',
    is_active: true,
  },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  {
    text: 'CREATED DATE',
    value: 'created_at',
    is_active: true,
    sortable: true,
  },
  {
    text: 'LAST MODIFIED DATE',
    value: 'updated_at',
    sortable: true,
    is_active: true,
  },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date',
    sortable: true,
    is_active: true,
  },
  { text: 'NATIONALITY', value: 'nationality', is_active: true },
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  { text: 'PRICE', value: 'premium', sortable: true, is_active: true },
  {
    text: 'Previous Policy Number',
    value: 'previous_quote_policy_number',
    is_active: true,
  },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
  {
    text: 'Renewal Batch',
    value: 'renewal_batch_model',
    is_active: true,
  },
  { text: 'Tenure of Cover', value: 'number_of_years', is_active: true },
  { text: 'Sum Assured', value: 'sum_insured_value', is_active: true },
  {
    text: 'Private Client',
    value: 'customer.pcp_tag_formatted',
    is_active: true,
  },
]);

const filteredTableHeader = computed(() => {
  let headers = [];
  if (!hasAnyRole([rolesEnum.RMAdvisor, rolesEnum.EBPAdvisor])) {
    headers = tableHeader.value;
  } else {
    headers = tableHeader.value.filter(
      column => column.value !== 'source' && column.value !== 'assignment_type',
    );
  }
  return headers.filter(x => x.is_active);
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
});

const renewalBatchOptions = computed(() => {
  return page.props.renewalBatches.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

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

  const filtersCleaned = cleanObj(filters);
  filtersCount.value = Object.keys(filtersCleaned).length;

  serverOptions.value.page = 1;

  router.visit(route('life-quotes-list'), {
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
      loader.table = true;
    },
  });
}

const handleSelectedFilters = selectedFilters => {
  if (selectedFilters.created_at_start && selectedFilters.created_at_end) {
    filters.created_at_start = selectedFilters.created_at_start;
    filters.created_at_end = selectedFilters.created_at_end;
  }

  if (selectedFilters.quote_status) {
    filters.quote_status_id = selectedFilters.quote_status;
  }

  filterQuotes(true);
};

function resetFilters() {
  router.visit(route('life-quotes-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryFilters() {
  // Define which fields should have integer values
  const integerFields = [
    'quote_status_id',
    'insurer_aml_status',
    'advisor_id',
    'renewal_batch_id',
    'payment_status_id',
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
    } else {
      // Keep as string for dates, text fields, enums, etc.
      filters[key] = value;
    }
  }
}
const quotesSelected = ref([]);

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'life',
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
      .post('/quotes/life/manualLeadAssign', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          let title =
            quotesSelected.value.length > 1
              ? 'Life Leads Assigned'
              : 'Life Lead Assigned';
          quotesSelected.value = [];
          notification.success({
            title: title,
            position: 'top',
          });
        },
      });
  }
}

const canExport = ref(false);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

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
  const url = route('data-extraction', 'life');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Life'),
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

watch(
  () => filters,
  () => {
    if (
      can(permissionsEnum.DATA_EXTRACTION) &&
      ((filters.created_at_start && filters.created_at_end) ||
        (filters.policy_expiry_date && filters.policy_expiry_date_end) ||
        filters.payment_due_date ||
        filters.booking_date)
    ) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);
function daysAgoFromAuthorizedDate(authorizedDate) {
  let date = authorizedDate.split(' ')[0];

  if (!date) {
    return;
  }

  const [day, month, year] = date.split('-').map(Number);
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
  filtersCount.value = Object.keys(filtersCleaned).length;
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
const formatDate = dateString =>
  useDateFormat(useConvertDate(dateString), 'DD-MMM-YYYY').value;

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

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) filterQuotes(true);
  },
  { deep: true },
);

const quoteStatuses = computed(() => page.props.leadStatuses || []);

const insurerAMLStatusOption = computed(() =>
  (page.props.insurerAMLStatus || []).map(item => ({
    value: item.id || item.value,
    label: item.text || item.label,
  })),
);
</script>

<template>
  <div>
    <Head title="Life List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3 flex">
        <ColumnSelection
          v-model:columns="tableHeader"
          storage-key="life-list"
        />
        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          :filterStatuses="filterButtonStatuses"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />
        <Link :href="route('life-quotes-card')">
          <x-button
            size="sm"
            color="#1d83bc"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Cards View
          </x-button>
        </Link>
        <Link :href="route('life-quotes-create')">
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
    <x-form v-show="showFilters" @submit="filterQuotes" :auto-focus="false">
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
          :options="
            quoteStatuses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          filterable
          label="Lead Status"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.quote_status_id = quoteStatuses.map(item => item.id)
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
          v-if="!hasRole(rolesEnum.TravelAdvisor)"
          v-model="filters.advisor_id"
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
                filters.advisor_id = advisorOptions.map(item => item.value)
              "
              @clear="filters.advisor_id = []"
            />
          </template>
        </x-select>

        <x-input
          v-model="filters.previous_quote_policy_number_text"
          type="text"
          name="previous_quote_policy_number"
          class="w-full"
          placeholder="Policy Number"
          label="Policy Number"
        />
        <x-select
          v-model="filters.is_renewal"
          placeholder="Renewal"
          label="Renewal"
          :options="[
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
            { value: '', label: 'All' },
          ]"
        />

        <x-select
          v-model="filters.renewal_batch_id"
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
                filters.renewal_batch_id = renewalBatchOptions.map(
                  item => item.value,
                )
              "
              @clear="filters.renewal_batch_id = []"
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
          v-model="filters.last_modified_date"
          name="created_at_start"
          label="Last Modified Date"
          range
          format="dd-MM-yyyy"
        />
        <x-select
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="quoteSegmentsLife"
          filterable
          filterPlaceholder="Filter Segment...."
          :single="true"
        />
        <x-input
          v-if="can(permissionsEnum.SEARCH_INSURER_TAX_INVOICE_NUMBER)"
          v-model="filters.insurer_tax_number"
          type="text"
          name="insurer_tax_number"
          label="Insurer Tax Invoice No"
          class="w-full"
          placeholder="Insurer Tax Invoice No"
        />
        <x-input
          v-if="
            can(permissionsEnum.SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER)
          "
          v-model="filters.insurer_commmission_invoice_number"
          type="text"
          name="insurer_commmission_invoice_number"
          label="Insurer Commission Tax Invoice No"
          class="w-full"
          placeholder="Insurer Commission Tax Invoice No"
        />
        <x-select
          v-model="filters.tenure_of_insurance_id"
          placeholder="Plan Type"
          label="Plan Type"
          :options="
            planSubTypes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
        />
        <x-select
          v-model="filters.number_of_years_id"
          placeholder="Tenure of Cover"
          label="Tenure of Cover"
          :options="
            numberOfYears.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
        />

        <div v-if="!hasRole(rolesEnum.LifeAdvisor)">
          <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-1">
            <x-select
              v-model="filters.sum_insured_currency_id"
              placeholder="Currency"
              label="Sum Assured"
              :options="
                currency.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
            />
            <x-select
              v-model="filters.sum_insured_range"
              placeholder="Value Range"
              :options="[
                { value: 'lt500k', label: 'Less than 500k' },
                { value: '500k-1m', label: '500k to less than 1M' },
                { value: 'gte1m', label: 'Greater than or equal to 1M' },
              ]"
              class="border-l-0 rounded-tl-none rounded-bl-none"
              label="&nbsp;"
            />
          </div>
        </div>
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
            class="justify-self-start mr-3"
            @click.prevent="onDataExport"
            :loading="exportLoader"
          >
            Export
          </x-button>
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            class="justify-self-start mr-3"
            @click.prevent="onDataExport('email')"
            :loading="exportLoader"
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
        </div>
        <div v-else />
        <div class="flex gap-3 justify-self-end">
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
          v-if="isManualAllocationAllowed"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :rules="[rules.isRequired]"
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
      </div>
    </Transition>

    <DataTable
      v-model:items-selected="quotesSelected"
      v-model:server-options="serverOptions"
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="filteredTableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid }">
        <Link
          :href="route('life-quotes-show', uuid)"
          class="text-primary-500 hover:underline"
        >
          <span>{{ code }}</span>
        </Link>
      </template>
      <template #item-authorized_at="item">
        <p v-if="item?.payment_status?.text === 'AUTHORISED'">
          {{ item?.payments[0]?.authorized_at }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p
          v-if="
            item?.payment_status?.text === 'AUTHORISED' &&
            item?.payments[0]?.authorized_at
          "
        >
          {{ daysAgoFromAuthorizedDate(item?.payments[0]?.authorized_at) }}
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
      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
      </template>
      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.code }}
      </template>
      <template #item-nationality="{ nationality }">
        {{ nationality?.code }}
      </template>
      <template #item-lost_reason="{ quote_detail }">
        {{ quote_detail?.lost_reason?.text }}
      </template>

      <!-- <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template> -->
      <template #item-renewal_batch_model="item">
        <p>
          {{ item?.renewal_batch_model?.name ?? '' }}
        </p>
      </template>
      <template #item-number_of_years="item">
        <p>
          {{ item?.life_quote?.number_of_years?.text ?? '' }}
        </p>
      </template>
      <template #item-sum_insured_value="item">
        <p>
          {{ item?.life_quote?.sum_insured_value ?? '' }}
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
  </div>
</template>
