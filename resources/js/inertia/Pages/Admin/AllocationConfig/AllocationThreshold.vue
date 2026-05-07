<script setup>
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted } from 'vue';

const props = defineProps({
  teams: Object,
});

const page = usePage();

/** Role- and direct-permissions via shared auth; enum map via HandleInertiaRequests `permissionsEnum`. */
const canEditTeamAllocationThreshold = computed(() =>
  useCan(page.props.permissionsEnum?.TEAM_ALLOCATION_THRESHOLD_EDIT ?? ''),
);

const notification = useToast();
const tabs = reactive([
  {
    label: 'AUH',
    postfix: '(Branch)',
  },
  {
    label: 'Non AUH',
    postfix: '(HQ)',
  },
]);
const activeTab = ref(0);
const loading = ref(false);

const teamsForm = useForm({
  teams: [...Object.values(props.teams)],
});

let minErrorTeam = ref('');
const validateTeams = () => {
  let teams = teamsForm.teams.map(team => {
    return {
      ...team,
      min_price: parseFloat(team.min_price || 0),
      max_price: parseFloat(team.max_price || 0),
    };
  });

  for (let i = 0; i < teams.length; i++) {
    // Check min value against 1 and max value
    if (teams[i].min_price < 1 || teams[i].min_price > teams[i].max_price) {
      notification.error({
        title: {
          team: teams[i].name,
          error:
            'Minimum value cannot be less than 1 and must be less than max value',
        },
        position: 'top',
      });

      return false;
    }

    // Chcek sequence (as per business requirement)
    // Comment this sequence for now as business might need it later
    /*
    if (i != 0 && teams[i].min_price !== teams[i - 1].max_price + 1) {
      notification.error({
        title: {
          team: teams[i].name,
          error:
            'Tier start values must increase sequentially and must not overlap or skip ranges',
        },
        position: 'top',
      });
      return false;
    }
    */
  }

  return true;
};

const generateTeamsToPost = () => {
  return teamsForm.teams.map(team => {
    return {
      team_id: team.id,
      team_name: team.name,
      min: parseFloat(team.min_price),
      max: parseFloat(team.max_price),
    };
  });
};

const loadTeams = index => {
  activeTab.value = index;
  loading.value = true;

  // Get teams
  axios
    .get(`/generic/teams-by-category/${tabs[index].label}`)
    .then(response => {
      teamsForm.teams = response.data.teams;
    })
    .catch(error => {
      notification.error({
        title: 'Error',
        message: 'An error occurred while fetching the teams',
        position: 'top',
      });
    })
    .finally(() => {
      loading.value = false;
    });
};

const updateTeams = () => {
  let valid = validateTeams();
  if (valid) {
    loading.value = true;
    let teams = generateTeamsToPost();

    axios
      .post('/update-team-allocation-threshold', {
        category: tabs[activeTab.value].label,
        teams: teams,
      })
      .then(response => {
        notification.success({
          title: 'Allocation Threshold updated successfully',
          position: 'top',
        });
      })
      .catch(error => {
        notification.error({
          title: 'Error',
          message: 'An error occurred while updating the allocation threshold',
          position: 'top',
        });
      })
      .finally(() => {
        loading.value = false;
      });
  }
};

onMounted(() => {
  loadTeams(activeTab.value);
});
</script>
<template>
  <Head title="Allocation Threshold" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Allocation Threshold</h2>
  </div>
  <x-divider class="my-4" />

  <!-- Disclaimer -->
  <p class="text-sm text-red-600 mt-2 mb-2">
    <span class="font-semibold">Effective 3rd April 2026:</span>
    price-threshold routing is disabled for Entry Level, Good, and Best teams.
    These leads will be routed based on customer intent. GBP routing continues
    based on threshold and GBP nationality pool.
  </p>

  <!-- Tabs -->
  <div class="flex border-b border-gray-300 mb-4">
    <button
      v-for="(tab, index) in tabs"
      :key="index"
      @click="loadTeams(index)"
      :disabled="loading"
      :class="[
        'px-4 py-2 font-semibold',
        loading ? 'opacity-50 cursor-not-allowed' : '',
        activeTab === index
          ? 'border-b-2 border-primary text-primary'
          : 'text-gray-500 hover:text-gray-700',
      ]"
    >
      {{ tab.label }} {{ tab.postfix }}
    </button>
  </div>

  <!-- Active Tab Content -->
  <div class="min-h-[150px] mb-4">
    <!-- Loader -->
    <div v-if="loading" class="flex justify-center items-center py-10">
      <span
        class="animate-spin h-6 w-6 border-2 border-primary border-t-transparent rounded-full"
      ></span>
    </div>

    <!-- Content -->
    <div v-else>
      <div v-for="team in teamsForm.teams" :key="team.name">
        <h2 class="my-3 font-semibold text-primary">{{ team.name }}:</h2>
        <x-form :auto-focus="false">
          <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
            <div>
              <x-input
                type="number"
                class="w-full"
                v-model="team.min_price"
                label="Min Price"
                :disabled="
                  team.name != 'GBP' && !canEditTeamAllocationThreshold
                "
              />
              <p class="text-xs -mt-4">
                Minimum annual premium (AED) required for this
                {{ tabs[activeTab].label }} tier to apply.
              </p>
            </div>
            <x-input
              type="number"
              class="w-full"
              v-model="team.max_price"
              label="Max Price"
              :disabled="team.name != 'GBP' && !canEditTeamAllocationThreshold"
            />
          </div>
        </x-form>
      </div>

      <div class="flex justify-end gap-3 mt-5">
        <x-button
          size="sm"
          color="#ff5e00"
          @click="updateTeams()"
          :disabled="!canEditTeamAllocationThreshold"
        >
          Update {{ tabs[activeTab].label }}
        </x-button>
      </div>
    </div>
  </div>

  <HealthRoutingLogs
    type="CONFIGURATION"
    :teamCategory="tabs[activeTab].label"
  />
</template>
