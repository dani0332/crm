<script setup>
const props = defineProps({
  teams: Object,
});

const notification = useToast();

const teamsForm = useForm({
  teams: [...Object.values(props.teams)],
});

let minErrorTeam = ref('');
const checkMinPriceEBP = computed(() => {});
const validateTeams = () => {
  let valid = true;
  let teams = teamsForm.teams.map(x => {
    return {
      ...x,
      min_price: parseFloat(x.min_price == '' ? 0 : x.min_price),
      max_price: parseFloat(x.max_price == '' ? 0 : x.max_price),
    };
  });
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
      }
      if (maxPriceValue < 2 || maxPriceValue <= minPriceValue) {
        notification.error({
          title: teams[i].name,
          message:
            'Invalid max range configuration. Please review the values for other teams.',
          position: 'top',
        });
      }
    }
    valid = false;
  }
};

const updateTeams = () => {
  validateTeams();
  //   if () {
  //     teamsForm.post(route('allocation-threshold.update'), {
  //       preserveState: true,
  //       preserveScroll: true,
  //       onSuccess: () => {
  //         toast.success('Allocation Threshold updated successfully');
  //       },
  //     });
  //   }
};
</script>
<template>
  <Head title="Allocation Threshold" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Allocation Threshold</h2>
  </div>
  <x-divider class="my-4" />
  <div v-for="team in teamsForm.teams" :key="team.name">
    <h2 class="my-3 font-semibold text-primary">{{ team.name }}:</h2>
    <x-form :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
        <x-field label="Min Price">
          <x-input
            type="number"
            class="w-full"
            v-model="team.min_price"
            :hasError="minErrorTeam == team.name"
          />
        </x-field>
        <x-field label="Max Price">
          <x-input type="number" class="w-full" v-model="team.max_price" />
        </x-field>
      </div>
    </x-form>
  </div>
  <div class="flex justify-end gap-3 mt-5">
    <x-button size="sm" color="#ff5e00" @click="updateTeams">Update</x-button>
  </div>
</template>