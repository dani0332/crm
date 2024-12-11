<script setup>
import ActivePolicies from './Partials/ActivePolicies.vue';
import EndingPolicies from './Partials/EndingPolicies.vue';
import SalesDetail from './Partials/SalesDetail.vue';
import SalesSummary from './Partials/SaleSummary.vue';
import Transaction from './Partials/Transaction.vue';
import Installment from './Partials/Installment.vue';
import Endorsement from './Partials/Endorsement.vue';

const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
  filterOptions: Object,
  reportName: String,
});

const page = usePage();

const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : null;

const reportComponents = {
  'Active Policies': ActivePolicies,
  'Ending Policies': EndingPolicies,
  'Sales Detail': SalesDetail,
  Transaction: Transaction,
  'Sales Summary': SalesSummary,
  Installment: Installment,
  Endorsement: Endorsement,
};

const params = useUrlSearchParams('history');
const subTeams = ref([]);
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const isReportCategoryEmpty = ref(false);

const { isRequired } = useRules();

const filters = reactive({
  reportCategory: props.defaultFilters.reportCategory,
  reportType: 'Booked Policies',
  policyBookDate: props.defaultFilters.policyBookDate ?? [
    new Date(),
    new Date(),
  ],
  paymentDueDate: [dateFormat(new Date()), dateFormat(new Date())],
  paymentDate: [dateFormat(new Date()), dateFormat(new Date())],
  policyExpiredDate: [dateFormat(new Date()), dateFormat(new Date())],
  createdAt: dateFormat(new Date()),
  transactionType: props.defaultFilters.transactionType ?? [],
  teams: [],
  subTeams: [],
  leadSources: [],
  includeCancelledPolicies: 'Yes',
  groupBy: route().params.groupBy ?? 'advisor',
  utmGroupBy: [],
  export: 0, //false
  page: 1,
  lob: [],
});

const filterkeys = () => {
  const filterConditions = {
    policyExpiredDate: filters.reportCategory !== 'Ending Policies',
    policyBookDate: !(
      [
        'Sales Summary',
        'Sales Detail',
        'Transaction',
        'Installment',
        'Endorsement',
      ].includes(filters.reportCategory) &&
      filters.reportType === 'Booked Policies'
    ),
    paymentDueDate: !(
      [
        'Sales Summary',
        'Sales Detail',
        'Transaction',
        'Installment',
        'Endorsement',
      ].includes(filters.reportCategory) &&
      filters.reportType === 'Approved Transactions'
    ),
    paymentDate: !(
      [
        'Sales Summary',
        'Sales Detail',
        'Transaction',
        'Installment',
        'Endorsement',
      ].includes(filters.reportCategory) &&
      filters.reportType === 'Paid Transactions'
    ),
    activePolicies: filters.reportCategory === 'Active Policies',
    lobs: !(filters.reportCategory !== 'Sales Summary'),
  };

  if (filterConditions.policyExpiredDate) delete filters.policyExpiredDate;
  if (filterConditions.policyBookDate) delete filters.policyBookDate;
  if (filterConditions.paymentDueDate) delete filters.paymentDueDate;
  if (filterConditions.paymentDate) delete filters.paymentDate;
  if (filterConditions.lobs) delete filters.lob;
};

const loaders = reactive({
  table: false,
  subTeams: false,
});

