<script setup>
import LeadAssignment from '../Partials/LeadAssignment.vue';

defineProps({
  quotes: Object,
  advisors: Array,
  dropdownSource: Object,
  todayAssignmentCount: String,
  userMaxCap: Number,
  todayAutoCount: Number,
  todayManualCount: Number,
  yesterdayAutoCount: Number,
  yesterdayManualCount: Number,
  genericRequestEnum: Array,
  isBetaUser: Boolean,
  teams: Object,
  authorizedDays: Number,
  assignmentTypes: Object,
});

const page = usePage();
const { isRequired } = useRules();
const notification = useNotifications('toast');
const params = useUrlSearchParams('history');
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;
const cleanObj = obj => useCleanObj(obj);
const exportLoader = ref(false);

const createLead = reactive({
  modal: false,
  type: '',
});

const serverOptions = ref({
  page: 1,
  sortType: 'desc',
});

const tableHeader = [
  { text: 'REF-ID', value: 'code' },
  { text: 'BATCH', value: 'quote_batch_id_text' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at' },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date' },
  { text: 'DATE OF BIRTH', value: 'dob' },
  { text: 'LEAD SOURCE', value: 'source' },
  { text: 'ADVISOR REQUESTED', value: 'sic_advisor_requested' },
  { text: 'NATIONALITY', value: 'nationality_id_text' },
  { text: 'UAE LICENCE HELD FOR', value: 'uae_license_held_for_id_text' },
  { text: 'CAR MAKE', value: 'car_make_id_text' },
  { text: 'CAR MODEL', value: 'car_model_id_text' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'FIRST REGISTRATION DATE', value: 'year_of_first_registration' },
  { text: 'CAR VALUE', value: 'car_value' },
  { text: 'CAR VALUE (AT ENQUIRY)', value: 'car_value_tier' },
  { text: 'VEHICLE TYPE', value: 'vehicle_type_id_text' },
  { text: 'TYPE OF CAR INSURANCE', value: 'current_insurance_status' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with_text' },
  { text: 'CLAIM HISTORY', value: 'claim_history_id_text' },
  { text: 'CREATED DATE', value: 'created_at' },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date',
    sortable: true,
  },
  { text: 'ADVISOR ASSIGNED DATE', value: 'advisor_assigned_date' },
  { text: 'LEAD COST', value: 'cost_per_lead' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'PAYMENT STATUS', value: 'payment_status_id_text' },
  { text: 'ECOMMERCE', value: 'is_ecommerce' },
  { text: 'TIER NAME', value: 'tier_id_text' },
  { text: 'VISIT COUNT', value: 'visit_count' },
  { text: 'FOLLOW UP DATE', value: 'next_followup_date' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'UPDATED BY', value: 'updated_by' },
  { text: 'ADDITIONAL NOTES', value: 'additional_notes' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  { text: 'ASSIGNMENT TYPE', value: 'assignment_type' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'IS GCC STANDARD', value: 'is_gcc_standard' },
  { text: 'IS VEHICLE MODIFIED', value: 'is_modified' },
  { text: 'PRICE', value: 'premium' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'QUOTE LINK', value: 'quote_link' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch' },
];

const ecommerceOptions = [
  { value: '', label: 'Please select is ecommerce' },
  { value: 'Yes', label: 'Yes' },
  { value: 'No', label: 'No' },
];

const filteredTableHeader = computed(() => {
  if (!hasRole(rolesEnum.CarAdvisor)) {
    // If the user does not have the "CarAdvisor" role, include all columns
    return tableHeader;
  } else {
    // If the user has the "CarAdvisor" role, exclude "Lead Source" and "Assignment Type" columns
    return tableHeader.filter(
      column => column.value !== 'source' && column.value !== 'assignment_type',
    );
  }
});

const advisorOptions = computed(() => {
  let options = page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));

  options.push({
    value: '-1',
    label: 'UnAssigned',
  });

  return options;
});

