<script setup>
const props = defineProps({
  totalLeadsReceived: Number,
  totalLeadsReceivedEcommerce: Number,
  totalUnAssignedLeadsReceived: Number,
  totalUnAssignedOnlySICLeadsReceived: Number,
  totalUnAssignedOnlyPaidSICLeadsReceived: Number,
  totalUnAssignedLeadsReceivedEcommerce: Number,
  teams: Object,
  carAdvisors: Array,
  teamWiseLeadsAssignedAverage: Array,
  totalUnAssignedRevivalLeads: Number,
  leadsCountByTier: Array,
  unAssignedLeadsByTier: Array,
  revivalLeadsCount: Array,
  advisorLeadsAssignedData: Array,
  assignedLeadsBySource: Array,
  leadReceivedSummaryBySource: Array,
});

const { isActive, pasue, resume } = useTimeoutPoll(fetchData, 60000);

const totalLeadsReceived = ref(props.totalLeadsReceived);
const totalLeadsReceivedEcommerce = ref(props.totalLeadsReceivedEcommerce);
const totalUnAssignedLeadsReceived = ref(props.totalUnAssignedLeadsReceived);
const totalUnAssignedOnlySICLeadsReceived = ref(
  props.totalUnAssignedOnlySICLeadsReceived,
);
const totalUnAssignedOnlyPaidSICLeadsReceived = ref(
  props.totalUnAssignedOnlyPaidSICLeadsReceived,
);
const totalUnAssignedLeadsReceivedEcommerce = ref(
  props.totalUnAssignedLeadsReceivedEcommerce,
);
const teams = ref(props.teams);
const teamWiseLeadsAssignedAverage = ref(props.teamWiseLeadsAssignedAverage);
const totalUnAssignedRevivalLeads = ref(props.totalUnAssignedRevivalLeads);
const leadsCountByTier = ref(props.leadsCountByTier);
const unAssignedLeadsByTier = ref(props.unAssignedLeadsByTier);
const revivalLeadsCount = ref(props.revivalLeadsCount);
const advisorLeadsAssignedData = ref(props.advisorLeadsAssignedData);
const leadReceivedSummaryBySource = ref(props.leadReceivedSummaryBySource);
const assignedLeadsBySource = ref(props.assignedLeadsBySource);
const carAdvisors = ref(props.carAdvisors);

const LeadRcdSummary = ref([]);
const UnassignedLeadRcdSummary = ref([]);
const revivalLeadsCountChart = ref([]);
const columnChartData = ref([]);
const loader = reactive({
  bar: false,
});

const tableHeader = ref([
  { text: 'LEAD SOURCE', value: 'source' },
  { text: 'COUNT BY LEAD SOURCE', value: 'leadSourceCount' },
  { text: 'PERCENTAGE', value: 'percentage' },
]);

const allTeams = computed(() => [...Object.values(teams.value)]);

const filters = reactive({
  teamFilter: [],
  range: [],
});

async function fetchData() {
  if (!filters.range) return;
  let response = await axios.get(
    `/get-recent-daily-stats?range=${filters.range}&teamFilter[]=${filters.teamFilter}`,
  );
  if (response.data) {
    totalLeadsReceived.value = response.data.totalLeadsReceived;
    totalLeadsReceivedEcommerce.value =
      response.data.totalLeadsReceivedEcommerce;
    totalUnAssignedLeadsReceived.value =
      response.data.totalUnAssignedLeadsReceived;
    totalUnAssignedOnlySICLeadsReceived.value =
      response.data.totalUnAssignedOnlySICLeadsReceived;
    totalUnAssignedOnlyPaidSICLeadsReceived.value =
      response.data.totalUnAssignedOnlyPaidSICLeadsReceived;
    totalUnAssignedLeadsReceivedEcommerce.value =
      response.data.totalUnAssignedLeadsReceivedEcommerce;
    totalUnAssignedRevivalLeads.value =
      response.data.totalUnAssignedRevivalLeads;
    unAssignedLeadsByTier.value = [...response.data.unAssignedLeadsByTier];
    teamWiseLeadsAssignedAverage.value = [
      ...response.data.teamWiseLeadsAssignedAverage,
    ];
    revivalLeadsCount.value = [...response.data.revivalLeadsCount];
    leadsCountByTier.value = [...response.data.leadsCountByTier];
    advisorLeadsAssignedData.value = [
      ...response.data.advisorLeadsAssignedData,
    ];
    setInitialState();
  }
}

function prepareGraphData(source, xAxisName, yAxisName) {
  var graphData = [];
  for (let index = 0; index < source.length; index++) {
    var node = source[index];
    graphData.push({ name: node[xAxisName], y: parseFloat(node[yAxisName]) });
  }
  return graphData;
}

function createAssignedChartByTier() {
  LeadRcdSummary.value = prepareGraphData(
    leadsCountByTier.value,
    'tierNames',
    'leadCount',
  );
}

function createUnassignedChartByTier() {
  UnassignedLeadRcdSummary.value = prepareGraphData(
    unAssignedLeadsByTier.value,
    'tierNames',
    'leadCount',
  );
}

function createUnassignedChartBySource() {
  revivalLeadsCountChart.value = [
    {
      name: 'Revival Leads',
      y: parseInt(revivalLeadsCount.value[0]['revival_leads']),
    },
    {
      name: 'Non Revival Leads',
      y: parseInt(revivalLeadsCount.value[0]['non_revival_leads']),
    },
  ];
}

function createLeadCountByAdvisor() {
  columnChartData.value = prepareGraphData(
    advisorLeadsAssignedData.value,
    'name',
    'total_leads',
  );
}

