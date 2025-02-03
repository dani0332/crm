<script setup>
import { computed } from 'vue';

defineProps({
  quotes: Object,
  leadStatuses: Array,
  advisors: Array,
  renewalBatches: Array,
  teams: Object,
  userMaxCap: Number,
  todayAutoCount: Number,
  todayManualCount: Number,
  yesterdayAutoCount: Number,
  yesterdayManualCount: Number,
  quoteSegments: Object,
  totalCount: {
    type: Number,
    default: 0,
  },
  authorizedDays: Number,
  assignmentTypes: Object,
});

const page = usePage();
const notification = useToast();

const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const quoteSegments = page.props.quoteSegments;

const loader = reactive({
  table: false,
  export: false,
});

const { isRequired } = useRules();

const objToUrl = obj => useObjToUrl(obj);
const quotesSelected = ref([]);

let params = useUrlSearchParams('history');
const cleanObj = obj => useCleanObj(obj);
const showFilters = ref(true);
const filtersCount = ref(0);
const serverOptions = ref({
  page: 1,
  sortType: 'desc',
});

const assignForm = useForm({
  assign_team: null,
  assigned_to_id_new: null,
  assignment_type: '1',
  modelType: 'Health',
  selectTmLeadId: '',
  isManagerOrDeputy: 1,
  isLeadPool: null,
  isManualAllocationAllowed: 1,
});

const tableHeader = ref([
  { text: 'Ref-ID', value: 'code', is_active: true },
  { text: 'FIRST NAME', value: 'first_name', is_active: true },
  { text: 'LAST NAME', value: 'last_name', is_active: true },
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at', is_active: true },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date', is_active: true },
  { text: 'LEAD STATUS', value: 'quote_status_id_text', is_active: true },
  { text: 'ADVISOR', value: 'advisor_id_text', is_active: true },
  { text: 'ASSIGNMENT TYPE', value: 'assignment_type', is_active: true },
  {
    text: 'ADVISOR REQUESTED',
    value: 'sic_advisor_requested',
    is_active: true,
  },
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
  { text: 'HEALTH TEAM TYPE', value: 'health_team_type', is_active: true },
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  {
    text: 'STARTING FROM',
    value: 'price_starting_from',
    is_active: true,
    sortable: true,
  },
  { text: 'PRICE', value: 'premium', is_active: true, sortable: true },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LEAD TYPE', value: 'lead_type_id_text', is_active: true },
  { text: 'SALARY BAND', value: 'salary_band_id_text', is_active: true },
  {
    text: 'MEMBER CATEGORY',
    value: 'member_category_id_text',
    is_active: true,
  },
  {
    text: 'CURRENTLY INSURED WITH',
    value: 'currently_insured_with_id_text',
    is_active: true,
  },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce', is_active: true },
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

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: new Date() || '',
  created_at_end: new Date() || '',
  sub_team: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  is_renewal: '',
  previous_quote_policy_number: '',
  renewal_batches: [],
  date: null,
  assigned_to_date_start: '',
  assigned_to_date_end: '',
  payment_status: [],
  is_cold: false,
  is_stale: false,
  status_filters: null,
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  segment_filter: '',
  sic_advisor_requested: 'All',
  transaction_approved_dates: '',
  last_modified_date: null,
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
});

