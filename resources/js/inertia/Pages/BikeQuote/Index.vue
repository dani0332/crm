<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';
import CreateLeadModal from '../../Components/CreateLeadModal.vue';

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  renewalBatches: Array,
  quoteType: {
    type: String,
    default: 'bike',
  },
  authorizedDays: Number,
  insurerAMLStatus: Array,
  subSources: { type: Array, default: () => [] },
});
const notification = useNotifications('toast');
const cleanObj = obj => useCleanObj(obj);
const page = usePage();
const teamNamesEnum = page.props.teamNamesEnum;
const loader = reactive({
  table: false,
  export: false,
});

const createLeadModal = ref(false);

const onLeadConfirmed = () => {
  createLeadModal.value = false;
};

const serverOptions = ref({
  page: 1,
  sortType: 'desc',
});

let availableFilters = {
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  renewal_batch_id: [],
  previous_quote_policy_number: '',
  is_ecommerce: '',
  quote_status_id: '',
  insurer_aml_status: [],
  advisor_id: [],
  page: 1,
  previous_quote_policy_number_text: '',
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  insurer_tax_number: '',
  insurer_commmission_invoice_number: '',
  advisor_assigned_date: [],
  private_client: 'all',
  authorize_date: '',
  captured_date: '',
  ea_model: '',
  lead_generator: '',
};
const canExport = ref(false);
const permissionAssignLeads = ref(false);
const filters = reactive(availableFilters);
const eaModelOptions = [{ value: 'referral', label: 'Referral' }, { value: 'collaborate', label: 'Collaborate' }];
const hasRole = role => useHasRole(role);

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const renewalBatchOptions = computed(() => {
  return page.props.renewalBatches.map(renewalBatch => ({
    value: renewalBatch.id,
    label: renewalBatch.name,
  }));
});

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

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    serverOptions.value.page = 1;
    // /personal-quotes/bike'
    router.visit(route('bike-quotes-list'), {
      method: 'get',
      data: { ...filters, ...serverOptions.value },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}
// '/personal-quotes/bike'
function onReset() {
  router.visit(route('bike-quotes-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  for (const [key] of Object.entries(availableFilters)) {
    if (urlParams.has(key)) {
      filters[key] = urlParams.get(key);
    }
  }
}
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  setQueryStringFilters();
  if (hasRole(rolesEnum.BikeManager) || hasRole(rolesEnum.Admin)) {
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

  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const tableHeader = [
  { text: 'Ref-ID', value: 'uuid' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'PAYMENT AUTHORISED DATE', value: 'authorized_at' },
  { text: 'PAYMENT EXPIRY', value: 'expiry_date' },
  { text: 'DOB', value: 'dob' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'INSURER AML STATUS', value: 'insurer_aml_status_display' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'BRANCH', value: 'branch_name' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  {
    text: 'POLICY EXPIRY DATE',
    value: 'previous_policy_expiry_date',
    sortable: true,
  },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NO', value: 'policy_number' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
  {
    text: 'Previous Policy Premium',
    value: 'previous_quote_policy_premium',
    sortable: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch_model' },
  { text: 'Private Client', value: 'customer.pcp_tag_formatted' },
  { text: 'IMCRM SUB-SOURCE', value: 'sub_source' },
  { text: 'EA MODEL', value: 'ea_model' },
  { text: 'LEAD GENERATOR', value: 'lead_generator' },
];

const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const isPcpSubSourceOptionAllowed = ref(
  useHasRole(rolesEnum.Admin) || useHasAnyTeam([{ name: teamNamesEnum.PCP }]),
);

const advisorOptionsFilter = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
});

const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);

const onLeadAssigned = () => {
  quotesSelected.value = [];
};

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
  const url = route('data-extraction', 'bike');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Bike'),
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

const formatDate = dateString => useDateFormat(dateString, 'DD-MMM-YYYY').value;

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
</script>

<template>
  <div>
    <Head title="Bike Quotes" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Bike Quotes List</h2>
      <div v-if="readOnlyMode.isDisable === true">
        <x-button
          v-if="can(permissionsEnum.BikeQuotesCreate)"
          size="sm"
          color="#ff5e00"
          @click="createLeadModal = true"
        >
          <!-- href="/personal-quotes/bike/create" -->
          Create Lead
        </x-button>
      </div>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
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
          class="w-full"
          label="Created Date Start"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          class="w-full"
          label="Created Date End"
        />
        <DatePicker
          v-model="filters.advisor_assigned_date"
          name="created_at_start"
          label="Advisor Assigned Date"
          range
          format="dd-MM-yyyy"
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
          v-model="filters.advisor_id"
          name="advisor_id"
          placeholder="Search by Advisor"
          :options="advisorOptionsFilter"
          class="w-full"
          filterable
          label="Advisor"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.advisor_id = advisorOptionsFilter.map(
                  item => item.value,
                )
              "
              @clear="filters.advisor_id = []"
            />
          </template>
        </x-select>
        <x-select
          v-model="filters.is_ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          label="Is Ecommerce"
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

        <x-select
          v-model="filters.previous_quote_policy_number"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 0, label: 'Yes' },
            { value: 1, label: 'No' },
          ]"
          class="w-full"
          label="Is Renewal"
        />
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
        <x-select
          label="EA Model"
          v-model="filters.ea_model"
          placeholder="All Models"
          :options="eaModelOptions"
          class="w-full"
        />
        <x-input
          v-model="filters.lead_generator"
          type="search"
          name="lead_generator"
          label="Lead Generator"
          class="w-full"
          placeholder="Search by lead generator name"
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
            @click.prevent="onDataExport"
            class="justify-self-start mr-3"
            :loading="exportLoader"
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
      <template #item-uuid="{ code, uuid }">
        <!-- :href="`/personal-quotes/bike/${uuid}`" -->
        <Link
          v-if="
            canAny([
              permissionsEnum.BikeQuotesShow,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          class="text-primary-500 hover:underline"
          :href="route('bike-quotes-show', uuid)"
        >
          {{ code }}
        </Link>
        <span v-else>{{ code }}</span>
      </template>
      <template #item-authorized_at="item">
        <p v-if="item?.payments[0]?.payment_status_id === 4">
          {{ item?.payments[0]?.authorized_at }}
        </p>
      </template>
      <template #item-expiry_date="item">
        <p v-if="item?.payments[0]?.payment_status_id === 4">
          {{ daysAgoFromAuthorizedDate(item.payments[0].authorized_at) }}
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
      <template #item-sub_source="item">
        <p>
          {{ item?.sub_source?.text ?? '' }}
        </p>
      </template>
      <template #item-ea_model="item">
        <span class="capitalize">{{ item.ea_model }}</span>
      </template>
      <template #item-lead_generator="item">
        {{ item.lead_generator?.name }}
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
      route-name="bike-quotes-create"
      :sub-sources="subSources"
      :is-pcp-allowed="isPcpSubSourceOptionAllowed"
      @confirmed="onLeadConfirmed"
    />
  </div>
</template>
