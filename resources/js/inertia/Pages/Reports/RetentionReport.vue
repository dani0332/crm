<script setup>
import { filter } from 'lodash';
import { ref } from 'vue';

const props = defineProps({
  filterOptions: Object,
  filtersByLob: Object,
  reportData: Object,
  footerData: Array,
  filters: Array,
  productName: String,
  retentionReportEnum: Array,
  departments: Array,
});

const page = usePage();
const isDirty = ref(false);
const isMounted = ref(false);
const advisorOptions = ref([]);
const batchOptions = ref([]);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const canExport = ref(false);
const RetentionReportEnum = props.retentionReportEnum;

const objToUrl = obj => {
  // Helper function to convert nested objects to query string format
  const toQueryString = (prefix, value) => {
    if (typeof value === 'object' && value !== null) {
      return Object.keys(value)
        .map(key => toQueryString(`${prefix}[${key}]`, value[key]))
        .join('&');
    }
    return `${prefix}=${encodeURIComponent(value)}`;
  };

  return Object.keys(obj)
    .filter(
      key =>
        obj[key] !== '' &&
        (Array.isArray(obj[key]) ? obj[key].length > 0 : obj[key] !== null),
    )
    .map(key => toQueryString(key, obj[key]))
    .join('&');
};

const getFiltersObject = () => {
  return {
    lob: props.productName,
    displayBy: '',
    department: '',
    policyExpiryDate: [],
    asAtDate: '',
    teams: [],
    advisors: [],
    page: 1,
    insurance_type: '',
    type: '',
    advisor_id: '',
    quote_batch_id: '',
    batch: [],
    renewal_batch_id: '',
  };
};

let filters = reactive(getFiltersObject());

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

const displayBy = ref([
  { label: 'Month', value: RetentionReportEnum.MONTHLY },
  { label: 'Batch', value: RetentionReportEnum.BATCH },
]);

const teamOptions = ref([]);

const quoteTypesOptions = computed(() => {
  const quoteTypesOptions = [
    ...Object.keys(page.props.filterOptions.lob).map(text => ({
      label: text,
      value: page.props.filterOptions.lob[text],
    })),
  ];
  return quoteTypesOptions;
});

function onReset() {
  const initialFilters = getFiltersObject();
  Object.keys(filters).forEach(key => {
    filters[key] = initialFilters[key];
  });
  onSubmit(false);
}

const loaders = reactive({
  table: false,
  advisorLeadTable: false,
  teamsOptions: false,
  advisorOptions: false,
  batchOption: false,
});

