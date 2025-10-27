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
  genericRequestEnum: Object,
  isBetaUser: Boolean,
  teams: Object,
  authorizedDays: Number,
  assignmentTypes: Object,
  insurerAMLStatus: Object,
});

const page = usePage();
const { isRequired } = useRules();
const notification = useNotifications('toast');
const params = useUrlSearchParams('history');
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const carRegistrationTypeEnum = page.props.carRegistrationType;
const carVehicleUseEnum = page.props.carVehicleUse;
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
  { text: 'BATCH', value: 'batch.name' },
  { text: 'Vehicle Use', value: 'vehicle_use' },
  { text: 'Company Name', value: 'car_company_name' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'PAYMENT AUTHORISED DATE', value: 'payment.authorized_at_formatted' },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date' },
  { text: 'DATE OF BIRTH', value: 'dob_formatted' },
  { text: 'LEAD SOURCE', value: 'source' },
  { text: 'ADVISOR REQUESTED', value: 'sic_advisor_requested' },
  { text: 'NATIONALITY', value: 'nationality.text' },
  { text: 'UAE LICENCE HELD FOR', value: 'uae_license_held_for.text' },
  { text: 'CAR MAKE', value: 'car_make.text' },
  { text: 'CAR MODEL', value: 'car_model.text' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'FIRST REGISTRATION DATE', value: 'year_of_first_registration' },
  { text: 'CAR VALUE', value: 'car_value' },
  { text: 'CAR VALUE (AT ENQUIRY)', value: 'car_value_tier' },
  { text: 'VEHICLE TYPE', value: 'vehicle_type.text' },
  { text: 'TYPE OF CAR INSURANCE', value: 'car_type_insurance.text' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'CLAIM HISTORY', value: 'claim_history.text' },
  { text: 'CREATED DATE', value: 'created_at' },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date_formatted',
  },
  {
    text: 'ADVISOR ASSIGNED DATE',
    value: 'car_quote_request_detail.advisor_assigned_date_formatted',
  },
  { text: 'LEAD COST', value: 'tier.cost_per_lead' },
  { text: 'LEAD STATUS', value: 'quote_status.text' },
  { text: 'INSURER AML STATUS', value: 'insurer_aml_status_text' },
  { text: 'PAYMENT STATUS', value: 'payment_status.text' },
  { text: 'ECOMMERCE', value: 'is_ecommerce' },
  { text: 'TIER NAME', value: 'tier.name' },
  { text: 'VISIT COUNT', value: 'quote_view_count.visit_count' },
  { text: 'INSURER', value: 'insurance_provider.text' },
  {
    text: 'FOLLOW UP DATE',
    value: 'car_quote_request_detail.next_followup_date_formatted',
  },
  { text: 'API ISSUANCE STATUS', value: 'api_issuance_status_id' },
  { text: 'INSURER API STATUS', value: 'insurer_api_status_id' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'UPDATED BY', value: 'updated_by' },
  { text: 'ADDITIONAL NOTES', value: 'additional_notes' },
  { text: 'ADVISOR', value: 'advisor.name' },
  { text: 'ASSIGNMENT TYPE', value: 'assignment_type_text' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'IS GCC STANDARD', value: 'is_gcc_standard' },
  { text: 'IS VEHICLE MODIFIED', value: 'is_modified' },
  { text: 'PRICE', value: 'premium' },
  { text: 'LOST REASON', value: 'car_quote_request_detail.lost_reason.text' },
  { text: 'QUOTE LINK', value: 'quote_link' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch' },
  { text: 'Private Client', value: 'customer.pcp_tag_formatted' },
];

const ecommerceOptions = [
  { value: '', label: 'Please select is ecommerce' },
  { value: 1, label: 'Yes' },
  { value: 0, label: 'No' },
];

const filteredTableHeader = ref([]);

const filterTableHeaders = () => {
  let filtered = [...tableHeader];

  if (hasRole(rolesEnum.CarAdvisor)) {
    filtered = filtered.filter(
      column => column.value !== 'source' && column.value !== 'assignment_type',
    );
  }

  if (filters.registration_type === carRegistrationTypeEnum.COMPANY) {
    // If the registration type is "Company", exclude "First Name" and "Last Name" columns
    filtered = filtered.filter(
      column =>
        column.value !== 'first_name' &&
        column.value !== 'last_name' &&
        column.value !== 'dob' &&
        column.value !== 'nationality_id_text' &&
        column.value !== 'uae_license_held_for_id_text',
    );
  } else {
    filtered = filtered.filter(
      column =>
        column.value !== 'vehicle_use' && column.value !== 'car_company_name',
    );
  }

  filteredTableHeader.value = filtered;
};

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

