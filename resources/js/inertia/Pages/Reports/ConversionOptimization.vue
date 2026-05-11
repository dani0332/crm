<script setup>
import { usePagination, useRowsPerPage } from 'use-vue3-easy-data-table';

const props = defineProps({
  reportData: { type: Array, default: () => [] },
  filtersByLob: Object,
  filterOptions: Object,
  defaultFilters: Object,
});

const EXPORT_TYPES = Object.freeze({
  DOWNLOAD: 'download',
  EMAIL: 'email',
});

const CONVERSION_OPTIMIZATION_UI = Object.freeze({
  LEAD_SOURCE_OPTION_LABEL_DISPLAY_MAX_LENGTH: 45,
});

const truncateLeadSourceOptionLabel = label => {
  if (typeof label !== 'string') {
    return label;
  }

  const max =
    CONVERSION_OPTIMIZATION_UI.LEAD_SOURCE_OPTION_LABEL_DISPLAY_MAX_LENGTH;

  if (label.length <= max) {
    return label;
  }

  return `${label.slice(0, max)}...`;
};

const NO_DEFAULT_FILTERS_PARAM = 'noDefaultFilters';

const loaders = reactive({
  table: false,
  subteamOptions: false,
  advisorOptions: false,
});

const page = usePage();
import { setQueryStringFilters as setQueryStringFiltersUtil } from '../../Composables/utilities';
const toast = useToast();
const params = useUrlSearchParams('history');
const dataTableRef = ref();
const subteamOptions = ref([]);
const advisorOptions = ref([]);
const isDirty = ref(false);
const isMounted = ref(false);
const canExportReport = ref(false);
const exportLoader = ref(false);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
let quoteSegments = reactive(page.props.quoteSegments ?? []);
const { maxSelections } = useRules();

const carRegistrationTypeEnum = page.props.carRegistrationType;
const carVehicleUseEnum = page.props.carVehicleUse;

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
  { text: 'Advisor Name', value: 'advisor_name' },
  { text: 'Total Leads', value: 'total_leads', sortable: true },
  { text: 'Sale Leads', value: 'sale_leads', sortable: true },
  { text: 'Conversion', value: 'conversion', sortable: true },
  { text: 'Rank', value: 'ranking', sortable: true },
  { text: 'Expected Sales', value: 'expected_sales', sortable: true },
  { text: 'Required Sales', value: 'required_sales', sortable: true },
  { text: 'New Conversion %', value: 'new_conversion', sortable: true },
  { text: 'Current Cap', value: 'current_cap', sortable: true },
  { text: 'Suggested Cap', value: 'suggested_cap', sortable: true },
];

const getFiltersObject = () => ({
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
  registration_type: '',
  vehicle_use: '',
  cap_percentage: '',
  department: [],
});

let filters = reactive(getFiltersObject());

const quoteTypesOptions = computed(() => {
  return Object.keys(props.filterOptions.lob).map(text => ({
    label: text,
    value: props.filterOptions.lob[text],
  }));
});

const capPercentageOptions = computed(() => {
  return (props.filterOptions.capPercentages ?? []).map(option => ({
    value: option.value.toString(),
    label: option.label,
  }));
});

const vehicleTypeOptions = computed(() => {
  const types = props.filterOptions.vehicle_type;
  if (types[filters.lob]) {
    return [
      { value: 'All', label: 'All' },
      ...types[filters.lob].map(option => ({
        value: option.value,
        label: option.label,
      })),
    ];
  }

  return [];
});

const insuranceTypeOptions = computed(() => {
  const types = props.filterOptions.insurance_type;
  if (types[filters.lob]) {
    return types[filters.lob].map(option => ({
      value: option.value.toString(),
      label: option.label,
    }));
  }

  return [];
});

const insuranceForOptions = computed(() => {
  const types = props.filterOptions.insurance_for;
  if (types[filters.lob]) {
    return types[filters.lob].map(option => ({
      value: option.id.toString(),
      label: option.text,
    }));
  }

  return [];
});

