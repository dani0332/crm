<script setup>
defineProps({
  transactors: Array,
  handlers: Array,
  insuranceCompanies: Array,
  paymentModes: Array,
  reasons: Array,
  isTransappAdmin: String,
  teams: Array,
  isCarManager: Boolean,
  data: Object,
});

const loader = reactive({
  table: false,
});

const page = usePage();
const notification = useToast();

const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;

const cleanObj = obj => useCleanObj(obj);

let params = useUrlSearchParams('history');

const tableHeader = ref([
  { text: 'Approval Code', value: 'approval_code' },
  { text: 'Transaction Date', value: 'created_at' },
  { text: 'Insurance Company', value: 'insurance' },
  { text: 'Premium', value: 'amount_paid' },
  { text: 'Name', value: 'customer_name' },
  { text: 'Risk Detail', value: 'risk_details' },
  { text: 'Transactor', value: 'created_by_name' },
  { text: 'Advisor', value: 'handler_name' },
  { text: 'Payment mode', value: 'payment_mode' },
  { text: 'Previous Approval Code', value: 'prev_approval_code' },
]);

const filters = reactive({
  transapp_start_date: useDateFormat(useNow(), 'YYYY-MM-DD').value || null,
  transapp_stop_date: useDateFormat(useNow(), 'YYYY-MM-DD').value || null,
  transapp_approval_code: null,
  transapp_customer_email: null,
  transapp_customer_name: null,
  transactor: null,
  handler: null,
  insurance_company: null,
  reason: null,
  payment_mode: null,
  page: 1,
});

function onSubmit(isValid) {
  const filtersCleaned = cleanObj(filters);
  router.visit(route('transaction.index'), {
    method: 'get',
    data: {
      ...filtersCleaned,
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('transaction.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key] ?? value;
    } else {
      filters[key] = isNaN(parseInt(params[key]))
        ? params[key]
        : parseInt(params[key]);
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Transaction List" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Transaction List</h2>
      </template>
    </StickyHeader>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-field label="Start Date">
          <DatePicker
            v-model="filters.transapp_start_date"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
          />
        </x-field>
        <x-field label="Stop Date">
          <DatePicker
            v-model="filters.transapp_stop_date"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
          />
        </x-field>
        <x-field
          label="Transactor"
          v-if="
            hasAnyRole([
              rolesEnum.TRANSAPP_ADVISOR,
              rolesEnum.TRANSAPP_APPROVER,
            ])
          "
        >
          <x-select
            v-model="filters.transactor"
            placeholder="Select Transactor"
            class="w-full"
            filterable
            :options="
              transactors.map(item => ({ label: item.name, value: item.id }))
            "
          />
        </x-field>
        <x-field
          label="Advisor"
          v-if="
            hasAnyRole([
              rolesEnum.TRANSAPP_ADVISOR,
              rolesEnum.TRANSAPP_APPROVER,
            ])
          "
        >
          <x-select
            v-model="filters.handler"
            :options="
              handlers.map(item => ({ label: item.name, value: item.id }))
            "
            filterable
            placeholder="Select Advisor"
            class="w-full"
          />
        </x-field>
        <x-field label="Insurance Company">
          <x-select
            v-model="filters.insurance_company"
            :options="
              insuranceCompanies.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Select Insurance Company"
            class="w-full"
          />
        </x-field>
        <x-field label="Reason">
          <x-select
            v-model="filters.reason"
            :options="
              reasons.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Reason"
            class="w-full"
          />
        </x-field>
        <x-field label="Customer Email">
          <x-input
            v-model="filters.transapp_customer_email"
            placeholder="Customer Email"
            class="w-full"
          />
        </x-field>
        <x-field label="Approval Code">
          <x-input
            v-model="filters.transapp_approval_code"
            placeholder="Approval Code"
            class="w-full"
          />
        </x-field>
        <x-field label="Payment mode">
          <x-select
            v-model="filters.payment_mode"
            :options="
              paymentModes.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Payment mode"
            class="w-full"
          />
        </x-field>
        <x-field label="Teams" v-if="hasRole(rolesEnum.CAR_MANAGER)">
          <x-select
            v-model="filters.team"
            :options="
              teams.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Select Team"
            class="w-full"
          />
        </x-field>
      </div>
      <div class="flex justify-end">
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset"
            >Reset</x-button
          >
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="compact text-wrap"
      :headers="tableHeader"
      :items="data.data || []"
      :loading="loader.table"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>

    <Pagination
      :links="{
        next: data.next_page_url,
        prev: data.prev_page_url,
        current: data.current_page,
        from: data.from,
        to: data.to,
      }"
    />
  </div>
</template>
