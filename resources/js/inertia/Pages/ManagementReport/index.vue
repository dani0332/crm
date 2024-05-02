<script setup>
import ActivePolicies from './Partials/ActivePolicies.vue';
import EndingPolicies from './Partials/EndingPolicies.vue';
import SalesDetail from './Partials/SalesDetail.vue';
import SalesSummary from './Partials/SaleSummary.vue';
import Transaction from './Partials/Transaction.vue';

const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
  filterOptions: Object,
  reportName: String,
});

const reportComponents = {
  'Active Policies': ActivePolicies,
  'Ending Policies': EndingPolicies,
  'Sales Detail': SalesDetail,
  Transaction: Transaction,
  'Sales Summary': SalesSummary,
};

const subTeams = ref([]);

const { isRequired } = useRules();

const filterkeys = () => {
  if (filters.reportCategory != 'Ending Policies')
    delete filters.policyExpiredDate;
  if (
    filters.reportCategory != 'Sales Summary' &&
    filters.reportCategory != 'Sales Detail' &&
    filters.reportCategory != 'Transaction'
  ) {
    delete filters.policyIssuanceDate;
    delete filters.paymentDueDate;
  }
  if (filters.reportCategory == 'Active Policies') {
    delete filters.policyIssuanceDate;
    delete filters.paymentDueDate;
    delete filters.policyExpiredDate;
  }
  if (
    (filters.reportCategory == 'Sales Summary' ||
      filters.reportCategory == 'Sales Detail') &&
    filters.reportType == 'Issued Policies'
  ) {
    delete filters.paymentDueDate;
  }
  if (
    (filters.reportCategory == 'Sales Summary' ||
      filters.reportCategory == 'Sales Detail') &&
    filters.reportType == 'Transaction Payments'
  ) {
    delete filters.policyIssuanceDate;
  }
};

let filters = reactive({
  reportCategory: props.defaultFilters.reportCategory,
  reportType: 'Issued Policies',
  policyIssuanceDate: props.defaultFilters.policyIssuanceDate ?? [
    new Date(),
    new Date(),
  ],
  paymentDueDate: [new Date(), new Date()],
  policyExpiredDate: [new Date(), new Date()],
  createdAt: new Date(),
  transactionType: props.defaultFilters.transactionType ?? [],
  teams: [],
  subTeams: [],
  leadSources: [],
  includeCancelledPolicies: null,
  groupBy: route().params.groupBy ?? 'advisor',
  utmGroupBy: [],
  page: 1,
});

const loaders = reactive({
  table: false,
  subTeams: false,
});

let selectedReport = computed(() => {
  console.log(props.reportName);
  return reportComponents[props.reportName] ?? SalesSummary;
});

const computedReportTypes = computed(() => {
  const filterCondition = filters.reportCategory ?? null;

  return filterCondition
    ? reportTypes.value.filter(x => x.report.includes(filterCondition))
    : reportTypes.value;
});

const leadSource = computed(() => {
  return Object.keys(props.filterOptions?.leadSources).map(key => ({
    value: key,
    label: props.filterOptions?.leadSources[key],
  }));
});

const teams = computed(() => {
  return Object.keys(props.filterOptions?.teams).map(key => ({
    value: key,
    label: props.filterOptions?.teams[key],
  }));
});

const disabledGroupBy = computed(() => {
  return filters.reportCategory == 'Sales Summary' ?? false;
});

const hideUmtGroup = computed(() => {
  return filters.reportCategory == 'Active Policies' ?? false;
});

const showPaymentDueDate = computed(() => {
  return filters.reportType == 'Transaction Payments' ?? false;
});

const showIssuanceDate = computed(() => {
  return filters.reportType == 'Issued Policies' ?? false;
});

const showExpiryDate = computed(() => {
  return filters.reportType == 'Expiring Policies' ?? false;
});

const showDateTo = computed(() => {
  return filters.reportType == 'Active Policies' ?? false;
});

const reportCategories = ref(props.filterOptions?.reportCategories);

const transactionTypes = ref(props.filterOptions?.transactionTypes);

const groupBy = reactive([
  { label: 'Advisor', value: 'advisor' },
  { label: 'Policy Issuer', value: 'policy_issuer' },
  { label: 'Customer Group', value: 'customer_group' },
  { label: 'Insurer', value: 'insurer' },
  { label: 'Line of Business', value: 'line_of_business' },
]);

