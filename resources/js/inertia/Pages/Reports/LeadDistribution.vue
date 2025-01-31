<script setup>
defineProps({
  reportData: Object,
  filterOptions: Object,
  defaultFilters: Object,
});
const loaders = reactive({
  table: false,
});
const page = usePage();
const can = permission => useCan(permission);
const { isRequired } = useRules();
const permissionsEnum = page.props.permissionsEnum;
let quoteSegments = reactive(page.props.quoteSegments ?? []);

const params = useUrlSearchParams('history');
const isDirty = ref(false);
const isMounted = ref(false);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const toast = useToast();
const tableHeader = [
  {
    text: 'Tier Name',
    value: 'tier_name',
  },
  {
    text: 'Team Name',
    value: 'team_name',
    tooltip: 'Filter teams by selected LOB.',
  },
  {
    text: 'Received Leads',
    value: 'received_leads',
    tooltip: 'Leads received via webform i.e. IM Leads.',
  },
  {
    text: 'LEADS CREATED',
    value: 'lead_created',
    tooltip: 'Leads manually created in IMCRM.',
  },
  {
    text: 'TOTAL LEADS',
    value: 'total_leads',
    tooltip: 'Total of IMCRM and webform leads.',
  },
  {
    text: 'UNASSIGNED LEADS',
    value: 'unassigned_leads',
    tooltip: 'Leads not yet assigned to advisors.',
  },
  {
    text: 'AUTO ASSIGNED',
    value: 'auto_assigned',
    tooltip: 'System-assigned IM Leads to advisors.',
  },
  {
    text: 'MANUALLY ASSIGNED',
    value: 'manually_assigned',
    tooltip: 'Leads manually assigned by the manager.',
  },
];

let filters = reactive({
  lob: quoteTypeCodeEnum.Car,
  createdAtDates: null,
  assignmentTypes: 'All',
  segment_filter: 'all',
  page: 1,
  tiers: [],
  isCommercial: 'All',
  sic_advisor_requested: 'All',
});

function onSubmit(isValid, isMounted = false) {
  if (!filters.lob && isMounted === false) {
    toast.error({
      title: 'Please select Line of Business',
      position: 'top',
    });
    return;
  }

  if (isValid && filters.lob) {
    isDirty.value = false;
    filters.page = 1;
    const payLoad = cleanFilters(filters);
    router.visit('/reports/lead-distribution', {
      method: 'get',
      data: {
        ...payLoad,
        ...(payLoad.batches && {
          batches: Array.isArray(payLoad.batches)
            ? payLoad.batches
            : [payLoad.batches],
        }),
        ...(payLoad.leadSources && {
          leadSources: Array.isArray(payLoad.leadSources)
            ? payLoad.leadSources
            : [payLoad.leadSources],
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
  setDefaultValues();

  isDirty.value = false;
  router.visit('/reports/lead-distribution', {
    method: 'get',
    data: filters,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const quoteTypesOptions = computed(() => {
  return Object.keys(page.props.filterOptions.lob).map(text => ({
    label: text,
    value: page.props.filterOptions.lob[text],
  }));
});

const cleanFilters = filters => {
  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' ||
        filters[key] == null ||
        filters[key].length == 0) &&
      delete filters[key],
  );
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

  isMounted.value = true;

  onSubmit(true, true);
});

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
};

const onLobChange = (e, isOnMounted = false) => {
  onSubmit(true, isOnMounted);
};
</script>

<template>
  <div>
    <Head title="Lead Distribution Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Lead Distribution Report
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <label> Line of Business <span class="text-red-500">*</span> </label>
          <ComboBox
            v-model="filters.lob"
            placeholder="Select Line of Business"
            :options="quoteTypesOptions"
            class="w-full"
            :single="true"
            @update:modelValue="onLobChange"
            :rules="[isRequired]"
          />
        </div>

        <div>
          <x-tooltip position="top">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Created Date <span class="text-red-500">*</span>
            </label>
            <template #tooltip> The date when the lead is created. </template>
          </x-tooltip>
          <DatePicker
            v-model="filters.createdAtDates"
            placeholder="Select Start & End Date"
            range
            :max-range="92"
            size="sm"
            model-type="yyyy-MM-dd"
            :rules="[isRequired]"
          />
        </div>

        <ComboBox
          v-if="filters.lob === quoteTypeCodeEnum.Car"
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
        <x-select
          v-if="filters.lob === quoteTypeCodeEnum.Car"
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
          v-model="filters.assignmentTypes"
          label="Assignment Type"
          placeholder="Select any option"
          :options="filterOptions?.assignmentTypes"
          :single="true"
        />

        <ComboBox
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="
            quoteSegments?.filter(segment =>
              [quoteTypeCodeEnum.Health, quoteTypeCodeEnum.Travel].includes(
                filters.lob,
              )
                ? segment.value !== 'sic-revival'
                : true,
            )
          "
          :single="true"
        />
        <ComboBox
          v-if="filters.lob === quoteTypeCodeEnum.Car"
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
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="
        tableHeader.filter(header => {
          if (filters.lob !== quoteTypeCodeEnum.Car) {
            return header.value !== 'tier_name';
          } else {
            return header.value !== 'team_name';
          }
        })
      "
      :items="reportData.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-team_name="{ team_name }">
        {{
          team_name
            ? team_name
            : filters.lob === quoteTypeCodeEnum.Health
              ? 'SIC 1.0'
              : ''
        }}
      </template>
      <template
        v-for="header in tableHeader.filter(header => header.tooltip)"
        :key="header.value"
        #[`header-${header.value}`]="header"
      >
        <HeaderWithTooltip :header="header" />
      </template>
      <template #body-append>
        <tr
          v-if="reportData.data && reportData.data.length > 0"
          class="total-row"
        >
          <td class="direction-left">Total</td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'received_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'lead_created') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'total_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'unassigned_leads') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'auto_assigned') }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSum(reportData.data, 'manually_assigned') }}
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