const loadTeams = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  if (!isMounted) {
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

const loadAdvisorsByDepartment = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisors-by-department`, {
      department_id: e,
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
  if (!isOnMounted) {
    filters.advisors = [];
    advisorOptions.value = [];
  }
  loadAdvisors(e);
};

const onDepartmentChange = (e, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.advisors = [];
    advisorOptions.value = [];
  }
  loadAdvisorsByDepartment(e);
};

const onLobChange = (e, isOnMounted = false) => {
  if (!isOnMounted) {
    filters.teams = [];
    filters.advisors = [];
    advisorOptions.value = [];
    filters.department = '';
    filters.displayBy = '';
    filters.policyExpiryDate = [];
    filters.asAtDate = '';
    filters.teams = [];
    filters.advisors = [];
    filters.page = 1;
    filters.insurance_type = '';
  }

  if ([quoteTypeCodeEnum.CORPLINE].includes(filters.lob)) {
    loadTeams(e);
  } else {
    loadAdvisorsByLob(e);
  }

  onSubmit(false);
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
    [quoteTypeCodeEnum.CORPLINE].includes(filters.lob) &&
    (!filters.teams || filters.teams.length == 0)
  ) {
    label = 'Advisors (select teams first)';
  }

  return label;
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

watch(
  () => filters.displayBy,
  (newValue, oldValue) => {
    filters.policyExpiryDate = [];
    filters.batch = [];
  },
);

onMounted(() => {
  const queryParams = new URLSearchParams(window.location.search);
  filters.lob = queryParams.get('lob') || '';
  filters.displayBy = queryParams.get('displayBy') || '';
  if (filters.lob == '') {
    filters.displayBy = RetentionReportEnum.BATCH;
    filters.lob = props.productName;
  }
  filters.department = queryParams.get('department')
    ? +queryParams.get('department')
    : '';

  if ([quoteTypeCodeEnum.CORPLINE].includes(filters.lob)) {
    loadTeams(filters.lob);
  } else if (
    [quoteTypeCodeEnum.Health].includes(filters.lob) &&
    filters.department
  ) {
    loadAdvisorsByDepartment(filters.department);
  } else {
    loadAdvisorsByLob(filters.lob);
  }
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

const addMissingFilters = payLoad => {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.forEach((value, key) => {
      if (key.includes('[') && key.includes(']')) {
        const baseKey = key.substring(0, key.indexOf('['));
        const index = key.substring(key.indexOf('[') + 1, key.indexOf(']'));
        if (!payLoad[baseKey]) {
          payLoad[baseKey] = [];
        }
        payLoad[baseKey][index] = value;
      } else {
        if (!(key in payLoad)) {
          payLoad[key] = value;
        }
      }
    });
  } catch (ex) {
    console.log(ex);
  }
  return payLoad;
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

function onSubmit(isValid = true) {
  if (isValid) {
    if (filters.lob == '') {
      notification.error({
        title: 'Please select Line of Business',
        position: 'top',
      });
      return;
    }
    if (filters.displayBy == '') {
      notification.error({
        title: 'Please select view by filter',
        position: 'top',
      });
      return;
    }
    if (filters.policyExpiryDate?.length == 0) {
      notification.error({
        title: 'Please select Start & End Date',
        position: 'top',
      });
      return;
    }
    if (
      filters.displayBy == RetentionReportEnum.BATCH &&
      filters.batch.length === 0
    ) {
      notification.error({
        title: 'Please select batch',
        position: 'top',
      });
      return;
    }
  }
  filters.page = 1;
  const payLoad = cleanFilters(filters);
  loaders.table = true;
  router.visit('/reports/retention-report', {
    method: 'get',
    data: {
      ...payLoad,
    },
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => {
      loaders.table = false;
    },
  });
}

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

const totalLeads = reactive({
  modal: false,
  loader: false,
  filters: {
    leadType: '',
    quote_batch_id: null,
    advisorId: null,
    renewal_batch_id: null,
  },
  data: {},
  current: '',
  tableHeader: [
    {
      text: 'Ref-ID',
      value: 'code',
    },
    {
      text: 'Customer Name',
      value: 'fullName',
    },
    {
      text: 'Lead Status',
      value: 'quoteStatusName',
    },
    {
      text: 'Previous Policy Expiry Date',
      value: 'previous_policy_expiry_date',
    },
    {
      text: 'Price',
      value: 'price',
    },
    {
      text: 'Renewal batch id',
      value: 'renewal_batch_id',
    },
  ],
});

function onFetchLeadsInfo(advisor_id, renewal_batch_id, type, month, page = 1) {
  totalLeads.data = [];
  totalLeads.modal = true;
  totalLeads.loader = true;
  filters.type = type;
  filters.page = page;
  filters.renewal_batch_id = renewal_batch_id;
  filters.advisor_id = advisor_id;
  filters.month = month;
  // Deep copy filters to payload
  let payload = JSON.parse(JSON.stringify(filters));
  payload = cleanFilters(payload);
  payload = addMissingFilters(payload);
  if (!payload.policyExpiryDate || payload.policyExpiryDate?.length === 0) {
    if (!payload.month || payload.month === '') {
      delete payload.displayBy;
    }
  }
  axios
    .get(`/reports/fetch-retention-leads-data`, {
      params: {
        ...payload,
      },
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

const setPageTable = page => {
  onFetchLeadsInfo(
    filters.advisor_id,
    filters.renewal_batch_id,
    filters.type,
    filters.month,
    page,
  );
};

const getRetentionReportHeaders = () => {
  const headers = [
    {
      text: 'Month',
      value: 'month',
      tooltip: RetentionReportEnum.MONTH_HEADING,
    },
    {
      text: 'Advisor Name',
      value: 'advisor_name',
      tooltip: RetentionReportEnum.ADVISOR_NAME_HEADING,
    },
    {
      text: 'Total',
      value: 'total',
      tooltip: RetentionReportEnum.TOTAL_HEADING,
    },
    { text: 'Lost', value: 'lost', tooltip: RetentionReportEnum.LOST_HEADING },
    {
      text: 'Invalid',
      value: 'invalid',
      tooltip: RetentionReportEnum.INVALID_HEADING,
    },
    {
      text: 'Policy Booked',
      value: 'sales',
      tooltip: RetentionReportEnum.POLICIES_BOOKED_HEADING,
    },
    {
      text: 'Volume Gross Retention',
      value: 'volume_gross_retention',
      tooltip: RetentionReportEnum.VOLUME_GROSS_RETENTION_HEADING,
    },
    {
      text: 'Volume Net Retention',
      value: 'volume_net_retention',
      tooltip: RetentionReportEnum.VOLUME_NET_RETENTION_HEADING,
    },
    {
      text: 'Relative Retention',
      value: 'relative_retention',
      tooltip: RetentionReportEnum.RELATIVE_RETENTION_HEADING,
    },
  ];

  if (hasBatchKey.value) {
    headers.splice(1, 0, {
      text: 'Batch',
      value: 'batch',
      tooltip: RetentionReportEnum.BATCH_HEADING,
    });
    headers.splice(2, 0, {
      text: 'Start Date',
      value: 'start_date',
      tooltip: RetentionReportEnum.START_DATE_HEADING,
    });
    headers.splice(3, 0, {
      text: 'End Date',
      value: 'end_date',
      tooltip: RetentionReportEnum.END_DATE_HEADING,
    });
  }
  return headers;
};

watch(
  () => filters,
  () => {
    if (
      filters.lob &&
      filters.displayBy &&
      (filters.policyExpiryDate || filters.month)
    ) {
      if (
        filters.displayBy == RetentionReportEnum.BATCH &&
        filters.batch.length == 0
      ) {
        canExport.value = false;
      } else {
        canExport.value = true;
      }
    } else {
      canExport.value = false;
    }
  },
  { deep: true, immediate: true },
);

function buildQuoteURL() {
  const lob = filters.lob.replace(/\s+/g, '');
  // Define the mapping of quote types to their corresponding paths
  const quoteTypePaths = {
    Car: 'car.show',
    Health: 'health.show',
    Travel: 'travel.show',
    Bike: 'bike-quotes-show',
    Pet: 'pet-quotes-show',
    Cycle: 'cycle-quotes-show',
    Yacht: 'yacht-quotes-show',
    Life: 'life-quotes-show',
    Home: 'home.show',
    GroupMedical: 'amt.show',
    CorpLine: 'business.show',
  };
  // Return the path corresponding to the quote type, or null if not found
  return quoteTypePaths[lob] || null;
}

// Define a ref to hold the result of the check
const hasBatchKey = ref(false);

// Watch for changes to reportData
watch(
  () => props.reportData,
  newReportData => {
    // Check if reportData has data and if the first item has a batch key
    if (newReportData?.data?.length > 0) {
      hasBatchKey.value = 'batch' in newReportData.data[0];
    } else {
      hasBatchKey.value = false;
    }
  },
  { immediate: true },
);

function handleDateChange(dateRange) {
  if (!filters.policyExpiryDate) {
    filters.policyExpiryDate = [];
    filters.asAtDate = '';
    return;
  }
  if (
    filters.displayBy == RetentionReportEnum.BATCH &&
    filters.policyExpiryDate &&
    filters.policyExpiryDate?.length == 2
  ) {
    loaders.batchOption = true;
    axios
      .post(`/reports/fetch-batch-by-date`, {
        policyExpiryDate: filters.policyExpiryDate,
      })
      .then(res => {
        if (res.data.length > 0) {
          batchOptions.value = Object.keys(res.data).map(key => ({
            value: res.data[key].id.toString(),
            label: res.data[key].name,
          }));
        }
      })
      .finally(() => {
        loaders.batchOption = false;
      });
  }
}
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
          label="View by"
          :options="displayBy"
          class="w-full"
          :single="true"
        />

        <DatePicker
          v-if="
            filters.displayBy === RetentionReportEnum.MONTHLY ||
            filters.displayBy === RetentionReportEnum.BATCH
          "
          v-model="filters.policyExpiryDate"
          label="Select Start & End Date"
          placeholder="Select Start & End Date"
          range
          size="sm"
          model-type="yyyy-MM-dd"
          :max-range="92"
          @update:model-value="handleDateChange"
        />

        <DatePicker
          v-model="filters.asAtDate"
          label="Select As At Date"
          placeholder="Select As At Date"
          size="sm"
          :disabled="
            !filters?.policyExpiryDate ||
            filters?.policyExpiryDate?.length === 0
          "
          :min-date="filters?.policyExpiryDate && filters?.policyExpiryDate[0]"
          :max-date="filters?.policyExpiryDate && filters?.policyExpiryDate[1]"
          model-type="yyyy-MM-dd"
        />

        <ComboBox
          v-if="
            filters.displayBy === RetentionReportEnum.BATCH &&
            filters.policyExpiryDate &&
            filters.policyExpiryDate?.length == 2
          "
          v-model="filters.batch"
          label="Batch"
          placeholder="Search by Batch"
          :options="batchOptions"
          :loading="loaders.batchOption"
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
          v-if="canShow('department')"
          v-model="filters.department"
          placeholder="Select Department"
          label="Department"
          :options="departments"
          class="w-full"
          @update:model-value="onDepartmentChange"
          :single="true"
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
          v-if="canShow('insurance_type')"
          v-model="filters.insurance_type"
          label="Insurance Type"
          placeholder="Select insurance type"
          :options="[
            { value: '', label: 'Select insurance type' },
            ...insuranceTypeOptions,
          ]"
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
          <x-button
            :loading="loaders.table"
            v-if="can(canExport && permissionsEnum.DATA_EXTRACTION)"
            size="sm"
            color="emerald"
            :href="`/${RetentionReportEnum.RETENTION}/report-export?${objToUrl(filters)}`"
            class="justify-self-start"
          >
            Export
          </x-button>
          <x-tooltip
            v-if="!canExport && can(permissionsEnum.DATA_EXTRACTION)"
            position="right"
          >
            <x-button
              :loading="loaders.table"
              tag="div"
              size="sm"
              color="emerald"
            >
              Export
            </x-button>
            <template #tooltip>
              <span class="font-medium">
                Select LOB and select view by and select month or select expiry
                date and batch to export
              </span>
            </template>
          </x-tooltip>
          <x-button
            :loading="loaders.table"
            size="sm"
            color="#ff5e00"
            type="submit"
            >Search</x-button
          >
          <x-button
            :loading="loaders.table"
            size="sm"
            color="primary"
            @click.prevent="onReset"
          >
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="getRetentionReportHeaders()"
      :items="reportData.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #header="header">
        <HeaderCell :header="header" />
      </template>
      <template #item-total="item">
        <x-tooltip position="right bootom">
          <p v-if="item.total == 0">{{ item.total }}</p>
          <button
            v-else
            @click="
              onFetchLeadsInfo(
                item.advisor_id,
                item.renewal_batch_id,
                RetentionReportEnum.TOTAL,
                item.month,
              )
            "
            class="text-primary underline"
          >
            {{ item.total }}
          </button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              {{ RetentionReportEnum.TOTAL_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>
      <template #item-lost="item">
        <x-tooltip position="right bootom">
          <p v-if="item.lost == 0">{{ item.lost }}</p>
          <button
            v-else
            @click="
              onFetchLeadsInfo(
                item.advisor_id,
                item.renewal_batch_id,
                RetentionReportEnum.LOST,
                item.month,
              )
            "
            class="text-primary underline"
          >
            {{ item.lost }}
          </button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              {{ RetentionReportEnum.LOST_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>
      <template #item-invalid="item">
        <x-tooltip position="right bootom">
          <p v-if="item.invalid == 0">{{ item.invalid }}</p>
          <button
            v-else
            @click="
              onFetchLeadsInfo(
                item.advisor_id,
                item.renewal_batch_id,
                RetentionReportEnum.INVALID,
                item.month,
              )
            "
            class="text-primary underline"
          >
            {{ item.invalid }}
          </button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              {{ RetentionReportEnum.INVALID_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>
      <template #item-sales="item">
        <x-tooltip position="right bootom">
          <p v-if="item.sales == 0">{{ item.sales }}</p>
          <button
            v-else
            @click="
              onFetchLeadsInfo(
                item.advisor_id,
                item.renewal_batch_id,
                RetentionReportEnum.SALES,
                item.month,
              )
            "
            class="text-primary underline"
          >
            {{ item.sales }}
          </button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              {{ RetentionReportEnum.SALES_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>

      <template #item-volume_gross_retention="item">
        <x-tooltip placement="left">
          <span class="underline decoration-dotted">{{
            item.volume_gross_retention
          }}</span>
          <template #tooltip>
            <span class="whitespace-break-spaces !normal-case">
              {{ RetentionReportEnum.VOLUME_GROSS_RETENTION_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>

      <template #item-volume_net_retention="item">
        <x-tooltip placement="left">
          <span class="underline decoration-dotted">{{
            item.volume_net_retention
          }}</span>
          <template #tooltip>
            <span class="whitespace-break-spaces !normal-case">
              {{ RetentionReportEnum.VOLUME_NET_RETENTION_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>

      <template #item-relative_retention="item">
        <x-tooltip placement="left">
          <span class="underline decoration-dotted">{{
            item.relative_retention
          }}</span>
          <template #tooltip>
            <span class="whitespace-break-spaces !normal-case">
              {{ RetentionReportEnum.RELATIVE_RETENTION_COLUMN }}
            </span>
          </template>
        </x-tooltip>
      </template>

      <template #body-append>
        <tr
          v-if="
            reportData.length > 0 ||
            (reportData.data && reportData.data.length > 0)
          "
          class="total-row"
        >
          <td class="direction-left">Total</td>
          <td v-if="hasBatchKey"></td>
          <td v-if="hasBatchKey"></td>
          <td v-if="hasBatchKey"></td>
          <td></td>
          <td>{{ footerData.total }}</td>
          <td>{{ footerData.lost }}</td>
          <td>{{ footerData.invalid }}</td>
          <td>{{ footerData.sales }}</td>
          <td>{{ footerData.volume_gross_retention }}</td>
          <td>{{ footerData.volume_net_retention }}</td>
          <td></td>
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

    <x-modal v-model="totalLeads.modal" size="xl" show-close backdrop>
      <template #header>
        <div class="text-center">
          Advisor Assigned :
          {{ filters?.type?.charAt(0).toUpperCase() + filters?.type?.slice(1) }}
          Leads
        </div>
      </template>
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
          >
            <template #item-code="{ code, uuid }">
              <a
                :href="route(buildQuoteURL(), uuid)"
                target="_blank"
                class="text-primary-500 hover:underline"
              >
                {{ code }}
              </a>
            </template>
          </DataTable>
        </div>
        <div v-else class="p-4 flex flex-col justify-center items-center gap-4">
          <x-spinner size="lg" color="#1d83bc" />
          <p class="text-sm">Fetching records...</p>
        </div>
      </section>
    </x-modal>
  </div>
</template>

<style scoped>
.custom-tooltip-content {
  max-width: 200px;
  white-space: normal;
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
}
</style>
