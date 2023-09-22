<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

const notification = useToast();

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  quoteBatches: Object,
  advisors: Array,
});

const quoteType = 'car';

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const dateFormat = date => {
  return date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
};

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
  previous_quote_policy_number: '',
  renewal_batch: '',
  quote_batch_id: '',
  page: 1,
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/personal-quotes/car/car-quotes-search', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    notification.error({
      title: 'Error while fetching quotes. Please try again',
      position: 'top',
    });
  }
}

function onReset() {
  router.visit('/personal-quotes/car/car-quotes-search', {
    method: 'get',
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
});

const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'SOURCE', value: 'source' },
  { text: 'NATIONALITY', value: 'nationality' },
  { text: 'CAR MAKE', value: 'car_make' },
  { text: 'CAR MODEL', value: 'car_model' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'TYPE OF CAR INSURANCE', value: 'car_type_insurance_id' },
  { text: 'CURRENTLY INSURED WITH', value: 'insurance_provider' },
  { text: 'CREATED DATE', value: 'created_at' },
  {
    text: 'ADVISOR ASSIGNED DATE',
    value: 'advisor_assigned_date',
  },
  { text: 'ADVISOR', value: 'advisor' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);

const manualAssignmentSuccess = () => {
  quotesSelected.value = [];
  notification.success({
    title: `${quoteType.capitalizeFirstChar()} Manual Leads Assigned`,
    position: 'top',
  });
};

const manualAssignmentError = () => {
  notification.error({
    title: 'Manual Assignment Failed',
    position: 'top',
  });
};
</script>

<template>
  <div>
    <Head title="Car Search" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Search</h2>
      <x-button
        v-if="can(permissionsEnum.CarQuotesCreate)"
        size="sm"
        color="#ff5e00"
        href="/quotes/car/create"
      >
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
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="search"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />

        <x-input
          v-model="filters.renewal_batch"
          type="search"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />

        <x-field label="Quote Batch">
          <ComboBox
            v-model="filters.quote_batch_id"
            placeholder="Search by Quote Batch"
            :options="
              quoteBatches.map(quoteBatch => ({
                value: quoteBatch.id,
                label: quoteBatch.name,
              }))
            "
          />
        </x-field>
      </div>
      <div class="flex justify-end gap-3 mb-5">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>

      <div class="flex justify-end gap-3 mb-4 mt-4">
        <x-button size="sm" color="#ff5e00" type="button" @click.prevent=""
          >Send Followup Emails
        </x-button>
      </div>
    </x-form>

    <Transition name="fade">
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <LeadAssignment
          :selected="quotesSelected.map(e => e.id)"
          :advisors="advisorOptions"
          :quoteType="quoteType"
          @success="manualAssignmentSuccess"
          @error="manualAssignmentError"
        />
      </div>
    </Transition>

    <div class="mb-4 font-bold">Total Records : {{ quotes.total || 0 }}</div>
    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-code="{ code, uuid }">
        <a
          :href="route('car.show', uuid)"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </a>
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
      </template>

      <template #item-insurance_provider="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-car_type_insurance_id="{ car_type_insurance_id }">
        {{ car_type_insurance_id?.text }}
      </template>

      <template #item-car_make="{ car_make }">
        {{ car_make?.text }}
      </template>

      <template #item-car_model="{ car_model }">
        {{ car_model?.text }}
      </template>

      <template #item-nationality="{ nationality }">
        {{ nationality?.text }}
      </template>

      <template #item-advisor_assigned_date="item">
        {{ item?.car_quote_request_detail?.advisor_assigned_date }}
      </template>
    </DataTable>

    <Pagination
      v-if="quotes.total > 0"
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
        total: quotes.total,
      }"
    />
  </div>
</template>
