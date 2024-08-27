<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  quoteType: {
    type: String,
    default: 'yacht',
  },
  totalCount: {
    type: Number,
    default: 0,
  },
});

const page = usePage();
const hasAnyRole = role => useHasAnyRole(role);
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
  created_at_start: '',
  created_at_end: '',
  renewal_batch: '',
  previous_quote_policy_number: '',
  is_ecommerce: '',
  quote_status_id: '',
  page: 1,
  previous_quote_policy_number_text: '',
  payment_status: [],
  is_cold: '',
  stale_at: '',
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
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
  { text: 'LEAD STATUS', value: 'quote_status', is_active: true },
  { text: 'ADVISOR', value: 'advisor', is_active: true },
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
  { text: 'PRICE', value: 'price_with_vat', is_active: true, sortable: true },
  { text: 'POLICY NO', value: 'policy_number', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  {
    text: 'CURRENTLY INSURED WITH',
    value: 'currently_insured_with',
    is_active: true,
  },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce', is_active: true },
  {
    text: 'Previous Policy Number',
    value: 'previous_quote_policy_number',
    is_active: true,
  },
  { text: 'Renewal Batch', value: 'renewal_batch', is_active: true },
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

    router.visit(route('yacht-quotes-list'), {
      method: 'get',
      data: {
        ...filtersCleaned,
        ...serverOptions.value,
      },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  removedSavedParams();
  router.visit(route('yacht-quotes-list'), {
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

  filters.is_cold = selectedFilters.cold ? '1' : '';
  filters.stale_at = selectedFilters.stale ? '0' : '';

  onSubmit(true);
};

const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'yacht');
  window.open(url + '?' + new URLSearchParams(data).toString());
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
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const onLeadAssigned = () => {
  quotesSelected.value = [];
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  params = getSavedQueryParams() || params;
  setQueryStringFilters();

  if (hasRole(rolesEnum.YachtManager) || hasRole(rolesEnum.Admin)) {
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
const formatDate = date => {
  if (!date) return '';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(date).toLocaleDateString('en-GB', options);
};
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
    <Head title="Yacht Quotes" />

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Yacht Quotes List</h2>
        <!-- PD Revert
          <LeadsCount
          :leadsCount="$page.props.totalCount"
          :key="$page.props.totalCount"
        /> -->
      </template>
      <template #default>
        <ColumnSelection
          v-model:columns="tableHeader"
          storage-key="yacht-list"
        />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />

        <Link :href="route('yacht-quotes-card')">
          <x-button
            size="sm"
            color="#1d83bc"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            Cards View
          </x-button>
        </Link>
        <div v-if="readOnlyMode.isDisable === true">
          <x-button
            v-if="can(permissionsEnum.YachtQuotesCreate)"
            size="sm"
            color="#ff5e00"
            :href="route('yacht-quotes-create')"
          >
            Create Lead
          </x-button>
        </div>
      </template>
    </StickyHeader>
    <!-- <div class="flex justify-between items-center">
      <div class="flex items-center gap-5">
        <h2 class="text-xl font-semibold">Yacht Quotes List</h2>
        <LeadsCount :leadsCount="$page.props.totalCount" />
      </div>

      <div class="flex items-center space-x-2">
        <ColumnSelection
          v-model:columns="tableHeader"
          storage-key="yacht-list"
        />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />

        <Link :href="route('yacht-quotes-card')">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>
        <x-button
          v-if="can(permissionsEnum.YachtQuotesCreate)"
          size="sm"
          color="#ff5e00"
          :href="route('yacht-quotes-create')"
        >
          Create Lead
        </x-button>
      </div>
    </div> -->
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
        <x-field label="Created Date Start">
          <DatePicker
            v-model="filters.created_at_start"
            type="date"
            name="created_at_start"
            class="w-full"
          />
        </x-field>
        <x-field label="Created Date End">
          <DatePicker
            v-model="filters.created_at_end"
            type="date"
            name="created_at_end"
            class="w-full"
          />
        </x-field>
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
        <x-field
          label="Advisor"
          v-if="
            !hasAnyRole([
              rolesEnum.YachtAdvisor,
              rolesEnum.YachtNewBusinessAdvisor,
              rolesEnum.YachtRenewalAdvisor,
            ])
          "
        >
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptionsFilter"
          />
        </x-field>
        <x-field label="Is E-Commerce">
          <x-select
            v-model="filters.is_ecommerce"
            placeholder="Search by Ecommerce"
            :options="[
              { value: '', label: 'All' },
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-field label="Renewal Batch">
          <x-input
            v-model="filters.renewal_batch"
            type="search"
            name="renewal_batch"
            class="w-full"
            placeholder="Search by Renewal Batch"
          />
        </x-field>
        <x-field label="Renewal">
          <x-select
            v-model="filters.previous_quote_policy_number"
            placeholder="Search by Renewal"
            :options="[
              { value: '', label: 'All' },
              { value: 0, label: 'Yes' },
              { value: 1, label: 'No' },
            ]"
            class="w-full"
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
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div v-if="can(permissionsEnum.DATA_EXTRACTION)">
          <x-button
            v-if="canExport"
            size="sm"
            color="emerald"
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
      fixed-checkbox
    >
      <template #item-uuid="{ code, uuid, stale_at }">
        <Link
          v-if="can(permissionsEnum.YachtQuotesShow)"
          :href="route('yacht-quotes-show', uuid)"
          class="text-primary-500 hover:underline flex items-center space-x-1"
        >
          <span>{{ code }}</span>
          <StaleLeadsBadge :date="stale_at" :align="`left`" />
        </Link>
        <span v-else>{{ code }}</span>
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
