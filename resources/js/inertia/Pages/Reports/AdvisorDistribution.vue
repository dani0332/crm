<script setup>
defineProps({
  reportData: Object,
  filtersByLob: Object,
  filterOptions: Object,
  defaultFilters: Object,
  assignmentTypes: Object,
});
const loaders = reactive({
  table: false,
  teamsOptions: false,
  subteamOptions: false,
  advisorOptions: false,
});
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;
const teamOptions = ref([]);
const subteamOptions = ref([]);
const advisorOptions = ref([]);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const tableHeader = ref([]);
const canShowFooterColumn = ref([]);
const toast = useToast();

const params = useUrlSearchParams('history');
const filters = reactive({
  lob: quoteTypeCodeEnum.Car,
  advisorAssignedDates: [],
  tiers: [],
  leadSources: [],
  advisors: [],
  teams: [],
  sub_teams: [],
  isCommercial: 'All',
  isEmbeddedProducts: '',
  segment_filter: 'all',
  sic_advisor_requested: 'All',
  page: 1,
  insurance_type: '',
  insurance_for: '',
  travel_coverage: '',
  assignmentType: 'All',
});

function onSubmit(isValid, isMounted = false) {
  if (!filters.lob && isMounted === false) {
    toast.error({
      title: 'Please select LOB',
      position: 'top',
    });
    return;
  }

  if (isValid) {
    filters.page = 1;
    const payLoad = cleanFilters(filters);
    router.visit('/reports/advisor-distribution', {
      method: 'get',
      data: {
        ...payLoad,
        ...(payLoad.tiers && {
          tiers: Array.isArray(payLoad.tiers) ? payLoad.tiers : [payLoad.tiers],
        }),
        ...(payLoad.leadSources && {
          leadSources: Array.isArray(payLoad.leadSources)
            ? payLoad.leadSources
            : [payLoad.leadSources],
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
      onBefore: () => (loaders.table = true),
      onFinish: () => {
        setTableHeader();
        loaders.table = false;
      },
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  setDefaultValues();

  router.visit('/reports/advisor-distribution', {
    method: 'get',
    data: {
      advisorAssignedDates: filters.advisorAssignedDates,
      page: 1,
    },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

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

onMounted(() => {
  setDefaultValues();
  setQueryStringFilters();
  onLobChange(filters.lob, true);

  if (params['teams[]'] && params['teams[]'].length > 0) {
    onTeamChange(params['teams[]'], true);
  }

  if (params['sub_teams[]'] && params['sub_teams[]'].length > 0) {
    onSubTeamChange(params['sub_teams[]'], true);
  }

  onSubmit(true, true);
});

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
};

const setTableHeader = () => {
  let headers = [
    {
      text: 'Advisor Name',
      value: 'advisor_name',
    },
    {
      text: 'Total Leads',
      value: 'total_leads',
    },
  ];
  canShowFooterColumn.value = false;

  if ([quoteTypeCodeEnum.Car].includes(filters.lob)) {
    headers.push(
      {
        text: 'TIER 0',
        value: 'tier_0_lead_count',
      },
      {
        text: 'TIER 1',
        value: 'tier_1_lead_count',
      },
      {
        text: 'TIER 2',
        value: 'tier_2_lead_count',
      },
      {
        text: 'TIER 3',
        value: 'tier_3_lead_count',
      },
      {
        text: 'TIER 4',
        value: 'tier_4_lead_count',
      },
      {
        text: 'TIER 5',
        value: 'tier_5_lead_count',
      },
      {
        text: 'TIER L',
        value: 'tier_l_lead_count',
      },
      {
        text: 'TIER H',
        value: 'tier_h_lead_count',
      },
      {
        text: 'TIER R',
        value: 'tier_r_lead_count',
      },
      {
        text: 'TIER 6 NON-ECOM',
        value: 'tier_6_lead_count',
      },
      {
        text: 'TIER 6 ECOM',
        value: 'tier_6_lead_count_e',
      },
      {
        text: 'TIER TR ECOM',
        value: 'tier_tr_lead_count_e',
      },
      {
        text: 'TIER TR NON-ECOM',
        value: 'tier_tr_lead_count',
      },
      {
        text: 'TOTAL LEAD COST',
        value: 'total_lead_cost',
      },
    );

    canShowFooterColumn.value = true;
  }

  tableHeader.value = headers;
};

const onLobChange = (e, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.teams = [];
    filters.sub_teams = [];
    filters.advisors = [];
    teamOptions.value = [];
    subteamOptions.value = [];
    advisorOptions.value = [];
    filters.insurance_type = '';
    filters.insurance_for = '';
    filters.travel_coverage = '';
    filters.isCommercial = '';
    filters.is_ecommerce = '';
    filters.tiers = [];
  } else {
    setTableHeader();
  }

  if (!isOnMounted) {
    filters.teams = [];
    filters.sub_teams = [];
    filters.advisors = [];
    teamOptions.value = [];
    subteamOptions.value = [];
    advisorOptions.value = [];
    filters.insurance_type = '';
    filters.insurance_for = '';
    filters.travel_coverage = '';
    filters.isCommercial = '';
    (filters.isEmbeddedProducts = ''), (filters.is_ecommerce = '');
    filters.tiers = [];
  } else {
    setTableHeader();
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

const onInsuranceTypeChange = e => {
  filters.travel_coverage = '';
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

const isDisabled = element => {
  if (
    page.props.filtersByLob &&
    page.props.filtersByLob[element] &&
    filters.lob
  ) {
    const canView =
      page.props.filtersByLob[element]['can_view'][[filters.lob]] ?? true;

    if (canView) {
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

const travelCoverageOptions = computed(() => {
  const types = page.props.filterOptions.travel_coverage;
  if (types[filters.lob] && types[filters.lob][filters.insurance_type]) {
    return types[filters.lob][filters.insurance_type].map(option => ({
      value: option.value,
      label: option.label,
    }));
  }

  return [];
});
</script>

<template>
  <div>
    <Head title="Advisor Distribution Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Distribution Report
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <ComboBox
          v-model="filters.lob"
          label="LOB"
          placeholder="Select LOB"
          :options="quoteTypesOptions"
          class="w-full"
          :single="true"
          @update:modelValue="onLobChange"
        />

        <DatePicker
          v-model="filters.advisorAssignedDates"
          label="Advisor Assigned Date"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
        />
        <x-tooltip placement="top" v-if="canShow('tiers')">
          <template #tooltip v-if="filters.lob === quoteTypeCodeEnum.Bike">
            Development for Bike Tiers still in progress
          </template>
          <template #tooltip v-else> Select Tiers </template>
          <ComboBox
            :disabled="filters.lob === quoteTypeCodeEnum.Bike"
            :class="{
              'opacity-50': filters.lob === quoteTypeCodeEnum.Bike,
            }"
            v-model="filters.tiers"
            label="Tiers"
            placeholder="Search by Tiers"
            :options="
              Object.keys(filterOptions.tiers).map(key => ({
                value: key,
                label: filterOptions.tiers[key],
              }))
            "
          />
        </x-tooltip>
        <ComboBox
          v-if="canShow('teams')"
          :disabled="!isDisabled('teams')"
          :class="{
            'opacity-50': !isDisabled('teams'),
          }"
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="teamOptions"
          @update:model-value="onTeamChange"
          :loading="loaders.teamsOptions"
        />

        <ComboBox
          v-if="canShow('sub_teams')"
          :disabled="!isDisabled('sub_teams')"
          :class="{
            'opacity-50': !isDisabled('sub_teams'),
          }"
          v-model="filters.sub_teams"
          label="SubTeams"
          placeholder="Search by SubTeams"
          :options="subteamOptions"
          @update:model-value="onSubTeamChange"
          :loading="loaders.subteamOptions"
        />

        <ComboBox
          v-if="canShow('advisors')"
          :disabled="!isDisabled('advisors')"
          :class="{
            'opacity-50': !isDisabled('advisors'),
          }"
          v-model="filters.advisors"
          :label="getAdvisorLabel()"
          :options="advisorOptions"
          :loading="loaders.advisorOptions"
        />
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
        <x-select
          v-if="canShow('isCommercial')"
          v-model="filters.isCommercial"
          label="Commercial"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />

        <ComboBox
          v-model="filters.leadSources"
          label="Lead Source"
          placeholder="Search by Lead Source"
          :options="
            Object.keys(filterOptions.leadSources).map(key => ({
              value: key,
              label: filterOptions.leadSources[key],
            }))
          "
          :max-limit="3"
        />
        <x-select
          v-if="canShow('insurance_type')"
          v-model="filters.insurance_type"
          label="Insurance Type"
          placeholder="Select insurance type"
          :options="[
            { value: '', label: 'Select insurance type' },
            ...insuranceTypeOptions,
          ]"
          class="w-full"
          @update:model-value="onInsuranceTypeChange"
        />
        <x-select
          v-if="canShow('insurance_for')"
          v-model="filters.insurance_for"
          label="Insurance For"
          placeholder="Select insurance for"
          :options="[
            { value: '', label: 'Select insurance for' },
            ...insuranceForOptions,
          ]"
          class="w-full"
        />
        <x-select
          v-if="canShow('travel_coverage')"
          v-model="filters.travel_coverage"
          :label="
            !filters.insurance_type
              ? `Travel Coverage (Select Insurance Type first)`
              : `Travel Coverage`
          "
          :options="[
            { value: '', label: 'Select travel coverage' },
            ...travelCoverageOptions,
          ]"
          placeholder="Select travel coverage"
          class="w-full"
        />
        <ComboBox
          v-if="
            can(permissionsEnum.SEGMENT_FILTER) && canShow('segment_filter')
          "
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="quoteSegments"
          :single="true"
        />

        <ComboBox
          v-if="
            filters.lob === quoteTypeCodeEnum.Car ||
            filters.lob === quoteTypeCodeEnum.Health ||
            filters.lob === quoteTypeCodeEnum.Travel
          "
          v-model="filters.sic_advisor_requested"
          label="Advisor Requested"
          placeholder="Select any option"
          :options="[
            { value: 'All', label: 'All' },
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          :single="true"
        />

        <ComboBox
          v-if="
            filters.lob === quoteTypeCodeEnum.Car ||
            filters.lob === quoteTypeCodeEnum.Health
          "
          v-model="filters.assignmentType"
          label="Assignment Type"
          placeholder="Select any option"
          :options="assignmentTypes"
          :single="true"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      table-class-name="compact text-wrap"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-advisor_name="item">
        <span class="font-bold"> {{ item.advisor_name }} </span>
      </template>
      <template #body-append>
        <tr v-if="reportData?.data?.length > 0" class="total-row">
          <td class="direction-left">Total</td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'total_leads') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_0_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_1_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_2_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_3_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_4_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_5_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_l_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_h_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_r_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_6_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_6_lead_count_e') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_tr_lead_count_e') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'tier_tr_lead_count') }}
          </td>
          <td class="direction-center" v-if="canShowFooterColumn">
            {{ calculateTotalSum(reportData.data, 'total_lead_cost') }}
          </td>
        </tr>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: reportData.next_page_url,
        prev: reportData.prev_page_url,
        current: reportData.current_page,
        from: reportData.from,
        to: reportData.to,
        total: reportData.total,
        last: reportData.last_page,
      }"
    />
  </div>
</template>
