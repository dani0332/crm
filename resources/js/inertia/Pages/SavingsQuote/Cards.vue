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
  investmentFrequencies: Array,
});

const page = usePage();
const notification = useNotifications('toast');
provide('quoteStatusEnum', props.quoteStatusEnum);
provide('quoteTypeId', props.quoteTypeId);
provide('lostReasons', props.lostReasons);
provide('quoteType', props.quoteType);

const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;

const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

watch(
  () => page.props.quotes,
  () => {
    quotes.data = page.props.quotes;
    // Reinitialize pages and queries when data changes
    quotes.data.forEach(quote => {
      quotes.pages[quote.id] = quote.data.leads_list?.current_page
        ? quote.data.leads_list.current_page + 1
        : 2;
      quotes.queries[quote.id] = quotes.queries[quote.id] || '';
    });
  },
  { deep: true },
);

const loader = reactive({
  request: false,
});

const cleanObj = obj => useCleanObj(obj);
const showFilters = ref(false);
const filtersCount = ref(0);

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status_id: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  advisor_id: '',
  investment_frequency: '',
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
    filters.quote_status_id = selectedFilters.quote_status;
  }

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

    router.visit(route('savings-quotes-cards'), {
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
  }
}

function onReset() {
  removedSavedParams();
  router.visit(route('savings-quotes-cards'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.request = true),
    onFinish: () => (loader.request = false),
  });
}

onMounted(() => {
  setQueryStringFilters(useUrlSearchParams('history'), filters);

  // Initialize pages and queries for each quote status
  quotes.data.forEach(quote => {
    quotes.pages[quote.id] = quote.data.leads_list?.current_page
      ? quote.data.leads_list.current_page + 1
      : 2;
    quotes.queries[quote.id] = '';
  });

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
    <Head title="Savings Quotes - Cards View" />

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Savings Quotes Cards View</h2>
      </template>
      <template #default>
        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />

        <Link :href="route('savings-quotes-list')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('savings-quotes-create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
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
            :options="leadStatusOptions"
          />
        </x-field>
        <DatePicker
          v-model="filters.policy_expiry_date"
          name="policy_expiry_date"
          class="w-full"
          label="Policy Expiry Start Date"
        />
        <DatePicker
          v-model="filters.policy_expiry_date_end"
          name="policy_expiry_date_end"
          class="w-full"
          label="Policy Expiry End Date"
        />
        <x-field label="Advisor" v-if="!hasAnyRole([rolesEnum.SavingsAdvisor])">
          <ComboBox
            v-model="filters.advisor_id"
            placeholder="Search by Advisor"
            :options="advisorOptions"
          />
        </x-field>
        <x-field label="Investment Frequency">
          <ComboBox
            v-model="filters.investment_frequency"
            placeholder="Search by Investment Frequency"
            :options="investmentFrequencies"
            :single="true"
          />
        </x-field>
      </div>

      <div class="flex justify-between gap-3 mb-4 mt-1">
        <div />
        <div class="flex justify-self-end gap-3">
          <x-button
            size="sm"
            color="#ff5e00"
            type="submit"
            :loading="loader.request"
          >
            Search
          </x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <!-- Cards Display -->
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
        :quoteTypeId="quoteTypeId"
        :quoteType="quoteType"
        :lostReasons="lostReasons"
        :quoteStatusEnum="quoteStatusEnum"
      />
    </div>

    <div v-if="quotes.data.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-lg">No quotes found</p>
    </div>
  </div>
</template>