const canExport = ref(false);
watch(
  () => filters,
  () => {
    if (
      (filters.created_at_start && filters.created_at_end) ||
      filters.payment_due_date ||
      filters.booking_date ||
      filters.transaction_approved_dates
    ) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

const canExportRMLeads = computed(() => {
  return filters.transaction_approved_dates ?? false;
});
const subTeamOptions = [
  { value: '', label: 'All' },
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

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

const renewalBatchOptions = computed(() => {
  return page.props.renewalBatches.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

const modifiedAdvisorOptions = ref([]);

modifiedAdvisorOptions.value = advisorOptions.value;

modifiedAdvisorOptions.value.push({
  value: 'unassigned',
  label: 'Unassigned',
});

// const subTeamsOptions = computed(() => {

//     let subteamArray = page.props.teams?.map(team => ({
//         value: team.name,
//         label: team.name,
//     }));

//     subteamArray.push({ value: 'No-Type', label: 'No-Type' });

//     return subteamArray;

// });

const subTeamsOptions = [
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

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

    router.visit(route('health.index'), {
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
    console.log('Invalid');
  }
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

function onReset() {
  removedSavedParams();
  router.visit(route('health.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    const url =
      assignForm.assign_team === 'Wow-Call'
        ? '/quotes/wcuAssign'
        : '/quotes/health/manualLeadAssign';
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post(url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          quotesSelected.value = [];
          notification.success({
            title: 'Health Leads Assigned',
            position: 'top',
          });
        },
      });
  }
}

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key] ?? value;
    } else {
      filters[key] = isNaN(parseInt(params[key]))
        ? params[key]
        : parseInt(params[key]);
    }
  }
}

const fixedValue = numberString => {
  const number = parseFloat(numberString);
  if (isNaN(number)) {
    return 'Invalid number';
  } else if (number === Math.floor(number)) {
    return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  } else {
    return parseFloat(number.toFixed(2)).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
};

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const exportLoader = ref(false);
const onDataExport = () => {
  if (filters.created_at_start && filters.created_at_end) {
    filters.created_at_start = useDateFormat(
      filters.created_at_start,
      'YYYY-MM-DD',
    ).value;

    filters.created_at_end = useDateFormat(
      filters.created_at_end,
      'YYYY-MM-DD',
    ).value;
  } else if (
    filters.transaction_approved_dates[0] &&
    filters.transaction_approved_dates[1]
  ) {
    filters.transaction_approved_dates[0] = useDateFormat(
      filters.transaction_approved_dates[0],
      'YYYY-MM-DD',
    ).value;
    filters.transaction_approved_dates[1] = useDateFormat(
      filters.transaction_approved_dates[1],
      'YYYY-MM-DD',
    ).value;
  }

  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'health');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Health'),
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

const exportRmLeads = () => {
  let filtersCleaned = { ...cleanObj(filters) };
  let maxdays = calculateDaysDifference(
    filtersCleaned.transaction_approved_dates[0],
    filtersCleaned.transaction_approved_dates[1],
  );

  if (maxdays > 31) {
    notification.error({
      message:
        'Maximum of 31 days (Transaction Approved date) are allowed to be exported.',
      position: 'top',
    });
    return;
  }

  exportLoader.value = true;

  const params = new URLSearchParams(cleanObj(filtersCleaned));
  // filters.transaction_approved_dates.forEach(date => {
  //   params.append('transaction_approved_dates[]', date);
  // });

  // console.log(params.toString());
  // return;
  // const data = useObjToUrl(filters);

  logAndExportQuotes({
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Health'),
    // url: `${window.location.origin}/rm-leads-export`,
    url: `${window.location.origin}/rm-leads-export` + '?' + params.toString(),
  }).then(result => {
    if (result)
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
  });
};

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
});

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) onSubmit(true);
  },
);
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

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
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
  if (filters.transaction_approved_dates) {
    filters.created_at_start = '';
    filters.created_at_end = '';
  }
});
</script>

