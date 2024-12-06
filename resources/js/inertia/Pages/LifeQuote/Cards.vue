<script setup>
const props = defineProps({
  quoteStatusEnum: Object,
  quoteTypeId: String,
  lostReasons: Object,
  quoteType: String,
  quoteStatuses: Array,
  renewalBatches: Array,
  advisors: Array,
  typesOfInsurance: Array,
  numberOfYears: Array,
  currency: Array,
});
const page = usePage();
const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};

provide('quoteStatusEnum', props.quoteStatusEnum);
provide('quoteTypeId', props.quoteTypeId);
provide('lostReasons', props.lostReasons);
provide('quoteType', props.quoteType);

// Flattens nested lead properties to ensure data is serializable.
const flattenLeads = (leadsTypes) => {
  leadsTypes.forEach((leadType) => {
    const leadsList = leadType.data?.leads_list?.data;

    if (Array.isArray(leadsList)) {
      leadsList.forEach((lead) => {
        lead.nationality_text = lead.nationality?.text || '';
        lead.insurance_tenure_text = lead.insurance_tenure?.text || '';

        delete lead.nationality;
        delete lead.insurance_tenure;
      });
    }
  });

  return leadsTypes;
};

const serverOptions = ref({
  page: 1,
  sortBy: 'created_at',
  sortType: 'desc',
});

const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const cleanObj = obj => useCleanObj(obj);
const showFilters = ref(true);
const filtersCount = ref(0);

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status_id: [],
  advisor_id: [],
  renewal_batch_id: [],
  payment_due_date: '',
  is_ecommerce: '',
  payment_status_id: '',
  previous_quote_policy_number_text: '',
  page: 1,
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  last_modified_date: null,
  advisor_assigned_date: null,
  insurer_tax_number: '',
  insurer_commmission_invoice_number: '',
  tenure_of_insurance_id: '',
  sum_insured_currency_id: '',
  sum_insured_range: '',
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

  filterQuotes(true);
};

