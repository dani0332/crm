<script setup>
import { computed, ref } from 'vue';
import CreateLeadModal from '../../Components/CreateLeadModal.vue';

defineProps({
  quotes: Object,
  dropdownSource: Object,
  permissions: Object,
  advisors: Object,
  renewalBatches: Array,
  authorizedDays: Number,
  amlStatuses: Object,
  insuranceProviders: Array,
  travelPlans: Array,
  insurerAMLStatus: Object,
  assignmentTypes: Object,
  subSources: { type: Array, default: () => [] },
});

let params = useUrlSearchParams('history');

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

const quotesSelected = ref([]);
const canExport = ref(false);
const page = usePage();
const createLeadModal = ref(false);

const onLeadConfirmed = () => {
  createLeadModal.value = false;
};

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const teamNamesEnum = page.props.teamNamesEnum;
const isPcpSubSourceOptionAllowed = ref(
  useHasRole(rolesEnum.Admin) || useHasAnyTeam([{ name: teamNamesEnum.PCP }])
);
const notification = useNotifications('toast');
const cleanObj = obj => useCleanObj(obj);
const quoteSegments = page.props.quoteSegments?.filter(
  segment => segment.value !== 'sic-revival',
);

const serverOptions = ref({
  page: 1,
  sortType: 'desc',
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: new Date() || '',
  created_at_end: new Date() || '',
  quote_status_id: [],
  insurer_aml_status: [],
  advisor_id: [],
  is_ecommerce: '',
  payment_status_id: '',
  page: 1,
  direction_code: '',
  coverage_code: '',
  previous_quote_policy_number: '',
  renewal_batches: [],
  payment_due_date: '',
  booking_date: '',
  segment_filter: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  sic_advisor_requested: 'All',
  transaction_approved_dates: page.props.transaction_approved_dates || '',
  last_modified_date: null,
  advisor_assigned_date: '',
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
  insurer_api_status_id: '',
  api_issuance_status_id: '',
  amlStatus: [],
  insurance_provider_ids: [],
  plan_name: [],
  travel_start_date: '',
  travel_end_date: '',
  assignment_type: '',
  private_client: 'all',
  age_group: 'all',
  authorize_date: '',
  captured_date: '',

});

const loader = reactive({
  table: false,
  export: false,
});
const inboundCoverageCode = [
  { value: 'singleTrip', label: 'Single Trip' },
  { value: 'multiTrip', label: 'Multi Trip' },
];
const outboundCoverageCode = [
  { value: 'singleTrip', label: 'Single Trip' },
  { value: 'annualTrip', label: 'Annual Trip' },
];

const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'PAYMENT AUTHORISED DATE', value: 'payment.authorized_at' },
  { text: 'PAYMENT EXPIRY', value: 'expiry_dates' },
  { text: 'Travel Type', value: 'direction_code' },
  { text: 'Travel Coverage', value: 'coverage_code' },
  { text: 'LEAD STATUS', value: 'quote_status.text' },
  { text: 'AML Status', value: 'aml_status' },
  { text: 'INSURER AML STATUS', value: 'insurer_aml_status_text' },
  { text: 'ADVISOR', value: 'advisor.name' },
  {
    text: 'ADVISOR REQUESTED',
    value: 'sic_advisor_requested',
  },
  {
    text: 'Advisor Assigned Date And Time',
    value: 'travel_quote_request_detail.advisor_assigned_date',
  },
  { text: 'API ISSUANCE STATUS', value: 'api_issuance_status' },
  { text: 'INSURER API STATUS', value: 'insurer_api_status' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date_formatted',
    sortable: true,
  },
  { text: 'DATE OF BIRTH', value: 'dob_formatted' },
  {
    text: 'LOST REASON',
    value: 'travel_quote_request_detail.lost_reason.text',
  },
  { text: 'SOURCE', value: 'source' },
  { text: 'Provider Name', value: 'insurance_provider.text' },
  { text: 'Plan Name', value: 'plan.text' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'DESTINATION', value: 'nationality.country_name' },
  { text: 'CURRENTLY LOCATED IN', value: 'currently_located_in.text' },
  { text: 'EXPIRY DATE', value: 'expiry_date' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'PAYMENT STATUS', value: 'payment_status.text' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch.name' },
  { text: 'Age Group', value: 'age_group' },
  { text: 'Private Client', value: 'customer.pcp_tag_formatted' },
  { text: 'IMCRM SUB-SOURCE', value: 'sub_source.text' },
];

