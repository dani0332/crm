<script setup>

defineProps({
  filterOptions: Object,
  filtersByLob: Object,
  reportData: Object,
});

const page = usePage();
const isDirty = ref(false);
const isMounted = ref(false);
const advisorOptions = ref([]);

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const getFiltersObject = () => {
  return {
    lob: '',
    displayBy: '',
    advisorAssignedDates: [],
    teams: [],
    advisors: [],
  }
};

const displayBy = ref([
  { label: 'Month', value: 'month' },
  { label: 'Batch', value: 'batch' },
]);
const teamOptions = ref([]);

let filters = reactive(getFiltersObject());

function onSubmit(isValid, isMounted = false) {

}
const quoteTypesOptions = computed(() => {
  const quoteTypesOptions = [...Object.keys(page.props.filterOptions.lob).map(text => ({
    label: text,
    value: page.props.filterOptions.lob[text],    
  })), { label: "Motor", value: "Motor" }];

  return quoteTypesOptions
});

function onReset() {

}

const canShow = (element) => {
  if(page.props.filtersByLob &&
  page.props.filtersByLob[element]) {
      const lobs = page.props.filtersByLob[element]['lobs'] ?? [];
      if((lobs.length == 0 ||
      (lobs.length != 0 && Object.values(lobs).includes(filters.lob)))) {
          return true;
      }

      return false;
  }

  return true;
}

const loaders = reactive({
  table: false,
  advisorLeadTable: false,
  teamsOptions: false,
  subteamOptions: false,
  advisorOptions: false,
});

const loadTeams = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;

  }

  if(!isMounted) {
      filters.advisors = [];
      advisorOptions.value = [];
  }

  loaders.teamsOptions = true;

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
      loaders.teamsOptions = false;
    });
};

const loadAdvisorsByLob = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
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

const onTeamChange = (e, isOnMounted = false) => {
  if(!isOnMounted) {
      filters.advisors = [];
      advisorOptions.value = [];
  }

  loadAdvisors(e);
};


const onLobChange = (e, isOnMounted = false) => {
  if(!isOnMounted) {
      filters.teams = [];
      filters.advisors = [];
      advisorOptions.value = [];
    }

  if([quoteTypeCodeEnum.Car,
      quoteTypeCodeEnum.Health,
      quoteTypeCodeEnum.CORPLINE,
      quoteTypeCodeEnum.GroupMedical
  ].includes(filters.lob)) {
      loadTeams(e);
  } else {
      loadAdvisorsByLob(e);
  }
};

const isDisabled = (element) => {
    if(page.props.filtersByLob &&
      page.props.filtersByLob[element] &&
      filters.lob) {
        const canView = page.props.filtersByLob[element]['can_view'][filters.lob] ?? true;

        if(canView) {
            return true;
        }

        return false;
    }

    return true;
}

const getAdvisorLabel = () => {
    let label = 'Advisors'
    if ([quoteTypeCodeEnum.Car,
        quoteTypeCodeEnum.Health,
        quoteTypeCodeEnum.CORPLINE,
        quoteTypeCodeEnum.GroupMedical].includes(filters.lob) &&
    (!filters.teams || filters.teams.length == 0)) {
        label = 'Advisors (select teams first)';
    }

    return label;
}

const loadAdvisors = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
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

const tableHeader = [
  {
    text: 'Month',
    value: 'month',
  },
  {
    text: 'Batch',
    value: 'batch',
  },
  {
    text: 'Start Date',
    value: 'start_date',
  },
  {
    text: 'End Date',
    value: 'end_date',
  },
  {
    text: 'Advisor Name',
    value: 'advisor_name',
  },
  {
    text: 'Total',
    value: 'total',
  },
  {
    text: 'Lost',
    value: 'lost',
  },
  {
    text: 'Invalid',
    value: 'invalid',
  },
  {
    text: 'Sales',
    value: 'sales',
  },
  {
    text: 'Volume Gross Retention',
    value: 'volume_gross_retention',
  },
  {
    text: 'Volumme Net Retention',
    value: 'volume_net_retention',
  },
];

</script>

<template>
  <div>
    <Head title="Advisor Retention Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Retention Report
    </h1>

    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <ComboBox
          v-model="filters.lob"
          label="Line of Business"
          placeholder="Select Line of Business"
          :options="quoteTypesOptions"
          class="w-full"
          :single="true"
          @update:modelValue="onLobChange"
        />

        <ComboBox
          v-model="filters.displayBy"
          placeholder="Search by Group"
          label="Display by"
          :options="displayBy"
          class="w-full"
          :single="true"
        />

        <DatePicker
          v-model="filters.advisorAssignedDates"
          label="Policy Expiry Date"
          placeholder="Select Start & End Date"
          range
          size="sm"
          model-type="yyyy-MM-dd"
        />

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

      </div>
      <div class="flex justify-between gap-3 mb-4 items-center">
        <div class="flex-1">
          <p v-if="isDirty" class="text-xs text-red-500 text-center font-bold">
            Please click search, to show updated records based on the selected
            filters
          </p>
        </div>
        <div class="flex gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
  </DataTable>

  <!-- <Pagination
    :links="{
      next: reportData.next_page_url,
      prev: reportData.prev_page_url,
      current: reportData.current_page,
      from: reportData.from,
      to: reportData.to,
      total: reportData.total,
      last: reportData.last_page,
    }"
  /> -->
  </div>
</template>
