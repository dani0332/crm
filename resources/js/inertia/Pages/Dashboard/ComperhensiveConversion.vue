<script setup>
const props = defineProps({
  comprehensiveDashboardStats: Array,
  teams: Object,
  tiers: Array,
});

const params = useUrlSearchParams('history');
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;

const columnChartData = ref([]);
const advisors = ref([]);
const subTeams = ref([]);

const allTeams = computed(() => [...Object.values(props.teams)]);

const filters = reactive({
  team_filter: [],
  userFilter: [],
  sub_team_filter: [],
  tier_filter: [],
  segment_filter: 'all',
  isCommercial: 'All',
});

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = [params[key]];
    } else {
      filters[key] = params[key];
    }
  }
}

function setState() {
  columnChartData.value = [];
  const data = props.comprehensiveDashboardStats.map(x => {
    if (typeof x == 'string') return JSON.parse(x);
    else return x;
  });
  for (let index = 0; index < data[0].length; index++) {
    columnChartData.value.push({
      name: data[0][index],
      y: Number(data[1][index]),
    });
  }
}

function getComprehensiveConversionStats() {
  Object.keys(filters).forEach(
    key => filters[key] === '' && delete filters[key],
  );
  router.visit(route('comprehensive-dashboard-view'), {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => setState(),
  });
}

function fetchTeamUsers() {
  axios
    .post('/get-users-by-team', { team_filter: filters.team_filter })
    .then(response => {
      advisors.value = [...response.data];
    })
    .catch(error => {
      console.log(error);
    });
  fetchSubTeams();
}

function fetchSubTeams() {
  axios
    .post(route('getSubTeamsByTeams'), {
      team_filter: filters.team_filter,
    })
    .then(response => {
      subTeams.value = [...response.data];
    })
    .catch(error => console.log(error));
}

watch(
  () => filters,
  () => {
    getComprehensiveConversionStats();
  },
  { deep: true },
  { immediate: true },
);

onMounted(() => {
  filters.team_filter = [allTeams.value[0].id];
  fetchTeamUsers();
  setQueryStringFilters();
});
</script>
<template>

    <Head title="Comprehensive Conversion" />
    <div class="flex flex-col h-[85vh]">
        <div class="flex gap-3 justify-end">
            <x-field label="Segment" v-if="can(permissionsEnum.SEGMENT_FILTER)">
                <x-select v-model="filters.segment_filter" placeholder="Select Segment" :options="quoteSegments" class="w-full" />
            </x-field>
            <x-field label="Teams">
                <ComboBox v-model="filters.team_filter" name="team_name" placeholder="Select Teams" :options="
            allTeams.map(item => ({
              value: item.id,
              label: item.name,
            }))
          " :disabled="can(permissionsEnum.ViewTeamsFilters)" />
            </x-field>
            <x-field label="Sub Teams">
                <ComboBox v-model="filters.sub_team_filter" name="team_name" placeholder="Select Sub Teams" :options="
            subTeams.map(item => ({
              value: item.id,
              label: item.name,
            }))
          " :disabled="
            filters.team_filter.length == 0 ||
            can(permissionsEnum.ViewTeamsFilters)
          " />
            </x-field>
            <x-field label="Advisor">
                <ComboBox v-model="filters.userFilter" placeholder="Select Advisor" :options="
            advisors.map(item => ({
              value: item.id,
              label: item.name,
            }))
          " :disabled="filters.team_filter.length == 0" />
            </x-field>
            <x-field label="Tiers">
                <ComboBox v-model="filters.tier_filter" name="team_name" placeholder="Select Teams" :options="
            tiers.map(item => ({
              value: item.id,
              label: item.name,
            }))
          " />
            </x-field>
            <x-field label="Commercial">
                <x-select v-model="filters.isCommercial" placeholder="Select any option" :options="[
            { value: 'All', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]" class="w-full" />
            </x-field>
        </div>
        <ChartsColumn :title="'COMPREHENSIVE CONVERSION REPORT'" :yAxisTitle="'Total Net Conversion'"
            :seriesName="'Net Conversion'" :data="columnChartData" />
        <div class="mt-auto">
            <span class="text-xs">
                © AFIA Insurance Brokerage Services LLC, registration no. 85, under UAE
                Insurance Authority</span>
        </div>
    </div>
</template>