const registrationTypeOptions = Object.values(carRegistrationTypeEnum).map(
  item => ({
    value: item,
    label: item.charAt(0).toUpperCase() + item.slice(1),
  }),
);

const vehicleUseOptions = Object.values(carVehicleUseEnum).map(item => ({
  value: item,
  label: item.charAt(0).toUpperCase() + item.slice(1),
}));

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

const issuanceStatuses = computed(() => {
  return Object.entries(page.props.issuanceStatuses).map(([index, value]) => {
    return {
      value: index,
      label: value,
    };
  });
});

const insurerApiStatus = computed(() => {
  return Object.entries(page.props.insurerApiStatus).map(([index, value]) => {
    return {
      value: index,
      label: value,
    };
  });
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  quote_status_id: [],
  insurer_aml_status: [],
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
  registration_type: carRegistrationTypeEnum.PERSONAL,
  vehicle_use: '',
  company_name: '',
  private_client: 'all',
  authorize_date: '',
  captured_date: '',
  api_issuance_status_id: [],
  insurer_api_status_id: [],
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

// PUA Export Modal state
const puaExportModal = reactive({
  show: false,
  exportType: 'all',
  payment_date: '',
});

watch(
  () => filters,
  () => {
    if (
      (filters.created_at_start && filters.created_at_end) ||
      filters.payment_due_date ||
      filters.booking_date ||
      filters.renewal_batch
    ) {
      canExport.value = true;
      // Export buttons will be visible when date filters are set
    } else {
      canExport.value = false;
    }
    if (filters.paid_at_start && filters.paid_at_end) {
      canExportLeadsAndPlan.value = true;
    } else {
      canExportLeadsAndPlan.value = false;
    }

    if (filters.registration_type == carRegistrationTypeEnum.COMPANY) {
      filters.first_name = '';
      filters.last_name = '';
    } else {
      filters.vehicle_use = '';
      filters.company_name = '';
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
      onFinish: () => {
        loader.table = false;
        filterTableHeaders();
      },
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
  // Define which fields should have integer values
  const integerFields = [
    'quote_status_id',
    'tier_id',
    'vehicle_type_id',
    'car_type_insurance_id',
    'quote_batch_id',
    'advisor_id',
    'teams',
    'payment_status_id',
    'page',
    'api_issuance_status_id',
    'insurer_api_status_id',
  ];

  // Group array parameters
  const arrayParams = {};
  const singleParams = {};

  for (const [key, value] of Object.entries(params)) {
    // Check for indexed array format like quote_status_id[0], quote_status_id[1]
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
    } else if (key === 'is_ecommerce' && (value === '0' || value === '1')) {
      // Boolean-like fields
      filters[key] = parseInt(value);
    } else if (key === 'is_ecommerce' && value === '') {
      // Empty string for ecommerce
      filters[key] = '';
    } else {
      // Keep as string for dates, text fields, enums, etc.
      filters[key] = value;
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

  filterTableHeaders();

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

const onExport = (url, isLoading = false, exportType = 'download') => {
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
        title: `Maximum of ${maxPeriod} (created date) are allowed to be exported.`,
        position: 'top',
      });
      return;
    }
  }

  exportLoader.value = isLoading;

  // Add exportType to URL parameters if it's not already there
  const separator = url.includes('?') ? '&' : '?';
  const exportTypeParam = `exportType=${exportType}`;

  // Only add exportType if it's not already in the URL
  if (!url.includes('exportType=')) {
    url = `${url}${separator}${exportTypeParam}`;
  }

  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Car'),
    exportType: exportType,
    url: `${window.location.origin}${url}`,
    filters: { ...filters },
  };

  // console.log('onexport', payload);
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

const insurerAMLStatusOption = computed(() => {
  return Object.entries(page.props.insurerAMLStatus).map(([key, value]) => ({
    value: key,
    label: value,
  }));
});

const yesterday = computed(() => {
  const date = new Date();
  date.setDate(date.getDate() - 1);
  return date;
});

const canExportPUA = computed(() => {
  return puaExportModal.payment_date;
});

const onPUAExport = () => {
  // Open the PUA export modal
  puaExportModal.show = true;
};

const onConfirmPUAExport = () => {
  // Use the single date for both authorize and capture date filters
  const filtersForExport = {
    authorize_date: useFormatDateToYMD(puaExportModal.payment_date),
    captured_date: useFormatDateToYMD(puaExportModal.payment_date),
  };

  const data = objToUrl(filtersForExport);
  const url = `/Car/pua-leads-export?${data}`;

  puaExportModal.show = false; // Close modal

  // Reuse the existing onExport function
  onExport(url, true);
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
        <x-select
          v-model="filters.registration_type"
          label="Registration Type"
          name="registration_type"
          placeholder="Please select registration type"
          :options="registrationTypeOptions"
          class="w-full"
        />
        <ComboBox
          v-if="filters.registration_type == carRegistrationTypeEnum.COMPANY"
          v-model="filters.vehicle_use"
          label="Vehicle use"
          name="vehicle_ue"
          placeholder="Please select vehicle use"
          :options="vehicleUseOptions"
        />

        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="REF-ID"
          class="w-full"
          placeholder="Search by REF-ID"
        />
        <x-select
          v-model="filters.quote_batch_id"
          label="Batch"
          name="quote_batch_id"
          :options="batchOptions"
          placeholder="Please select batch"
          filterable
          multiple
          truncate
          multipleCheckbox
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.quote_batch_id = batchOptions.map(item => item.value)
              "
              @clear="filters.quote_batch_id = []"
            />
          </template>
        </x-select>
        <x-input
          v-if="filters.registration_type == carRegistrationTypeEnum.COMPANY"
          v-model="filters.company_name"
          type="search"
          name="company_name"
          label="Company Name"
          class="w-full"
          placeholder="Search by Company Name"
        />
        <x-input
          v-if="filters.registration_type == carRegistrationTypeEnum.PERSONAL"
          v-model="filters.first_name"
          type="search"
          name="first_name"
          label="First Name"
          class="w-full"
          placeholder="Search by First Name"
        />
        <x-input
          v-if="filters.registration_type == carRegistrationTypeEnum.PERSONAL"
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
        <x-select
          v-model="filters.payment_status_id"
          label="Payment Status"
          name="payment_status_id"
          :options="paymentStatusOptions"
          placeholder="Please select payment status"
          class="w-full"
          filterable
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Ecommerce"
          name="is_ecommerce"
          :options="ecommerceOptions"
          placeholder="Please select is ecommerce"
          class="w-full"
        />
        <x-select
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status_id"
          :options="leadStatuses"
          placeholder="Please select lead status"
          filterable
          multiple
          truncate
          multipleCheckbox
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.quote_status_id = leadStatuses.map(item => item.value)
              "
              @clear="filters.quote_status_id = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.insurer_aml_status"
          label="Insurer AML Status"
          name="insurer_aml_status"
          :options="insurerAMLStatusOption"
          placeholder="Please select status"
          filterable
          multiple
          truncate
          multipleCheckbox
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
        <x-select
          v-model="filters.tier_id"
          label="Tier Name"
          name="tier_id"
          :options="leadTiers"
          placeholder="Please select tier"
          filterable
          multiple
          truncate
          multipleCheckbox
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.tier_id = leadTiers.map(item => item.value)"
              @clear="filters.tier_id = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.vehicle_type_id"
          label="Vehicle Type"
          name="vehicle_type_id"
          :options="vehicleTypes"
          placeholder="Please select an option"
          class="w-full"
          filterable
        />
        <x-select
          v-model="filters.car_type_insurance_id"
          label="Type of Car Insurance"
          name="car_type_insurance_id"
          :options="carTypeInsurances"
          placeholder="Please select an option"
          class="w-full"
          filterable
        />
        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />
        <x-select
          v-model="filters.currently_insured_with"
          label="Currently Insured with"
          name="currently_insured_with"
          :options="providers"
          placeholder="Please select an option"
          class="w-full"
          filterable
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
        <x-select
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.advisor_id"
          label="Advisors (select teams first)"
          name="advisor_id"
          :options="
            teamUsers.map(user => ({
              value: user.id,
              label: user.name,
            }))
          "
          placeholder="Please select Advisor"
          :loading="loader.advisorTeamOptions"
          filterable
          multiple
          truncate
          multipleCheckbox
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.advisor_id = teamUsers.map(user => user.id)"
              @clear="filters.advisor_id = []"
            />
          </template>
        </x-select>
        <x-select
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.assignment_type"
          label="Assignment Type"
          name="assignment_type"
          :options="assignmentTypes"
          placeholder="Please select assignment type"
          class="w-full"
          filterable
        />
        <x-select
          v-if="!hasRole(rolesEnum.CarAdvisor)"
          v-model="filters.teams"
          label="Teams"
          name="teams"
          :options="teamOptions"
          placeholder="Search by Teams"
          filterable
          multiple
          truncate
          multipleCheckbox
          @update:modelValue="fetchTeamUsers"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.teams = teamOptions.map(item => item.value)"
              @clear="filters.teams = []"
            />
          </template>
        </x-select>
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

        <x-select
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          label="Segment"
          name="segment_filter"
          :options="quoteSegments"
          placeholder="Select Segment"
          class="w-full"
          filterable
        />
        <x-select
          v-model="filters.sic_advisor_requested"
          label="Advisor Requested"
          name="sic_advisor_requested"
          :options="[
            { value: 'All', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          placeholder="Select any option"
          class="w-full"
          filterable
        />

        <x-select
          v-model="filters.api_issuance_status_id"
          name="api_issuance_status_id"
          placeholder="Search by API Issuance Status"
          :options="issuanceStatuses"
          class="w-full"
          label="API Issuance Status"
          filterable
          multiple
          truncate
          multipleCheckbox
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.api_issuance_status_id = issuanceStatuses.map(
                  item => item.value,
                )
              "
              @clear="filters.api_issuance_status_id = []"
            />
          </template>
        </x-select>

        <x-select
          v-model="filters.insurer_api_status_id"
          name="insurer_api_status_id"
          placeholder="Search by Insurer API Status"
          :options="insurerApiStatus"
          class="w-full"
          label="Insurer API Status"
          filterable
          multiple
          truncate
          multipleCheckbox
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.insurer_api_status_id = insurerApiStatus.map(
                  item => item.value,
                )
              "
              @clear="filters.insurer_api_status_id = []"
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
            @click="
              () => {
                onExport(
                  `/car/leads-export?${objToUrl(filters)}`,
                  true,
                  'download',
                );
              }
            "
            class="justify-self-start mr-3"
          >
            Export
          </x-button>
          <x-button
            v-if="canExport && can(permissionsEnum.DATA_EXTRACTION)"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click="
              () => {
                onExport(
                  `/car/leads-export?${objToUrl(filters)}`,
                  true,
                  'email',
                );
              }
            "
            class="justify-self-start mr-3"
          >
            Export via email
          </x-button>
          <x-tooltip
            v-if="!canExport && can(permissionsEnum.DATA_EXTRACTION)"
            placement="right"
          >
            <x-button tag="div" size="sm" color="emerald" class="mr-3">
              Export
            </x-button>
            <x-button tag="div" size="sm" color="emerald" class="mr-3"
              >Export via email</x-button
            >
            <template #tooltip>
              <span class="font-medium">
                Created dates, payment due date, booking date, or renewal batch
                are required to export data.
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
            @click="onPUAExport"
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
          :canAssignLeadAdvisor="
            !hasRole(rolesEnum.CarAdvisor) &&
            !can(permissionsEnum.VIEW_ALL_LEADS)
          "
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

      <template #item-vehicle_use="{ vehicle_use }">
        <div class="text-center">
          {{
            vehicle_use
              ? vehicle_use.charAt(0).toUpperCase() + vehicle_use.slice(1)
              : ''
          }}
        </div>
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
      <template #item-api_issuance_status_id="{ api_issuance_status_id }">
        <div class="text-center">
          <x-tag
            v-if="api_issuance_status_id"
            size="sm"
            :color="api_issuance_status_id == 1 ? 'success' : 'error'"
          >
            {{
              issuanceStatuses.find(s => s.value == api_issuance_status_id)
                ?.label
            }}
          </x-tag>
          <span v-else>N/A</span>
        </div>
      </template>
      <template #item-insurer_api_status_id="{ insurer_api_status_id }">
        <div class="text-center">
          {{
            insurer_api_status_id
              ? insurerApiStatus.find(s => s.value == insurer_api_status_id)
                  ?.label
              : 'N/A'
          }}
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
        <p v-if="item.payment_status?.text === 'AUTHORISED'">
          {{ item.payment?.authorized_at_formatted }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p v-if="item.payment_status?.text === 'AUTHORISED'">
          {{ daysAgoFromAuthorizedDate(item.payment?.authorized_at_formatted) }}
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

    <!-- PUA Export Modal -->
    <x-modal
      v-model="puaExportModal.show"
      size="lg"
      title="Export Car PUA Updates"
      show-close
      backdrop
      persistent
    >
      <div class="grid grid-cols-1 gap-4">
        <DatePicker
          v-model="puaExportModal.payment_date"
          label="Payment Date"
          class="w-full"
          :max-date="new Date()"
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