const renewalBatchOptions = computed(() => {
  return page.props.renewalBatches.map(batch => ({
    value: batch.id,
    label: batch.name,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
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

const loader = reactive({
  table: false,
  export: false,
});

const quotes = reactive({
  data: flattenLeads(page.props.quotes || []),
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

const onLoadMore = id => {
  quotes.loader = true;
  quotes.pages = {
    ...quotes.pages,
    [id]: quotes.pages[id] ? Number(quotes.pages[id]) + 1 : 2,
  };
  axios
    .post(
      route('loadMoreRecords', {
        page: quotes.pages[id],
        modelType: 'Life',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.data = quotes.data.map(quote => {
        if (quote.id === id) {
          quote.data.leads_list = {
            ...data.leads_list,
            data: quote.data.leads_list.data.concat(data.leads_list.data),
          };
        }
        return quote;
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      quotes.loader = false;
    });
};

const onSearch = id => {
  quotes.searching = true;
  if (
    !quotes.queries[id] ||
    quotes.queries[id] === '' ||
    quotes.queries[id] === null
  ) {
    axios
      .post(
        route('loadMoreRecords', {
          page: quotes.pages[id],
          modelType: 'Life',
          status: id,
        }),
      )
      .then(({ data }) => {
        quotes.data = quotes.data.map(quote => {
          if (quote.id === id) {
            quote.data.leads_list = data.leads_list;
          }
          return quote;
        });
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => {
        quotes.searching = false;
      });
    return;
  }
  axios
    .post(
      route('searchLead', {
        term: quotes.queries[id],
        modelType: 'Life',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.data = quotes.data.map(quote => {
        if (quote.id === id) {
          quote.data.leads_list = {
            ...data.leads_list,
            next_page_url: null,
            data: data.leads_list,
          };
        }
        return quote;
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      quotes.searching = false;
    });
};
function resetFilters() {
  router.visit(route('life-quotes-card'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
function filterQuotes(isValid) {
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
  if(!filters['sum_insured_range'] || !filters['sum_insured_currency_id']) {
    delete filters['sum_insured_range'];
    delete filters['sum_insured_currency_id'];
  }
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  serverOptions.value.page = 1;

  const filtersCleaned = cleanObj(filters);
  filtersCount.value = Object.keys(filtersCleaned).length;

  router.visit(route('life-quotes-card'), {
    method: 'get',
    data: {
      ...filters,
      ...serverOptions.value,
    },
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
watch(
  () => page.props.quotes,
  () => {
    quotes.data = page.props.quotes;
  },
  { deep: true },
);
</script>

<template>
  <div>
    <Head title="Life List ~ Card View" />
    <sticky-header>
      <template #header>
      <h2 class="text-xl font-semibold">Life List</h2>
      </template>
      <template #default>
        <FiltersButton
          :is-shown="showFilters"
          :filters="filters"
          :filters-count="filtersCount"
          @selected-filters="handleSelectedFilters"
          @toggleFilters="showFilters = !showFilters"
        />

        <Link :href="route('life-quotes-list')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('life-quotes-create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </template>
    </sticky-header>
    <x-form v-show="showFilters" @submit="filterQuotes" :auto-focus="false">
      <x-divider class="my-4" />
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
        <x-field label="Advisor">
          <ComboBox
            v-if="!hasRole(rolesEnum.TravelAdvisor)"
            v-model="filters.advisors"
            placeholder="Search by Advisor"
            :options="advisorOptions"
          />
        </x-field>
        <x-field label="Policy Number">
          <x-input
            v-model="filters.previous_quote_policy_number"
            type="text"
            name="previous_quote_policy_number"
            class="w-full"
            placeholder="Policy Number"
          />
        </x-field>
        <x-select
          v-model="filters.is_renewal"
          placeholder="Renewal"
          label="Renewal"
          :options="[
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
            { value: '', label: 'All' },
          ]"
        />

        <x-field label="Renewal Batch">
          <ComboBox
            v-model="filters.renewal_batch"
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
          v-if="hasRole(rolesEnum.LifeManager)"
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
        <x-select
          v-model="filters.tenure_of_insurance_id"
          placeholder="Type of Insurance"
          label="Type of Insurance"
          :options="
              typesOfInsurance.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
        />
        <x-select
          v-model="filters.number_of_years_id"
          placeholder="Tenure of Cover"
          label="Tenure of Cover"
          :options="
              numberOfYears.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
        />

        <div>
          <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-1">
            <x-select
              v-model="filters.sum_insured_currency_id"
              placeholder="Currency"
              label="Sum Assured"
              :options="
                  currency.map(item => ({
                    value: item.id,
                    label: item.text,
                  }))
                "
            />
            <x-select
            v-model="filters.sum_insured_range"
            placeholder="Value Range"
            :options="[
                { value: 'lt500k', label: '<500K' },
                { value: '500k-1m', label: '500K- <1M' },
                { value: 'gte1m', label: '>/= 1M' },
              ]
              "
            class="border-l-0 rounded-tl-none rounded-bl-none"
            label="&nbsp;"
          />
          </div>
        </div>
        
      </div>

      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex gap-3 justify-self-end">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>
    <x-divider class="my-4" />
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
        :quoteTypeId="quoteTypeId"
        :lostReasons="props.lostReasons"
        :quoteStatusEnum="props.quoteStatusEnum"
      />
      <!-- <div
        v-for="quote in quotes.data"
        :key="quote.id"
        class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
      >
        <div
          class="flex flex-col flex-shrink-0 gap-1.5 p-3 border-b border-gray-300 bg-white text-xs"
        >
          <h4 class="font-semibold text-sm">{{ quote.text }}</h4>
          <div class="flex justify-between gap-1">
            <span>Total Leads</span>
            <span>{{ quote.data.total_leads }}</span>
          </div>
          <div class="flex justify-between gap-1">
            <span>Total Premium</span>
            <span>{{ Number(quote.data.total_premium).toLocaleString() }}</span>
          </div>
          <div>
            <x-input
              v-model="quotes.queries[quote.id]"
              type="search"
              size="xs"
              class="w-full"
              placeholder="Search"
              @change.prevent="onSearch(quote.id)"
              :disabled="quotes.searching"
            />
          </div>
        </div>
        <div class="flex flex-col px-2 pb-2 overflow-auto">
          <div
            v-if="quotes.queries[quote.id] && quotes.searching"
            class="text-center p-4"
          >
            <x-spinner class="text-primary-500" />
          </div>
          <div
            v-if="quote.data.leads_list.data == 0 && quote.data.total_leads > 0"
            class="text-center text-xs text-gray-800 p-4"
          >
            <x-icon icon="box" class="text-secondary-600 mb-2" />
            <p>No Leads Found</p>
          </div>
          <a
            v-for="{
              id,
              uuid,
              code,
              first_name,
              last_name,
              premium,
              updated_at,
              company_name,
            } in quote.data.leads_list.data"
            :key="id"
            :href="route('life-quotes-show', uuid)"
            target="_blank"
            title="View Lead"
            class="block p-3 mt-2 border bg-white border-gray-300 space-y-2 hover:transition hover:border-primary-500 rounded"
          >
            <div class="font-semibold text-sm">
              {{ code }}
            </div>
            <div class="flex items-center gap-2">
              <x-icon icon="person" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ first_name }} {{ last_name }}</p>
            </div>

            <div v-if="company_name" class="flex items-center gap-2">
              <x-icon icon="company" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ company_name }}</p>
            </div>

            <div class="flex items-center gap-2">
              <x-icon icon="money" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ Number(premium).toLocaleString() }}</p>
            </div>

            <div class="flex items-center gap-2">
              <x-icon icon="calendar" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ updated_at }}</p>
            </div>
          </a>
          <div
            class="mt-3"
            v-if="
              quote.data.total_leads > 0 &&
              quote.data.leads_list.next_page_url !== null
            "
          >
            <x-button
              size="xs"
              color="#1d83bc"
              class="w-full"
              outlined
              @click.prevent="onLoadMore(quote.id)"
              :disabled="quotes.loader"
              :loading="quotes.loader"
            >
              Load More
            </x-button>
          </div>
        </div>
      </div> -->
    </div>
  </div>
</template>
