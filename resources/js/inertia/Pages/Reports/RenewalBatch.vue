<script setup>
import { useDateFormat } from '@vueuse/shared';

defineProps({
  reportData: Object,
  superRetentionData: Object,
  filterOptions: Object,
  defaultFilters: Object,
  renewalBatchesList: Object,
});

const loaders = reactive({
  table: false,
  advisorsOptions: false,
  subTeamsOptions: false,
});

const page = usePage();
const dataTableRef = ref();
const isMounted = ref(false);
const isDirty = ref(false);
const { maxSelections } = useRules();

const carRegistrationTypeEnum = page.props.carRegistrationType;
const carVehicleUseEnum = page.props.carVehicleUse;

const advisorOptions = ref(
  Object.keys(page.props.filterOptions.advisors).map(key => ({
    value: key.toString(),
    label: page.props.filterOptions.advisors[key],
  })),
);

const defaultAdvisorOptions = advisorOptions;

const subTeamsOptions = ref(
  Object.keys(page.props.filterOptions.subTeams).map(key => ({
    value: key,
    label: page.props.filterOptions.subTeams[key].toUpperCase(),
  })),
);

const defaultSubTeamsOptions = subTeamsOptions;

const params = useUrlSearchParams('history');
const tableHeader = [
  {
    text: 'Batch No.',
    value: 'renewal_batch',
  },
  {
    text: 'Week Ending',
    value: 'end_date',
  },
  {
    text: 'Renewed',
    value: 'renewed',
  },
  {
    text: 'Total Allocated',
    value: 'total_allocated_leads',
  },
  {
    text: 'Car Sold/Cancelled',
    value: 'car_sold',
  },
  // {
  //     text: 'Uncontactable',
  //     value: 'uncontactable',
  // },
  {
    text: 'Total Allocated (excluding approved car sold)',
    value: 'total_allocate_minus_cancelled_uncontactable',
  },
  {
    text: 'Advisor Retention',
    value: 'advisor_retention',
  },
  {
    text: 'Value Segment Retention',
    value: 'value_segment_retention',
  },
  {
    text: 'Volume Segment Retention',
    value: 'volume_segment_retention',
  },
  {
    text: 'Relative Retention on Value Segment',
    value: 'relative_retention_value_segment',
  },
  {
    text: 'Relative Retention on  Volume Segment',
    value: 'relative_retention_volume_segment',
  },
  {
    text: 'IM Retention',
    value: 'im_retention',
  },
  {
    text: 'Relative Retention',
    value: 'relative_retention',
  },
  {
    text: 'Monthly Retention',
    value: 'monthly_retention',
  },
];

const filters = reactive({
  reportDate: '',
  batchNo: [],
  subTeams: [],
  segment: '',
  advisors: [],
  teams: [],
  page: 1,
  registration_type: '',
  vehicle_use: '',
});

