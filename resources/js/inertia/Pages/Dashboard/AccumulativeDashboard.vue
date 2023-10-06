<script setup>
const props = defineProps({
  totalLeadsReceived: Number,
  totalLeadsReceivedEcommerce: Number,
  totalUnAssignedLeadsReceived: Number,
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
});

const LeadRcdSummary = ref([]);
const UnassignedLeadRcdSummary = ref([]);
const revivalLeadsCountChart = ref([]);
const columnChartData = ref([]);

const tableHeader = ref([
  { text: 'LEAD SOURCE', value: 'uuid' },
  { text: 'COUNT BY LEAD SOURCE', value: 'first_name' },
  { text: 'PERCENTAGE', value: 'last_name' },
]);

const allTeams = computed(() => [...Object.values(props.teams)]);

const filters = reactive({
  teamFilter: [],
  range: [],
});

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
    props.leadsCountByTier,
    'tierNames',
    'leadCount',
  );
}

function createUnassignedChartByTier() {
  UnassignedLeadRcdSummary.value = prepareGraphData(
    props.unAssignedLeadsByTier,
    'tierNames',
    'leadCount',
  );
}

function createUnassignedChartBySource() {
  revivalLeadsCountChart.value = [
    {
      name: 'Revival Leads',
      y: parseInt(props.revivalLeadsCount[0]['revival_leads']),
    },
    {
      name: 'Non Revival Leads',
      y: parseInt(props.revivalLeadsCount[0]['non_revival_leads']),
    },
  ];
}

function createLeadCountByAdvisor() {
  columnChartData.value = prepareGraphData(
    props.advisorLeadsAssignedData,
    'name',
    'total_leads',
  );
}

function getDataForAdvisor() {
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
    });
}

watch(
  () => filters.range,
  () => {
    createLeadCountByAdvisor();
    createUnassignedChartByTier();
    createAssignedChartByTier();
    createUnassignedChartBySource();
    // getDataForAdvisor();
  },
);

watch(
  () => filters.teamFilter,
  () => {
    // getDataForAdvisor();
  },
);

onMounted(() => {
  filters.range = [new Date().toDateString(), new Date().toDateString()];
});
</script>
<template>
  <Head title="Accmulative Dashboard" />
  <x-card class="p-8">
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
      <x-card class="text-center h-auto shadow-md border p-2">
        <span class="text-[#308BCA] uppercase font-bold">Total leads rcvd</span>
        <b class="block">{{ totalLeadsReceived }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <span class="text-[#308BCA] uppercase font-bold"
          >Total leads rcvd Ecom</span
        >
        <b class="block">{{ totalLeadsReceivedEcommerce }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <span class="text-[#308BCA] uppercase font-bold"
          >TOTAL UNASSIGNED LEADS</span
        >
        <b class="block">{{ totalUnAssignedLeadsReceived }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <span class="text-[#308BCA] uppercase font-bold"
          >TOTAL UNASSIGNED LEADS ECOM</span
        >
        <b class="block">{{ totalUnAssignedLeadsReceivedEcommerce }}</b>
      </x-card>
      <x-card class="text-center h-auto shadow-md border p-2">
        <span class="text-[#308BCA] uppercase font-bold"
          >TOTAL UNASSIGNED REVIVAL LEADS</span
        >
        <b class="block">{{ totalUnAssignedRevivalLeads }}</b>
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
        model-type="yyyy-MM-dd"
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
    <h2 class="text-[#308BCA] font-bold text-2xl mb-3">
      Total Leads Received Summary (by LeadSource)
    </h2>
    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
      :items="[]"
    >
    </DataTable>
  </div>

  <div>
    <x-field label="Teams" class="w-64 ml-auto">
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
      />
    </x-field>
    <ChartsColumn
      :title="'Lead Assign Count Summary Per Advisor'"
      :yAxisTitle="'Lead Assign Count Summary Per Advisor'"
      :seriesName="'Leads Assigned'"
      :data="columnChartData"
    />
    <div class="mt-auto">
      <span class="text-xs">
        © AFIA Insurance Brokerage Services LLC, registration no. 85, under UAE
        Insurance Authority</span
      >
    </div>
  </div>
</template>