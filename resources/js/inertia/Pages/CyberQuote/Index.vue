<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  renewalBatches: Array,
  quoteType: {
    type: String,
    default: 'cyber',
  },
  totalCount: {
    type: Number,
    default: 0,
  },
  authorizedDays: Number,
});

const page = usePage();
const hasAnyRole = role => useHasAnyRole(role);
const canAny = permissions => useCanAny(permissions);
const rolesEnum = page.props.rolesEnum;
const notification = useNotifications('toast');
const loader = reactive({
  table: false,
  export: false,
});

let availableFilters = {
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  payment_status_id: '',
  is_ecommerce: '',
  created_at_start: new Date() || '',
  created_at_end: new Date() || '',
  quote_status_id: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  sic_advisor_requested: 'All',
  advisor_id: [],
  previous_quote_policy_number_text: '',
  payment_due_date: '',
  booking_date: '',
  last_modified_date: '',
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
  transaction_approved_dates: '',
  api_issuance_status_id: '',
  insurer_api_status_id: [],
  insurer_aml_status: [],
  page: 1,
};

const filters = reactive(availableFilters);
const canExport = ref(false);
const hasRole = role => useHasRole(role);
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
  { text: 'Ref-ID', value: 'uuid', is_active: true },
  { text: 'FIRST NAME', value: 'first_name', is_active: true },
  { text: 'LAST NAME', value: 'last_name', is_active: true },
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at', is_active: true },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date', is_active: true },
  { text: 'LEAD STATUS', value: 'quote_status', is_active: true },
  { text: 'ADVISOR', value: 'advisor', is_active: true },
  { text: 'POLICY NO', value: 'policy_number', is_active: true },
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
  { text: 'Nationality', value: 'nationality.text', is_active: true },
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  { text: 'PRICE', value: 'premium', is_active: true },
  {
    text: 'Previous Policy Number',
    value: 'previous_quote_policy_number',
    is_active: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch_model', is_active: true },
]);

const quotesSelected = ref([]);
const permissionAssignLeads = ref(false);

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

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

    router.visit(route('cyber-quotes-list'), {
      method: 'get',
      data: {
        ...filtersCleaned,
        ...serverOptions.value,
      },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
      onFinish: () => {
        loader.table = false;
      },
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  removedSavedParams();
  router.visit(route('cyber-quotes-list'), {
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

  onSubmit(true);
};

const exportLoader = ref(false);
const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'cyber');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Cyber'),
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

const advisorOptionsFilter = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
});

const advisorOptions = computed(() => {
  const advisors = page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
  return [
    ...advisors,
    {
      value: -1,
      label: 'UnAssigned',
    },
  ];
});

