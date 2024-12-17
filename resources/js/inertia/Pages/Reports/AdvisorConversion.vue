<script setup>
import { usePagination, useRowsPerPage } from 'use-vue3-easy-data-table';

defineProps({
  reportData: Array,
  filtersByLob: Object,
  filterOptions: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
  advisorLeadTable: false,
  teamsOptions: false,
  subteamOptions: false,
  advisorOptions: false,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
let quoteSegments = reactive(page.props.quoteSegments ?? []);
const params = useUrlSearchParams('history');
const dataTableRef = ref();
const teamOptions = ref([]);
const subteamOptions = ref([]);
const advisorOptions = ref([]);
const isDirty = ref(false);
const isMounted = ref(false);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const toast = useToast();

const {
  currentPageFirstIndex,
  currentPageLastIndex,
  clientItemsLength,
  isFirstPage,
  isLastPage,
  nextPage,
  prevPage,
} = usePagination(dataTableRef);

const {
  rowsPerPageOptions,
  rowsPerPageActiveOption,
  updateRowsPerPageActiveOption,
} = useRowsPerPage(dataTableRef);

const updateRowsPerPageSelect = e => {
  updateRowsPerPageActiveOption(Number(e.target.value));
};

const tableHeader = [
  {
    text: 'Batch Number',
    value: 'batch_name',
  },
  {
    text: 'Start Date',
    value: 'start_date',
  },
  {
    text: 'Stop Date',
    value: 'end_date',
  },
  {
    text: 'Advisor Name',
    value: 'advisor_name',
  },
  {
    text: 'Total Leads',
    value: 'total_leads',
  },
  {
    text: 'New Leads',
    value: 'new_leads',
  },
  {
    text: 'Not Interested',
    value: 'not_interested',
  },
  {
    text: 'In Progress',
    value: 'in_progress',
  },
  {
    text: 'Bad Leads',
    value: 'bad_leads',
  },
  {
    text: 'Sale Leads',
    value: 'sale_leads',
  },
  {
    text: 'Created Sale Leads',
    value: 'created_sale_leads',
  },
  {
    text: 'IM Renewals',
    value: 'afia_renewals_count',
  },
  {
    text: 'Manual Created',
    value: 'manual_created',
  },
  {
    text: 'Gross Conversion',
    value: 'gross_conversion',
  },
  {
    text: 'Net Conversion',
    value: 'net_conversion',
    sortable: true,
  },
];

const totalLeads = reactive({
  modal: false,
  loader: false,
  filters: {
    leadType: '',
    quote_batch_id: null,
    advisorId: null,
  },
  data: {},
  current: '',
  tableHeader: [
    {
      text: 'Ref-ID',
      value: 'cdbId',
    },
    {
      text: 'Customer Name',
      value: 'fullName',
    },
    {
      text: 'Lead Status',
      value: 'quoteStatusName',
    },
  ],
});

function calculateGrossConversion(item) {
  if (item) {
    const totalLeadsCount = item.total_leads;
    const manualCreated = item.manual_created;
    const saleLeads = item.sale_leads;
    const createdSaleLeads = item.created_sale_leads;
    const numerator = saleLeads;
    const denominator = totalLeadsCount;

    if (denominator > 0) {
      return parseFloat((numerator / denominator) * 100).toFixed(2) + ' %';
    } else {
      return 'NaN';
    }
  } else {
    return 'undefined';
  }
}

function calculateTotalNetConversion(data) {
  let totalLeads = 0;
  let manualCreated = 0;
  let badLeads = 0;
  let manualCreatedBadLeads = 0;
  let saleLeads = 0;
  let createdSaleLeads = 0;
  data.forEach(row => {
    totalLeads += Number(row.total_leads);
    manualCreated += Number(row.manual_created);
    saleLeads += Number(row.sale_leads);
    createdSaleLeads += Number(row.created_sale_leads);
    badLeads += Number(row.bad_leads);
    manualCreatedBadLeads += Number(row.manual_created_bad_leads);
  });
  const numerator = saleLeads;
  const denominator = totalLeads - badLeads;

  return denominator > 0
    ? ((numerator / denominator) * 100).toFixed(2) + ' %'
    : 'NaN';
}

function calculateTotalGrossConversion(data) {
  let totalLeads = 0;
  let manualCreated = 0;
  let saleLeads = 0;
  let createdSaleLeads = 0;
  data.forEach(row => {
    totalLeads += Number(row.total_leads);
    manualCreated += Number(row.manual_created);
    saleLeads += Number(row.sale_leads);
    createdSaleLeads += Number(row.created_sale_leads);
  });
  const numerator = saleLeads;
  const denominator = totalLeads;
  return denominator > 0
    ? ((numerator / denominator) * 100).toFixed(2) + ' %'
    : 'NaN';
}

function calculateNetConversion(row) {
  const totalLeads = row.total_leads - row.bad_leads;
  const manualCreated = row.manual_created;
  const badLeads = row.bad_leads;
  const manualCreatedBadLeads = row.manual_created_bad_leads;
  const saleLeads = row.sale_leads;
  const createdSaleLeads = row.created_sale_leads;
  const numerator = saleLeads;
  const denominator = totalLeads - badLeads;
  if (denominator > 0) {
    return parseFloat((numerator / denominator) * 100).toFixed(2) + ' %';
  } else {
    return 'NaN';
  }
}

const getFiltersObject = () => {
  return {
    lob: quoteTypeCodeEnum.Car,
    advisorAssignedDates: [],
    is_ecommerce: '',
    batches: [],
    tiers: [],
    leadSources: [],
    advisors: [],
    teams: [],
    sub_teams: [],
    isCommercial: '',
    isEmbeddedProducts: '',
    page: 1,
    vehicle_type: 'All',
    insurance_type: '',
    insurance_for: '',
    travel_coverage: '',
    segment_filter: 'all',
  };
};

let filters = reactive(getFiltersObject());

function onSubmit(isValid, isMounted = false) {
  if (!filters.lob && isMounted === false) {
    toast.error({
      title: 'Please select LOB',
      position: 'top',
    });
    return;
  }

  if (isValid && filters.lob) {
    isDirty.value = false;
    filters.page = 1;
    const payLoad = cleanFilters(filters);
    router.visit('/reports/advisor-conversion', {
      method: 'get',
      data: {
        ...payLoad,
        ...(payLoad.batches && {
          batches: Array.isArray(payLoad.batches)
            ? payLoad.batches
            : [payLoad.batches],
        }),
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
      onFinish: () => (loaders.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  filters = getFiltersObject();
  setDefaultValues();

  isDirty.value = false;
  router.visit('/reports/advisor-conversion', {
    method: 'get',
    data: filters,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const currentTypeTitle = computed(() => {
  if (totalLeads.current == 'new_leads') {
    return 'Advisor Assigned : New Leads';
  } else if (totalLeads.current == 'not_interested') {
    return 'Advisor Assigned : Not Interested';
  } else if (totalLeads.current == 'in_progress') {
    return 'Advisor Assigned : In Progress';
  } else if (totalLeads.current == 'bad_leads') {
    return 'Advisor Assigned : Bad Leads';
  } else if (totalLeads.current == 'sale_leads') {
    return 'Advisor Assigned : Sale Leads';
  } else if (totalLeads.current == 'created_sale_leads') {
    return 'Advisor Assigned : Created Sale Leads';
  } else if (totalLeads.current == 'afia_renewals_count') {
    return 'Advisor Assigned : IM Renewals';
  } else if (totalLeads.current == 'manual_created') {
    return 'Advisor Assigned : Manual Created';
  } else {
    return 'Advisor Assigned : Total Leads';
  }
});

const quoteTypesOptions = computed(() => {
  return Object.keys(page.props.filterOptions.lob).map(text => ({
    label: text,
    value: page.props.filterOptions.lob[text],
  }));
});

const vehicleTypeOptions = computed(() => {
  const types = page.props.filterOptions.vehicle_type;
  if (types[filters.lob]) {
    return [
      {
        value: 'All',
        label: 'All',
      },
      ...types[filters.lob].map(option => ({
        value: option.value,
        label: option.label,
      })),
    ];
  }

  return [];
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

function onFetchAdvisorAssignedLeads(item, type, page = 1) {
  if (page == 1 && !totalLeads.modal) {
    loaders.advisorLeadTable = true;
  }

  totalLeads.current = type;
  totalLeads.modal = true;
  totalLeads.loader = true;

  if (item) {
    totalLeads.filters = {
      leadType: type,
      quote_batch_id: item.quote_batch_id,
      advisorId: item.advisorId,
    };
  }

  const payLoad = cleanFilters(filters);

  axios
    .post(`/reports/fetch-advisor-assigned-leads-data`, {
      ...payLoad,
      ...(payLoad.batches && {
        batches: Array.isArray(payLoad.batches)
          ? payLoad.batches
          : [payLoad.batches],
      }),
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
      page: page,
      leadType: totalLeads.filters.leadType,
      quote_batch_id: totalLeads.filters.quote_batch_id,
      advisorId: totalLeads.filters.advisorId,
    })
    .then(response => {
      totalLeads.data = response.data;
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      loaders.advisorLeadTable = false;
      totalLeads.loader = false;
    });
}

const cleanFilters = filters => {
  // remove unused filters
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

const setPageTable = page => {
  onFetchAdvisorAssignedLeads(null, totalLeads.current, page);
};

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
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
    filters.vehicle_type = 'All';
    filters.is_ecommerce = '';
    filters.tiers = [];
  }

  if (
    [
      quoteTypeCodeEnum.Car,
      quoteTypeCodeEnum.Health,
      quoteTypeCodeEnum.CORPLINE,
      quoteTypeCodeEnum.GroupMedical,
    ].includes(filters.lob)
  ) {
    if (filters.lob == quoteTypeCodeEnum.Health) {
      quoteSegments = quoteSegments.filter(
        segment => segment.value !== 'sic-revival',
      );
    }

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

  if (isMounted.value) {
    isDirty.value = true;
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

  if (isMounted.value) {
    isDirty.value = true;
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

const loadAdvisorsBySubteams = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
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

const onInsuranceTypeChange = e => {
  filters.travel_coverage = '';
};

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

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

  isMounted.value = true;

  onSubmit(true, true);
});

watch(
  () => totalLeads.modal,
  val => {
    if (!val) {
      totalLeads.data = {};
    }
  },
);

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
      page.props.filtersByLob[element]['can_view'][filters.lob] ?? true;

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
    [
      quoteTypeCodeEnum.Car,
      quoteTypeCodeEnum.Health,
      quoteTypeCodeEnum.CORPLINE,
      quoteTypeCodeEnum.GroupMedical,
    ].includes(filters.lob) &&
    (!filters.teams || filters.teams.length == 0)
  ) {
    label = 'Advisors (select teams first)';
  }

  return label;
};
</script>

<template>
  <div>
    <Head title="Advisor Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Conversion Report
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

        <x-select
          v-if="canShow('is_ecommerce')"
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'All', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />

        <x-select
          v-if="canShow('vehicle_type')"
          v-model="filters.vehicle_type"
          label="Vehicle Type"
          placeholder="Search by vehicle type"
          :options="vehicleTypeOptions"
          class="w-full"
        />

        <ComboBox
          v-model="filters.batches"
          label="Batch Number"
          placeholder="Search by Batch Number"
          :options="
            Object.keys(filterOptions.batches).map(key => ({
              value: key,
              label: filterOptions.batches[key],
            }))
          "
          :max-limit="8"
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
          :options="
            quoteSegments?.filter(segment =>
              filters.lob === 'Travel' ? segment.value !== 'sic-revival' : true,
            )
          "
          :single="true"
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
      ref="dataTableRef"
      table-class-name="compact text-wrap"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData || []"
      border-cell
      :rows-per-page-message="'Records per page'"
      :rows-items="[10, 25, 50, 100]"
      :rows-per-page="100"
      :empty-message="'No Records Available'"
      hide-footer
      :sort-by="'net_conversion'"
      :sort-type="'desc'"
    >
      <template #item-gross_conversion="item">
        <p v-if="item.gross_conversion == 0">NaN</p>
        <p v-else>{{ item.gross_conversion }} %</p>
      </template>
      <template #item-net_conversion="item">
        <p v-if="item.net_conversion == 0">NaN</p>
        <p v-else>{{ item.net_conversion }} %</p>
      </template>
      <template #item-total_leads="item">
        <p v-if="item.total_leads == 0">{{ item.total_leads }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'total_leads')"
          class="text-primary underline"
        >
          {{ item.total_leads }}
        </button>
      </template>

      <template #item-new_leads="item">
        <p v-if="item.new_leads == 0">{{ item.new_leads }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'new_leads')"
          class="text-primary underline"
        >
          {{ item.new_leads }}
        </button>
      </template>

      <template #item-not_interested="item">
        <p v-if="item.not_interested == 0">{{ item.not_interested }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'not_interested')"
          class="text-primary underline"
        >
          {{ item.not_interested }}
        </button>
      </template>

      <template #item-in_progress="item">
        <p v-if="item.in_progress == 0">{{ item.in_progress }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'in_progress')"
          class="text-primary underline"
        >
          {{ item.in_progress }}
        </button>
      </template>

      <template #item-bad_leads="item">
        <p v-if="item.bad_leads == 0">{{ item.bad_leads }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'bad_leads')"
          class="text-primary underline"
        >
          {{ item.bad_leads }}
        </button>
      </template>

      <template #item-sale_leads="item">
        <p v-if="item.sale_leads == 0">{{ item.sale_leads }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'sale_leads')"
          class="text-primary underline"
        >
          {{ item.sale_leads }}
        </button>
      </template>

      <template #item-created_sale_leads="item">
        <p v-if="item.created_sale_leads == 0">{{ item.created_sale_leads }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'created_sale_leads')"
          class="text-primary underline"
        >
          {{ item.created_sale_leads }}
        </button>
      </template>

      <template #item-afia_renewals_count="item">
        <p v-if="item.afia_renewals_count == 0">
          {{ item.afia_renewals_count }}
        </p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'afia_renewals_count')"
          class="text-primary underline"
        >
          {{ item.afia_renewals_count }}
        </button>
      </template>

      <template #item-manual_created="item">
        <p v-if="item.manual_created == 0">{{ item.manual_created }}</p>
        <button
          v-else
          @click="onFetchAdvisorAssignedLeads(item, 'manual_created')"
          class="text-primary underline"
        >
          {{ item.manual_created }}
        </button>
      </template>

      <template #body-append>
        <tr v-if="reportData.length > 0" class="total-row">
          <td class="direction-left">Total</td>
          <td></td>
          <td></td>
          <td></td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'total_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'new_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'not_interested') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'in_progress') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'bad_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'sale_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'created_sale_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'afia_renewals_count') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData, 'manual_created') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalGrossConversion(reportData) }}
          </td>
          <td class="direction-center">
            {{ calculateTotalNetConversion(reportData) }}
          </td>
        </tr>
      </template>
    </DataTable>

    <div class="flex flex-wrap justify-between items-center gap-2 py-6">
      <div>
        <select
          class="form-select text-sm border shadow-sm rounded-md border-gray-300 hover:border-gray-400 disabled:opacity-30 disabled:cursor-not-allowed"
          @change="updateRowsPerPageSelect"
        >
          <option
            v-for="item in rowsPerPageOptions"
            :key="item"
            :selected="item === rowsPerPageActiveOption"
            :value="item"
          >
            {{ item }} rows per page
          </option>
        </select>
      </div>

      <div class="text-xs lining-nums text-gray-700 text-center">
        Now displaying: {{ currentPageFirstIndex }} ~
        {{ currentPageLastIndex }} of {{ clientItemsLength }}
      </div>

      <div class="flex gap-2">
        <x-button
          size="sm"
          icon-left="prev"
          :disabled="isFirstPage"
          @click="prevPage"
        >
          Prev
        </x-button>
        <x-button
          size="sm"
          icon-right="next"
          :disabled="isLastPage"
          @click="nextPage"
        >
          Next
        </x-button>
      </div>
    </div>

    <x-modal
      v-model="totalLeads.modal"
      size="xl"
      :title="`${currentTypeTitle}`"
      show-close
      backdrop
      :has-actions="false"
    >
      <section class="min-h-[70vh]">
        <div v-if="!loaders.advisorLeadTable">
          <PaginateClient
            :links="{
              next: totalLeads.data.next_page_url,
              prev: totalLeads.data.prev_page_url,
              current: totalLeads.data.current_page,
              from: totalLeads.data.from,
              to: totalLeads.data.to,
              total: totalLeads.data.total,
              last: totalLeads.data.last_page,
            }"
            :loading="totalLeads.loader"
            @update="setPageTable"
          />
          <DataTable
            table-class-name="tablefixed compact"
            :loading="totalLeads.loader"
            :headers="totalLeads.tableHeader"
            :items="totalLeads.data.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
          ></DataTable>
        </div>
        <div v-else class="p-4 flex flex-col justify-center items-center gap-4">
          <x-spinner size="lg" color="#1d83bc" />
          <p class="text-sm">Fetching records...</p>
        </div>
      </section>
    </x-modal>
  </div>
</template>
