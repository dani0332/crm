<script setup>
defineProps({
  embeddedProduct: Object,
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
const params = useUrlSearchParams('history');
const serverOptions = ref({
  page: 1,
  sortBy: 'id',
  sortType: 'desc',
});

const loader = reactive({
  table: false,
  export: false,
});

const filters = reactive({
  ref_id: '',
  email: '',
  name: '',
  date_of_purchase: '',
  months: '',
});

const page = usePage();

const tableHeader = [
  { text: 'EP Ref-ID', value: 'ref_id' },
  { text: 'Advisor Name', value: 'advisor_name' },
  { text: 'Date of Issuance', value: 'payment_date', sortable: true },
  { text: 'Plan Commencement Date', value: 'plan_start_date' },
  { text: 'Plan End Date', value: 'plan_end_date' },
  { text: 'Full Name', value: 'name' },
  { text: 'EMIRATES ID NUMBER', value: 'emirates_id_number' },
  { text: 'DOB', value: 'dob' },
  { text: 'AGE', value: 'age' },
  { text: 'PASSPORT', value: 'passport_number' },
  { text: 'NATIONALITY', value: 'nationality' },
  { text: 'Vehicle', value: 'vehicle' },
  { text: 'Contact Number', value: 'contact_number' },
  { text: 'Email ID', value: 'email' },
  { text: 'Contribution Amount', value: 'contribution_amount', sortable: true },
  { text: 'Policy Issue Status', value: 'status' },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  router.visit(
    route(
      'embedded-products.reports.certificates',
      page.props.embeddedProduct.detail.id,
    ),
    {
      method: 'get',
      data: { page: 1 },
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        loader.table = false;
      },
      onBefore: () => {
        loader.table = true;
      },
    },
  );
}

function filterTransactions(isValid) {
  if (!isValid) {
    return;
  }

  serverOptions.value.page = 1;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit(
    route(
      'embedded-products.reports.certificates',
      page.props.embeddedProduct.detail.id,
    ),
    {
      method: 'get',
      data: {
        ...filters,
        ...serverOptions.value,
      },
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        loader.table = false;
        setQueryFilters();
      },
      onBefore: () => {
        loader.table = true;
      },
    },
  );
}

function setQueryFilters() {
  var currentParams = {
    ...params,
    ...serverOptions.value,
  };
  Object(currentParams).hasOwnProperty('rowsPerPage') &&
    delete currentParams.rowsPerPage;
  for (const [key] of Object.entries(currentParams)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = currentParams[key];
    } else {
      filters[key] = currentParams[key];
    }
  }
}

function exportReport() {
  const filteredData = Object.fromEntries(
    Object.entries(filters).filter(([key, value]) => value !== null),
  );
  const data = useObjToUrl(filteredData);
  const url = route(
    'embedded-products.reports.certificates.export',
    page.props.embeddedProduct.detail.id,
  );
  window.open(url + '?' + new URLSearchParams(data).toString());
}

onMounted(() => {
  setQueryFilters();
});

watch(
  serverOptions,
  value => {
    filterTransactions(true);
  },
  { deep: true },
);
</script>

<template>
  <div>
    <Head title="Reports" />
    <nav class="mb-4">
      <ol class="flex gap-1">
        <li>
          <Link
            :href="route('embedded-products.index')"
            class="text-sm border-b text-gray-500"
            ><span> Embedded Products </span></Link
          >
        </li>
        <li><span class="text-gray-400">/</span></li>
        <li>
          <Link
            :href="route('embedded-products.reports')"
            class="text-sm border-b text-gray-500"
            ><span> Reports </span></Link
          >
        </li>
        <li><span class="text-gray-400">/</span></li>
        <li>
          <span class="text-sm font-semibold">
            {{ embeddedProduct.detail.product_name }}
          </span>
        </li>
      </ol>
    </nav>
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ embeddedProduct.detail.product_name }}
      </h2>
      <x-button size="sm" color="#ff5e00" @click.prevent="exportReport">
        Export Report XLS
      </x-button>
    </div>

    <x-divider class="my-4" />
    <x-form @submit="filterTransactions" :auto-focus="false">
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
            v-model="filters.ref_id"
            type="search"
            name="ref_id"
            class="w-full"
            placeholder="Search by Ref-ID"
          />
        </div>
        <x-field label="Email">
          <x-input
            v-model="filters.email"
            type="search"
            name="first_name"
            class="w-full"
            placeholder="Type here"
          />
        </x-field>
        <x-field label="Name">
          <x-input
            v-model="filters.name"
            type="search"
            name="last_name"
            class="w-full"
            placeholder="Type here"
          />
        </x-field>
        <div>
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Date of issuance
            </label>
            <template #tooltip>
              This is the date on which the EP product was issued and sent to
              the client by the system
            </template>
          </x-tooltip>
          <x-field>
            <DatePicker
              v-model="filters.date_of_purchase"
              name="date_of_purchase"
              class="w-full"
              model-type="yyyy-MM-dd"
              range
              max-range="30"
            />
          </x-field>
        </div>
        <div>
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Months
            </label>
            <template #tooltip>
              This is the month in which the EP product was issued to the client
            </template>
          </x-tooltip>
          <DatePicker
            v-model="filters.months"
            name="months"
            placeholder="Select month"
            class="w-full"
            month-picker
            model-type="yyyy-MM"
            format="MM-yyyy"
          />
        </div>
      </div>
      <div class="flex flex-row-reverse gap-3">
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" />

    <DataTable
      v-model:server-options="serverOptions"
      table-class-name=""
      :headers="tableHeader"
      :loading="loader.table"
      :items="embeddedProduct.transactions.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-id="{ id }">
        <Link
          :href="route('embedded-products.edit', id)"
          class="text-primary-500 hover:underline"
        >
          {{ id }}
        </Link>
      </template>

      <template #item-company_name="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-is_active="{ is_active }">
        <x-icon
          :icon="is_active ? 'true' : 'false'"
          :color="is_active ? 'green' : 'red'"
          size="lg"
        />
      </template>

      <template #item-updated_at="{ updated_at }">
        <div class="text-sm text-center">{{ dateFormat(updated_at) }}</div>
      </template>

      <template #item-actions="{ id }">
        <div class="flex gap-1.5 justify-end">
          <Link :href="route('embedded-products.reports.certificates', id)">
            <x-button color="primary" size="xs" outlined> View </x-button>
          </Link>
        </div>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: embeddedProduct.transactions.next_page_url,
        prev: embeddedProduct.transactions.prev_page_url,
        current: embeddedProduct.transactions.current_page,
        from: embeddedProduct.transactions.from,
        to: embeddedProduct.transactions.to,
      }"
    />
  </div>
</template>