const selectedReport = computed(() => {
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

const departments = computed(() => {
  return props.filterOptions?.departments?.map(item => {
    return { value: item.id, label: item.name };
  });
});

const lobs = computed(() => {
  return props.filterOptions?.lobs?.map(item => {
    return { value: item, label: item };
  });
});

const teams = computed(() => {
  return Object.keys(props.filterOptions?.teams).map(key => ({
    value: key,
    label: props.filterOptions?.teams[key],
  }));
});

const disabledGroupBy = computed(() => {
  return filters.reportCategory == 'Sales Summary' ? true : false;
});

const hideUmtGroup = computed(() => {
  return filters.reportCategory == 'Active Policies' ? true : false;
});

const showPaymentDueDate = computed(() => {
  return filters.reportType == 'Approved Transactions' ? true : false;
});

const showPaymentDate = computed(() => {
  return filters.reportType == 'Paid Transactions' ? true : false;
});

const showBookingDate = computed(() => {
  return filters.reportType == 'Booked Policies' ? true : false;
});

const showExpiryDate = computed(() => {
  return filters.reportType == 'Expiring Policies' ? true : false;
});

const showDateTo = computed(() => {
  return filters.reportType == 'Active Policies' ? true : false;
});

const reportCategories = ref(props.filterOptions?.reportCategories);

const transactionTypes = ref(props.filterOptions?.transactionTypes);

const groupBy = reactive([
  { label: 'Advisor', value: 'advisor' },
  { label: 'Policy Issuer', value: 'policy_issuer' },
  { label: 'Customer Group', value: 'customer_group' },
  { label: 'Insurer', value: 'insurer' },
  { label: 'Line of Business', value: 'line_of_business' },
  { label: 'Department', value: 'department' },
]);

const umtGroup = reactive([
  { label: 'UTM Source', value: 'UTM Source' },
  { label: 'UTM Medium', value: 'UTM Medium' },
  { label: 'UTM Campaign', value: 'UTM Campaign' },
]);

const reportTypes = ref([
  {
    label: 'Booked Policies',
    value: 'Booked Policies',
    report: ['Sales Summary', 'Sales Detail', 'Transaction', 'Endorsement'],
  },
  {
    label: 'Approved Transactions',
    value: 'Approved Transactions',
    report: ['Sales Summary', 'Sales Detail', 'Transaction', 'Endorsement'],
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
  {
    label: 'Approved Transactions',
    value: 'Approved Transactions',
    report: ['Installment'],
  },
  {
    label: 'Paid Transactions',
    value: 'Paid Transactions',
    report: [
      'Sales Summary',
      'Sales Detail',
      'Transaction',
      'Endorsement',
      'Installment',
    ],
  },
]);

const cleanFilters = filters => {
  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' ||
        filters[key] == null ||
        filters[key].length == 0) &&
      delete filters[key],
  );
  return filters;
};

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
  isReportCategoryEmpty.value = !filters.reportCategory;

  filterkeys();
  if (!isValid || !filters.reportCategory) return;
  filters.page = 1;
  filters.export = 0;
  router.visit(route('management-report'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

const onDataExport = flag => {
  filterkeys();
  filters.export = flag;
  filters.page = 1;
  const data = useGenerateQueryString(filters);
  const url = route('management-report-export');
  window.open(url + '?' + useObjToUrl(data));
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

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});

watch(
  () => filters.reportCategory,
  newReportCategory => {
    const selectedReport = reportTypes.value.find(x =>
      x.report.includes(newReportCategory),
    );

    if (selectedReport) {
      filters.reportType = selectedReport.value;
    }
  },
);
</script>
<template>
  <Head title="Management Reports" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Management Reports
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Report Category <span class="text-red-500">*</span>
          </label>
          <template #tooltip>
            What kind of report do you want to generate?
          </template>
        </x-tooltip>
        <x-select
          v-model="filters.reportCategory"
          placeholder="Select Report Category"
          :options="reportCategories"
          class="w-full"
          :rules="[isRequired]"
        />
      </div>
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Report Type <span class="text-red-500">*</span>
          </label>
          <template #tooltip>
            <span v-if="filters.reportType == 'Booked Policies'"
              >Booked Policies are based on booking date.</span
            >
            <span v-if="filters.reportType == 'Approved Transactions'"
              >Approved Transactions are based on payment due date.</span
            >
            <span v-if="filters.reportType == 'Paid Transactions'"
              >Paid Transactions are based on payment date.</span
            >
            <span v-if="filters.reportType == 'Expiring Policies'"
              >Expiring Policies (for Ending Policies report) are based on
              policy expiry date.</span
            >
            <span v-if="filters.reportType == 'Active Policies'"
              >Active Policies (for Active Policies report) are based on policy
              start date.</span
            >
          </template>
        </x-tooltip>
        <x-select
          v-model="filters.reportType"
          placeholder="Select Report Type"
          :options="computedReportTypes"
          class="w-full"
          :rules="[isRequired]"
        />
      </div>
      <div v-if="showBookingDate">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Booking Date <span class="text-red-500">*</span>
          </label>
          <template #tooltip> Select the booking date range </template>
        </x-tooltip>
        <DatePicker
          v-model="filters.policyBookDate"
          placeholder="Select Start & End Date"
          range
          :max-range="31"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </div>
      <div v-if="showPaymentDate">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Paid At <span class="text-red-500">*</span>
          </label>
          <template #tooltip> Select the booking date range </template>
        </x-tooltip>
        <DatePicker
          v-model="filters.paymentDate"
          placeholder="Select Start & End Date"
          range
          :max-range="31"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </div>
      <div v-if="showPaymentDueDate">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Payment Due Date <span class="text-red-500">*</span>
          </label>
          <template #tooltip> Select the payment due date range </template>
        </x-tooltip>
        <DatePicker
          v-model="filters.paymentDueDate"
          placeholder="Select Start & End Date"
          range
          :max-range="31"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </div>
      <div v-if="showExpiryDate">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Policy Expiry Date <span class="text-red-500">*</span>
          </label>
          <template #tooltip> Select the policy expiry date range </template>
        </x-tooltip>
        <DatePicker
          v-model="filters.policyExpiredDate"
          placeholder="Select Start & End Date"
          range
          :max-range="31"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </div>
      <div v-if="showDateTo">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Date To <span class="text-red-500">*</span>
          </label>
          <template #tooltip>
            When is the end date of your selected report type?
          </template>
        </x-tooltip>
        <DatePicker
          :single="true"
          v-model="filters.createdAt"
          placeholder="Select Date"
          size="sm"
          model-type="yyyy-MM-dd"
          :rules="[isRequired]"
          :onlySelect="true"
        />
      </div>
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Transaction Type
          </label>
          <template #tooltip>
            What is the transaction type you want to see?
          </template>
        </x-tooltip>
        <x-select
          v-model="filters.transactionType"
          placeholder="Search by Transaction"
          :options="transactionTypes"
          class="w-full"
        />
      </div>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Teams
          </label>
          <template #tooltip>
            What is the department you want to see?
          </template>
        </x-tooltip>
        <ComboBox
          v-model="filters.teams"
          placeholder="Search By Teams"
          :options="teams"
          deselect-all
          @update:modelValue="onTeamChange($event)"
        />
      </div>
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Sub Teams
          </label>
          <template #tooltip> What is the team you want to see? </template>
        </x-tooltip>
        <ComboBox
          v-model="filters.subTeams"
          placeholder="Search By Teams"
          :options="subTeams"
          :maxLimit="3"
          deselect-all
          :loading="loaders.subTeams"
        />
      </div>
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Lead Source
          </label>
          <template #tooltip>
            What is the lead source you want to see?
          </template>
        </x-tooltip>
        <ComboBox
          v-model="filters.leadSources"
          placeholder="Search by Lead Source"
          :options="leadSource"
          :maxLimit="10"
          deselect-all
        />
      </div>
      <div>
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Include Cancelled Policies
          </label>
          <template #tooltip>
            Do you want to include cancelled policies in the report?
          </template>
        </x-tooltip>
        <x-select
          v-model="filters.includeCancelledPolicies"
          placeholder="Search by Cancelled Policies"
          :options="[
            { label: 'Yes', value: 'Yes' },
            { label: 'No', value: 'No' },
          ]"
          class="w-full"
        />
      </div>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <div v-if="disabledGroupBy">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Group By
          </label>
          <template #tooltip>
            Based on how will the report be presented?
          </template>
        </x-tooltip>
        <x-select
          v-model="filters.groupBy"
          placeholder="Search by Group"
          :options="groupBy"
          class="w-full"
        />
      </div>
      <div v-if="!hideUmtGroup">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            UTM
          </label>
          <template #tooltip>
            Based on which UTM source will the report be presented?
          </template>
        </x-tooltip>
        <ComboBox
          :single="true"
          v-model="filters.utmGroupBy"
          placeholder="Search by Lead Source"
          :options="umtGroup"
          deselect-all
        />
      </div>
      <x-field label="Departments">
        <ComboBox
          :single="false"
          v-model="filters.department_id"
          placeholder="Search by Department"
          :options="departments"
          deselect-all
        />
      </x-field>
      <div v-if="filters.reportCategory != 'Sales Summary'">
        <x-tooltip position="top">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Line of Business
          </label>
          <template #tooltip> Line of Business assigned to the user </template>
        </x-tooltip>
        <ComboBox
          :single="false"
          v-model="filters.lob"
          placeholder="Filter by Line of Business"
          :options="lobs"
          deselect-all
        />
      </div>
    </div>

    <div class="flex gap-3 justify-end">
      <x-button
        v-if="can(permissionsEnum.EXTRACT_REPORT)"
        size="sm"
        color="#48bb78"
        @click.prevent="onDataExport(1)"
        :disabled="loaders.table"
      >
        Export to Excel
      </x-button>
      <x-button
        size="sm"
        color="#ff5e00"
        type="submit"
        :disabled="loaders.table"
        >Search</x-button
      >
      <x-button
        size="sm"
        color="primary"
        @click.prevent="onReset"
        :disabled="loaders.table"
      >
        Reset
      </x-button>
    </div>
  </x-form>
  <component
    :groupBy="route().params.groupBy ?? 'advisor'"
    :reportData="$page.props.reportData"
    :loader="loaders.table"
    :is="selectedReport"
  ></component>
</template>
