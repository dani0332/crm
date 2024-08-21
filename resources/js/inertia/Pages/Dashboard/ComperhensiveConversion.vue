<script setup>
const props = defineProps({
  reportData: Array,
  filtersByLob: Object,
  filterOptions: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  teamsOptions: false,
  subteamOptions: false,
  advisorOptions: false,
});

const params = useUrlSearchParams('history');
const page = usePage();
const teamOptions = ref([]);
const subteamOptions = ref([]);
const advisorOptions = ref([]);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;

const columnChartData = ref([]);
const advisors = ref([]);
const subTeams = ref([]);

const allTeams = computed(() => [...Object.values(props.teams)]);

const filters = reactive({
  lob: quoteTypeCodeEnum.Car,
  teams: [],
  advisors: [],
  sub_teams: [],
  tiers: [],
  isCommercial: 'All',
  isEmbeddedProducts: '',
  insurance_type: '',
  insurance_for: '',
  segment_filter: 'all',
});

const cleanFilters = filters => {
  filters = removeUnusedFilters(filters);
  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' ||
        filters[key] == null ||
        filters[key].length == 0) &&
      delete filters[key],
  );
  return filters;
};

const removeUnusedFilters = filters => {
  const filtersByLob = page.props.filtersByLob;
  Object.keys(filtersByLob).forEach(key => {
    if (
      filtersByLob[key]['lobs'] &&
      !filtersByLob[key]['lobs'].includes(filters.lob)
    ) {
      delete filters[key];
    }
  });
  return filters;
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

const setDefaultValues = () => {
  if (page.props.defaultFilters && !params['page']) {
    Object.keys(page.props.defaultFilters).forEach(key => {
      if (filters.hasOwnProperty(key)) {
        filters[key] = page.props.defaultFilters[key];
      }
    });
  }
};

function setState() {
  columnChartData.value = [];
  const data = props.reportData.map(x => {
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
  const payLoad = cleanFilters(filters);
  router.visit(route('comprehensive-dashboard-view'), {
    method: 'get',
    data: {
      ...payLoad,
      ...(payLoad.tiers && {
        tiers: Array.isArray(payLoad.tiers) ? payLoad.tiers : [payLoad.tiers],
      }),
      ...(payLoad.advisors && {
        advisors: Array.isArray(payLoad.advisors)
          ? payLoad.advisors
          : [payLoad.advisors],
      }),
      ...(payLoad.teams && {
        teams: Array.isArray(payLoad.teams) ? payLoad.teams : [payLoad.teams],
      }),
      ...(payLoad.sub_teams && {
        sub_teams: Array.isArray(payLoad.sub_teams)
          ? payLoad.sub_teams
          : [payLoad.sub_teams],
      }),
    },
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => setState(),
  });
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
  window.addEventListener(
    'resize',
    function (event) {
      setState();
    },
    true,
  );
  setDefaultValues();
  setQueryStringFilters();
  onLobChange(filters.lob, true);

  if (params['teams[]'] && params['teams[]'].length > 0) {
    onTeamChange(params['teams[]'], true);
  }

  if (params['sub_teams[]'] && params['sub_teams[]'].length > 0) {
    onSubTeamChange(params['sub_teams[]'], true);
  }

  getComprehensiveConversionStats();
});

const onLobChange = (e, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.teams = [];
    filters.sub_teams = [];
    filters.advisors = [];
    filters.tiers = [];

    filters.insurance_type = '';
    filters.insurance_for = '';
    filters.isCommercial = '';
    filters.isEmbeddedProducts = false;
    teamOptions.value = [];
    subteamOptions.value = [];
    advisorOptions.value = [];
  }

  if (filters.lob === quoteTypeCodeEnum.Car && filters.teams.length == 0) {
    filters.teams = [page.props.defaultFilters.car_team_id];
  }

  if (filters.lob !== quoteTypeCodeEnum.Bike) {
    loadTeams(e);
  } else {
    loadAdvisorsByLob(e);
  }
};

const onTeamChange = (e, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.sub_teams = [];
    subteamOptions.value = [];
    filters.advisors = [];
    advisorOptions.value = [];
  }

  if (
    [quoteTypeCodeEnum.Car, quoteTypeCodeEnum.GroupMedical].includes(
      filters.lob,
    )
  ) {
    loadSubTeams(e);

    if (!(isOnMounted && filters.sub_teams.length > 0)) {
      loadAdvisors(e);
    }
  } else {
    loadAdvisors(e);
  }
};

const onSubTeamChange = (e, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.advisors = [];
  }

  advisorOptions.value = [];

  if (
    e.length == 0 &&
    [quoteTypeCodeEnum.Car, quoteTypeCodeEnum.GroupMedical].includes(
      filters.lob,
    ) &&
    filters.teams.length > 0
  ) {
    loadAdvisors(filters.teams);
  } else {
    loadAdvisorsBySubteams(e);
  }
};

