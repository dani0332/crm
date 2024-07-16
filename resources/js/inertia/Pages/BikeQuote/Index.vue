<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  quoteType: {
    type: String,
    default: 'bike',
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
  renewal_batch: '',
  previous_quote_policy_number: '',
  is_ecommerce: '',
  quote_status_id: '',
  advisor_id: [],
  page: 1,
  previous_quote_policy_number_text: '',
  payment_due_date:"",
  booking_date: ""
};
const canExport = ref(false);
const permissionAssignLeads = ref(false);
const filters = reactive(availableFilters);
const hasRole = role => useHasRole(role);

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});
function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    // /personal-quotes/bike'
    router.visit(route('bike-quotes-list'), {
      method: 'get',
      data: filters,
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

onMounted(() => {
  setQueryStringFilters();
  if (hasRole(rolesEnum.BikeManager) || hasRole(rolesEnum.Admin)) {
    permissionAssignLeads.value = true;
  }
});

const tableHeader = [
  { text: 'Ref-ID', value: 'uuid' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'DOB', value: 'dob' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'PRICE', value: 'premium' },
  { text: 'POLICY NO', value: 'policy_number' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'Previous Policy Number', value: 'previous_quote_policy_number' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

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

const onDataExport = () => {
  const data = useObjToUrl(filters);
  const url = route('data-extraction', 'bike');
  window.open(url + '?' + new URLSearchParams(data).toString());
};

watch(
  () => filters,
  () => {
    if (filters.created_at_start && filters.created_at_end) {
      canExport.value = true;
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);
const resetDateFilters = (filterName) => {
  if (filterName === 'payment_due_date') {
    filters.created_at_start = '';
    filters.created_at_end = '';
    filters.booking_date = '';
  } else if (filterName === 'booking_date') {
    filters.payment_due_date = '';
    filters.created_at_start = '';
    filters.created_at_end = '';
  } else if (filterName === 'created_at_start' || filterName === 'created_at_end') {
    filters.booking_date = '';
    filters.payment_due_date = '';
  }
};
watch(() => filters.payment_due_date, (newValue) => {
  if (newValue) {
    resetDateFilters('payment_due_date');
  }
});

watch(() => filters.booking_date, (newValue) => {
  if (newValue) {
    resetDateFilters('booking_date');
  }
});

watch(() => filters.created_at_start, (newValue) => {
  if (newValue) {
    resetDateFilters('created_at_start');
  }
});

watch(() => filters.created_at_end, (newValue) => {
  if (newValue) {
    resetDateFilters('created_at_end');
  }
});
</script>

<template>
  <div>
    <Head title="Bike Quotes" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Bike Quotes List</h2>
      <x-button
        v-if="can(permissionsEnum.BikeQuotesCreate)"
        size="sm"
        color="#ff5e00"
        :href="route('bike-quotes-create')"
      >
        <!-- href="/personal-quotes/bike/create" -->
        Create Lead
      </x-button>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
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
        <x-field label="Advisor">
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptionsFilter"
          />
        </x-field>
        <x-field label="Is Ecommerce">
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
        <x-field label="Is Renewal">
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
          :quoteType="quoteType"
          @success="onLeadAssigned"
        />
      </div>
    </Transition>

    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-uuid="{ code, uuid }">
        <!-- :href="`/personal-quotes/bike/${uuid}`" -->
        <Link
          v-if="can(permissionsEnum.BikeQuotesShow)"
          class="text-primary-500 hover:underline"
          :href="route('bike-quotes-show', uuid)"
        >
          {{ code }}
        </Link>
        <span v-else>{{ code }}</span>
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
