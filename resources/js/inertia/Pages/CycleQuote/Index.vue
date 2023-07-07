<script setup>
import LeadAssignment from "../PersonalQuote/Partials/LeadAssignment.vue";

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
    quoteType: {
        type: String,
        default: 'cycle',
    }
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();
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
};

const filters = reactive(availableFilters);
const quotesSelected = ref([]);

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

    router.visit('/personal-quotes/cycle', {
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
  router.visit('/personal-quotes/cycle', {
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
    if (hasRole(rolesEnum.CycleAdvisor)) {
        quotesSelected.value = null;
    }
});

const tableHeader = [
  { text: 'Ref ID', value: 'uuid' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'POLICY NO', value: 'policy_no' },
  { text: 'SOURCE', value: 'source' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
];

const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

</script>

<template>
  <div>
    <Head title="Cycle Quotes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Cycle Quotes List</h2>
      <x-button v-if="canAny([permissionsEnum.CycleQuotesCreate])" size="sm" color="#ff5e00" href="/personal-quotes/cycle/create">
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
          label="Ref ID"
          class="w-full"
          placeholder="Search by Ref ID"
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
          class="w-full"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />

        <x-input
          v-model="filters.renewal_batch"
          type="search"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />

        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="
            quoteStatuses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
        />

        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
        />

        <x-select
          v-model="filters.previous_quote_policy_number"
          label="Is Renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 0, label: 'Yes' },
            { value: 1, label: 'No' },
          ]"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

      <Transition name="fade" >
          <div v-if="quotesSelected?.length > 0" class="mb-4">
              <LeadAssignment
                  :selected="quotesSelected.map(e => e.id)"
                  :advisors="advisorOptions"
                  :quoteType="quoteType"
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
        <Link v-if="can(permissionsEnum.CycleQuotesShow)"
          :href="`/personal-quotes/cycle/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
        <span v-else>{{code}}</span>
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.email }}
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
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
