<script setup>
const props = defineProps({
  quoteStatusEnum: Object,
  quoteTypeId: String,
  lostReasons: Object,
  quoteType: String,
  totalCount: {
    type: Number,
    default: 0,
  },
  leadStatuses: Array,
  advisors: Array,
  teams: Object,
  areBothTeamsPresent: Boolean,
  is_renewal: String,
});

const page = usePage();
const notification = useNotifications('toast');
provide('quoteStatusEnum', props.quoteStatusEnum);
provide('quoteTypeId', props.quoteTypeId);
provide('lostReasons', props.lostReasons);
provide('quoteType', props.quoteType);

const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

const previousDate = getPreviousDate;
const leadsCount = ref(props.totalCount);

const loader = reactive({
  request: false,
});

const params = useUrlSearchParams('history');
const cleanObj = obj => useCleanObj(obj);
const showFilters = ref(false);
const filtersCount = ref(0);

const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;

const isAllowed = computed(() => {
  return !hasAnyRole([
    rolesEnum.RMAdvisor,
    rolesEnum.EBPAdvisor,
    rolesEnum.HealthRenewalAdvisor,
    rolesEnum.HealthAdvisor,
  ]);
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  sub_team: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  is_renewal: props.is_renewal,
  previous_quote_policy_number: '',
  renewal_batch: '',
  date: null,
  assigned_to_date_start: '',
  assigned_to_date_end: '',
  payment_status: [],
  is_cold: false,
  is_stale: false,
  status_filters: null,
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  last_modified_date: null,
});

provide('filters', filters);

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const modifiedAdvisorOptions = ref([]);

modifiedAdvisorOptions.value = advisorOptions.value;

modifiedAdvisorOptions.value.push({
  value: 'unassigned',
  label: 'Unassigned',
});

const subTeamOptions = [
  { value: '', label: 'All' },
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

const assignmentTypeOptions = [
  { value: '', label: 'Please select is assignment type' },
  { value: 1, label: 'System Assigned' },
  { value: 2, label: 'System ReAssigned' },
  { value: 3, label: 'Manual Assigned' },
  { value: 4, label: 'Manual ReAssigned' },
];

const subTeamsOptions = [
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

const serverOptions = ref({
  page: 1,
  sortBy: 'created_at',
  sortType: 'desc',
});

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

  filters.is_cold = selectedFilters.cold;
  filters.is_stale = selectedFilters.stale;

  onSubmit(true);
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
    serverOptions.value.page = 1;

    const filtersCleaned = cleanObj(filters);

    filtersCount.value = Object.keys(filtersCleaned).length;

    router.visit(route('health.cards'), {
      method: 'get',
      data: {
        ...filtersCleaned,
        ...serverOptions.value,
      },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.request = true),
      onFinish: () => (loader.request = false),
    });
  } else {
    notification.error({
      title: 'Error!',
      position: 'top',
    });
  }
}

onMounted(() => {
  setQueryStringFilters(params, filters);

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
  () => page.props.quotes,
  () => {
    quotes.data = page.props.quotes;
  },
  { deep: true },
);

function onReset() {
  removedSavedParams();
  router.visit(route('health.cards'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
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
    <Head title="Health List ~ Card View" />
    <sticky-header>
      <template #header>
        <h2 class="text-xl font-semibold">Health List</h2>
        <!-- PD Revert
          <LeadsCount
          :leadsCount="$page.props.totalCount"
          :key="$page.props.totalCount"
        /> -->
      </template>
      <template #default>
        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />
        <Link :href="route('health.index')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('health.create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </template>
    </sticky-header>
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
        <x-input
          v-model="filters.first_name"
          type="search"
          name="first_name"
          label="First Name"
          class="w-full"
          placeholder="Search by First Name"
        />
        <x-input
          v-model="filters.last_name"
          type="search"
          name="last_name"
          label="Last Name"
          class="w-full"
          placeholder="Search by Last Name"
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
          v-model="filters.created_at_start"
          name="created_at_start"
          label="Created Date Start"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
        />
        <x-select
          v-if="isAllowed"
          v-model="filters.sub_team"
          label="Sub Team"
          placeholder="Search by Sub Team"
          class="w-full"
          :options="subTeamOptions"
        />

        <ComboBox
          v-if="isAllowed"
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />
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
        <ComboBox
          v-if="isAllowed"
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="modifiedAdvisorOptions"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          :disabled="!props.areBothTeamsPresent"
          v-model="filters.is_renewal"
          label="Renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          v-if="
            !hasAnyRole([
              rolesEnum.RMAdvisor,
              rolesEnum.EBPAdvisor,
              rolesEnum.CarAdvisor,
            ])
          "
          v-model="filters.assignment_type"
          label="Assignment Type"
          name="assignment_type"
          placeholder="Please select assignment type"
          class="w-full"
          :options="assignmentTypeOptions"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="text"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Policy Number"
        />
        <x-input
          v-model="filters.renewal_batch"
          type="text"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />

        <DatePicker
          v-if="!hasAnyRole([rolesEnum.CarAdvisor])"
          v-model="filters.assigned_to_date_start"
          name="assigned_to_date_start"
          label="Advisor Assigned Date Start"
        />
        <DatePicker
          v-if="!hasAnyRole([rolesEnum.CarAdvisor])"
          v-model="filters.assigned_to_date_end"
          name="assigned_to_date_end"
          label="Advisor Assigned Date End"
        />
        <DatePicker
          v-model="filters.last_modified_date"
          name="created_at_start"
          label="Last Modified Date"
          range
          format="dd-MM-yyyy"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit"> Search </x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>
    <div
      v-if="quotes.data.length > 0"
      class="flex w-full h-[85vh] space-x-4 overflow-auto"
    >
      <LeadsCard
        class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
        v-for="quote in quotes.data"
        :key="quote.id"
        :quote="quote"
        :quotes="quotes"
        :quoteType="quoteType"
        :lostReasons="props.lostReasons"
        :quoteStatusEnum="props.quoteStatusEnum"
      />
    </div>
  </div>
</template>
