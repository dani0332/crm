<script setup>
const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

const { isRequired } = useRules();

const filters = reactive({
  reportCategory: [],
  createdAt: [],
  reportType: [
    {label: 'Issued Policies', value: 'Issued Policies', report: 'SalesSummary', column: 'policy_issuance_date'},
    {label: 'Transaction Payments', value: 'Transaction Payments', report: 'SalesSummary', column: 'payment_due_date'},
    {label: 'Issued Policies', value: 'Issued Policies', report: 'SalesDetail', column: 'policy_issuance_date'},
    {label: 'Transaction Payments', value: 'Transaction Payments', report: 'SalesDetail', column: 'payment_due_date'},
    {label: 'Expiring Policies', value: 'Expiring Policies', report: 'EndingPolicies', column: 'policy_expiry_date'},
    {label: 'Transaction Payments', value: 'Transaction Payments', report: 'TransactionReport', column: 'payment_due_date'},

  ],
  teams: [],
  subTeams: [],
  transactionType: [
    { label: 'All', value: 'All' },
    { label: 'New Business', value: 'New Business' },
    { label: 'Existing Customer - Renewal', value: 'Existing Customer - Renewal'},
    { label: 'Existing Customer - New Business', value: 'Existing Customer -  New Business' },
    { label: 'Endorsement', value: 'Endorsement' },
  ],
  leadSources: [],
  utmFirst : [],
  utmSecond : [],
  includeCancelledPolicies: [
    { label: 'Yes', value: 'Yes' },
    { label: 'No', value: 'No' },
  ],
  groupBy : [
    { label: 'Advisor', value: 'Advisor' },
    { label: 'Policy Issuer', value: 'Policy Issuer' },
    { label: 'Customer Group', value: 'Customer Group' },
    { label: 'Insurer', value: 'Insurer' },
    { label: 'Line of Business', value: 'Line of Business' },
  ],
  page: 1,
});

onMounted(() => {
  if (page.props.defaultFilters && !params['page']) {
    filters.advisorAssignedDates =
      page.props.defaultFilters.advisorAssignedDates;
  }

  setQueryStringFilters();

  if (params['teams[]'] && params['teams[]'].length > 0) {
    onTeamChange(params['teams[]']);
  }
  isMounted.value = true;
});

const leadSource = computed(() => {
  return Object.keys(props.defaultFilters.leadSource).map(key => ({
    value: key,
    label: props.defaultFilters.leadSource[key],
  }));
});

const teams = computed(() => {
  return Object.keys(props.defaultFilters.teams).map(key => ({
    value: key,
    label: props.defaultFilters.teams[key],
  }));
});

const subTeams = computed(() => {
  return Object.keys(props.defaultFilters.subTeams).map(key => ({
    value: key,
    label: props.defaultFilters.tiers[key],
  }));
});

const onSubmit = isValid => {
  if (!isValid) return;
  filters.page = 1;
  router.visit(route('lead-list-report'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

function onReset() {
  router.visit(route('lead-list-report'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const tableHeader = reactive([
  {
    text: 'LEAD CODE',
    value: 'uuid',
  },
  {
    text: 'Name',
    value: 'first_name',
  },
  {
    text: 'Lead Source',
    value: 'source',
  },
  {
    text: 'Lead Status',
    value: 'quoteStatus',
  },
  {
    text: 'Payment Status',
    value: 'payment_status_id',
  },
  {
    text: 'IS ECOMMERCE',
    value: 'is_ecommerce',
  },
  {
    text: 'ASSIGNED TO',
    value: 'advisor',
  },
  {
    text: 'CREATED AT',
    value: 'created_at',
  },
  {
    text: 'LAST MODIFIED',
    value: 'updated_at',
  },
  {
    text: 'TIER',
    value: 'tier',
  },
  {
    text: 'RECEIVED FROM DEVICE',
    value: 'device',
  },
]);
</script>
<template>
  <Head title="Management Reports" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Management Reports
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <!-- <x-field label="Search">
        <x-input
          v-model="filters.uuid"
          type="text"
          class="w-full"
          placeholder="Search By Ref i.e CAR-12345678"
        />
      </x-field>-->
      <x-field label="Create Date" required>
        <DatePicker
          v-model="filters.createdAt"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
        />
      </x-field>
      <x-field label="Report Category">
        <ComboBox
          v-model="filters.reportCategory"
          placeholder="Select Report Category"
          :options="tiers"
          :max-limit="3"
          deselect-all
        />
      </x-field>
      <x-field label="Report Type">
        <ComboBox
          v-model="filters.ReportType"
          placeholder="Select Report Type"
          :options="tiers"
          :max-limit="3"
          deselect-all
        />
      </x-field>
      <x-field label="Lead Source">
        <ComboBox
          v-model="filters.leadSources"
          placeholder="Search by Lead Source"
          :options="leadSource"
          :max-limit="3"
          deselect-all
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Teams">
        <ComboBox
          v-model="filters.teams"
          placeholder="Search By Teams"
          :options="teams"
          :max-limit="3"
          deselect-all
        />
      </x-field>
      <x-field label="Is Ecommerce">
        <x-select
          v-model="filters.is_ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'All', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field label="Payment Status">
        <ComboBox
          v-model="filters.payment_status"
          placeholder="Search By Payment Status"
          :options="paymentStatus"
          :max-limit="3"
          deselect-all
        />
      </x-field>
    </div>
    <div class="flex gap-3 justify-end">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loaders.table"
    :headers="tableHeader"
    :items="props.reportData.data || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
    <template #item-uuid="{ uuid }">
      <Link
        :href="route('car.show', uuid)"
        class="text-primary-500 hover:underline"
      >
        {{ uuid }}
      </Link>
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
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  />
</template>
