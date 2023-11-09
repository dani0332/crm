<script setup>
import SaleSummary from './Partials/SaleSummary.vue';

const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

const selectedReport = computed(() => {
  if (filters.reportCategory == 'Sale Summary Report') return SaleSummary;
});

const { isRequired } = useRules();

const filters = reactive({
  reportCategory: 'Sale Summary Report',
  createdAt: [],
  reportType: null,
  teams: [],
  subTeams: [],
  transactionType: [],
  leadSources: [],
  utmFirst: [],
  utmSecond: [],
  includeCancelledPolicies: null,
  groupBy: null,
  page: 1,
});

const reportCategories = reactive([
  { label: 'Sales Summary Report', value: 'Sale Summary Report' },
  { label: 'Sales Report Detailed', value: 'Sales Report Detailed' },
  {
    label: 'Ending Policies Report',
    value: 'Ending Policies Report',
  },
  {
    label: 'Installment Report',
    value: 'Installment Report',
  },
  {
    label: 'Customer Active Policies Report',
    value: 'Customer Active Policies Report',
  },
  {
    label: 'Renewals - Daily Summary Report',
    value: 'Renewals - Daily Summary Report',
  },
]);

const transactionTypes = reactive([
  { label: 'All', value: 'All' },
  { label: 'New Business', value: 'New Business' },
  {
    label: 'Existing Customer - Renewal',
    value: 'Existing Customer - Renewal',
  },
  {
    label: 'Existing Customer - New Business',
    value: 'Existing Customer -  New Business',
  },
  { label: 'Endorsement', value: 'Endorsement' },
]);

const groupBy = reactive([
  { label: 'Advisor', value: 'Advisor' },
  { label: 'Policy Issuer', value: 'Policy Issuer' },
  { label: 'Customer Group', value: 'Customer Group' },
  { label: 'Insurer', value: 'Insurer' },
  { label: 'Line of Business', value: 'Line of Business' },
]);

const reportTypes = ref([
  {
    label: 'Issued Policies',
    value: 'Issued Policies',
    report: 'SalesSummary',
    column: 'policy_issuance_date',
  },
  {
    label: 'Transaction Payments',
    value: 'Transaction Payments',
    report: 'SalesSummary',
    column: 'payment_due_date',
  },
  {
    label: 'Issued Policies',
    value: 'Issued Policies',
    report: 'SalesDetail',
    column: 'policy_issuance_date',
  },
  {
    label: 'Transaction Payments',
    value: 'Transaction Payments',
    report: 'SalesDetail',
    column: 'payment_due_date',
  },
  {
    label: 'Expiring Policies',
    value: 'Expiring Policies',
    report: 'EndingPolicies',
    column: 'policy_expiry_date',
  },
  {
    label: 'Transaction Payments',
    value: 'Transaction Payments',
    report: 'TransactionReport',
    column: 'payment_due_date',
  },
]);

// onMounted(() => {
//   if (page.props.defaultFilters && !params['page']) {
//     filters.advisorAssignedDates =
//       page.props.defaultFilters.advisorAssignedDates;
//   }

//   setQueryStringFilters();

//   if (params['teams[]'] && params['teams[]'].length > 0) {
//     onTeamChange(params['teams[]']);
//   }
//   isMounted.value = true;
// });

const leadSource = computed(() => {
  return Object.keys(props.defaultFilters?.leadSource).map(key => ({
    value: key,
    label: props.defaultFilters?.leadSource[key],
  }));
});

const teams = computed(() => {
  return Object.keys(props.defaultFilters?.teams).map(key => ({
    value: key,
    label: props.defaultFilters?.teams[key],
  }));
});

const subTeams = computed(() => {
  return Object.keys(props.defaultFilters?.subTeams).map(key => ({
    value: key,
    label: props.defaultFilters?.tiers[key],
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
</script>
<template>
  <Head title="Management Reports" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Management Reports
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Report Category">
        <x-select
          :single="true"
          v-model="filters.reportCategory"
          placeholder="Select Report Category"
          :options="reportCategories"
          class="w-full"
        />
      </x-field>
      <x-field label="Report Type">
        <x-select
          :single="true"
          v-model="filters.ReportType"
          placeholder="Select Report Type"
          :options="reportTypes"
          class="w-full"
        />
      </x-field>
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
      <x-field label="Transaction Type" required>
        <x-select
          v-model="filters.transactionType"
          placeholder="Search by Transaction"
          :options="transactionTypes"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Teams">
        <ComboBox
          v-model="filters.teams"
          placeholder="Search By Teams"
          :options="props.defaultFilters?.teams"
          :max-limit="3"
          deselect-all
        />
      </x-field>
      <x-field label="Sub Teams">
        <ComboBox
          v-model="filters.subTeams"
          placeholder="Search By Teams"
          :options="props.defaultFilters?.subTeams"
          :max-limit="3"
          deselect-all
        />
      </x-field>
      <x-field label="Lead Source">
        <ComboBox
          v-model="filters.leadSources"
          placeholder="Search by Lead Source"
          :options="props.defaultFilters?.leadSource"
          :max-limit="3"
          deselect-all
        />
      </x-field>
      <x-field label="Include Cancelled Policies">
        <x-select
          v-model="filters.includeCancelledPolicies"
          placeholder="Search by Cancelled Policies"
          :options="[
            { label: 'Yes', value: 'Yes' },
            { label: 'No', value: 'No' },
          ]"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Group By">
        <x-select
          v-model="filters.groupBy"
          placeholder="Search by Group"
          :options="groupBy"
          class="w-full"
        />
      </x-field>
      <x-field label="UMT (Group By 1)">
        <x-select
          v-model="filters.transactionType"
          placeholder="Search by UMT Group"
          :options="groupBy"
          class="w-full"
        />
      </x-field>
      <x-field label="UMT (Group By 2)">
        <x-select
          v-model="filters.transactionType"
          placeholder="Search by Ecommerce"
          :options="groupBy"
          class="w-full"
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
  <component :reportData="{}" :is="selectedReport"></component>
</template>
