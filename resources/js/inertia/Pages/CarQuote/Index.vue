<script setup>
import { ref } from 'vue';
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
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

    router.visit('/personal-quotes/car', {
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

function onReset() {
  router.visit('/personal-quotes/car', {
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
});

const tableHeader = [
  { text: 'CDB ID', value: 'uuid' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'DOB', value: 'dob_formatted' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'POLICY NO', value: 'policy_no' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);


</script>

<template>
  <div>
    <Head title="Bike Quotes" />

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
          :advisors="advisors"
        />
      </div>
    </Transition>


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