const umtGroup = reactive([
  { label: 'UTM Source', value: 'UTM Source' },
  { label: 'UTM Medium', value: 'UTM Medium' },
  { label: 'UTM Campaign', value: 'UTM Campaign' },
]);

const reportTypes = ref([
  {
    label: 'Issued Policies',
    value: 'Issued Policies',
    report: ['Sales Summary', 'Sales Detail'],
  },
  {
    label: 'Transaction Payments',
    value: 'Transaction Payments',
    report: ['Sales Summary', 'Sales Detail', 'Transaction'],
  },
  {
    label: 'Expiring Policies',
    value: 'Expiring Policies',
    report: ['Ending Policies'],
  },
  {
    label: 'Active Policies',
    value: 'Active Policies',
    report: ['Active Policies'],
  },
]);

watch(
  () => filters.reportCategory,
  (newReportCategory, oldReportCategory) => {
    // This function will only run when reportCategory changes
    // Your logic to update reportType based on reportCategory
    let selectedReport = reportTypes.value.find(x =>
      x.report.includes(newReportCategory),
    );

    if (selectedReport) {
      filters.reportType = selectedReport.value;
    }
  },
);

const onTeamChange = e => {
  if (e.length == 0) return;

  filters.subTeams = [];
  loaders.subTeams = true;
  axios
    .post(`/get-sub-teams-by-team`, {
      team_filter: Array.isArray(e) ? e : [e],
    })
    .then(res => {
      if (res.data.length > 0) {
        subTeams.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id,
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.subTeams = false;
    });
};

const onSubmit = isValid => {
  filterkeys();
  if (!isValid) return;
  filters.page = 1;
  router.visit(route('management-report'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

function onReset() {
  router.visit(route('management-report'), {
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
      <x-field label="Report Category" required>
        <x-select
          v-model="filters.reportCategory"
          placeholder="Select Report Category"
          :options="reportCategories"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <x-field label="Report Type" required>
        <x-select
          v-model="filters.reportType"
          placeholder="Select Report Type"
          :options="computedReportTypes"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <x-field v-if="showIssuanceDate" label="Policy Issuance Date" required>
        <DatePicker
          v-model="filters.policyIssuanceDate"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </x-field>
      <x-field v-if="showPaymentDueDate" label="Payment Due Date" required>
        <DatePicker
          v-model="filters.paymentDueDate"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </x-field>
      <x-field v-if="showExpiryDate" label="Policy Expiry Date" required>
        <DatePicker
          v-model="filters.policyExpiredDate"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </x-field>
      <x-field v-if="showDateTo" label="Date To" required>
        <DatePicker
          :single="true"
          v-model="filters.createdAt"
          placeholder="Select Date"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </x-field>
      <x-field label="Transaction Type">
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
          :options="teams"
          deselect-all
          @update:modelValue="onTeamChange($event)"
        />
      </x-field>
      <x-field label="Sub Teams">
        <ComboBox
          v-model="filters.subTeams"
          placeholder="Search By Teams"
          :options="subTeams"
          :maxLimit="3"
          deselect-all
          :loading="loaders.subTeams"
        />
      </x-field>
      <x-field label="Lead Source">
        <ComboBox
          v-model="filters.leadSources"
          placeholder="Search by Lead Source"
          :options="leadSource"
          :maxLimit="3"
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
      <x-field label="Group By" v-if="disabledGroupBy">
        <x-select
          v-model="filters.groupBy"
          placeholder="Search by Group"
          :options="groupBy"
          class="w-full"
        />
      </x-field>
      <x-field label="UTM" v-if="!hideUmtGroup">
        <ComboBox
          :single="true"
          v-model="filters.utmGroupBy"
          placeholder="Search by Lead Source"
          :options="umtGroup"
          deselect-all
        />
        <!-- <x-select
          v-model="filters.utmGroupBy"
          placeholder="Search by UTM Group"
          :options="umtGroup"
          class="w-full"
        /> -->
      </x-field>
    </div>

    <div class="flex gap-3 justify-end">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <component
    :groupBy="route().params.groupBy ?? 'advisor'"
    :reportData="props.reportData"
    :is="selectedReport"
  ></component>
</template>