const travelCoverageOptions = computed(() => {
  const types = props.filterOptions.travel_coverage;
  if (types[filters.lob] && types[filters.lob][filters.insurance_type]) {
    return types[filters.lob][filters.insurance_type].map(option => ({
      value: option.value,
      label: option.label,
    }));
  }

  return [];
});

const departmentFilterOptions = computed(() => {
  const rows = props.filterOptions.departments ?? [];

  return rows.map(row => ({
    value: parseInt(String(row.value), 10),
    label: row.label,
  }));
});

/** Teams for team multi-select (server-built in {@link ConversionOptimizationReportService.getFilterOptions}); LOB-independent. */
const teamOptions = computed(() => {
  const rows = props.filterOptions?.teams ?? [];

  return rows.map(team => ({
    value: parseInt(String(team.value), 10),
    label: team.label,
  }));
});

const registrationTypeOptions = [
  { value: 'All', label: 'All' },
  ...Object.values(carRegistrationTypeEnum).map(item => ({
    value: item,
    label: item.charAt(0).toUpperCase() + item.slice(1),
  })),
];

const vehicleUseOptions = [
  { value: 'All', label: 'All' },
  ...Object.values(carVehicleUseEnum).map(item => ({
    value: item,
    label: item.charAt(0).toUpperCase() + item.slice(1),
  })),
];

const commercialOptions = [
  { value: 'All', label: 'All' },
  { value: true, label: 'Yes' },
  { value: false, label: 'No' },
];

const isVehicleUseDisabled = computed(() => {
  return filters.registration_type === carRegistrationTypeEnum.COMPANY;
});

const clearIsCommercialUnlessPersonal = () => {
  if (filters.registration_type !== carRegistrationTypeEnum.PERSONAL) {
    filters.isCommercial = '';
  }
};

watch(() => filters.registration_type, clearIsCommercialUnlessPersonal);

const showCommercialRule = computed(
  () => filters.registration_type === carRegistrationTypeEnum.PERSONAL,
);

const cleanFilters = sourceFilters => {
  const payload = JSON.parse(JSON.stringify(sourceFilters));
  delete payload[NO_DEFAULT_FILTERS_PARAM];
  const cleanedFilters = removeUnusedFilters(payload);

  Object.keys(cleanedFilters).forEach(key => {
    const value = cleanedFilters[key];
    if (
      value === '' ||
      value == null ||
      (Array.isArray(value) && value.length === 0)
    ) {
      delete cleanedFilters[key];
    }
  });

  return cleanedFilters;
};

/**
 * Drops filter fields that are not valid for the selected LOB (per `filtersByLob` metadata).
 * Each filter key may list `lobs`; if the current `localFilters.lob` is not in that list, the key is removed
 * so report requests and exports do not send irrelevant parameters (e.g. car-only fields for Travel).
 */
const removeUnusedFilters = localFilters => {
  const filtersByLob = props.filtersByLob;
  Object.keys(filtersByLob).forEach(key => {
    if (
      filtersByLob[key].lobs &&
      !filtersByLob[key].lobs.includes(localFilters.lob)
    ) {
      delete localFilters[key];
    }
  });

  return localFilters;
};

const canShow = element => {
  if (props.filtersByLob && props.filtersByLob[element]) {
    const lobs = props.filtersByLob[element].lobs ?? [];

    return lobs.length === 0 || Object.values(lobs).includes(filters.lob);
  }

  return true;
};

const isDisabled = element => {
  if (props.filtersByLob && props.filtersByLob[element] && filters.lob) {
    return props.filtersByLob[element].can_view?.[filters.lob] ?? true;
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
    (!filters.teams || filters.teams.length === 0)
  ) {
    label = 'Advisors (select teams first)';
  }

  return label;
};

const INTEGER_DEFAULT_FILTER_KEYS = new Set(['page']);
const INTEGER_ID_ARRAY_DEFAULT_FILTER_KEYS = new Set([
  'teams',
  'sub_teams',
  'advisors',
  'department',
]);

