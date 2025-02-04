<script setup>
const props = defineProps({
  leadsOrEndorsementData: Object,
  quoteStatuses: Array,
  paymentStatuses: Array,
  quoteTypes: Array,
  businessInsuranceTypes: Array,
  insuranceProviders: Array,
  advisors: Array,
  departments: Array,
  sendUpdateStatuses: Array,
  sendUpdateTypes: Array,
  quoteTypeIdEnum: Array,
});

const page = usePage();
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const isSendUpdateListView = ref(false);
const filterModal = ref(false);
const cleanObj = obj => useCleanObj(obj);
const loader = reactive({
  table: false,
  export: false,
});

function listChange() {
  isSendUpdateListView.value = !isSendUpdateListView.value;
  const listType = isSendUpdateListView.value ? 'endorsements' : 'leads';
  updateTableDetails();
  router.visit(route('search-leads'), {
    method: 'get',
    data: {
      list: listType,
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

const dateFormat = dateString =>
  dateString
    ? useDateFormat(useConvertDate(dateString), 'DD-MM-YYYY').value
    : '';

const getDetailPageRoute = (
  uuid,
  quote_type_id,
  business_type_of_insurance_id,
) => useGetShowPageRoute(uuid, quote_type_id, business_type_of_insurance_id);

const getSendUpdatePageRoute = (
  uuid,
  [quote_uuid, quote_type_id, quote_business_type_of_insurance_id],
) => {
  const path = new URL(
    getDetailPageRoute(
      quote_uuid,
      quote_type_id,
      quote_business_type_of_insurance_id,
    ),
  ).pathname;
  return route('send-update.show', { uuid, refURL: path });
};

function statusTitleFormat(str) {
  return str
    .toLowerCase()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}

let params = useUrlSearchParams('history');
const expandNotes = ref(false);
const filtersCount = ref(0);
const serverOptions = ref({
  page: 1,
  sortBy: 'created_at',
  sortType: 'desc',
});

let tableHeader = ref([]);
let tableData = props.leadsOrEndorsementData.data;

function getTableHeader() {
  return [
    { text: 'Ref-ID', value: 'code', sortingOrder: 1 },
    { text: 'First Name', value: 'first_name', sortingOrder: 2 },
    { text: 'Last Name', value: 'last_name', sortingOrder: 3 },
    { text: 'Company Name', value: 'company_name', sortingOrder: 4 },
    { text: 'Line of Business', value: 'quote_type', sortingOrder: 5 },
    {
      text: 'Business Insurance Type',
      value: 'business_insurance_type',
      sortingOrder: 6,
    },
    {
      text: 'Created Date',
      value: 'created_at',
      sortable: true,
      sortingOrder: 7,
    },
    {
      text: 'Policy Expiry Date ',
      value: 'policy_expiry_date',
      sortable: true,
      sortingOrder: 8,
    },
    {
      text: 'Policy Number',
      value: 'policy_number',
      width: 60,
      align: 'center',
      sortingOrder: 9,
    },
    { text: 'Status', value: 'quote_status', sortingOrder: 10 },
  ];
}

function updateTableDetails() {
  tableHeader = ref(getTableHeader());
  if (isSendUpdateListView.value) {
    // Define the sortingOrder values to be excluded
    const excludedSortingOrders = [1, 10];
    // Filter out columns with sortingOrder matching any of the excludedSortingOrders
    tableHeader.value = tableHeader.value.filter(
      item => !excludedSortingOrders.includes(item.sortingOrder),
    );
    tableHeader.value.push(
      { text: 'SU Ref-ID', value: 'code', width: 100, sortingOrder: 1 },
      { text: 'Type', value: 'category', width: 200, sortingOrder: 10 },
      { text: 'Sub type', value: 'option', width: 200, sortingOrder: 11 },
      { text: 'Notes', value: 'notes', width: 300, sortingOrder: 12 },
      { text: 'Status', value: 'status', sortingOrder: 13 },
    );
  }
  tableHeader.value.sort((a, b) => a.sortingOrder - b.sortingOrder);
  tableData = props.leadsOrEndorsementData.data;
}

const availableFilters = reactive({
  code: '',
  insured_name: '',
  member_first_name: '',
  member_last_name: '',
  company_name: '',
  policy_number: '',
  mobile_no: '',
  email: '',
  su_code: '',
  date_type: '',
  date_range: '',
  quote_status: [],
  payment_status: [],
  line_of_business: [],
  business_insurance_type: [],
  currently_insured_with: [],
  department: [],
  advisors: [],
  insurer_tax_invoice_number: '',
  insurer_commission_tax_invoice_number: '',
  update_status: '',
  send_update_type: '',
});

const dateTypesFilter = ref([
  { value: 'created_at', label: 'Created Date' },
  { value: 'payment_due_date', label: 'Payment Due Date' },
  { value: 'payment_date', label: 'Payment Date' },
  { value: 'transaction_approved_at', label: 'Transaction Approved Date' },
  { value: 'policy_booking_date', label: 'Booking Date' },
  { value: 'policy_start_date', label: 'Policy Start Date' },
  { value: 'policy_expiry_date', label: 'Policy End Date' },
]);

dateTypesFilter.value.sort((a, b) => a.label.localeCompare(b.label));

const [
  today,
  last7Days,
  last30Days,
  lastMonthStart,
  lastMonthEnd,
  thisMonthStart,
  thisMonthEnd,
] = useDateRange();

const presetDates = [
  { label: 'Today', value: [today, today] },
  { label: 'Last 7 days', value: [last7Days, today] },
  { label: 'Last 30 days', value: [last30Days, today] },
  { label: 'Last month', value: [lastMonthStart, lastMonthEnd] },
  { label: 'This month', value: [thisMonthStart, thisMonthEnd] },
];

const autoApplyDateRangeFields = [
  'insured_name',
  'member_first_name',
  'member_last_name',
  'company_name',
  'policy_number',
  'quote_status',
  'payment_status',
  'line_of_business',
  'business_insurance_type',
  'currently_insured_with',
  'department',
  'advisors',
  'update_status',
];

function updateDateRange() {
  availableFilters.date_type = 'created_at';
  availableFilters.date_range = presetDates[2].value;
}

autoApplyDateRangeFields.forEach(fields => {
  watch(
    () => availableFilters[fields],
    () => {
      if (checkAutoDateApplyFilters()) {
        updateDateRange();
      }
    },
  );
});

function checkAutoDateApplyFilters() {
  return autoApplyDateRangeFields.some(field => {
    const value = availableFilters[field];
    return Array.isArray(value)
      ? value?.length > 0
      : value !== '' && value !== undefined;
  });
}

function filterValidation(filtersCleaned) {
  if (Object.keys(filtersCleaned).length === 0) {
    notification.error({
      title: 'Please select at least one filter before performing the search',
      position: 'top',
    });
    return;
  }

  if (checkAutoDateApplyFilters()) {
    if (!availableFilters.date_type || !availableFilters.date_range) {
      notification.error({
        title: 'Date range is required for the selected filters',
        position: 'top',
      });
      return false;
    }
  }
  return true;
}

function onReset() {
  removedSavedParams();
  router.visit(route('search-leads'));
}

function checkMemberOrCompanyFilter() {
  const { member_first_name, member_last_name, company_name } =
    availableFilters;

  if (member_first_name || member_last_name || company_name) {
    let isQueryStringSet = false;
    for (const [key] of Object.entries(params)) {
      if (key === 'line_of_business') {
        isQueryStringSet = true;
        availableFilters.line_of_business = parseInt(params[key]);
      }
    }
    if (!isQueryStringSet) {
      availableFilters.line_of_business = company_name
        ? props.quoteTypeIdEnum.Business
        : props.quoteTypeIdEnum.Health;
    }
    return true;
  }

  availableFilters.line_of_business = '';
  return false;
}

function onSubmit() {
  const filtersCleaned = cleanObj(availableFilters);
  if (filterValidation(filtersCleaned)) {
    filtersCleaned.list = isSendUpdateListView.value ? 'endorsements' : 'leads';
    filtersCount.value = Object.keys(filtersCleaned).length;
    serverOptions.value.page = 1;
    filterModal.value = false;

    router.visit(route('search-leads'), {
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
  }
}

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key === 'date_range[0]' || key === 'date_range[1]') {
      availableFilters.date_range = [
        params['date_range[0]'],
        params['date_range[1]'],
      ];
    } else {
      availableFilters[key] = params[key];
    }
  }
}

function exportExcel() {
  const url = new URL(window.location.href);
  const exportFilters = url.search;
  const exportURL = '/search-all-export' + exportFilters;

  window.open(exportURL, '_blank');
}

watch(() => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('list') === 'endorsements') {
    isSendUpdateListView.value = true;
  } else {
    isSendUpdateListView.value = false;
  }
  updateTableDetails();
});

onMounted(() => {
  params = getSavedQueryParams() || params;
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Search" />
    <div class="flex justify-between items-center">
      <x-tooltip placement="bottom">
        <h2
          class="font-semibold text-gray-800 text-xl underline decoration-dotted decoration-primary-600"
        >
          {{ isSendUpdateListView ? 'Send Update' : 'Lead' }} List
        </h2>
        <template #tooltip>
          {{
            isSendUpdateListView
              ? 'Displays here are the requests created or booked under send update'
              : 'Displays here are the leads created or booked under the main lead'
          }}
        </template>
      </x-tooltip>
      <div class="space-x-3">
        <template v-if="can(permissionsEnum.DATA_EXTRACTION_SEARCH_ALL_LEADS)">
          <template v-if="!tableData?.length > 0">
            <x-tooltip placement="bottom">
              <x-button
                disabled
                size="sm"
                color="emerald"
                :loading="loader.table"
              >
                Export to Excel
              </x-button>
              <template #tooltip>
                Perform a search first to display results. Export to Excel will
                be available once results are shown
              </template>
            </x-tooltip>
          </template>
          <template v-else>
            <x-button
              size="sm"
              color="emerald"
              :loading="loader.table"
              @click="exportExcel()"
            >
              Export to Excel
            </x-button>
          </template>
        </template>
        <x-button
          size="sm"
          color="primary"
          @click="listChange()"
          :loading="loader.table"
        >
          {{ isSendUpdateListView ? 'Lead' : 'Send Update' }} List
        </x-button>
        <x-button
          size="sm"
          color="orange"
          @click.prevent="filterModal = true"
          :loading="loader.table"
        >
          <x-icon
            icon="magnifyingGlass"
            size="sm"
            class="transition transform duration-300"
          />
          Search Filters
        </x-button>
      </div>
    </div>
    <x-divider class="my-4" />
    <DataTable
      table-class-name="table-fixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="tableData || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-header
    >
      <!-- Datatable Header Tooltips Start -->
      <template #header-code="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip>
            {{
              isSendUpdateListView
                ? 'A unique reference identifier assigned to each "Send Update" request, allowing for easy tracking and reference.'
                : 'Ref ID of the lead/policy'
            }}
          </template>
        </x-tooltip>
      </template>

      <template #header-quote_type_id="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip>Line of business of the lead</template>
        </x-tooltip>
      </template>

      <template #header-business_insurance_type="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip
            >The business insurance type (i.e. property, holiday homes, etc).
            This is only applicable for Business/Corpline</template
          >
        </x-tooltip>
      </template>

      <template v-if="isSendUpdateListView" #header-created_at="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip>
            The date when the "Send Update" request was created. It indicates
            when the action was initiated
          </template>
        </x-tooltip>
      </template>

      <template v-if="!isSendUpdateListView" #header-policy_number="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip>The policy number of the lead</template>
        </x-tooltip>
      </template>

      <template v-if="!isSendUpdateListView" #header-quote_status="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip>The status of the lead</template>
        </x-tooltip>
      </template>

      <template v-if="isSendUpdateListView" #header-status="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip
            >The current status of the "Send Update" request, indicating whether
            it is pending, transaction approved, or declined, among other
            possible states.
          </template>
        </x-tooltip>
      </template>

      <template v-if="isSendUpdateListView" #header-category_id="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip
            >The type of "Send Update" request, categorizing the nature of the
            action being taken.</template
          >
        </x-tooltip>
      </template>

      <template v-if="isSendUpdateListView" #header-option_id="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted">
            {{ header.text }}
          </p>
          <template #tooltip
            >A further classification of the "Send Update" request, providing
            additional context or details.</template
          >
        </x-tooltip>
      </template>
      <!-- Datatable Header Tooltips End -->

      <template
        #item-code="{
          code,
          uuid,
          quote_type_id,
          quote_uuid,
          business_type_of_insurance_id,
        }"
      >
        <Link
          :href="
            isSendUpdateListView
              ? getSendUpdatePageRoute(uuid, [
                  quote_uuid,
                  quote_type_id,
                  business_type_of_insurance_id,
                ])
              : getDetailPageRoute(
                  uuid,
                  quote_type_id,
                  business_type_of_insurance_id,
                )
          "
          class="text-primary-500 hover:underline flex items-center space-x-1"
        >
          <span>{{ code }}</span>
        </Link>
      </template>
      <template #item-company_name="{ company_name }">
        {{ company_name ?? 'N/A' }}
      </template>
      <template
        #item-business_insurance_type="{
          quote_type_id,
          business_insurance_type,
        }"
      >
        {{
          quote_type_id === props.quoteTypeIdEnum.Business
            ? business_insurance_type
            : 'N/A'
        }}
      </template>
      <template #item-created_at="{ created_at }">
        {{ created_at ? dateFormat(created_at) : 'N/A' }}
      </template>
      <template #item-policy_expiry_date="{ policy_expiry_date }">
        {{ policy_expiry_date ? dateFormat(policy_expiry_date) : 'N/A' }}
      </template>
      <template #item-policy_number="{ policy_number }">
        {{ policy_number ?? 'N/A' }}
      </template>
      <template
        v-if="!isSendUpdateListView"
        #item-quote_status="{ quote_status, policy_number }"
      >
        {{ policy_number == null ? 'N/A' : quote_status }}
      </template>
      <template v-if="isSendUpdateListView" #item-notes="{ notes }">
        <div class="flex gap-2 cursor-pointer">
          <p
            class="overflow-hidden h-auto"
            :class="expandNotes ? 'overflow-auto' : 'truncate w-60'"
          >
            <span v-if="expandNotes" class="whitespace-normal">{{
              notes
            }}</span>
            <span v-else class="whitespace-nowrap">{{ notes }}</span>
          </p>
          <x-icon
            v-if="notes && notes.length > 40"
            @click="expandNotes = !expandNotes"
            icon="chevronDown"
            :class="{ 'rotate-180': expandNotes }"
          />
        </div>
      </template>
      <template v-if="isSendUpdateListView" #item-status="{ status }">
        {{ statusTitleFormat(status) }}
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: leadsOrEndorsementData.next_page_url,
        prev: leadsOrEndorsementData.prev_page_url,
        current: leadsOrEndorsementData.current_page,
        from: leadsOrEndorsementData.from,
        to: leadsOrEndorsementData.to,
      }"
    />

    <x-modal
      v-model="filterModal"
      size="lg"
      title="Search Filters"
      show-close
      backdrop
    >
      <x-form :auto-focus="false">
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
              v-model="availableFilters.code"
              type="search"
              name="code"
              class="w-full"
              placeholder="Search by Ref-ID"
            />
          </div>
          <x-field label="Insured Name">
            <x-input
              v-model="availableFilters.insured_name"
              type="search"
              name="insured_name"
              class="w-full"
              placeholder="Search by Insured Name"
            />
          </x-field>
          <x-field label="Member First Name">
            <x-input
              v-model="availableFilters.member_first_name"
              type="search"
              name="member_first_name"
              class="w-full"
              placeholder="Search by Member First Name"
            />
          </x-field>
          <x-field label="Member Last Name">
            <x-input
              v-model="availableFilters.member_last_name"
              type="search"
              name="member_last_name"
              class="w-full"
              placeholder="Search by Member Last Name"
            />
          </x-field>
          <x-field label="Company Name">
            <x-input
              v-model="availableFilters.company_name"
              type="search"
              name="company_name"
              class="w-full"
              placeholder="Search by Company Name"
            />
          </x-field>
          <x-field label="Policy Number">
            <x-input
              v-model="availableFilters.policy_number"
              type="search"
              name="policy_number"
              class="w-full"
              placeholder="Search by Policy Number"
            />
          </x-field>
          <x-field label="Mobile Number">
            <x-input
              v-model="availableFilters.mobile_no"
              type="search"
              name="mobile_no"
              class="w-full"
              placeholder="Search by Mobile Number"
            />
          </x-field>
          <x-field label="Email">
            <x-input
              v-model="availableFilters.email"
              type="search"
              name="email"
              class="w-full"
              placeholder="Search by Email"
            />
          </x-field>
          <x-field label="SU Ref-ID">
            <x-input
              v-model="availableFilters.su_code"
              type="search"
              name="su_code"
              class="w-full"
              placeholder="Search by SU Ref-ID"
            />
          </x-field>
          <x-field label="Search By Date Type">
            <x-select
              v-model="availableFilters.date_type"
              placeholder="Search By Date Type"
              :options="dateTypesFilter"
              class="w-full"
            />
          </x-field>
          <x-field label="Date Range">
            <DatePicker
              v-model="availableFilters.date_range"
              :disabled="!availableFilters.date_type"
              range
              :max-range="365"
              size="sm"
              placeholder="Select Date Range"
              model-type="yyyy-MM-dd"
              :preset-dates="presetDates"
              :helper="
                availableFilters.date_type
                  ? ''
                  : 'Please select the date type first'
              "
            />
          </x-field>
          <x-field label="Lead Status">
            <ComboBox
              v-model="availableFilters.quote_status"
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
          <x-field label="Payment Status">
            <ComboBox
              v-model="availableFilters.payment_status"
              name="payment_status"
              placeholder="Search by Payment Status"
              :options="
                paymentStatuses.map(item => ({
                  value: item.id,
                  label: statusTitleFormat(item.text),
                }))
              "
            />
          </x-field>
          <x-field label="Line of Business" v-if="checkMemberOrCompanyFilter()">
            <x-select
              v-model="availableFilters.line_of_business"
              placeholder="Search by Line of Business"
              :options="
                quoteTypes.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
              class="w-full"
            />
          </x-field>
          <x-field label="Line of Business" v-else>
            <ComboBox
              v-model="availableFilters.line_of_business"
              name="line_of_business"
              placeholder="Search by Line of Business"
              :options="
                quoteTypes.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
            />
          </x-field>
          <x-field label="Business Insurance Type">
            <ComboBox
              v-model="availableFilters.business_insurance_type"
              name="business_insurance_type"
              placeholder="Search by Business Insurance Type"
              :options="
                businessInsuranceTypes.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
            />
          </x-field>
          <x-field label="Currently Insured with">
            <ComboBox
              v-model="availableFilters.currently_insured_with"
              name="currently_insured_with"
              placeholder="Search by Currently Insured with"
              :options="
                insuranceProviders.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
            />
          </x-field>
          <x-field label="Department">
            <ComboBox
              v-model="availableFilters.department"
              name="department"
              placeholder="Search by Department"
              :options="
                departments.map(item => ({
                  value: item.id,
                  label: item.name,
                }))
              "
            />
          </x-field>
          <x-field label="Advisor">
            <ComboBox
              v-model="availableFilters.advisors"
              name="advisors"
              placeholder="Search by Advisor"
              :options="
                advisors.map(item => ({
                  value: item.id,
                  label: item.name,
                }))
              "
            />
          </x-field>
          <x-field
            v-if="can(permissionsEnum.SEARCH_INSURER_TAX_INVOICE_NUMBER)"
            label="Insurer Tax Invoice No"
          >
            <x-input
              v-model="availableFilters.insurer_tax_invoice_number"
              type="search"
              name="insurer_tax_invoice_number"
              class="w-full"
              placeholder="Search by Insurer Tax Invoice No"
            />
          </x-field>
          <x-field
            v-if="
              can(permissionsEnum.SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER)
            "
            label="Insurer Commission Tax Invoice No"
            class="!text-xs"
          >
            <x-input
              v-model="availableFilters.insurer_commission_tax_invoice_number"
              type="search"
              name="insurer_commission_tax_invoice_number"
              class="w-full"
              placeholder="Search by Insurer Commission Tax Invoice No"
            />
          </x-field>
          <x-field v-if="isSendUpdateListView" label="Update Status">
            <ComboBox
              v-model="availableFilters.update_status"
              placeholder="Search By Update Status"
              :options="
                sendUpdateStatuses.map(item => ({
                  value: item,
                  label: statusTitleFormat(item),
                }))
              "
            />
          </x-field>
          <x-field v-if="isSendUpdateListView" label="Send Update Type">
            <ComboBox
              v-model="availableFilters.send_update_type"
              placeholder="Search By Send Update Type"
              :options="
                sendUpdateTypes.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
            />
          </x-field>
        </div>
      </x-form>
      <template #primary-action>
        <x-button
          size="sm"
          color="primary"
          @click="filterModal = false"
          @click.prevent="onReset"
          >Reset</x-button
        >
      </template>
      <template #secondary-action>
        <x-button size="sm" color="orange" @click.prevent="onSubmit"
          >Search</x-button
        >
      </template>
    </x-modal>
  </div>
</template>