function onSubmit(isValid) {
  if (isValid) {
    isDirty.value = false;
    filters.page = 1;
    const payLoad = cleanFilters(filters);
    router.visit('/reports/renewal-report', {
      method: 'get',
      data: {
        ...payLoad,
        ...(payLoad.advisors && {
          advisors: Array.isArray(payLoad.advisors)
            ? payLoad.advisors
            : [payLoad.advisors],
        }),
        ...(payLoad.batchNo && {
          batchNo: Array.isArray(payLoad.batchNo)
            ? payLoad.batchNo
            : [payLoad.batchNo],
        }),
        ...(payLoad.subTeams && {
          subTeams: Array.isArray(payLoad.subTeams)
            ? payLoad.subTeams
            : [payLoad.subTeams],
        }),
        ...(payLoad.teams && {
          teams: Array.isArray(payLoad.teams) ? payLoad.teams : [payLoad.teams],
        }),
      },
      preserveState: false,
      preserveScroll: true,
      onBefore: () => (loaders.table = true),
      onSuccess: () => {
        loaders.table = false;
        calculateValuesAndHighlight();
      },
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  if (page.props.defaultFilters) {
    filters.reportDate = page.props.defaultFilters.reportDate;
  }

  isDirty.value = false;
  router.visit('/reports/renewal-report', {
    method: 'get',
    data: {
      reportDate: filters.reportDate,
      page: 1,
    },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

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
  for (const [key, value] of Object.entries(params)) {
    const match = key.match(/(.+?)\[(\d+)\]/);
    if (match) {
      const baseKey = match[1];
      if (!filters[baseKey]) {
        filters[baseKey] = [];
      }
      filters[baseKey][match[2]] = value;
    } else {
      filters[key] = value;
    }
  }
}

const onTeamChange = e => {
  if (e.length == 0) {
    // filters.teams = [];
    filters.subTeams = [];
    advisorOptions.value = [];
    subTeamsOptions.value = [];

    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;
  loaders.subTeamsOptions = true;

  axios
    .post(`/reports/fetch-subteams-advisor-by-team`, {
      teamIds: Array.isArray(e) ? e : [e],
    })
    .then(res => {
      if (res.data.advisors.length > 0) {
        advisorOptions.value = Object.keys(res.data.advisors).map(key => ({
          value: res.data.advisors[key].id.toString(),
          label: res.data.advisors[key].name,
        }));
      }
      if (res.data.subTeams) {
        subTeamsOptions.value = Object.keys(res.data.subTeams).map(key => ({
          value: key,
          label: res.data.subTeams[key],
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
      loaders.subTeamsOptions = false;
    });
};

const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);

let lastMonthSummedIndex = 0;
let lastMonthSummedIndexForSuperRetention = 0;
let currentRowSpan = 0;
let currentRowSpanForSuperRetention = 0;

const rolesEnum = page.props.rolesEnum;

let avgImRetentionArr = {};
let avgRawRetentionArr = {};

let monthlyRawAverages = {};
let totalAllocationList = [];
let renewedCountsList = [];

const reportDataRef = reactive(page.props.reportData.data);
const superRetentionDataRef = reactive(page.props.superRetentionData);

function getMonthName(monthNumber) {
  const date = new Date();
  let firstDate = new Date(date.getFullYear(), date.getMonth(), 1);
  firstDate.setMonth(monthNumber - 1);

  return firstDate.toLocaleString('en-US', { month: 'short' });
}

function calculateValuesAndHighlight() {
  lastMonthSummedIndex = 0; // to track till which month the sum has been calculated
  lastMonthSummedIndexForSuperRetention = 0; // to track till which month the sum has been calculated for super retention
  currentRowSpan = 0; // to determine the rowspan of the monthly retention column

  let segmentFilter = filters.segment ? filters.segment : '';

  // process the data to get the correct values for the retention report
  reportDataRef.forEach((item, index) => {
    let dynamicIndexForRenewedByVolumeSegment =
      'renewed_by_volume_segment_advisors_for_' + item.name;
    let dynamicIndexForRenewedByValueSegment =
      'renewed_by_value_segment_advisors_for_' + item.name;
    let dynamicIndexForTotalByVolumeSegment =
      'total_by_volume_segment_advisors_for_' + item.name;
    let dynamicIndexForTotalByValueSegment =
      'total_by_value_segment_advisors_for_' + item.name;

    if (segmentFilter == 'volume') {
      item.total_allocated_leads = item[dynamicIndexForTotalByVolumeSegment];
      item.renewed = item[dynamicIndexForRenewedByVolumeSegment];
      // item.early_renewal = item.early_renewal_by_volume_segment    // tempory hidden don't remove
    } else if (segmentFilter == 'value') {
      item.total_allocated_leads = item[dynamicIndexForTotalByValueSegment];
      item.renewed = item[dynamicIndexForRenewedByValueSegment];
      // item.early_renewal = item.early_renewal_by_value_segment   // tempory hidden don't remove
    }
  });
  // process the data to get the correct values for the super retention report
  superRetentionDataRef.forEach((superItem, superIndex) => {
    let dynamicIndexForConvertedByVolumeSegment =
      'health_converted_by_volume_segment_advisors_for_' + superItem.name;
    let dynamicIndexForConvertedByValueSegment =
      'health_converted_by_value_segment_advisors_for_' + superItem.name;

    if (segmentFilter == 'volume') {
      superItem.health_converted =
        superItem[dynamicIndexForConvertedByVolumeSegment];
    } else if (segmentFilter == 'value') {
      superItem.health_converted =
        superItem[dynamicIndexForConvertedByValueSegment];
    }
    superItem.monthlyHealthRenewed = calculateMonthlyHealthRenewed(
      superRetentionDataRef,
      superIndex,
    );
    superItem.rowSpan = currentRowSpanForSuperRetention;
  });

  reportDataRef.forEach((item, index) => {
    let dynamicIndexForRenewedByVolumeSegment =
      'renewed_by_volume_segment_advisors_for_' + item.name;
    let dynamicIndexForRenewedByValueSegment =
      'renewed_by_value_segment_advisors_for_' + item.name;
    let dynamicIndexForTotalByVolumeSegment =
      'total_by_volume_segment_advisors_for_' + item.name;
    let dynamicIndexForTotalByValueSegment =
      'total_by_value_segment_advisors_for_' + item.name;

    let fontColorAssigned = false;
    let rawRetention = 0.0;
    let overallRawRetention = 0.0;

    rawRetention = ((item.renewed / item.total_allocated_leads) * 100).toFixed(
      2,
    );
    overallRawRetention =
      item.renewed_by_all_advisors && item.total_allocated_leads_by_all_advisors
        ? (
            (item.renewed_by_all_advisors /
              item.total_allocated_leads_by_all_advisors) *
            100
          ).toFixed(2)
        : rawRetention;

    let advisorRetention = rawRetention;
    let imRetention = overallRawRetention;

    let valueSegmentConversion = (
      (parseInt(item[dynamicIndexForRenewedByValueSegment]) /
        parseInt(item[dynamicIndexForTotalByValueSegment])) *
      // - parseInt(item.early_renewal_by_value_segment) // tempory hidden don't remove
      100
    ).toFixed(2);
    valueSegmentConversion =
      valueSegmentConversion == 'NaN' ? '0.00' : valueSegmentConversion;

    let volumeSegmentConversion = (
      (parseInt(item[dynamicIndexForRenewedByVolumeSegment]) /
        parseInt(item[dynamicIndexForTotalByVolumeSegment])) *
      // - parseInt(item.early_renewal_by_volume_segment) // tempory hidden don't remove
      100
    ).toFixed(2);
    volumeSegmentConversion =
      volumeSegmentConversion == 'NaN' ? '0.00' : volumeSegmentConversion;

    const monthlySum = calculateMonthlySum(reportDataRef, index);

    item.volumeSegmentConversion =
      volumeSegmentConversion == 'NaN' ? '0.00' : volumeSegmentConversion;
    item.valueSegmentConversion =
      valueSegmentConversion == 'NaN' ? '0.00' : valueSegmentConversion;
    item.monthlySum = monthlySum == 'NaN' ? '0.00' : monthlySum;
    item.rawRetention = rawRetention == 'NaN' ? '0.00' : rawRetention;
    item.overallRawRetention =
      overallRawRetention == 'NaN' ? '0.00' : overallRawRetention;
    item.advisorRetention = item.rawRetention;
    item.imRetention = item.overallRawRetention;
    item.rowSpan = currentRowSpan;

    item.highlight =
      Number(advisorRetention) < Number(valueSegmentConversion) ||
      Number(advisorRetention) < Number(volumeSegmentConversion) ||
      Number(advisorRetention) < Number(imRetention);

    let year = useDateFormat(new Date(), 'YY').value;
    let monthName = getMonthName(item.month) + '-' + year;

    // Check if the property exists and initialize it as an array if it doesn't
    if (!avgImRetentionArr[monthName]) {
      avgImRetentionArr[monthName] = [];
    }
    if (!avgRawRetentionArr[monthName]) {
      avgRawRetentionArr[monthName] = [];
    }

    avgImRetentionArr[monthName].push(
      imRetention == 'NaN' ? parseFloat('0.00') : parseFloat(imRetention),
    );
    avgRawRetentionArr[monthName].push(
      overallRawRetention == 'NaN'
        ? parseFloat('0.00')
        : parseFloat(overallRawRetention),
    );

    if (!page.props.filterOptions.isMCR) {
      page.props.renewalBatchesList.forEach(batch => {
        if (batch.name == item.renewal_batch) {
          batch.slabs.forEach(slab => {
            if (page.props.filterOptions.isBDM && !fontColorAssigned) {
              let teamName = slab.team_name;
              if (teamName.includes('BDM')) {
                let slabId = slab.pivot.slab_id;
                let slabMax = slab.pivot.max;
                let slabMin = slab.pivot.min;

                if (slabId === 3 && item.advisorRetention > slabMin) {
                  item.advisorRetentionClass = 'text-green-500';
                  fontColorAssigned = true;
                  return;
                } else if (
                  slabId === 2 &&
                  item.advisorRetention < slabMax &&
                  item.advisorRetention > slabMin
                ) {
                  item.advisorRetentionClass = 'text-amber-500';
                  fontColorAssigned = true;
                  return;
                } else if (slabId === 1 && item.advisorRetention < slabMax) {
                  item.advisorRetentionClass = 'text-red-500';
                  fontColorAssigned = true;
                  return;
                }
              }
            } else if (
              page.props.filterOptions.isRenewals &&
              !fontColorAssigned
            ) {
              let teamName = slab.team_name;
              if (teamName.includes('Volume') || teamName.includes('Value')) {
                let slabId = slab.pivot.slab_id;
                let slabMax = slab.pivot.max;
                let slabMin = slab.pivot.min;

                if (slabId === 4 && item.advisorRetention > slabMin) {
                  item.advisorRetentionClass = 'text-green-500';
                  fontColorAssigned = true;
                  return;
                } else if (
                  slabId === 3 &&
                  item.advisorRetention < slabMax &&
                  item.advisorRetention > slabMin
                ) {
                  item.advisorRetentionClass = 'text-amber-500';
                  fontColorAssigned = true;
                  return;
                } else if (
                  slabId === 2 &&
                  item.advisorRetention < slabMax &&
                  item.advisorRetention > slabMin
                ) {
                  item.advisorRetentionClass = 'text-orange-500';
                  fontColorAssigned = true;
                  return;
                } else if (slabId === 1 && item.advisorRetention < slabMax) {
                  item.advisorRetentionClass = 'text-red-500';
                  fontColorAssigned = true;
                  return;
                }
              }
            }
          });
        }
      });
    }
  });
  monthlyRawAverages = calculateMonthlyAverages(avgRawRetentionArr);
  // reset arrays
  avgImRetentionArr = {};
  avgRawRetentionArr = {};
}

const calculateMonthlyHealthRenewed = (data, index) => {
  let totalRenewed = 0;
  let currentMonthValue = data[index].month;

  currentRowSpanForSuperRetention = 0;

  if (lastMonthSummedIndexForSuperRetention <= index) {
    while (index <= data.length - 1 && currentMonthValue == data[index].month) {
      totalRenewed =
        parseInt(totalRenewed) + parseInt(data[index].health_converted);
      index++;
      lastMonthSummedIndexForSuperRetention = index;
      currentRowSpanForSuperRetention++;
    }

    return Number(totalRenewed);
  }
};

onMounted(() => {
  if (page.props.defaultFilters && !params['page']) {
    filters.reportDate = page.props.defaultFilters.reportDate;
  }

  setQueryStringFilters();

  if (filters.teams && filters.teams.length > 0) {
    onTeamChange(filters.teams);
  }

  calculateValuesAndHighlight();
  isMounted.value = true;
});

const calculateMonthlySum = (data, index) => {
  let totalRenewed = 0;
  let totalAllocated = 0;
  // let totalEarlyRenewal = 0; // tempory hidden don't remove
  currentRowSpan = 0;

  let currentMonthValue = data[index].month;

  if (lastMonthSummedIndex <= index) {
    while (index <= data.length - 1 && currentMonthValue == data[index].month) {
      totalRenewed = parseInt(totalRenewed) + parseInt(data[index].renewed);
      totalAllocated =
        parseInt(totalAllocated) + parseInt(data[index].total_allocated_leads);
      // totalEarlyRenewal = parseInt(totalEarlyRenewal) + parseInt(data[index].early_renewal); // tempory hidden don't remove
      index++;
      lastMonthSummedIndex = index;
      currentRowSpan++;
    }

    renewedCountsList[currentMonthValue] = totalRenewed;
    totalAllocationList[currentMonthValue] = totalAllocated;
    // + totalEarlyRenewal // tempory hidden don't remove

    let result =
      (totalRenewed / totalAllocated) *
      // - totalEarlyRenewal // tempory hidden don't remove
      100;

    return result.toFixed(2);
  }
};

function calculateAverage(arr) {
  const sum = arr.reduce((acc, val) => acc + val, 0);
  return sum / arr.length;
}

function calculateMonthlyAverages(data) {
  const monthlyAverages = {};

  for (const month in data) {
    const avg = calculateAverage(data[month]);
    monthlyAverages[month] = avg;
  }

  return monthlyAverages;
}

watch(
  () => page.props.reportData.current_page,
  () => {
    calculateValuesAndHighlight();
  },
  { deep: true, immediate: false },
);

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

const isVehicleUseDisabled = computed(() => {
  return filters.registration_type === carRegistrationTypeEnum.COMPANY;
});

watch(
  () => filters.registration_type,
  newValue => {
    if (newValue != carRegistrationTypeEnum.COMPANY) {
      filters.vehicle_use = '';
    }
  },
);
</script>
<template>
  <div>
    <Head title="Renewal Batches Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Renewal Batches Report
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <DatePicker
          v-model="filters.reportDate"
          label="Report Date"
          placeholder="Select Date"
          size="sm"
          model-type="yyyy-MM-dd"
        />

        <x-select
          v-model="filters.batchNo"
          label="Batch Number"
          placeholder="Search by Batch Number"
          :options="
            Object.keys(filterOptions.batches).map(key => ({
              value: key,
              label: filterOptions.batches[key],
            }))
          "
          multiple
          truncate
          filterable
          :rules="[maxSelections(15)]"
          filterPlaceholder="Filter Batches...."
          helper="You can select up to 15 batches"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.batchNo = filterOptions.batches.map(
                  batch => batch.value,
                )
              "
              @clear="filters.batchNo = []"
            />
          </template>
        </x-select>
        <x-select
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
          v-if="
            hasAnyRole([
              rolesEnum.CarManager,
              rolesEnum.RenewalsManager,
              rolesEnum.SeniorManagement,
              rolesEnum.Accounts,
            ])
          "
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="
            Object.keys(filterOptions.teams).map(key => ({
              value: key,
              label: filterOptions.teams[key],
            }))
          "
          @update:model-value="onTeamChange"
          filterable
          filterPlaceholder="Filter Teams...."
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="filters.teams = teamOptions.map(team => team.value)"
              @clear="filters.teams = []"
            />
          </template>
        </x-select>

        <x-select
          v-if="
            hasAnyRole([
              rolesEnum.CarManager,
              rolesEnum.RenewalsManager,
              rolesEnum.SeniorManagement,
            ])
          "
          v-model="filters.subTeams"
          label="Sub Team"
          placeholder="Search by Sub Team"
          class="w-full"
          :options="subTeamsOptions"
          filterable
          filterPlaceholder="Filter Sub Teams...."
          multiple
          truncate
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                filters.subTeams = subTeamsOptions.map(subTeam => subTeam.value)
              "
              @clear="filters.subTeams = []"
            />
          </template>
        </x-select>

        <x-select
          v-if="
            hasAnyRole([
              rolesEnum.CarManager,
              rolesEnum.RenewalsManager,
              rolesEnum.SeniorManagement,
              rolesEnum.Accounts,
            ])
          "
          v-model="filters.advisors"
          label="Advisors"
          placeholder="Search by Advisors"
          :options="advisorOptions"
          :loading="loaders.advisorOptions"
          filterable
          filterPlaceholder="Filter Advisors...."
          multiple
          truncate
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
          v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager])"
          v-model="filters.segment"
          label="Segment"
          placeholder="Search by Segment"
          class="w-full"
          :options="
            Object.keys(filterOptions.segments).map(key => ({
              value: filterOptions.segments[key],
              label: filterOptions.segments[key].toUpperCase(),
            }))
          "
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

    <!-- ============================================================= -->

    <div class="text-sm my-4">
      <div class="w-full">
        <div class="flex overflow-x-scroll">
          <table class="x-table w-full relative">
            <thead class="h-24 align-bottom bg-primary-700">
              <tr class="text-sm text-gray-600 border-b">
                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Batch
                </th>
                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left w-28"
                >
                  Week Ending
                </th>
                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Renewed
                </th>
                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Total Allocated
                </th>

                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Advisor retention
                </th>
                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Monthly Retention
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                :class="{ 'bg-[#ffff00]': item.highlight }"
                v-for="(item, index) in reportDataRef"
                :key="index"
                class="border-b border-gray-200 align-top"
              >
                <td class="x-table-cell px-3 py-4 align-middle">
                  {{ item.name }}
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  <!-- {{ moment(item.end_date).format('MMMM do') }} -->
                  {{ useDateFormat(item.end_date, 'MMM DD').value }}
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  {{ item.renewed?.toLocaleString() || 0 }}
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  {{ item.total_allocated_leads?.toLocaleString() || 0 }}
                </td>
                <!-- <td class="x-table-cell px-3 py-4 align-middle">
                        {{ item.early_renewal.toLocaleString() }} // tempory hidden don't remove
                    </td> -->
                <td
                  :class="item.advisorRetentionClass"
                  class="x-table-cell px-3 py-4 align-middle"
                >
                  {{ item.rawRetention }}%
                </td>
                <td
                  v-if="item.rowSpan > 0"
                  class="x-table-cell px-3 py-4 align-middle text-center"
                  :rowspan="item.rowSpan"
                >
                  <b> {{ item.monthlySum }}%</b>
                </td>
              </tr>
            </tbody>
          </table>
          <!-- =================== new table ===================== -->

          <table class="x-table w-full ms-3 relative">
            <thead class="h-24 align-bottom bg-primary-700">
              <tr class="text-sm text-gray-600 border-b">
                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Value Retention
                </th>

                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Volume Retention
                </th>

                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Relative Retention on Value
                </th>

                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Relative Retention on Volume
                </th>

                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  IM Retention
                </th>

                <th
                  class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                >
                  Relative Retention
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                :class="{ 'bg-[#ffff00]': item.highlight }"
                v-for="(item, index) in reportDataRef"
                :key="index"
                class="border-b border-gray-200 align-top"
              >
                <td class="x-table-cell px-3 py-4 align-middle">
                  {{
                    item.valueSegmentConversion == '0.00'
                      ? 'N/A'
                      : item.valueSegmentConversion + '%'
                  }}
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  {{
                    item.volumeSegmentConversion == '0.00'
                      ? 'N/A'
                      : item.volumeSegmentConversion + '%'
                  }}
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  <p>
                    {{
                      parseFloat(item.valueSegmentConversion) > 0
                        ? (
                            parseFloat(item.rawRetention) -
                            parseFloat(item.valueSegmentConversion)
                          ).toFixed(2) + '%'
                        : 'N/A'
                    }}
                  </p>
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  <p>
                    {{
                      parseFloat(item.volumeSegmentConversion) > 0
                        ? (
                            parseFloat(item.rawRetention) -
                            parseFloat(item.volumeSegmentConversion)
                          ).toFixed(2) + '%'
                        : 'N/A'
                    }}
                  </p>
                </td>
                <td class="x-table-cell px-3 py-4 align-middle text-center">
                  <p>{{ item.overallRawRetention }}%</p>
                </td>
                <td class="x-table-cell px-3 py-4 align-middle">
                  <p>
                    {{
                      (
                        parseFloat(item.rawRetention) -
                        parseFloat(item.overallRawRetention)
                      ).toFixed(2)
                    }}%
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ======================= new table ==================== -->
        <table
          class="x-table relative w-50 mt-10"
          v-if="
            hasAnyRole([
              rolesEnum.CarManager,
              rolesEnum.RenewalsManager,
              rolesEnum.SeniorManagement,
              rolesEnum.Accounts,
            ])
          "
        >
          <thead class="align-bottom bg-primary-700">
            <tr class="text-sm text-gray-600 border-b">
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Month
              </th>
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                AVERAGE IM RETENTION
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(value, index) in monthlyRawAverages"
              :key="index"
              class="border-b border-gray-200 align-top"
            >
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ index }}
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                {{
                  monthlyRawAverages[index]
                    ? monthlyRawAverages[index].toFixed(2)
                    : 0
                }}%
              </td>
            </tr>
          </tbody>
        </table>

        <!-- ======================= supper Retention table ==================== -->
        <table class="x-table relative w-50 mt-10">
          <thead class="align-bottom bg-primary-700">
            <tr class="text-sm text-gray-600 border-b">
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Batch No.
              </th>
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Week Ending
              </th>
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Converted (Health)
              </th>
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Monthly Super Retention
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in superRetentionDataRef"
              :key="index"
              class="border-b border-gray-200 align-top"
            >
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ item.name }}
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ useDateFormat(item.end_date, 'MMM DD').value }}
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ item.health_converted }}
              </td>
              <td
                v-if="item.rowSpan > 0"
                class="x-table-cell px-3 py-4 align-middle"
                :rowspan="item.rowSpan"
              >
                <p
                  v-if="
                    totalAllocationList[item.month] == 0 ||
                    totalAllocationList[item.month] == undefined
                  "
                >
                  0%
                </p>
                <p v-else>
                  {{
                    (
                      ((Number(item.monthlyHealthRenewed) +
                        Number(renewedCountsList[item.month])) /
                        Number(totalAllocationList[item.month])) *
                      100
                    ).toFixed(2)
                  }}%
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================= -->

    <div>
      <Pagination
        :links="{
          next: page.props.reportData.next_page_url,
          prev: page.props.reportData.prev_page_url,
          current: page.props.reportData.current_page,
          from: page.props.reportData.from,
          to: page.props.reportData.to,
          total: page.props.reportData.total,
          last: page.props.reportData.last_page,
        }"
        :loading="page.props.reportData.loader"
      />
    </div>
  </div>
</template>
<style scoped>
thead th {
  color: #e6e6e6;
}

thead,
th,
td {
  border: 1px solid #e6e6e6 !important;
}
</style>
