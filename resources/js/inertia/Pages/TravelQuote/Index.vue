<script setup>
import { computed, ref } from 'vue';

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
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
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
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at' },
  { text: 'PAYMENT EXPIRY', value: 'expiry_dates' },
  { text: 'Travel Type', value: 'direction_code' },
  { text: 'Travel Coverage', value: 'coverage_code' },
  { text: 'LEAD STATUS', value: 'quote_status_id_text' },
  { text: 'AML Status', value: 'aml_status' },
  { text: 'ADVISOR', value: 'advisor_id_text' },
  {
    text: 'ADVISOR REQUESTED',
    value: 'sic_advisor_requested',
  },
  { text: 'Advisor Assigned Date And Time', value: 'advisor_assigned_date' },
  { text: 'API ISSUANCE STATUS', value: 'api_issuance_status' },
  { text: 'INSURER API STATUS', value: 'insurer_api_status' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date',
    sortable: true,
  },
  { text: 'DATE OF BIRTH', value: 'dob' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'SOURCE', value: 'source' },
  { text: 'Provider Name', value: 'travel_plan_provider_text' },
  { text: 'Plan Name', value: 'plan_id_text' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'DESTINATION', value: 'destination_id_text' },
  { text: 'CURRENTLY LOCATED IN', value: 'currently_located_in_id_text' },
  { text: 'EXPIRY DATE', value: 'expiry_date' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'PAYMENT STATUS', value: 'payment_status_id_text' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch_text' },
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

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const travelQuoteEnum = page.props.travelQuoteEnum;
const exportLoader = ref(false);
const onDataExport = () => {
  filters.created_at_start = useDateFormat(
    filters.created_at_start,
    'YYYY-MM-DD',
  ).value;

  filters.created_at_end = useDateFormat(
    filters.created_at_end,
    'YYYY-MM-DD',
  ).value;

  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'travel');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Travel'),
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
        <Link :href="route('travel.create')">
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
          />
        </x-field>
        <x-field label="Created Date End">
          <DatePicker v-model="filters.created_at_end" name="created_at_end" />
        </x-field>
        <x-field label="Lead Status">
          <ComboBox
            v-model="filters.quote_status_id"
            name="quote_status_id"
            placeholder="Search by Lead Status"
            :options="leadsStatusOptions"
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
        <x-field label="Advisor" v-if="!permissions.travelAdvisor">
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorsOptions"
          />
        </x-field>
        <DatePicker
          v-model="filters.transaction_approved_dates"
          label="Transaction Approved Date"
          class="w-full"
          range
          multi-calendars
          multi-calendars-solo
          max-range="30"
        />
        <x-field label="Ecommerce">
          <x-select
            v-model="filters.is_ecommerce"
            placeholder="Search by Ecommerce"
            :options="[
              { value: '', label: 'All' },
              { value: 'Yes', label: 'Yes' },
              { value: 'No', label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-field label="Payment Status">
          <ComboBox
            v-model="filters.payment_status_id"
            placeholder="Search by Payment Status"
            :options="paymentStatusOptions"
            :single="true"
          />
        </x-field>
        <x-field label="Travel Type" required>
          <x-select
            v-model="filters.direction_code"
            :options="subTeamOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="Travel Coverage" required>
          <x-select
            v-model="filters.coverage_code"
            :options="
              filters.direction_code == 'travelUaeInbound'
                ? inboundCoverageCode
                : outboundCoverageCode
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Source">
          <x-input
            v-model="filters.source"
            type="search"
            name="source"
            class="w-full"
            placeholder="Search by Source"
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
        <ComboBox
          v-model="filters.renewal_batches"
          label="Renewal Batch"
          placeholder="Search by Renewal Batch"
          :options="renewalBatchOptions"
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
          label="Segment"
          v-model="filters.segment_filter"
          placeholder="Select Segment"
          :options="quoteSegments"
          class="w-full"
          :single="true"
        />
        <DatePicker
          v-model="filters.last_modified_date"
          name="created_at_start"
          label="Last Modified Date"
          range
          format="dd-MM-yyyy"
        />
        <DatePicker
          v-if="hasRole(rolesEnum.TravelManager)"
          v-model="filters.advisor_assigned_date"
          name="created_at_start"
          label="Advisor Assigned Date"
          range
          format="dd-MM-yyyy"
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
          label="API Issuance Status"
          v-model="filters.api_issuance_status_id"
          placeholder="Select API Issuance Status"
          :options="issuanceStatuses"
          class="w-full"
        />
        <ComboBox
          label="Insurer API Status"
          v-model="filters.insurer_api_status_id"
          placeholder="Select Status"
          :options="insurerApiStatus"
          class="w-full"
        />
        <x-field label="AML Status">
          <ComboBox
            v-model="filters.amlStatus"
            name="source"
            class="w-full"
            placeholder="Search by AMLStatus"
            :options="computedAmlStatuses"
          />
        </x-field>
        <x-field label="Provider Name">
          <ComboBox
            v-model="filters.insurance_provider_ids"
            name="source"
            class="w-full"
            placeholder="Search by Provider Name"
            :options="computedInsuranceProviders"
          />
        </x-field>
        <x-field label="Plan Name">
          <ComboBox
            v-model="filters.plan_name"
            name="source"
            class="w-full"
            placeholder="Search by Plan Name"
            :options="computedTravelPlans"
          />
        </x-field>
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            :loading="exportLoader"
            @click.prevent="onDataExport"
            class="justify-self-start"
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
          v-if="permissions.isManualAllocationAllowed == true"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-field label="Assign Advisor" class="w-full">
                <x-select
                  v-model="assignForm.assigned_to_id_new"
                  :options="advisorOptions"
                  placeholder="Select Advisor"
                  class="flex-1 w-full"
                  :rules="[rules.isRequired]"
                  filterable
                  v-if="readOnlyMode.isDisable === true"
                />
              </x-field>

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