const leadStatuses = computed(() => {
  return page.props.dropdownSource.quote_status_id.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadTiers = computed(() => {
  return page.props.dropdownSource.tier_id.map(tier => ({
    value: tier.id,
    label: tier.name,
  }));
});

const vehicleTypes = computed(() => {
  return page.props.dropdownSource.vehicle_type_id.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

const carTypeInsurances = computed(() => {
  return page.props.dropdownSource.car_type_insurance_id.map(
    carTypeInsurance => ({
      value: carTypeInsurance.id,
      label: carTypeInsurance.text,
    }),
  );
});

const providers = computed(() => {
  return page.props.dropdownSource.car_plan_provider_id.map(provider => ({
    value: provider.text,
    label: provider.text,
  }));
});

const batchOptions = computed(() => {
  return page.props.dropdownSource.quote_batch_id.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

const teamOptions = computed(() => {
  return page.props.teams.map(team => ({
    value: team.id,
    label: team.name,
  }));
});

function formatString(input) {
  const lowercaseString = input.toLowerCase();
  const words = lowercaseString.replace(/_/g, ' ').split(' ');
  for (let i = 0; i < words.length; i++) {
    words[i] = words[i][0].toUpperCase() + words[i].slice(1);
  }
  const formattedString = words.join(' ');
  return formattedString;
}
const paymentStatusOptions = computed(() => {
  if (hasRole(rolesEnum.BetaUser)) {
    //FOR NEW PAYMENTS SECTION
    return page.props.dropdownSource.payment_status_id
      .filter(
        status =>
          status.text !== 'STARTED' &&
          status.text !== 'FAILED' &&
          status.text !== 'DRAFT' &&
          status.text !== 'CAPTURED' &&
          status.text !== 'PARTIAL CAPTURED',
      )
      .sort((a, b) => a.text.localeCompare(b.text))
      .map(status => ({
        value: status.id,
        label: formatString(status.text),
      }));
  } else {
    return page.props.dropdownSource.payment_status_id.map(status => ({
      value: status.id,
      label: status.text,
    }));
  }
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  quote_status_id: [],
  created_at_start: '',
  currently_insured_with: '',
  is_ecommerce: '',
  payment_status_id: '',
  renewal_batch: '',
  previous_quote_policy_number: '',
  car_type_insurance_id: '',
  vehicle_type_id: '',
  advisor_assigned_date: '',
  tier_id: [],
  quote_batch_id: [],
  advisor_id: [],
  advisor_assigned_date_end: '',
  created_at_end: '',
  page: 1,
  paid_at_start: '',
  paid_at_end: '',
  sic_advisor_requested: 'All',
  segment_filter: 'all',
  teams: [],
  transaction_approved_dates: page.props.transaction_approved_dates || '',
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
});

const teamUsers =
  hasRole(rolesEnum.LeadPool) || hasRole(rolesEnum.Admin)
    ? ref([
        {
          id: -1,
          name: 'UnAssigned',
        },
      ])
    : ref([]);

const loader = reactive({
  table: false,
  export: false,
  advisorTeamOptions: false,
});

const quotesSelected = ref([]);
const canExport = ref(false);
const canExportLeadsAndPlan = ref(false);

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
    if (filters.paid_at_start && filters.paid_at_end) {
      canExportLeadsAndPlan.value = true;
    } else {
      canExportLeadsAndPlan.value = false;
    }
  },
  { deep: true, immediate: true },
);

const rules = {
  isRequired: v => !!v || 'Please select this option',
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
    filters.page = 1;
    serverOptions.value.page = 1;
    let data = { ...filters };
    Object.keys(data).forEach(
      key => (data[key] === '' || data[key]?.length === 0) && delete data[key],
    );
    router.visit(route('car.index'), {
      method: 'get',
      data: { ...data, ...serverOptions.value },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onFinish: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

const onLeadAssigned = () => {
  quotesSelected.value = [];
};

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

function onReset() {
  router.visit(route('car.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const objToUrl = obj => {
  Object.keys(obj).forEach(
    key => (obj[key] === '' || obj[key]?.length === 0) && delete obj[key],
  );
  return Object.keys(obj)
    .map(key => {
      if (Array.isArray(obj[key])) {
        return obj[key].map(value => `${key}[]=${value}`).join('&');
      }
      return `${key}=${obj[key]}`;
    })
    .join('&');
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = isNaN(parseInt(params[key]))
        ? params[key]
        : parseInt(params[key]);
    }
  }
}

const fetchTeamUsers = () => {
  loader.advisorTeamOptions = true;
  axios
    .post('/get-users-by-team', { team_filter: filters.teams })
    .then(response => {
      if (
        response.data.length > 0 &&
        (hasRole(rolesEnum.LeadPool) || hasRole(rolesEnum.Admin))
      ) {
        response.data.push({
          id: -1,
          name: 'UnAssigned',
        });
      }
      teamUsers.value = response.data;
    })
    .finally(() => {
      loader.advisorTeamOptions = false;
    });
};

const onConfirmCreateLead = () => {
  if (createLead.type === 'referral') {
    router.get(route('car.create'));
  }
  createLead.modal = false;
};

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

const exportPUAUrl = () => {
  let url = '/pua-leads-export';
  return url;
};

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) onSubmit(true);
  },
  { deep: true },
);

const onExport = (url, isLoading = false) => {
  exportLoader.value = isLoading;
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Car'),
    url: `${window.location.origin}${url}`,
  };
  logAndExportQuotes(payload).then(result => {
    if (result)
      setTimeout(() => {
        exportLoader.value = false;
      }, 1000);
  });
};
</script>

<template>
  <div>
    <Head title="View Car" />
    <div class="flex justify-between items-center flex-wrap gap-4">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <LeadAssignedWidget
        v-if="hasRole(rolesEnum.CarAdvisor)"
        :todayAutoCount="todayAutoCount"
        :todayManualCount="todayManualCount"
        :yesterdayAutoCount="yesterdayAutoCount"
        :yesterdayManualCount="yesterdayManualCount"
        :userMaxCap="userMaxCap"
      />
      <!-- <Link :href="route('car.create')"> -->
      <x-button
        size="sm"
        color="#ff5e00"
        tag="div"
        @click="createLead.modal = true"
        v-if="readOnlyMode.isDisable === true"
      >
        Create Lead
      </x-button>
      <!-- </Link> -->
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="REF-ID"
          class="w-full"
          placeholder="Search by REF-ID"
        />
        <ComboBox
          v-model="filters.quote_batch_id"
          label="Batch"
          name="quote_batch_id"
          placeholder="Please select batch"
          :options="batchOptions"
        />
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
        <DatePicker
          v-model="filters.created_at_start"
          label="Created Date Start"
          :rules="
            filters.previous_quote_policy_number ||
            filters.code ||
            filters.email ||
            filters.renewal_batch ||
            filters.quote_batch_id ||
            filters.payment_due_date ||
            filters.booking_date ||
            filters.insurer_tax_invoice_number ||
            filters.insurer_commission_tax_invoice_number ||
            filters.mobile_no
              ? []
              : [isRequired]
          "
        />
        <DatePicker
          v-model="filters.created_at_end"
          label="Created Date End"
          :rules="
            filters.previous_quote_policy_number ||
            filters.code ||
            filters.email ||
            filters.renewal_batch ||
            filters.quote_batch_id ||
            filters.payment_due_date ||
            filters.booking_date ||
            filters.insurer_tax_invoice_number ||
            filters.insurer_commission_tax_invoice_number ||
            filters.mobile_no
              ? []
              : [isRequired]
          "
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
          v-model="filters.advisor_assigned_date"
          name="advisor_assigned_date"
          label="Advisor Assigned Date Start"
        />
        <DatePicker
          v-model="filters.advisor_assigned_date_end"
          name="advisor_assigned_date_end"
          label="Advisor Assigned Date End"
        />
        <ComboBox
          v-model="filters.payment_status_id"
          label="Payment Status"
          name="payment_status_id"
          :options="paymentStatusOptions"
          placeholder="Please select payment status"
          class="w-full"
          :single="true"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Ecommerce"
          name="is_ecommerce"
          :options="ecommerceOptions"
          placeholder="Please select is ecommerce"
          class="w-full"
        />
        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status_id"
          :options="leadStatuses"
        />
        <ComboBox
          v-model="filters.tier_id"
          label="Tier Name"
          name="tier_id"
          placeholder="Please select batch"
          :options="leadTiers"
        />
        <ComboBox
          :single="true"
          v-model="filters.vehicle_type_id"
          label="Vehicle Type"
          name="vehicle_type_id"
          :options="vehicleTypes"
          placeholder="Please select an option"
          class="w-full"
        />
        <ComboBox
          :single="true"
          v-model="filters.car_type_insurance_id"
          label="Type of Car Insurance"
          name="car_type_insurance_id"
          :options="carTypeInsurances"
          placeholder="Please select an option"
          class="w-full"
        />
        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />
        <ComboBox
          :single="true"
          v-model="filters.currently_insured_with"
          label="Currently Insured with"
          name="currently_insured_with"
          :options="providers"
          placeholder="Please select an option"
          class="w-full"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
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
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.advisor_id"
          label="Advisors (select teams first)"
          name="advisor_id"
          placeholder="Please select Advisor"
          :options="
            teamUsers.map(user => ({
              value: user.id,
              label: user.name,
            }))
          "
          :loading="loader.advisorTeamOptions"
        />
        <ComboBox
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.assignment_type"
          label="Assignment Type"
          placeholder="Please select assignment type"
          :options="assignmentTypes"
          :single="true"
          class="w-full"
        />
        <ComboBox
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="teamOptions"
          @update:modelValue="fetchTeamUsers"
        />
        <DatePicker
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.transaction_approved_dates"
          label="Transaction Approved Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
          max-range="30"
        />

        <DatePicker
          v-if="can(permissionsEnum.EXPORT_PLAN_DETAIL)"
          v-model="filters.paid_at_start"
          label="Paid Date Start"
        />

        <DatePicker
          v-if="can(permissionsEnum.EXPORT_PLAN_DETAIL)"
          v-model="filters.paid_at_end"
          label="Paid Date End"
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
        <div>
          <x-button
            v-if="canExport && can(permissionsEnum.DATA_EXTRACTION)"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click="onExport(`/car/leads-export?${objToUrl(filters)}`, true)"
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
                Created dates or payment due date or booking date are required
                to export data.
              </span>
            </template>
          </x-tooltip>
          <x-button
            v-if="
              canExportLeadsAndPlan && can(permissionsEnum.EXPORT_PLAN_DETAIL)
            "
            size="sm"
            color="emerald"
            @click="
              onExport(
                `/car/leads-export-plan/${
                  genericRequestEnum.EXPORT_PLAN_DETAIL
                }?${objToUrl(filters)}`,
              )
            "
            class="justify-self-start mr-3"
          >
            Extract leads and plan detail
          </x-button>
          <x-tooltip
            v-if="
              !canExportLeadsAndPlan && can(permissionsEnum.EXPORT_PLAN_DETAIL)
            "
            placement="right"
          >
            <x-button class="mr-3" tag="div" size="sm" color="emerald">
              Extract leads and plan detail</x-button
            >
            <template #tooltip>
              <span class="font-medium">
                Paid dates are required to export data.
              </span>
            </template>
          </x-tooltip>

          <x-button
            v-if="
              canExport &&
              can(permissionsEnum.EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE)
            "
            size="sm"
            color="emerald"
            @click="
              onExport(
                `/car/leads-details-with-email/${
                  genericRequestEnum.EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE
                }?${objToUrl(filters)}`,
                true,
              )
            "
            :loading="exportLoader"
            class="justify-self-start mr-3"
          >
            Extract leads detail with email/mobile_no
          </x-button>
          <x-tooltip
            v-if="
              !canExport &&
              can(permissionsEnum.EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE)
            "
            placement="right"
          >
            <x-button class="mr-3" tag="div" size="sm" color="emerald"
              >Extract leads detail with email/mobile_no</x-button
            >
            <template #tooltip>
              <span class="font-medium">
                Created dates are required to export data.
              </span>
            </template>
          </x-tooltip>
          <x-button
            v-if="can(permissionsEnum.EXPORT_MAKES_MODELS)"
            size="sm"
            color="emerald"
            @click="
              onExport(
                `/car/export-makes-model/${
                  genericRequestEnum.EXPORT_MAKES_MODELS
                }?${objToUrl(filters)}`,
              )
            "
            class="justify-self-start mr-3"
          >
            Extract makes models trims
          </x-button>
          <x-button
            v-if="can(permissionsEnum.EXPORT_CAR_PUA_UPDATES)"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click="onExport('/pua-leads-export', true)"
            class="justify-self-start mr-3"
          >
            Export PUA Updates
          </x-button>
        </div>
        <div class="flex justify-self-end gap-3">
          <x-button type="submit" size="sm" color="#ff5e00">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <Transition name="fade" v-if="!hasRole(rolesEnum.CarAdvisor)">
      <div
        v-if="quotesSelected.length > 0 && !can(permissionsEnum.VIEW_ALL_LEADS)"
        class="mb-4"
      >
        <LeadAssignment
          :selected="quotesSelected.map(e => e.id)"
          :advisors="advisorOptions"
          quoteType="Car"
          @success="onLeadAssigned"
        />
      </div>
    </Transition>
    <x-divider class="my-4" />
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
          :href="route('car.show', uuid)"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
      </template>

      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-sic_advisor_requested="{ sic_advisor_requested }">
        <div class="text-center">
          <x-tag size="sm" :color="sic_advisor_requested ? 'success' : 'error'">
            {{ sic_advisor_requested ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-is_gcc_standard="{ is_gcc_standard }">
        <div class="text-center">
          <x-tag size="sm" :color="is_gcc_standard ? 'success' : 'error'">
            {{ is_gcc_standard ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-is_modified="{ is_modified }">
        <div class="text-center">
          <x-tag size="sm" :color="is_modified ? 'success' : 'error'">
            {{ is_modified ? 'Yes' : 'No' }}
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

    <x-modal
      v-model="createLead.modal"
      size="md"
      title="Create Lead"
      show-close
      backdrop
    >
      <div class="w-full grid md:grid-cols-2 gap-5">
        <p class="text-md font-bold text-gray-500">
          Select reason to create manual lead <span class="error">*</span>
        </p>
      </div>
      <div class="flex w-full flex-col gap-5 mt-4 mb-4">
        <x-form-group v-model="createLead.type">
          <x-radio value="referral" label="Referral" />
          <x-radio value="early_renewal" label="Early Renewal" />
          <x-radio value="payment_status" label="Payment Status" />
        </x-form-group>
      </div>
      <template #actions>
        <x-button
          ghost
          tabindex="-1"
          size="md"
          type="button"
          @click.prevent="createLead.modal = false"
        >
          Cancel
        </x-button>
        <x-button
          size="md"
          color="emerald"
          type="button"
          @click.prevent="onConfirmCreateLead"
        >
          Confirm
        </x-button>
      </template>
    </x-modal>
  </div>
</template>
