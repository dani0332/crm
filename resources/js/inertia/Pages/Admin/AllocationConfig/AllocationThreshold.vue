<script setup>
import axios from 'axios';
import { onMounted } from 'vue';

const props = defineProps({
  teams: Object,
});

const notification = useToast();
const tabs = reactive([{
    label: "AUH",
    postfix: "(Branch)",
  },
  {
    label: "Non AUH",
    postfix: "(HQ)",
  },
]);
const activeTab = ref(0);
const loading = ref(false);

const teamsForm = useForm({
  teams: [...Object.values(props.teams)],
});

let minErrorTeam = ref('');
const validateTeams = () => {
  let valid = true;
  let teams = teamsForm.teams.map(x => {
    return {
      ...x,
      min_price: parseFloat(x.min_price || 0),
      max_price: parseFloat(x.max_price || 0),
    };
  });

  console.log(teams.length);
  return false;

  for (let i = 0; i < teams.length; i++) {
    const minPriceValue = parseFloat(
      teams[i] && teams[i].min_price == '' ? 0 : teams[i].min_price,
    );
    const maxPriceValue = parseFloat(
      teams[i + 1] && teams[i + 1].max_price == ''
        ? 0
        : teams[i + 1]?.max_price,
    );
    if (i == 0) {
      if (teams[i].min_price < 0) {
        notification.error({
          title: {
            team: teams[i].name,
            error: 'Minimum value cannot be less than zero',
          },
          position: 'top',
        });
        valid = false;
        break;
      }
      if (teams[i].max_price < 2) {
        notification.error({
          title: {
            team: teams[i].name,
            error: 'Max value cannot be 1',
          },
          position: 'top',
        });
        valid = false;
        break;
      }
    }
    if (i == 2 || i == 4) {
      var lastMaxValue = parseFloat(
        teams[i - 1].max_price == '' ? 0 : teams[i - 1].max_price,
      );

      if (minPriceValue <= lastMaxValue || minPriceValue > lastMaxValue + 1) {
        minErrorTeam = teams[i].name;
        notification.error({
          title: teams[i].name,
          message:
            'Invalid min range configuration. Please review the values for other teams.',
          position: 'top',
        });
        valid = false;
        break;
      }
      if (maxPriceValue < 2 || maxPriceValue <= minPriceValue) {
        notification.error({
          title: teams[i].name,
          message:
            'Invalid max range configuration. Please review the values for other teams.',
          position: 'top',
        });
        valid = false;
        break;
      }
    }
  }
  return valid;
};

const generateTeamsToPost = () => {
  return teamsForm.teams.map(team => {
    return {
      id: team.id,
      min: parseFloat(team.min_price),
      max: parseFloat(team.max_price),
    };
  });
};

const loadTeams = (index) => {
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
    })
};

const updateTeams = () => {
  let valid = validateTeams();
  if (valid) {
    loading.value = true;
    let teams = generateTeamsToPost();

    axios
      .post('/update-team-allocation-threshold', {
        category: tabs[activeTab.value].label,
        teams: teams
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
          : 'text-gray-500 hover:text-gray-700'
      ]"
    >
      {{ tab.label }} {{ tab.postfix }}
    </button>
  </div>

  <!-- Active Tab Content -->
  <div class="min-h-[150px] mb-4">
    <!-- Loader -->
    <div v-if="loading" class="flex justify-center items-center py-10">
      <span class="animate-spin h-6 w-6 border-2 border-primary border-t-transparent rounded-full"></span>
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
              />
              <p class="text-xs -mt-4">
                Minimum annual premium (AED) required for this {{ tabs[activeTab].label }} tier to apply.
              </p>
            </div>
            <x-input
              type="number"
              class="w-full"
              v-model="team.max_price"
              label="Max Price"
            />
          </div>
        </x-form>
      </div>

      <div class="flex justify-end gap-3 mt-5">
        <x-button size="sm" color="#ff5e00" @click="updateTeams()">
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
