<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

const props = defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  renewalBatches: Array,
  quoteType: {
    type: String,
    default: 'pet',
  },
  totalCount: {
    type: Number,
    default: 0,
  },
  authorizedDays: Number,
});

const page = usePage();

const hasRole = role => useHasRole(role);

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
  quote_status: [],
  advisor_id: [],
  is_ecommerce: '',
  is_renewal: '',
  renewal_batch_id: [],
  page: 1,
  previous_quote_policy_number_text: '',
  renewal_batch: '',
  payment_status: [],
  is_cold: '',
  stale_at: '',
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  last_modified_date: '',
  advisor_assigned_date: null,
  insurer_tax_number: '',
  insurer_commmission_invoice_number: '',
};

const canExport = ref(false);
const filters = reactive(availableFilters);

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
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  { text: 'TYPE OF PET', value: 'type_of_pet', is_active: true },
  { text: 'BREED OF PET', value: 'breed_of_pet1', is_active: true },
  { text: 'AGE OF PET', value: 'age_of_pet', is_active: true },
  { text: 'IS NEUTERED', value: 'is_neutered', is_active: true },
  { text: 'IS MICROCHIPPED', value: 'is_microchipped', is_active: true },
  { text: 'MICROCHIP NO', value: 'microchip_no', is_active: true },
  { text: 'IS MIXED BREED', value: 'is_mixed_breed', is_active: true },
  { text: 'HAS INJURY', value: 'has_injury', is_active: true },
  { text: 'ACCOMMODATION TYPE', value: 'accommodation_type', is_active: true },
  { text: 'POSSESION TYPE', value: 'possesion_type', is_active: true },
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
  { text: 'Renewal Batch', value: 'renewal_batch_model', is_active: true },
]);

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

    router.visit(route('pet-quotes-list'), {
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
  router.visit(route('pet-quotes-list'), {
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

const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const role = [rolesEnum.Admin, rolesEnum.PetManager];
const petManagerRole = [rolesEnum.PetManager];

const hasAnyRole = role => useHasAnyRole(role);

const isManualAllocationAllowed = ref(false);
const isManager = ref(false);

isManualAllocationAllowed.value = hasAnyRole(role);
isManager.value = hasAnyRole(petManagerRole);

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

const quotesSelected = ref([]);

const exportLoader = ref(false);
const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'pet');
  const payload = {
    quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Pet'),
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
</script>

<template>
  <div>
    <Head title="Pet Quotes" />

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Pet Quotes List</h2>
        <!-- PD Revert
          <LeadsCount
          :leadsCount="$page.props.totalCount"
          :key="$page.props.totalCount"
        /> -->
      </template>
      <template #default>
        <ColumnSelection v-model:columns="tableHeader" storage-key="pet-list" />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />
        <Link :href="route('pet-quotes-card')">
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
            v-if="can(permissionsEnum.PetQuotesCreate)"
            size="sm"
            color="#ff5e00"
            :href="route('pet-quotes-create')"
          >
            Create Lead
          </x-button>
        </div>
      </template>
    </StickyHeader>

    <!-- <div class="flex justify-between items-center">
      <div class="flex items-center gap-5">
        <h2 class="text-xl font-semibold">Pet Quotes List</h2>
        <LeadsCount :leadsCount="$page.props.totalCount" />
      </div>
      <div class="flex items-center space-x-2">
        <ColumnSelection v-model:columns="tableHeader" storage-key="pet-list" />

        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />
        <Link :href="route('pet-quotes-card')">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>
        <x-button
          v-if="can(permissionsEnum.PetQuotesCreate)"
          size="sm"
          color="#ff5e00"
          :href="route('pet-quotes-create')"
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
            name="created_at_start"
            class="w-full"
          />
        </x-field>
        <x-field label="Created Date End">
          <DatePicker
            v-model="filters.created_at_end"
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
            !hasAnyRole([rolesEnum.PetAdvisor, rolesEnum.PetRenewalAdvisor])
          "
        >
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptions"
          />
        </x-field>
        <x-field label="Renewal">
          <x-select
            v-model="filters.is_renewal"
            placeholder="Search by Renewal"
            :options="[
              { value: '', label: 'All' },
              { value: 'Yes', label: 'Yes' },
              { value: 'No', label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-field label="Is Ecommerce">
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
        <x-input
          v-model="filters.previous_quote_policy_number_text"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
        />
        <x-field label="Renewal Batch">
          <ComboBox
            v-model="filters.renewal_batch_id"
            placeholder="Search by Renewal Batch"
            :options="renewalBatchOptions"
          />
        </x-field>

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
        <DatePicker
          v-if="hasRole(rolesEnum.PetManager)"
          v-model="filters.advisor_assigned_date"
          name="created_at_start"
          label="Advisor Assigned Date"
          range
          format="dd-MM-yyyy"
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
        v-if="quotesSelected.length > 0 && isManualAllocationAllowed"
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
    >
      <template #item-uuid="{ code, uuid, stale_at }">
        <Link
          v-if="
            canAny([
              permissionsEnum.PetQuotesShow,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="route('pet-quotes-show', uuid)"
          class="text-primary-500 hover:underline flex items-center space-x-1"
        >
          <span>{{ code }} </span>
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

      <template #item-price_with_vat="{ price_with_vat }">
        <span>{{ price_with_vat }}</span>
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

      <template #item-transapp_code="{ quote_detail }">
        {{ quote_detail?.transapp_code }}
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
      </template>

      <template #item-currently_insured_with="{ currently_insured_with }">
        {{ currently_insured_with?.text }}
      </template>

      <template #item-policy_number="{ policy_number }">
        {{ policy_number }}
      </template>
      <template #item-type_of_pet="{ pet_quote }">
        {{ pet_quote?.pet_type?.text }}
      </template>
      <template #item-age_of_pet="{ pet_quote }">
        {{ pet_quote?.pet_age?.text }}
      </template>
      <template #item-is_neutered="{ pet_quote }">
        {{ pet_quote?.is_neutered ? 'Yes' : 'No' }}
      </template>

      <template #item-breed_of_pet1="{ pet_quote }">
        {{ pet_quote?.breed_of_pet1 }}
      </template>

      <template #item-is_microchipped="{ pet_quote }">
        {{ pet_quote?.is_microchipped ? 'Yes' : 'No' }}
      </template>
      <template #item-microchip_no="{ pet_quote }">
        {{ pet_quote?.microchip_no }}
      </template>
      <template #item-is_mixed_breed="{ pet_quote }">
        {{ pet_quote?.is_mixed_breed ? 'Yes' : 'No' }}
      </template>
      <template #item-has_injury="{ pet_quote }">
        {{ pet_quote?.has_injury ? 'Yes' : 'No' }}
      </template>
      <template #item-accommodation_type="{ pet_quote }">
        {{ pet_quote?.accomodation_type?.text }}
      </template>
      <template #item-possesion_type="{ pet_quote }">
        {{ pet_quote?.possession_type?.text }}
      </template>
      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          {{ is_ecommerce ? 'Yes' : 'No' }}
        </div>
      </template>
      <template
        #item-previous_quote_policy_number="{ previous_quote_policy_number }"
      >
        {{ previous_quote_policy_number ?? 'N/A' }}
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
