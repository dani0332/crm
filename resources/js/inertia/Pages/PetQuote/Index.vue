<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  quoteType: {
    type: String,
    default: 'pet',
  },
  totalCount: {
    type: Number,
    default: 0,
  },
});

const page = usePage();

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
  page: 1,
  previous_quote_policy_number_text: '',
  renewal_batch: '',
  payment_status: [],
  is_cold: '',
  stale_at: '',
};

const canExport = ref(false);
const filters = reactive(availableFilters);

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
  { text: 'TRANSAPP CODE', value: 'transapp_code', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'LOST REASON', value: 'lost_reason', is_active: true },
  { text: 'PRICE', value: 'premium', is_active: true },
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
  { text: 'Renewal Batch', value: 'renewal_batch', is_active: true },
]);

function onSubmit(isValid) {
  if (isValid) {
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

const quotesSelected = ref([]);

const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'pet');
  window.open(url + '?' + new URLSearchParams(data).toString());
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
</script>

<template>
  <div>
    <Head title="Pet Quotes" />

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Pet Quotes List</h2>
        <LeadsCount
          :leadsCount="$page.props.totalCount"
          :key="$page.props.totalCount"
        />
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
          <x-tooltip position="bottom">
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
        <x-field
          label="Advisor"
          v-if="
            !hasAnyRole([
              rolesEnum.PetAdvisor,
              rolesEnum.PetRenewalAdvisor,
              rolesEnum.PetNewBusinessAdvisor,
            ])
          "
        >
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptions"
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
          label="Previous Policy Number"
          class="w-full"
          placeholder="Search by Previous Policy Number"
        />
        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
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
          <x-tooltip v-else position="right">
            <x-button tag="div" size="sm" color="emerald"> Export </x-button>
            <template #tooltip>
              <span class="font-medium">
                Created dates are required to export data.
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
      fixed-checkbox
    >
      <template #item-uuid="{ code, uuid, stale_at }">
        <Link
          v-if="can(permissionsEnum.PetQuotesShow)"
          :href="route('pet-quotes-show', uuid)"
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

      <template #item-transapp_code="{ quote_detail }">
        {{ quote_detail?.transapp_code }}
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
      </template>

      <template #item-currently_insured_with="{ currently_insured_with }">
        {{ currently_insured_with?.text }}
      </template>

      <template #item-policy_number="{ pet_quote }">
        {{ pet_quote?.policy_number }}
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
