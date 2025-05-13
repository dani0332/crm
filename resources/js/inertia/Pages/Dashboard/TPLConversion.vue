<script setup>
let props = defineProps({
  tplDashboardStats: Array,
  teams: Object,
  tiers: Object,
});

const params = useUrlSearchParams('history');
const columnChartData = ref([]);
const advisors = ref([]);

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;

const allTeams = computed(() => [...Object.values(props.teams)]);

const filters = reactive({
  team_filter: [],
  userFilter: [],
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
  for (let index = 0; index < props.tplDashboardStats[0].length; index++) {
    columnChartData.value.push({
      name: props.tplDashboardStats[0][index],
      y: Number(props.tplDashboardStats[1][index]),
    });
  }
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
}

function getTplFilterStats() {
  Object.keys(filters).forEach(
    key => filters[key] === '' && delete filters[key],
  );
  router.visit(route('tpl-dashboard-view'), {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => setState(),
  });
}

watch(
  () => filters,
  () => {
    getTplFilterStats();
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
  <Head title="TPL Conversion" />
  <div class="flex flex-col h-[85vh]">
    <div class="flex gap-3 justify-end">
      <x-field label="Segment" v-if="can(permissionsEnum.SEGMENT_FILTER)">
        <x-select
          v-model="filters.segment_filter"
          placeholder="Select Segment"
          :options="quoteSegments"
          class="w-full min-w-[200px]"
          filterable
          filterPlaceholder="Filter Segment...."
        />
      </x-field>
      <x-field label="Teams">
        <x-select
          v-model="filters.team_filter"
          placeholder="Select Teams"
          :options="
            allTeams.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          :disabled="can(permissionsEnum.ViewTeamsFilters)"
          filterable
          filterPlaceholder="Filter Teams...."
          class="w-full min-w-[200px]"
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.team_filter = allTeams.map(item => item.id)"
              @clear="filters.team_filter = []"
            />
          </template>
        </x-select>
      </x-field>
      <x-field label="Advisor">
        <x-select
          v-model="filters.userFilter"
          placeholder="Select Advisor"
          :options="
            advisors.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          :disabled="filters.team_filter.length == 0"
          :class="{ 'cursor-no-drop': filters.team_filter.length == 0 }"
          multiple
          truncate
          class="w-full min-w-[200px]"
          filterable
          filterPlaceholder="Filter Advisor...."
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.userFilter = advisors.map(item => item.id)"
              @clear="filters.userFilter = []"
            />
          </template>
        </x-select>
      </x-field>
      <x-field label="Commercial">
        <x-select
          v-model="filters.isCommercial"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
      </x-field>
    </div>
    <ChartsColumn
      :title="'TPL CONVERSION REPORT'"
      :yAxisTitle="'Total Net Conversion'"
      :seriesName="'Net Conversion'"
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