const loadTeams = e => {
  if (e.length == 0) {
    return;
  }

  loaders.teamOptions = true;

  axios
    .post(`/reports/fetch-teams-by-lob`, {
      lob: e,
    })
    .then(res => {
      if (res.data.length > 0) {
        teamOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.teamOptions = false;
    });
};

const loadSubTeams = e => {
  if (e.length == 0) {
    return;
  }

  loaders.subteamOptions = true;

  axios
    .post(`/reports/fetch-subteams-by-team`, {
      teamIds: Array.isArray(e) ? e : [e],
      lob: filters.lob,
    })
    .then(res => {
      if (res.data.length > 0) {
        subteamOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.subteamOptions = false;
    });
};

const loadAdvisors = e => {
  if (e.length == 0) {
    return;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisor-by-team`, {
      teamIds: Array.isArray(e) ? e : [e],
      lob: filters.lob,
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

const loadAdvisorsBySubteams = e => {
  if (e.length == 0) {
    return;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisor-by-sub-team`, {
      teamIds: Array.isArray(e) ? e : [e],
      lob: filters.lob,
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

const loadAdvisorsByLob = e => {
  if (e.length == 0) {
    return;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisors-by-lob`, {
      lob: e,
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

const canShow = element => {
  if (page.props.filtersByLob && page.props.filtersByLob[element]) {
    const lobs = page.props.filtersByLob[element]['lobs'] ?? [];

    if (
      lobs.length == 0 ||
      (lobs.length != 0 && Object.values(lobs).includes(filters.lob))
    ) {
      return true;
    }

    return false;
  }

  return true;
};

const getAdvisorLabel = () => {
  let label = 'Advisors';
  if (
    filters.lob !== quoteTypeCodeEnum.Bike &&
    (!filters.teams || filters.teams.length == 0)
  ) {
    label = 'Advisors (select teams first)';
  }

  return label;
};

const quoteTypesOptions = computed(() => {
  return Object.keys(page.props.filterOptions.lob).map(text => ({
    label: text,
    value: page.props.filterOptions.lob[text],
  }));
});

const insuranceTypeOptions = computed(() => {
  const types = page.props.filterOptions.insurance_type;
  if (types[filters.lob]) {
    return types[filters.lob].map(option => ({
      value: option.value.toString(),
      label: option.label,
    }));
  }

  return [];
});

const insuranceForOptions = computed(() => {
  const types = page.props.filterOptions.insurance_for;
  if (types[filters.lob]) {
    return types[filters.lob].map(option => ({
      value: option.id.toString(),
      label: option.text,
    }));
  }

  return [];
});
</script>
<template>
  <Head title="Comprehensive Conversion" />
  <div class="flex flex-col h-[85vh] comprehensive-conversion-container">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4 mb-5">
      <x-field label="LOB">
        <x-select
          v-model="filters.lob"
          :options="quoteTypesOptions"
          class="w-full"
          @update:model-value="onLobChange"
        />
      </x-field>

      <x-field label="Teams" v-if="canShow('teams')">
        <ComboBox
          v-model="filters.teams"
          name="team_name"
          placeholder="Select Teams"
          :options="teamOptions"
          @update:model-value="onTeamChange"
          :loading="loaders.teamsOptions"
          :disabled="can(permissionsEnum.ViewTeamsFilters)"
        />
      </x-field>
      <x-field label="Sub Teams" v-if="canShow('sub_teams')">
        <ComboBox
          v-model="filters.sub_teams"
          name="team_name"
          placeholder="Select Sub Teams"
          :options="subteamOptions"
          @update:model-value="onSubTeamChange"
          :loading="loaders.subteamOptions"
          :disabled="can(permissionsEnum.ViewTeamsFilters)"
        />
      </x-field>
      <x-field :label="getAdvisorLabel()">
        <ComboBox
          v-model="filters.advisors"
          placeholder="Select Advisor"
          :options="advisorOptions"
          :loading="loaders.advisorOptions"
        />
      </x-field>
      <x-tooltip placement="top" v-if="canShow('tiers')">
        <template #tooltip v-if="filters.lob === quoteTypeCodeEnum.Bike">
          Development for Bike Tiers still in progress
        </template>
        <template #tooltip v-else> Select Tiers </template>

        <x-field class="w-full" label="Tiers">
          <ComboBox
            :disabled="filters.lob === quoteTypeCodeEnum.Bike"
            :class="{
              'opacity-50': filters.lob === quoteTypeCodeEnum.Bike,
            }"
            v-model="filters.tiers"
            name="team_name"
            placeholder="Select Teams"
            :options="
              Object.keys(filterOptions.tiers).map(key => ({
                value: key,
                label: filterOptions.tiers[key],
              }))
            "
          />
        </x-field>
      </x-tooltip>
      <x-select
        v-if="canShow('isEmbeddedProducts')"
        v-model="filters.isEmbeddedProducts"
        label="Include Embedded Products"
        placeholder="Select any option"
        :options="[
          { value: 'true', label: 'Yes' },
          { value: 'false', label: 'No' },
        ]"
      />
      <x-field label="Commercial" v-if="canShow('isCommercial')">
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
      <x-field label="Insurance Type" v-if="canShow('insurance_type')">
        <x-select
          v-model="filters.insurance_type"
          label=""
          placeholder="Select insurance type"
          :options="[
            { value: '', label: 'Select insurance type' },
            ...insuranceTypeOptions,
          ]"
          class="w-full"
        />
      </x-field>
      <x-field label="Insurance For" v-if="canShow('insurance_for')">
        <x-select
          v-model="filters.insurance_for"
          placeholder="Select insurance for"
          :options="[
            { value: '', label: 'Select insurance for' },
            ...insuranceForOptions,
          ]"
          class="w-full"
        />
      </x-field>
      <x-field
        label="Segment"
        v-if="can(permissionsEnum.SEGMENT_FILTER) && canShow('segment_filter')"
      >
        <ComboBox
          v-model="filters.segment_filter"
          placeholder="Select Segment"
          :options="quoteSegments"
          class="w-full"
          :single="true"
        />
      </x-field>
    </div>
    <ChartsColumn
      :title="'COMPREHENSIVE CONVERSION REPORT'"
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
