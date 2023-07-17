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
const dataTableRef = ref();
const isMounted = ref(false);
const isDirty = ref(false);

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
  {
    text: 'Uncontactable',
    value: 'uncontactable',
  },
  {
    text: 'Total Allocated (excluding cancelled and uncontactable)',
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
  batchNo: '',
//   is_ecommerce: '',
//   batches: [],
//   tiers: [],
  segment: '',
  advisors: [],
//   teams: [],
  page: 1,
});

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
      text: 'CDB Id',
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

function onSubmit(isValid) {
  if (isValid) {
    isDirty.value = false;
    filters.page = 1;
    const payLoad = cleanFilters(filters);
    router.visit('/reports/renewal-report', {
      method: 'get',
      data: {
        ...payLoad,
        // ...(payLoad.batches && {
        //   batches: Array.isArray(payLoad.batches)
        //     ? payLoad.batches
        //     : [payLoad.batches],
        // }),
        // ...(payLoad.tiers && {
        //   tiers: Array.isArray(payLoad.tiers) ? payLoad.tiers : [payLoad.tiers],
        // }),
        // ...(payLoad.leadSources && {
        //   leadSources: Array.isArray(payLoad.leadSources)
        //     ? payLoad.leadSources
        //     : [payLoad.leadSources],
        // }),
        ...(payLoad.advisors && {
          advisors: Array.isArray(payLoad.advisors)
            ? payLoad.advisors
            : [payLoad.advisors],
        })
        // ...(payLoad.teams && {
        //   teams: Array.isArray(payLoad.teams) ? payLoad.teams : [payLoad.teams],
        // }),
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
  if (page.props.defaultFilters) {
    filters.reportDate =
      page.props.defaultFilters.reportDate;
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

const setPageTable = page => {
  onFetchAdvisorAssignedLeads(null, totalLeads.current, page);
};

const calculateTotalSum = (data, key) => {
  return data.reduce((sum, item) => Number(sum) + Number(item[key]), 0);
};

const onTeamChange = e => {
  if (e.length == 0) {
    filters.teams = [];
    filters.advisors = [];
    advisorOptions.value = [];

    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisor-by-team`, {
      teamIds: Array.isArray(e) ? e : [e],
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id,
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

onMounted(() => {
  if (page.props.defaultFilters && !params['page']) {
    filters.reportDate =
      page.props.defaultFilters.reportDate;
  }

  setQueryStringFilters();

  if (params['teams[]'] && params['teams[]'].length > 0) {
    onTeamChange(params['teams[]']);
  }
  isMounted.value = true;
});

watch(
  () => totalLeads.modal,
  val => {
    if (!val) {
      totalLeads.data = {};
    }
  },
);
</script>

<template>
  <div>
    <Head title="Advisor Conversion Report" />
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

        <x-input
          v-model="filters.batchNo"
          type="search"
          name="batch_no"
          label="Batch No."
          class="w-full"
          placeholder="Search by batch no."
        />

        <ComboBox
          v-model="filters.advisors"
          label="Advisors"
          placeholder="Search by Advisors"
          :options="
            Object.keys(filterOptions.advisors).map(key => ({
              value: key,
              label: filterOptions.advisors[key],
            }))
          "
          :select-all="filters.advisors?.length > 0"
            :deselect-all="filters.advisors?.length > 0"
        />

        <x-select
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

    <DataTable
      ref="dataTableRef"
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData.data || []"
      border-cell
      :rows-per-page-message="'Records per page'"
      :rows-items="[10, 25, 50, 100]"
      :rows-per-page="10"
      :empty-message="'No Records Available'"
      hide-footer
    >
      <template #item-total_allocate_minus_cancelled_uncontactable="item">
        <!-- Sum of allocations per batch  - (Approved Car Sold + Approved Uncontactable) -->
        <p v-if="item.total_allocated_leads == 0"> NaN </p>
        <p v-else>{{ parseInt(item.total_allocated_leads) - ( parseInt(item.car_sold) + parseInt(item.uncontactable) ) }} </p>
      </template>
      <template #item-advisor_retention="item">
        <!-- (Sum of policies issued (Payment status: Captured) per batch / Sum of allocations per batch - Approved Car Sold - Approved Uncontactable) *100% -->
        <p v-if="item.renewed == 0"> NaN </p>
        <p v-else>{{ advisorRetention = Math.round(( parseInt(item.renewed) / (( parseInt(item.total_allocated_leads) - parseInt(item.car_sold) - parseInt(item.uncontactable) ) ) ) * 100)}} %</p>
      </template>
      <template #item-value_segment_retention="item">
        <!-- (Sum of all policies issued (Payment status: Captured) by Value Segment Advisors per batch / Sum of allocations of Value Segment Advisors per batch - Approved Car Sold - Approved Uncontactable) *100% -->
        <p v-if="item.renewed_by_value_segment_advisors == 0"> NaN </p>
        <p v-else>{{ valueSegmentConversion =  Math.round( (parseInt(item.renewed_by_value_segment_advisors) / ( parseInt(item.total_by_value_segment_advisors) - parseInt(item.car_sold) - parseInt(item.uncontactable) ) ) * 100)}} %</p>
      </template>
      <template #item-volume_segment_retention="item">
        <!-- (Sum of all policies issued (Payment status: Captured) by Volume Segment Advisors per batch / Sum of allocations of Volume Segment Advisors per batch - Approved Car Sold - Approved Uncontactable) *100% -->
        <p v-if="item.renewed_by_volume_segment_advisors == 0"> NaN </p>
        <p v-else>{{ volumeSegmentConversion = Math.round((parseInt(item.renewed_by_volume_segment_advisors) / ( parseInt(item.total_by_volume_segment_advisors) - parseInt(item.car_sold) - parseInt(item.uncontactable) ) ) * 100)}} %</p>
      </template>
      <template #item-relative_retention_value_segment="item">
        <!-- Advisor Retention - Value Segment Conversion -->
        <p>{{ parseInt(advisorRetention) - parseInt(valueSegmentConversion)}} %</p>
      </template>
      <template #item-relative_retention_volume_segment="item">
        <!-- Advisor Retention - Volume Segment Conversion -->
        <p >{{ parseInt(advisorRetention) - parseInt(volumeSegmentConversion)}} %</p>
      </template>
      <template #item-im_retention="item">
      <!-- (Sum of all policies issued (Payment status: Captured) by all Advisors per batch / Sum of allocations of all Advisors per batch - Approved Car Sold - Approved Uncontactable) *100% -->
        <p v-if="item.renewed_by_volume_segment_advisors == 0"> NaN </p>
        <p v-else>{{ imRetention = Math.round(( parseInt(item.renewed) / ( parseInt(item.total_allocated_leads) - parseInt(item.car_sold) - parseInt(item.uncontactable) ) ) * 100)}} %</p>
      </template>
      <template #item-relative_retention="item">
        <!-- Advisor Retention - IM Retention -->
        <p >{{ parseInt(advisorRetention) - parseInt(imRetention)}} %</p>
      </template>
      <template #item-monthly_retention="item">
        <tr>
            <td :rowspan="2">
                testing
            </td>
        </tr>
    </template>

        <!-- (Sum of policies issued (Payment status: Captured) per month / Sum of allocations per month - Approved Car Sold - Approved Uncontactable) *100% -->
        <!-- <p v-if="item.renewed_by_volume_segment_advisors == 0"> NaN </p>
        <p v-else>{{ imRetention = (item.renewed / (item.total_allocated_leads - item.car_sold - item.uncontactable) ) * 100}} %</p> -->
            <!-- <p>0</p> -->
      <template #body-append>
        <tr v-if="reportData.length > 0" class="total-row">
          <td class="direction-left">Total</td>
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

    <x-modal v-model="totalLeads.modal" size="xl" show-close backdrop>
      <template #header>
        <div class="text-center">{{ currentTypeTitle }}</div>
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