const paymentStatusOptions = computed(() => {
  return page.props.paymentStatuses.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const insurerApiStatusOptions = computed(() => {
  return Object.entries(page.props.insurerApiStatus).map(([index, value]) => ({
    value: index,
    label: value,
  }));
});

const issuanceStatusOptions = computed(() => {
  return Object.entries(page.props.issuanceStatuses).map(([index, value]) => ({
    value: index,
    label: value,
  }));
});

const insurerAMLStatusOptions = computed(() => {
  return Object.entries(page.props.insurerAMLStatus).map(([key, value]) => ({
    value: key,
    label: value,
  }));
});

const onLeadAssigned = () => {
  quotesSelected.value = [];
};

function setQueryStringFilters() {
  const integerFields = [
    'quote_status_id',
    'advisor_id',
    'payment_status_id',
    'insurer_aml_status',
    'insurer_api_status_id',
    'page',
  ];

  const arrayParams = {};
  const singleParams = {};

  for (const [key, value] of Object.entries(params)) {
    const arrayMatch = key.match(/^(.+)\[(\d+)\]$/);

    if (arrayMatch) {
      const [, fieldName, index] = arrayMatch;
      if (!arrayParams[fieldName]) {
        arrayParams[fieldName] = [];
      }
      arrayParams[fieldName][parseInt(index)] = value;
    } else if (key.includes('[]')) {
      const fieldName = key.substring(0, key.length - 2);
      arrayParams[fieldName] = Array.isArray(value) ? value : [value];
    } else {
      singleParams[key] = value;
    }
  }

  for (const [fieldName, values] of Object.entries(arrayParams)) {
    const cleanValues = values.filter(v => v !== undefined);

    if (integerFields.includes(fieldName)) {
      filters[fieldName] = cleanValues
        .map(v => parseInt(v))
        .filter(v => !isNaN(v));
    } else {
      filters[fieldName] = cleanValues;
    }
  }

  for (const [key, value] of Object.entries(singleParams)) {
    if (integerFields.includes(key) && !isNaN(parseInt(value))) {
      filters[key] = parseInt(value);
    } else if (key === 'is_ecommerce' && (value === '0' || value === '1')) {
      filters[key] = parseInt(value);
    } else {
      filters[key] = value;
    }
  }
}
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  params = getSavedQueryParams() || params;
  setQueryStringFilters();

  if (hasRole(rolesEnum.CyberManager) || hasRole(rolesEnum.Admin)) {
    permissionAssignLeads.value = true;
  }

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

  parsedDate.setHours(0, 0, 0, 0);

  const authorizedDays = page.props.authorizedDays || 8;
  const newDate = new Date(parsedDate);
  newDate.setDate(parsedDate.getDate() + authorizedDays);

  newDate.setHours(0, 0, 0, 0);

  const currentDate = new Date();
  currentDate.setHours(0, 0, 0, 0);

  const differenceInTime = newDate.getTime() - currentDate.getTime();
  const differenceInDays = Math.ceil(differenceInTime / (1000 * 3600 * 24));

  if (differenceInDays <= 0) {
    return 'Expired';
  }

  return differenceInDays === 1
    ? `${differenceInDays} day`
    : `${differenceInDays} days`;
}
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
    if (oneMonthLater.getDate() < startDate.getDate()) {
      oneMonthLater.setDate(0);
    }
    if (endDate > oneMonthLater) {
      return true;
    }
  }
  return false;
};
</script>