const parseDefaultFilterInt = raw => {
  if (raw === '' || raw === null || raw === undefined) {
    return raw;
  }

  const parsed = Number.parseInt(String(raw), 10);

  return Number.isNaN(parsed) ? raw : parsed;
};

const coerceDefaultFilterValue = (key, value) => {
  if (INTEGER_DEFAULT_FILTER_KEYS.has(key)) {
    return parseDefaultFilterInt(value);
  }

  if (INTEGER_ID_ARRAY_DEFAULT_FILTER_KEYS.has(key) && Array.isArray(value)) {
    return value.map(v => parseDefaultFilterInt(v));
  }

  return value;
};

const setDefaultValues = () => {
  if (props.defaultFilters && !params.page) {
    Object.keys(props.defaultFilters).forEach(key => {
      if (Object.prototype.hasOwnProperty.call(filters, key)) {
        filters[key] = coerceDefaultFilterValue(key, props.defaultFilters[key]);
      }
    });
  }
};

// Use shared utility to populate filters from URL params

// const loadTeams = async selectedLob => {
//   if (!selectedLob || selectedLob.length === 0) {
//     teamOptions.value = [];
//     return;
//   }
//
//   if (isMounted.value) {
//     isDirty.value = true;
//   }
//
//   loaders.teamOptions = true;
//
//   try {
//     const response = await axios.post('/reports/fetch-teams-by-lob', {
//       lob: selectedLob,
//     });
//
//     teamOptions.value = (response.data ?? []).map(team => ({
//       value: parseInt(String(team.id), 10),
//       label: team.name,
//     }));
//   } finally {
//     loaders.teamOptions = false;
//   }
// };

