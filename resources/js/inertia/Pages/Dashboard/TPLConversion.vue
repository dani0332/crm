<script setup>
let props = defineProps({
  tplDashboardStats: Array,
  teams: Object,
  commonTeam: Number,
  tiers: Object,
});

let columnChartData = ref([]);
let commercialFilterValue = ref('All');
const selectedTeams = ref([]);
const selectedAdvisor = ref([]);
const advisors = ref([]);

const filters = computed(() => {
  return {
    team_filter: selectedTeams.value,
    userFilter: selectedAdvisor.value,
    isCommercial:
      commercialFilterValue.value == 'All' ? '' : commercialFilterValue.value,
  };
});

const allTeams = computed(() => [...Object.values(props.teams)]);

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
    .post('/get-users-by-team', { team_filter: selectedTeams.value })
    .then(response => {
      advisors.value = [...response.data];
    })
    .catch(error => {
      console.log(error);
    });
}

function getTplFilterStats() {
  router.visit(route('tpl-dashboard-view'), {
    method: 'get',
    data: filters.value,
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => setState(),
  });
}

watch(
  [
    () => selectedTeams.value,
    () => selectedAdvisor.value,
    () => commercialFilterValue.value,
  ],
  () => {
    getTplFilterStats();
  },
  { deep: true },
);

onMounted(() => {
  selectedTeams.value.push(allTeams.value[0].id);
  fetchTeamUsers();
  setState(props.tplDashboardStats);
});
</script>

<template>
  <div class="flex flex-col h-[85vh]">
    <div class="flex gap-2 justify-end">
      <x-field label="Teams">
        <ComboBox
          v-model="selectedTeams"
          name="team_name"
          placeholder="Select Teams"
          :options="
            allTeams.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
        />
      </x-field>
      <x-field label="Advisor">
        <ComboBox
          v-model="selectedAdvisor"
          placeholder="Select Advisor"
          :options="
            advisors.map(item => ({
              value: item.id,
              label: item.name,
            }))
          "
          :disabled="selectedTeams.length == 0"
          :class="{ 'cursor-no-drop': selectedTeams.length == 0 }"
        />
      </x-field>
      <x-field label="Commercial">
        <x-select
          v-model="commercialFilterValue"
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
    <ChartsColumnChart :data="columnChartData" />
    <div class="mt-auto">
      <span class="text-xs">
        © AFIA Insurance Brokerage Services LLC, registration no. 85, under UAE
        Insurance Authority</span
      >
    </div>
  </div>
</template>