<template>
  <div>
    <Head title="Cyber Quotes" />

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Cyber Quotes List</h2>
      </template>
      <template #default>
        <ColumnSelection
          v-model:columns="tableHeader"
          storage-key="cyber-list"
        />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />

        <div v-if="readOnlyMode.isDisable === true">
          <Link :href="route('cyber-quotes-create')">
            <x-button
              v-if="can(permissionsEnum.CYBER_QUOTES_CREATE)"
              size="sm"
              color="#ff5e00"
              :href="route('cyber-quotes-create')"
            >
              Create Lead
            </x-button>
          </Link>
        </div>
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
        <x-field label="Payment Status">
          <x-select
            v-model="filters.payment_status_id"
            name="payment_status_id"
            placeholder="Search by Payment Status"
            :options="paymentStatusOptions"
            class="w-full"
            filterable
          />
        </x-field>
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
        <DatePicker
          v-model="filters.created_at_start"
          type="date"
          name="created_at_start"
          class="w-full"
          label="Created Date Start"
        />
        <DatePicker
          v-model="filters.created_at_end"
          type="date"
          name="created_at_end"
          class="w-full"
          label="Created Date End"
        />
        <x-field label="Lead Status">
          <ComboBox
            v-model="filters.quote_status_id"
            name="quote_status"
            placeholder="Search by Lead Status"
            :options="
              quoteStatuses.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
          />
        </x-field>
        <DatePicker
          v-model="filters.policy_expiry_date"
          name="policy_expiry_date"
          class="w-full"
          label="Policy Start Date"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_end"
          name="policy_expiry_date_end"
          class="w-full"
          label="Policy End Date"
        />
        <x-field label="Advisor Requested">
          <x-select
            v-model="filters.sic_advisor_requested"
            placeholder="Search by Advisor Requested"
            :options="[
              { value: 'All', label: 'All' },
              { value: 'Yes', label: 'Yes' },
              { value: 'No', label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-field label="Advisor" v-if="!hasAnyRole([rolesEnum.CyberAdvisor])">
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptions"
          />
        </x-field>
        <x-input
          v-model="filters.previous_quote_policy_number_text"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
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
        <DatePicker
          v-model="filters.last_modified_date"
          name="created_at_start"
          label="Last Modified Date"
          range
          format="dd-MM-yyyy"
        />
        <x-field label="Insurer Tax Invoice No">
          <x-input
            v-model="filters.insurer_tax_invoice_number"
            type="text"
            name="insurer_tax_invoice_number"
            class="w-full"
            placeholder="Insurer Tax Invoice No"
          />
        </x-field>
        <x-field label="Insurer Commission Tax Invoice No">
          <x-input
            v-model="filters.insurer_commission_tax_invoice_number"
            type="text"
            name="insurer_commission_tax_invoice_number"
            class="w-full"
            placeholder="Insurer Commission Tax Invoice No"
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
        <x-field label="API Issuance Status">
          <x-select
            v-model="filters.api_issuance_status_id"
            name="api_issuance_status_id"
            placeholder="Search by API Issuance Status"
            :options="issuanceStatusOptions"
            class="w-full"
            filterable
          />
        </x-field>
        <x-field label="Insurer API Status">
          <x-select
            v-model="filters.insurer_api_status_id"
            name="insurer_api_status_id"
            placeholder="Search by Insurer API Status"
            :options="insurerApiStatusOptions"
            class="w-full"
            filterable
            multiple
            truncate
          >
            <template #content-footer>
              <ui-select-actions
                @select-all="
                  filters.insurer_api_status_id = insurerApiStatusOptions.map(
                    item => item.value,
                  )
                "
                @clear="filters.insurer_api_status_id = []"
              />
            </template>
          </x-select>
        </x-field>
        <x-field label="IM AML Status">
          <x-select
            v-model="filters.insurer_aml_status"
            name="insurer_aml_status"
            placeholder="Search by IM AML Status"
            :options="insurerAMLStatusOptions"
            class="w-full"
            filterable
            multiple
            truncate
          >
            <template #content-footer>
              <ui-select-actions
                @select-all="
                  filters.insurer_aml_status = insurerAMLStatusOptions.map(
                    item => item.value,
                  )
                "
                @clear="filters.insurer_aml_status = []"
              />
            </template>
          </x-select>
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

    <Transition name="fade">
      <div
        v-if="quotesSelected.length > 0 && permissionAssignLeads"
        class="mb-4"
      >
        <LeadAssignment
          :selected="quotesSelected.map(e => e.id)"
          :advisors="advisorOptions"
          :canAssignLeadAdvisor="permissionAssignLeads"
          :quoteType="quoteType"
          @success="onLeadAssigned"
        />
      </div>
    </Transition>

    <DataTable
      v-model:items-selected="quotesSelected"
      v-model:server-options="serverOptions"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-uuid="{ code, uuid, stale_at }">
        <Link
          v-if="
            canAny([
              permissionsEnum.CYBER_QUOTES_SHOW,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="route('cyber-quotes-show', uuid)"
          class="text-primary-500 hover:underline flex items-center space-x-1"
        >
          <span>{{ code }}</span>
          <StaleLeadsBadge :date="stale_at" :align="`left`" />
        </Link>
        <span v-else>{{ code }}</span>
      </template>
      <template #item-authorized_at="item">
        <p v-if="item?.payment_status?.text === 'AUTHORISED'">
          {{ item?.payments[0]?.authorized_at }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p v-if="item?.payment_status?.text === 'AUTHORISED'">
          {{ daysAgoFromAuthorizedDate(item?.payments[0]?.authorized_at) }}
        </p>
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
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
      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
      </template>

      <template #item-currently_insured_with="{ currently_insured_with }">
        {{ currently_insured_with?.text }}
      </template>

      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-renewal_batch_model="item">
        <p>
          {{ item?.renewal_batch_model?.name ?? '' }}
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