const paymentStatusOptions = computed(() => {
  return page.props.dropdownSource.payment_status_id.map(item => {
    return {
      value: item.id,
      label: item.text,
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
const issuanceStatuses = computed(() => {
  return Object.entries(page.props.issuanceStatuses).map(([index, value]) => {
    return {
      value: index,
      label: value,
    };
  });
});

const computedAmlStatuses = computed(() => {
  return Object.entries(page.props.amlStatuses).map(([index, value]) => {
    return {
      value: index,
      label: value,
    };
  });
});

const computedInsuranceProviders = computed(() => {
  return page.props.insuranceProviders.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});

const computedTravelPlans = computed(() => {
  if (
    filters.insurance_provider_ids &&
    filters.insurance_provider_ids.length > 0
  ) {
    return page.props.travelPlans
      .filter(plan => filters.insurance_provider_ids.includes(plan.provider_id))
      .map(item => {
        return {
          value: item.id,
          label: item.text,
        };
      });
  }
  return [];
});

const advisorsOptions = computed(() => {
  const advisors = page.props.dropdownSource.advisor_id.map(item => {
    return {
      value: item.id,
      label: item.name,
    };
  });
  return [
    ...advisors,
    {
      value: -1,
      label: 'UnAssigned',
    },
  ];
});

const renewalBatchOptions = computed(() => {
  return page.props.renewalBatches.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

const leadsStatusOptions = computed(() => {
  return page.props.dropdownSource.quote_status_id.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });
});


const subTeamOptions = [
  { value: 'travelUaeInbound', label: 'To the UAE (Inbound)' },
  { value: 'travelUaeOutbound', label: 'Outside UAE (OutBound)' },
];

function onSubmit(isValid) {
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
  serverOptions.value.page = 1;
  router.visit(route('travel.index'), {
    method: 'get',
    data: { ...filters, ...serverOptions.value },
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

function resetFilters() {
  router.visit(route('travel.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'Travel',
  selectTmLeadId: '',
  isManagerOrDeputy: page.props.permissions.isManagerOrDeputy,
  isLeadPool: page.props.permissions.isLeadPool,
  isManualAllocationAllowed: page.props.permissions.isManualAllocationAllowed,
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post(route('manualLeadAssign', { quoteType: 'travel' }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: res => {
          if (res.props.session.message) {
            let title = res.props.session.message;
            notification.success({
              title: title,
              position: 'top',
              timeout: 0,
            });
            return false;
          }

          let title = res.props.session.success;
          quotesSelected.value = [];
          notification.success({
            title: title,
            position: 'top',
          });
        },
      });
  }
}

function setQueryFilters() {
  // Define which fields should have integer values
  const integerFields = [
    'quote_status_id',
    'insurer_aml_status',
    'advisor_id',
    'renewal_batches',
    'payment_status_id',
    'amlStatus',
    'insurance_provider_ids',
    'plan_name',
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
    } else if (key === 'is_ecommerce' && (value === '0' || value === '1')) {
      // Boolean-like fields
      filters[key] = parseInt(value);
    } else {
      // Keep as string for dates, text fields, enums, etc.
      filters[key] = value;
    }
  }
}

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const travelQuoteEnum = page.props.travelQuoteEnum;
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

  filters.created_at_start = filters.created_at_start
    ? useDateFormat(filters.created_at_start, 'YYYY-MM-DD').value
    : '';

  filters.created_at_end = filters.created_at_end
    ? useDateFormat(filters.created_at_end, 'YYYY-MM-DD').value
    : '';

  filters.exportType = exportType;

  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'travel');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Travel'),
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

const formatDate = date => {
  if (!date) return '';
  const [datePart] = date.split(' ');
  const [day, month, year] = datePart.split('-');
  const parsedDate = new Date(`${year}-${month}-${day}`);
  return useDateFormat(parsedDate, 'DD-MMM-YYYY').value;
};

watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue !== newValue) onSubmit(true);
  },
  { deep: true },
);

const insurerAMLStatusOption = computed(() => {
  return Object.entries(page.props.insurerAMLStatus).map(([key, value]) => ({
    value: key,
    label: value,
  }));
});

const calculateAge = dateOfBirth => {
  if (!dateOfBirth) return 0;

  const today = new Date();
  let birthDate;

  // Handle both DD-MM-YYYY and YYYY-MM-DD formats
  if (typeof dateOfBirth === 'string' && dateOfBirth.includes('-')) {
    const parts = dateOfBirth.split('-');

    // Check if first part is a 4-digit year (YYYY-MM-DD format)
    if (parts[0].length === 4) {
      // YYYY-MM-DD format
      const [year, month, day] = parts;
      birthDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    } else {
      // DD-MM-YYYY format
      const [day, month, year] = parts;
      birthDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    }
  } else {
    birthDate = new Date(dateOfBirth);
  }

  // Check if the date is valid
  if (isNaN(birthDate.getTime())) {
    return 0;
  }

  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();

  if (
    monthDiff < 0 ||
    (monthDiff === 0 && today.getDate() < birthDate.getDate())
  ) {
    age--;
  }

  return age;
};
</script>

<template>
  <div>
    <Head title="Travel List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div
        class="flex space-x-2 items-center"
        v-if="readOnlyMode.isDisable === true"
      >
        <Link :href="route('travel.expired.upload')" v-if="permissions.admin">
          <x-button size="sm" color="#1d83bc" tag="div">
            Upload Expired Leads
          </x-button>
        </Link>
        <Link :href="route('travel.cards')">
          <x-button
            size="sm"
            color="#1d83bc"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Cards View
          </x-button>
        </Link>
        <x-button
          size="sm"
          color="#ff5e00"
          v-if="readOnlyMode.isDisable === true"
          @click="createLeadModal = true"
        >
          Create Lead
        </x-button>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
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
          :options="leadsStatusOptions"
          class="w-full"
          filterable
          label="Lead Status"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.quote_status_id = leadsStatusOptions.map(
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
          v-model="filters.advisor_id"
          name="advisor_id"
          placeholder="Search by Advisor"
          :options="advisorsOptions"
          class="w-full"
          filterable
          label="Advisor"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.advisor_id = advisorsOptions.map(item => item.value)
              "
              @clear="filters.advisor_id = []"
            />
          </template>
        </x-select>
        <DatePicker
          v-model="filters.transaction_approved_dates"
          label="Transaction Approved Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
          max-range="30"
        />
        <x-select
          v-model="filters.is_ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
          label="Ecommerce"
        />

        <x-select
          v-model="filters.payment_status_id"
          name="payment_status_id"
          placeholder="Search by Payment Status"
          :options="paymentStatusOptions"
          class="w-full"
          filterable
          label="Payment Status"
        />

        <x-select
          v-model="filters.direction_code"
          :options="subTeamOptions"
          class="w-full"
          label="Travel Type"
          required
        />
        <x-select
          v-model="filters.coverage_code"
          :options="
            filters.direction_code == 'travelUaeInbound'
              ? inboundCoverageCode
              : outboundCoverageCode
          "
          class="w-full"
          label="Travel Coverage"
          required
        />
        <x-input
          v-model="filters.source"
          type="search"
          name="source"
          class="w-full"
          placeholder="Search by Source"
          label="Source"
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
          name="renewal_batches"
          placeholder="Search by Renewal Batch"
          :options="renewalBatchOptions"
          class="w-full"
          filterable
          multiple
          truncate
          label="Renewal Batch"
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

        <x-select
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          name="segment_filter"
          placeholder="Search by Segment"
          :options="quoteSegments"
          class="w-full"
          label="Segment"
          filterable
        />

        <DatePicker
          v-model="filters.last_modified_date"
          name="created_at_start"
          label="Last Modified Date"
          range
          format="dd-MM-yyyy"
        />

        <x-select
          v-model="filters.sic_advisor_requested"
          name="sic_advisor_requested"
          placeholder="Search by Advisor Requested"
          :options="[
            { value: 'All', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          label="Advisor Requested"
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



        <x-select
          v-model="filters.api_issuance_status_id"
          name="api_issuance_status_id"
          placeholder="Search by API Issuance Status"
          :options="issuanceStatuses"
          class="w-full"
          label="API Issuance Status"
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

        <x-select
          v-model="filters.amlStatus"
          name="source"
          class="w-full"
          placeholder="Search by AML Status"
          :options="computedAmlStatuses"
          label="AML Status"
          filterable
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.amlStatus = computedAmlStatuses.map(item => item.value)
              "
              @clear="filters.amlStatus = []"
            />
          </template>
        </x-select>

        <x-select
          v-model="filters.insurance_provider_ids"
          name="source"
          class="w-full"
          placeholder="Search by Provider Name"
          :options="computedInsuranceProviders"
          label="Provider Name"
          filterable
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.insurance_provider_ids = computedInsuranceProviders.map(
                  item => item.value,
                )
              "
              @clear="filters.insurance_provider_ids = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.plan_name"
          name="source"
          class="w-full"
          placeholder="Search by Plan Name"
          :options="computedTravelPlans"
          filterable
          label="Plan Name"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.plan_name = computedTravelPlans.map(item => item.value)
              "
              @clear="filters.plan_name = []"
            />
          </template>
        </x-select>
        <DatePicker
          v-model="filters.travel_start_date"
          label="Travel Start Date"
          format="dd-MM-yyyy"
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
          v-model="filters.travel_end_date"
          label="Travel End Date"
          format="dd-MM-yyyy"
        />

        <x-select
          v-model="filters.assignment_type"
          name="assignment_type"
          class="w-full"
          placeholder="Search by Assignment Type"
          :options="assignmentTypes"
          label="Assignment Type"
          filterable
        />
        <ComboBox
          v-model="filters.age_group"
          label="Age group"
          placeholder="Search by age group"
          :options="[
            { value: 'all', label: 'All' },
            { value: '0_64', label: '0 - 64' },
            { value: '65_plus', label: '65 and above' },
            { value: 'both', label: 'Both' },
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
            class="justify-self-start"
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
          v-if="permissions.isManualAllocationAllowed == true"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assigned_to_id_new"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-full"
                :rules="[rules.isRequired]"
                filterable
                v-if="readOnlyMode.isDisable === true"
                label="Assign Advisor"
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
          :href="route('travel.show', uuid)"
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
      <template #item-expiry_dates="item">
        <p v-if="item.payment_status_id_text === 'AUTHORISED'">
          {{ daysAgoFromAuthorizedDate(item.authorized_at) }}
        </p>
      </template>
      <template #item-dob="{ dob }">
        <div class="text-center">
          {{ dob == '00-00-0000' ? '' : dob }}
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
      <template #item-sic_advisor_requested="{ sic_advisor_requested }">
        <div class="text-center">
          <x-tag size="sm" :color="sic_advisor_requested ? 'success' : 'error'">
            {{ sic_advisor_requested ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-coverage_code="{ coverage_code, days_cover_for, source }">
        <div class="text-center">
          {{
            source == $page.props.leadSource.RENEWAL_UPLOAD
              ? travelQuoteEnum.COVERAGE_CODE_MULTI_TRIP
              : coverage_code != null
                ? coverage_code
                : days_cover_for <= 92
                  ? travelQuoteEnum.COVERAGE_CODE_SINGLE_TRIP
                  : travelQuoteEnum.COVERAGE_CODE_ANNUAL_TRIP +
                    '/' +
                    travelQuoteEnum.COVERAGE_CODE_MULTI_TRIP
          }}
        </div>
      </template>
      <template
        #item-direction_code="{
          currently_located_in_id,
          direction_code,
          currently_located_in_id_text,
          destination_id_text,
          region_cover_for_id_text,
          region_cover_for_id,
        }"
      >
        <div class="text-center">
          {{
            direction_code == travelQuoteEnum.TRAVEL_UAE_OUTBOUND
              ? 'Outbound'
              : direction_code == travelQuoteEnum.TRAVEL_UAE_INBOUND
                ? 'Inbound'
                : currently_located_in_id_text ==
                      travelQuoteEnum.LOCATION_UAE_TEXT &&
                    region_cover_for_id != travelQuoteEnum.REGION_COVER_ID_UAE
                  ? 'Outbound'
                  : destination_id_text ==
                        travelQuoteEnum.LOCATION_UNITED_ARAB_EMIRATES_TEXT ||
                      region_cover_for_id == travelQuoteEnum.REGION_COVER_ID_UAE
                    ? 'Inbound'
                    : ''
          }}
        </div>
      </template>
      <template #item-renewal_batch_text="item">
        <p>
          {{ item.renewal_batch_text }}
        </p>
      </template>
      <template #item-aml_status="{ aml_status }">
        <span>{{ aml_status?.replace(/_/g, ' ') }}</span>
      </template>
      <template #item-age_group="item">
        <span v-if="item.child || item.parent"> Both </span>
        <span v-else-if="calculateAge(item.dob) < 65"> 0 - 64 </span>
        <span v-else-if="calculateAge(item.dob) >= 65"> 65 and above </span>
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

    <CreateLeadModal
      v-model="createLeadModal"
      route-name="travel.create"
      :sub-sources="subSources"
      :is-pcp-allowed="isPcpSubSourceOptionAllowed"
      @confirmed="onLeadConfirmed"
    />
  </div>
</template>