<template>
  <div>
    <Head title="Health List" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Health List</h2>
        <!-- // PD Revert
          <LeadsCount
          :leadsCount="$page.props.totalCount"
          :key="$page.props.totalCount"
        /> -->
      </template>
      <template #default>
        <ColumnSelection
          v-model:columns="tableHeader"
          storage-key="health-list"
        />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />
        <Link :href="route('health.cards')">
          <x-button
            size="sm"
            color="#1d83bc"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Cards View
          </x-button>
        </Link>

        <Link :href="route('health.create')">
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

    <LeadAssignedWidget
      v-if="hasAnyRole([rolesEnum.RMAdvisor, rolesEnum.EBPAdvisor])"
      :todayAutoCount="todayAutoCount"
      :todayManualCount="todayManualCount"
      :yesterdayAutoCount="yesterdayAutoCount"
      :yesterdayManualCount="yesterdayManualCount"
      :userMaxCap="userMaxCap"
    />

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
          label="First Name"
          class="w-full"
          placeholder="Search by First Name"
        />
        <x-input
          v-model="filters.last_name"
          type="search"
          name="last_name"
          label="Last Name"
          class="w-full"
          placeholder="Search by Last Name"
        />
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-input
          v-model="filters.mobile_no"
          type="search"
          name="mobile_no"
          label="Mobile Number"
          class="w-full"
          placeholder="Search by Mobile Number"
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
        <ComboBox
          v-model="filters.sub_team"
          label="Sub Team"
          placeholder="Search by Sub Team"
          :options="subTeamOptions"
          :single="true"
        />

        <ComboBox
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />
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
        <ComboBox
          v-if="
            !hasAnyRole([
              rolesEnum.RMAdvisor,
              rolesEnum.EBPAdvisor,
              rolesEnum.CarAdvisor,
              rolesEnum.HealthRenewalAdvisor,
              rolesEnum.HealthAdvisor,
            ]) || hasRole(rolesEnum.SuperManagerLeadAllocation)
          "
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="modifiedAdvisorOptions"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          v-model="filters.is_renewal"
          label="Renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />

        <ComboBox
          v-if="
            !hasAnyRole([
              rolesEnum.RMAdvisor,
              rolesEnum.EBPAdvisor,
              rolesEnum.CarAdvisor,
            ])
          "
          v-model="filters.assignment_type"
          label="Assignment Type"
          placeholder="Search by Assignment Type"
          :options="assignmentTypes"
          :single="true"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
        />
        <ComboBox
          v-model="filters.renewal_batches"
          label="Renewal Batch"
          placeholder="Search by Renewal Batch"
          :options="renewalBatchOptions"
        />
        <DatePicker
          v-if="!hasAnyRole([rolesEnum.CarAdvisor])"
          v-model="filters.assigned_to_date_start"
          name="assigned_to_date_start"
          label="Advisor Assigned Date Start"
        />
        <DatePicker
          v-if="!hasAnyRole([rolesEnum.CarAdvisor])"
          v-model="filters.assigned_to_date_end"
          name="assigned_to_date_end"
          label="Advisor Assigned Date End"
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
        <ComboBox
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="quoteSegments"
          :single="true"
        />
        <ComboBox
          v-model="filters.sic_advisor_requested"
          label="Advisor Requested"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :single="true"
        />
        <DatePicker
          v-model="filters.transaction_approved_dates"
          label="Transaction Approved Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
          max-range="120"
        />
        <DatePicker
          v-model="filters.last_modified_date"
          name="created_at_start"
          label="Last Modified Date"
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
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div>
          <x-button
            v-if="canExport && can(permissionsEnum.DATA_EXTRACTION)"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click.prevent="onDataExport"
            class="justify-self-start mr-3"
          >
            Export
          </x-button>
          <x-tooltip
            v-if="!canExport && can(permissionsEnum.DATA_EXTRACTION)"
            placement="right"
          >
            <x-button tag="div" size="sm" color="emerald" class="mr-3">
              Export
            </x-button>
            <template #tooltip>
              <span class="font-medium">
                Created dates or Transaction Approved dates or payment due date
                or booking date are required to export data.
              </span>
            </template>
          </x-tooltip>

          <x-button
            v-if="canExportRMLeads && can(permissionsEnum.EXPORT_RM_LEADS)"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click="exportRmLeads()"
            class="justify-self-start mr-3"
          >
            Export RM Leads by Car Advisors
          </x-button>

          <x-tooltip
            v-if="!canExportRMLeads && can(permissionsEnum.EXPORT_RM_LEADS)"
            placement="right"
          >
            <x-button tag="div" size="sm" color="emerald" class="mr-3">
              Export RM Leads by Car Advisors
            </x-button>
            <template #tooltip>
              <span class="font-medium">
                Transaction Approved dates are required to export data.
              </span>
            </template>
          </x-tooltip>
        </div>
        <div class="flex justify-self-end gap-3">
          <x-button type="submit" size="sm" color="#ff5e00">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>
    <Transition name="fade">
      <div
        v-if="quotesSelected.length > 0 && !can(permissionsEnum.VIEW_ALL_LEADS)"
        class="mb-4"
      >
        <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assign_team"
                label="Assign Subteam"
                :options="subTeamsOptions"
                placeholder="Select Subteam"
                class="flex-1 w-auto"
                :rules="[isRequired]"
                filterable
                v-if="readOnlyMode.isDisable === true"
              />
              <x-select
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :rules="[isRequired]"
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
      <template #item-code="item">
        <Link
          :href="route('health.show', item.uuid)"
          class="text-primary-500 hover:underline flex items-center space-x-1"
        >
          <span>{{ item.code }}</span>
          <StaleLeadsBadge :date="item.stale_at" :align="`left`" />
        </Link>
      </template>
      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-authorized_at="item">
        <p v-if="item.payment_status_text === 'AUTHORISED'">
          {{ item.authorized_at }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p v-if="item.payment_status_text === 'AUTHORISED'">
          {{ daysAgoFromAuthorizedDate(item.authorized_at) }}
        </p>
      </template>
      <template #item-sic_advisor_requested="{ sic_advisor_requested }">
        <div class="text-center">
          <x-tag size="sm" :color="sic_advisor_requested ? 'success' : 'error'">
            {{ sic_advisor_requested ? 'Yes' : 'No' }}
          </x-tag>
        </div>
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
      <template #item-price_starting_from="item">
        <p v-if="item.price_starting_from != null">
          {{ fixedValue(item.price_starting_from) }}
        </p>
      </template>

      <template #item-premium="item">
        <p v-if="item.premium != null">{{ fixedValue(item.premium) }}</p>
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
  </div>
</template>