function getDataForAdvisor() {
  loader.bar = true;
  axios
    .post('/get-team-conversion-stats', {
      range: filters.range.join(','),
      teamFilter: filters.teamFilter,
    })
    .then(response => {
      if (response) {
        var cData = [];
        for (let index = 0; index < response.data.length; index++) {
          var node = response.data[index];
          cData.push({ name: node.name, y: parseFloat(node.total_leads) });
        }
        if (cData.length > 0) columnChartData.value = cData;
        else columnChartData.value = [{ name: '', y: 0 }];
      }
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      loader.bar = false;
    });
}

watchEffect(() => {
  setTimeout(() => {
    resume();
  }, 60000);
});

const setInitialState = () => {
  createLeadCountByAdvisor();
  createUnassignedChartByTier();
  createAssignedChartByTier();
  createUnassignedChartBySource();
};

onMounted(() => {
  let date = new Date();
  filters.range = [
    date.toISOString().split('T')[0],
    date.toISOString().split('T')[0],
  ];
  setInitialState();
});

onUnmounted(() => (isActive.value = false));
</script>
<template>
  <Head title="Accmulative Dashboard" />
  <x-card class="p-8">
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >Total leads rcvd</span
          >
          <template #tooltip>Total leads rcvd</template>
        </x-tooltip>
        <b class="block">{{ totalLeadsReceived }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >Total leads rcvd Ecom</span
          >
          <template #tooltip>Total leads rcvd Ecom</template>
        </x-tooltip>
        <b class="block">{{ totalLeadsReceivedEcommerce }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >TOTAL UNASSIGNED LEADS</span
          >
          <template #tooltip>TOTAL UNASSIGNED LEADS</template>
        </x-tooltip>
        <b class="block">{{ totalUnAssignedLeadsReceived }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >TOTAL UNASSIGNED LEADS ECOM</span
          >
          <template #tooltip>TOTAL UNASSIGNED LEADS ECOM</template>
        </x-tooltip>
        <b class="block">{{ totalUnAssignedLeadsReceivedEcommerce }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >TOTAL UNASSIGNED REVIVAL LEADS</span
          >
          <template #tooltip>TOTAL UNASSIGNED REVIVAL LEADS</template>
        </x-tooltip>
        <b class="block">{{ totalUnAssignedRevivalLeads }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >TOTAL UNASSIGNED SIC LEADS</span
          >
          <template #tooltip>TOTAL UNASSIGNED SIC LEADS</template>
        </x-tooltip>
        <b class="block">{{ totalUnAssignedOnlySICLeadsReceived }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <x-tooltip placement="bottom">
          <span
            class="text-[#308BCA] uppercase font-bold underline decoration-dotted decoration-primary-700 cursor-help"
            >TOTAL PAID UNASSIGNED SIC LEADS</span
          >
          <template #tooltip>TOTAL PAID UNASSIGNED SIC LEADS</template>
        </x-tooltip>
        <b class="block">{{ totalUnAssignedOnlyPaidSICLeadsReceived }}</b>
      </x-card>
    </div>
    <h2 class="text-center text-[#308BCA] font-bold text-2xl mt-12 mb-3">
      LEADS ASSIGNED AVERAGE
    </h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 md:grid-cols-3 gap-4">
      <x-card
        class="text-center h-auto shadow-md border p-2"
        v-for="item in teamWiseLeadsAssignedAverage"
        :key="item.teamName"
      >
        <span class="text-[#308BCA] uppercase font-bold">{{
          item.teamName
        }}</span>
        <b class="block">{{ item.stats }}</b>
      </x-card>
    </div>
  </x-card>
  <div class="mt-8">
    <div class="w-64 mb-3">
      <DatePicker
        placeholder="Select Start & End Date"
        range
        multi-calendars
        size="sm"
        v-model="filters.range"
        :max-range="30"
        :maxDate="new Date()"
        model-type="yyyy-MM-dd"
        @update:modelValue="fetchData()"
      />
    </div>

    <div class="grid grid-cols-2 gap-4">
      <ChartsPie
        :title="'Total Leads Received Summary (by tier)'"
        :seriesName="'Leads'"
        :data="LeadRcdSummary"
      />
      <ChartsPie
        :title="'Unassigned Leads Received Summary (by tier)'"
        :seriesName="'Leads'"
        :data="UnassignedLeadRcdSummary"
      />
    </div>
    <div class="grid grid-cols-2 gap-4">
      <ChartsPie
        :title="'Unassigned Leads Received Summary (by LeadSource)'"
        :seriesName="'Leads'"
        :data="revivalLeadsCountChart"
      />
    </div>
  </div>
  <div class="my-12">
    <h2 class="text-primary-500 font-bold text-2xl mb-3">
      Total Leads Received Summary (by LeadSource)
    </h2>
    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      border-cell
      hide-rows-per-page
      hide-footer
      :items="leadReceivedSummaryBySource || []"
    >
    </DataTable>
  </div>

  <div>
    <x-field label="Teams" class="w-64">
      <ComboBox
        v-model="filters.teamFilter"
        name="team_name"
        placeholder="Select Teams"
        :options="
          allTeams.map(item => ({
            value: item.id,
            label: item.name,
          }))
        "
        @update:model-value="getDataForAdvisor()"
        :loading="loader.bar"
      />
    </x-field>
    <ChartsColumn
      :title="'Lead Assign Count Summary Per Advisor'"
      :yAxisTitle="'Lead Assign Count Summary Per Advisor'"
      :seriesName="'Leads Assigned'"
      :data="columnChartData"
      :dataLabelsFormat="'{point.y:.2f}'"
    />
    <div class="mt-auto">
      <span class="text-xs">
        © AFIA Insurance Brokerage Services LLC, registration no. 85, under UAE
        Insurance Authority</span
      >
    </div>
  </div>
</template>
