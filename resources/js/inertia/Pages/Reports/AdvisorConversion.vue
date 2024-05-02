<script setup>
import { usePagination, useRowsPerPage } from 'use-vue3-easy-data-table';

defineProps({
  reportData: Array,
  filterOptions: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
  advisorLeadTable: false,
  advisorOptions: false,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteSegments = page.props.quoteSegments;
const params = useUrlSearchParams('history');
const dataTableRef = ref();
const advisorOptions = ref([]);
const isDirty = ref(false);
const isMounted = ref(false);

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
  const totalLeads = row.total_leads;
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

const filters = reactive({
  advisorAssignedDates: [],
  is_ecommerce: '',
  batches: [],
  tiers: [],
  leadSources: [],
  advisors: [],
  teams: [],
  segment_filter: 'all',
  isCommercial: 'All',
  page: 1,
});

function onSubmit(isValid) {
  if (isValid) {
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
    filters.advisorAssignedDates =
      page.props.defaultFilters.advisorAssignedDates;
  }

  isDirty.value = false;
  router.visit('/reports/advisor-conversion', {
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
    filters.advisorAssignedDates =
      page.props.defaultFilters.advisorAssignedDates;
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
      Advisor Conversion Report
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
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
          deselect-all
        />

        <ComboBox
          v-model="filters.tiers"
          label="Tiers"
          placeholder="Search by Tiers"
          :options="
            Object.keys(filterOptions.tiers).map(key => ({
              value: key,
              label: filterOptions.tiers[key],
            }))
          "
          select-all
          deselect-all
        />

        <template v-if="!hasRole(rolesEnum.CarAdvisor)">
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
            deselect-all
          />

          <ComboBox
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
            select-all
            deselect-all
          />

          <ComboBox
            v-model="filters.advisors"
            :label="
              !filters.teams || filters.teams.length == 0
                ? `Advisors (select teams first)`
                : `Advisors`
            "
            :options="advisorOptions"
            :select-all="filters.advisors?.length > 0"
            :deselect-all="filters.advisors?.length > 0"
            :loading="loaders.advisorOptions"
          />
        </template>
        <x-select
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
          v-if="can(permissionsEnum.SEGMENT_FILTER)"
          v-model="filters.segment_filter"
          label="Segment"
          placeholder="Select Segment"
          :options="quoteSegments"
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