const loadSubTeams = async selectedTeams => {
  if (!selectedTeams || selectedTeams.length === 0) {
    subteamOptions.value = [];
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.subteamOptions = true;

  try {
    const response = await axios.post('/reports/fetch-subteams-by-team', {
      teamIds: Array.isArray(selectedTeams) ? selectedTeams : [selectedTeams],
      lob: filters.lob,
    });

    subteamOptions.value = (response.data ?? []).map(subTeam => ({
      value: parseInt(String(subTeam.id), 10),
      label: subTeam.name,
    }));
  } finally {
    loaders.subteamOptions = false;
  }
};

const loadAdvisors = async selectedTeams => {
  if (!selectedTeams || selectedTeams.length === 0) {
    advisorOptions.value = [];
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  try {
    const response = await axios.post('/reports/fetch-advisor-by-team', {
      teamIds: Array.isArray(selectedTeams) ? selectedTeams : [selectedTeams],
      lob: filters.lob,
    });

    advisorOptions.value = (response.data ?? []).map(advisor => ({
      value: advisor.id.toString(),
      label: advisor.name,
    }));
  } finally {
    loaders.advisorOptions = false;
  }
};

const loadAdvisorsBySubteams = async selectedSubTeams => {
  if (!selectedSubTeams || selectedSubTeams.length === 0) {
    advisorOptions.value = [];
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  try {
    const response = await axios.post('/reports/fetch-advisor-by-sub-team', {
      teamIds: Array.isArray(selectedSubTeams)
        ? selectedSubTeams
        : [selectedSubTeams],
      lob: filters.lob,
    });

    advisorOptions.value = (response.data ?? []).map(advisor => ({
      value: advisor.id.toString(),
      label: advisor.name,
    }));
  } finally {
    loaders.advisorOptions = false;
  }
};

const loadAdvisorsByLob = async selectedLob => {
  if (!selectedLob || selectedLob.length === 0) {
    advisorOptions.value = [];
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  try {
    const response = await axios.post('/reports/fetch-advisors-by-lob', {
      lob: selectedLob,
    });

    advisorOptions.value = (response.data ?? []).map(advisor => ({
      value: advisor.id.toString(),
      label: advisor.name,
    }));
  } finally {
    loaders.advisorOptions = false;
  }
};

const onLobChange = async (_, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.teams = [];
    filters.sub_teams = [];
    filters.advisors = [];
    filters.insurance_type = '';
    filters.insurance_for = '';
    filters.travel_coverage = '';
    filters.isCommercial = '';
    filters.vehicle_type = 'All';
    filters.is_ecommerce = '';
    filters.tiers = [];
    subteamOptions.value = [];
    advisorOptions.value = [];
  }

  canExportReport.value = false;

  if (
    [
      quoteTypeCodeEnum.Car,
      quoteTypeCodeEnum.Health,
      quoteTypeCodeEnum.CORPLINE,
      quoteTypeCodeEnum.GroupMedical,
      quoteTypeCodeEnum.Life,
    ].includes(filters.lob)
  ) {
    quoteSegments = page.props.quoteSegments.filter(segment => {
      const isLifeQuote = filters.lob === quoteTypeCodeEnum.Life;
      const allowedSegments = isLifeQuote ? ['all', 'fic', 'non-fic'] : null;
      const excludedSegments = !isLifeQuote ? ['fic', 'non-fic'] : null;

      return isLifeQuote
        ? allowedSegments.includes(segment.value)
        : !excludedSegments.includes(segment.value);
    });
  } else {
    await loadAdvisorsByLob(filters.lob);
  }
};

const onTeamChange = async selectedTeams => {
  filters.sub_teams = [];
  subteamOptions.value = [];
  filters.advisors = [];
  advisorOptions.value = [];

  if (
    [quoteTypeCodeEnum.Car, quoteTypeCodeEnum.GroupMedical].includes(
      filters.lob,
    )
  ) {
    await loadSubTeams(selectedTeams);
  }

  await loadAdvisors(selectedTeams);
};

const applyTeamsFilter = async teams => {
  filters.teams = teams;
  await onTeamChange(teams);
};

const onSubTeamChange = async selectedSubTeams => {
  filters.advisors = [];
  advisorOptions.value = [];

  if (
    (!selectedSubTeams || selectedSubTeams.length === 0) &&
    [quoteTypeCodeEnum.Car, quoteTypeCodeEnum.GroupMedical].includes(
      filters.lob,
    ) &&
    filters.teams.length > 0
  ) {
    await loadAdvisors(filters.teams);
    return;
  }

  await loadAdvisorsBySubteams(selectedSubTeams);
};

const applySubTeamsFilter = async subTeams => {
  filters.sub_teams = subTeams;
  await onSubTeamChange(subTeams);
};

const onInsuranceTypeChange = () => {
  filters.travel_coverage = '';
};

const onDepartmentFilterChange = () => {
  if (isMounted.value) {
    isDirty.value = true;
  }
};

function onSubmit(isValid, isOnMounted = false) {
  if (!filters.lob && isOnMounted === false) {
    toast.error({
      title: 'Please select LOB',
      position: 'top',
    });
    return;
  }

  if (!isValid || !filters.lob) {
    return;
  }

  isDirty.value = false;
  filters.page = 1;
  const payload = cleanFilters(filters);

  router.visit('/reports/conversion-optimization', {
    method: 'get',
    only: ['reportData'],
    data: {
      ...payload,
      ...(payload.batches && {
        batches: Array.isArray(payload.batches)
          ? payload.batches
          : [payload.batches],
      }),
      ...(payload.tiers && {
        tiers: Array.isArray(payload.tiers) ? payload.tiers : [payload.tiers],
      }),
      ...(payload.leadSources && {
        leadSources: Array.isArray(payload.leadSources)
          ? payload.leadSources
          : [payload.leadSources],
      }),
      ...(payload.advisors && {
        advisors: Array.isArray(payload.advisors)
          ? payload.advisors
          : [payload.advisors],
      }),
      ...(payload.teams && {
        teams: Array.isArray(payload.teams) ? payload.teams : [payload.teams],
      }),
      ...(payload.sub_teams && {
        sub_teams: Array.isArray(payload.sub_teams)
          ? payload.sub_teams
          : [payload.sub_teams],
      }),
      ...(payload.department && {
        department: Array.isArray(payload.department)
          ? payload.department
          : [payload.department],
      }),
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => {
      loaders.table = true;
      canExportReport.value = false;
    },
    onFinish: () => {
      loaders.table = false;
      canExportReport.value = true;
    },
  });
}

function onReset() {
  isDirty.value = false;

  const defaultAdvisorDates = Array.isArray(
    props.defaultFilters?.advisorAssignedDates,
  )
    ? [...props.defaultFilters.advisorAssignedDates]
    : [];

  router.visit('/reports/conversion-optimization', {
    method: 'get',
    only: ['reportData'],
    data: {
      page: 1,
      lob: quoteTypeCodeEnum.Car,
      advisorAssignedDates: defaultAdvisorDates,
      [NO_DEFAULT_FILTERS_PARAM]: true,
    },
    preserveScroll: true,
    preserveState: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => {
      canExportReport.value = false;
      delete filters[NO_DEFAULT_FILTERS_PARAM];

      Object.assign(filters, getFiltersObject());
      filters.advisorAssignedDates = [...defaultAdvisorDates];
      void onLobChange(filters.lob, true);

      const url = new URL(globalThis.location.href);
      if (url.searchParams.has(NO_DEFAULT_FILTERS_PARAM)) {
        url.searchParams.delete(NO_DEFAULT_FILTERS_PARAM);
        router.replace({
          url: `${url.pathname}${url.search}${url.hash}`,
          preserveState: true,
          preserveScroll: true,
        });
      }

      loaders.table = false;
    },
  });
}

const onDataExport = async (exportType = EXPORT_TYPES.DOWNLOAD) => {
  if (canExportReport.value !== true) {
    toast.error({
      title: 'Please generate report first',
      position: 'top',
    });
    return;
  }

  exportLoader.value = true;

  try {
    const payload = cleanFilters(filters);
    const exportPayload = { ...payload };

    /** removing page, which removes the case where page=1 and advisorAssignedDate
     * both requested causing less-constrained export which can lead to memory exhaustion. */
    delete exportPayload.page;

    const data = { ...exportPayload, exportType };
    const url = route('conversion-optimization-export');
    const finalUrl = `${url}?${useObjToUrl(data)}`;

    if (exportType === EXPORT_TYPES.EMAIL) {
      const response = await axios.get(finalUrl);

      toast.success({
        title:
          response.data.message ??
          'Your export is being processed. You will receive an email shortly.',
        position: 'top',
      });

      return;
    }

    window.open(finalUrl);
    toast.success({
      title: 'Export initiated',
      position: 'top',
    });
  } catch (error) {
    toast.error({
      title: error?.response?.data?.message ?? 'Unable to start an export',
      position: 'top',
    });
  } finally {
    exportLoader.value = false;
  }
};

const calculateTotalSum = key => {
  return (props.reportData ?? []).reduce((sum, item) => {
    return Number(sum) + Number(item[key] ?? 0);
  }, 0);
};

const formatPercentage = value => {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  return `${Number(value).toFixed(2)} %`;
};

const formatValue = value => {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  return value;
};

onMounted(async () => {
  setDefaultValues();
  setQueryStringFiltersUtil(params, filters, {
    integerFields: ['page', 'teams', 'sub_teams', 'department'],
  });
  clearIsCommercialUnlessPersonal();
  await onLobChange(filters.lob, true);

  if (filters.teams?.length > 0 && filters.sub_teams?.length < 1) {
    await loadSubTeams(filters.teams);
    await loadAdvisors(filters.teams);
  } else if (filters.sub_teams?.length > 0) {
    await loadSubTeams(filters.teams);
    await loadAdvisorsBySubteams(filters.sub_teams);
  }

  isMounted.value = true;

  if (filters.lob) {
    canExportReport.value = true;
  }
});
</script>

<template>
  <div>
    <Head title="Conversion Optimization Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Conversion Optimization Engine
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-select
          v-model="filters.lob"
          label="LOB"
          placeholder="Select LOB"
          :options="quoteTypesOptions"
          class="w-full"
          @update:modelValue="onLobChange"
          filterable
          filterPlaceholder="Filter LOB...."
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
          v-model="filters.cap_percentage"
          label="Cap Percentage"
          placeholder="Select cap percentage"
          :options="capPercentageOptions"
          class="w-full"
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

        <x-select
          v-model="filters.batches"
          label="Batch Number"
          placeholder="Search by Batch Number"
          :options="
            Object.keys(props.filterOptions.batches).map(key => ({
              value: key,
              label: props.filterOptions.batches[key],
            }))
          "
          :rules="[maxSelections(8)]"
          filterable
          filterPlaceholder="Filter Batch Number...."
          truncate
          multiple
          helper="You can select up to 8 batch numbers"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.batches = Object.keys(props.filterOptions.batches).map(
                  batch => batch,
                )
              "
              @clear="filters.batches = []"
            />
          </template>
        </x-select>

        <x-tooltip placement="top" v-if="canShow('tiers')">
          <template #tooltip v-if="filters.lob === quoteTypeCodeEnum.Bike">
            Development for Bike Tiers still in progress
          </template>
          <template #tooltip v-else> Select Tiers </template>
          <x-select
            :disabled="filters.lob === quoteTypeCodeEnum.Bike"
            :class="{ 'opacity-50': filters.lob === quoteTypeCodeEnum.Bike }"
            v-model="filters.tiers"
            label="Tiers"
            placeholder="Search by Tiers"
            :options="
              Object.keys(props.filterOptions.tiers).map(key => ({
                value: key,
                label: props.filterOptions.tiers[key],
              }))
            "
            filterable
            filterPlaceholder="Filter Tiers...."
            truncate
            multiple
          >
            <template #content-footer>
              <ui-select-actions
                @select-all="
                  filters.tiers = Object.keys(props.filterOptions.tiers).map(
                    tier => tier,
                  )
                "
                @clear="filters.tiers = []"
              />
            </template>
          </x-select>
        </x-tooltip>

        <x-select
          v-if="filters.lob !== quoteTypeCodeEnum.Health"
          v-model="filters.leadSources"
          label="Lead Source"
          placeholder="Search by Lead Source"
          :options="
            Object.keys(props.filterOptions.leadSources).map(key => ({
              value: key,
              label: props.filterOptions.leadSources[key],
            }))
          "
          :rules="[maxSelections(3)]"
          filterable
          filterPlaceholder="Filter Lead Source..."
          truncate
          multiple
          helper="You can select up to 3 lead sources"
          class="w-full"
        >
          <template #label="{ item }">
            <span :title="item.label">{{
              truncateLeadSourceOptionLabel(item.label)
            }}</span>
          </template>
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.leadSources = Object.keys(
                  props.filterOptions.leadSources,
                ).map(leadSource => leadSource)
              "
              @clear="filters.leadSources = []"
            />
          </template>
        </x-select>

        <x-select
          v-model="filters.department"
          label="Department"
          placeholder="Search by Department"
          :options="departmentFilterOptions"
          @update:model-value="onDepartmentFilterChange"
          filterable
          filterPlaceholder="Filter Department...."
          truncate
          multiple
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.department = departmentFilterOptions.map(d => d.value)
              "
              @clear="filters.department = []"
            />
          </template>
        </x-select>

        <x-select
          v-if="canShow('teams')"
          :disabled="!isDisabled('teams')"
          :class="{ 'opacity-50': !isDisabled('teams') }"
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="teamOptions"
          @update:model-value="onTeamChange"
          filterable
          filterPlaceholder="Filter Teams...."
          truncate
          multiple
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                applyTeamsFilter(teamOptions.map(team => team.value))
              "
              @clear="applyTeamsFilter([])"
            />
          </template>
        </x-select>

        <x-select
          v-if="canShow('sub_teams')"
          :disabled="!isDisabled('sub_teams')"
          :class="{ 'opacity-50': !isDisabled('sub_teams') }"
          v-model="filters.sub_teams"
          label="SubTeams"
          placeholder="Search by SubTeams"
          :options="subteamOptions"
          @update:model-value="onSubTeamChange"
          :loading="loaders.subteamOptions"
          filterable
          filterPlaceholder="Filter SubTeams...."
          truncate
          multiple
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                applySubTeamsFilter(
                  subteamOptions.map(subteam => subteam.value),
                )
              "
              @clear="applySubTeamsFilter([])"
            />
          </template>
        </x-select>

        <x-select
          v-if="canShow('advisors')"
          :disabled="!isDisabled('advisors')"
          :class="{ 'opacity-50': !isDisabled('advisors') }"
          v-model="filters.advisors"
          :label="getAdvisorLabel()"
          :options="advisorOptions"
          :loading="loaders.advisorOptions"
          filterable
          filterPlaceholder="Filter Advisors...."
          placeholder="Search by Advisors"
          truncate
          multiple
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.advisors = advisorOptions.map(advisor => advisor.value)
              "
              @clear="filters.advisors = []"
            />
          </template>
        </x-select>

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
          v-model="filters.registration_type"
          label="Registration Type"
          placeholder="Select any option"
          :options="registrationTypeOptions"
        />

        <x-select
          v-if="isVehicleUseDisabled"
          v-model="filters.vehicle_use"
          label="Vehicle Use"
          placeholder="Select any option"
          :options="vehicleUseOptions"
        />

        <x-select
          v-if="canShow('isCommercial') && showCommercialRule"
          v-model="filters.isCommercial"
          label="Commercial Rule"
          placeholder="Select any option"
          :options="commercialOptions"
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
              ? 'Travel Coverage (Select Insurance Type first)'
              : 'Travel Coverage'
          "
          :options="[
            { value: '', label: 'Select travel coverage' },
            ...travelCoverageOptions,
          ]"
          placeholder="Select travel coverage"
          class="w-full"
        />

        <x-select
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
          filterable
          filterPlaceholder="Filter Segment...."
        />
      </div>

      <div class="flex justify-between gap-3 mb-4 items-center">
        <div class="flex-1">
          <p v-if="isDirty" class="text-xs text-red-500 text-center font-bold">
            Please click search to show updated records based on the selected
            filters
          </p>
        </div>
        <div class="flex gap-3">
          <x-button
            v-if="can(permissionsEnum.EXTRACT_REPORT)"
            size="sm"
            color="#48bb78"
            @click.prevent="onDataExport(EXPORT_TYPES.EMAIL)"
            :loading="exportLoader"
          >
            Export via email
          </x-button>
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
      :items="props.reportData || []"
      border-cell
      :rows-per-page-message="'Records per page'"
      :rows-items="[10, 25, 50, 100]"
      :rows-per-page="100"
      :empty-message="'No Records Available'"
      hide-footer
    >
      <template #item-conversion="item">
        <span>{{ formatPercentage(item.conversion) }}</span>
      </template>

      <template #item-advisor_name="item">
        <span>{{ item.advisor_name }}</span>
      </template>

      <template #item-team_average="item">
        <span>{{ formatPercentage(item.team_average) }}</span>
      </template>

      <template #item-new_conversion="item">
        <span>{{ formatPercentage(item.new_conversion) }}</span>
      </template>

      <template #item-expected_sales="item">
        <span>{{ formatValue(item.expected_sales) }}</span>
      </template>

      <template #item-required_sales="item">
        <span>{{ formatValue(item.required_sales) }}</span>
      </template>

      <template #item-current_cap="item">
        <span>{{ formatValue(item.current_cap) }}</span>
      </template>

      <template #item-suggested_cap="item">
        <span>{{ formatValue(item.suggested_cap) }}</span>
      </template>

      <template #body-append>
        <tr v-if="props.reportData?.length > 0" class="total-row">
          <td class="direction-left">Total</td>
          <td class="direction-center">
            {{ calculateTotalSum('total_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum('sale_leads') }}
          </td>
          <td class="direction-center">
            {{ formatPercentage(props.reportData[0]?.total_average) }}
          </td>
          <td></td>
          <td class="direction-center"></td>
          <td class="direction-center"></td>
          <td></td>
          <td></td>
          <td></td>
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
  </div>
</template>
