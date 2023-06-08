<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

const notification = useToast();

defineProps({
  quotes: Object,
  quoteStatuses: Array,
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
  policy_number: '',
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
  { text: 'CDB ID', value: 'code' },
  { text: 'BATCH', value: 'batch' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'DOB', value: 'dob' },
  { text: 'SOURCE', value: 'source' },
  { text: 'NATIONALITY', value: 'nationality' },
  { text: 'UAR LICENSE HELD FOR', value: 'uae_license_held_for_id' },
  { text: 'CAR MAKE', value: 'car_make' },
  { text: 'CAR MODEL', value: 'car_model' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'FIRST REGISTRATION YEAR', value: 'year_of_first_registration' },
  { text: 'CAR VALUE', value: 'car_value' },
  { text: 'VEHICLE TYPE', value: 'vehicle_type' },
  { text: 'TYPE OF CAR INSURANCE', value: 'car_type_insurance_id' },
  { text: 'CURRENTLY INSURED WITH', value: 'insurance_provider' },
  { text: 'CLAIM HISTORY', value: 'claim_history_id' },
  { text: 'CREATED DATE', value: 'created_at' },
  {
    text: 'ADVISOR ASSIGNED DATE',
    value: 'advisor_assigned_date',
  },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'PAYMENT STATUS', value: 'payment_status_id' },
  { text: 'ECOMMERCE', value: 'is_ecommerce' },
  { text: 'TIER NAME', value: 'tier' },
  { text: 'VISIT COUNT', value: 'quote_view_count' },
  {
    text: 'FOLLOW UP DATE',
    value: 'followupDate',
  },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'UPDATED BY', value: 'updated_by' },
  { text: 'ADDITIONAL NOTES', value: 'additional_notes' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'POLICY NO', value: 'policy_number' },
  { text: 'RENEWAL EXPIRY DATE', value: 'renewal_expiry_date' },
  { text: 'GCC STANDARD', value: 'is_gcc_standard' },
  { text: 'VEHICLE MODIFIED', value: 'is_modified' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'LOST REASON', value: 'lost' },
  { text: 'QUOTE LINK', value: 'quote_link' },
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
    <Head title="Car Quotes Search" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Car Quotes Search</h2>
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
        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="CDB ID"
          class="w-full"
          placeholder="Search by CDB ID"
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
          v-model="filters.policy_number"
          type="search"
          name="policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
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
      :items=" quotes.data || []"
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

      <template #item-advisor_assigned_date="item">
        {{ item?.car_quote_request_detail?.advisor_assigned_date }}
      </template>
      <template #item-followupDate="item">
        {{ item?.car_quote_request_detail?.next_followup_date }}
      </template>

      <template #item-lost="item">
        {{ item?.car_quote_request_detail?.lost_reason?.text }}
      </template>

      <template #item-claim_history_id="{ claim_history_id }">
        {{ claim_history_id?.text }}
      </template>

      <template #item-car_type_insurance_id="{ car_type_insurance_id }">
        {{ car_type_insurance_id?.text }}
      </template>

      <template #item-vehicle_type="{ vehicle_type }">
        {{ vehicle_type?.text }}
      </template>

      <template #item-quote_view_count="{ quote_view_count }">
        {{ quote_view_count?.visit_count }}
      </template>

      <template #item-car_make="{ car_make }">
        {{ car_make?.text }}
      </template>

      <template #item-car_model="{ car_model }">
        {{ car_model?.text }}
      </template>

      <template #item-uae_license_held_for_id="{ uae_license_held_for_id }">
        {{ uae_license_held_for_id?.text }}
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
      </template>

      <template #item-payment_status_id="{ payment_status_id }">
        {{ payment_status_id?.text }}
      </template>

      <template #item-tier="{ tier }">
        {{ tier?.name }}
      </template>

      <template #item-batch="{ batch }">
        {{ batch?.name }}
      </template>

      <template #item-nationality="{ nationality }">
        {{ nationality?.text }}
      </template>

      <template #item-dob="{ dob }">
        {{ dateFormat(dob) }}
      </template>

      <template #item-updated_at="{ updated_at }">
        {{ updated_at }}
      </template>

      <template #item-updated_by="{ updated_by }">
        {{ updated_by?.name }}
      </template>

      <template #item-policy_number="{ policy_number }">
        {{ policy_number != 'NULL' ? policy_number : 'N/A' }}
      </template>

      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-is_gcc_standard="{ is_gcc_standard }">
        <div class="text-center">
          <x-tag size="sm" :color="is_gcc_standard ? 'success' : 'error'">
            {{ is_gcc_standard ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>
      <template #item-is_modified="{ is_modified }">
        <div class="text-center">
          <x-tag size="sm" :color="is_modified ? 'success' : 'error'">
            {{ is_modified ? 'Yes' : 'No' }}
          </x-tag>
        </div>
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
