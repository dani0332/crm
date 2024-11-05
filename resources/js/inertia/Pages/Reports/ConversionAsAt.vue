<script setup>
import { usePagination, useRowsPerPage } from 'use-vue3-easy-data-table';

const props = defineProps({
  reportData: Array,
  unassignedLeadsCount: Number,
  filterOptions: Object,
  quoteTypes: Object,
  displayByColumn: String,
  createdAtDate: String,
  includeUnassignedLeads: String,
  quoteTypeCodes: Object,
  quoteTypeIdEnum: Object,
});

const notification = useToast();

const loaders = reactive({
  table: false,
  advisorLeadTable: false,
  advisorOptions: false,
});

const page = usePage();
const dataTableRef = ref();
const isDirty = ref(false);
const { isRequired } = useRules();
const isLobEmpty = ref(false);
const canExportReport = ref(false);
const exportLoader = ref(false);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const sortBy = ref('net_conversion');
const sortType = ref('desc');
const showTable = ref(true);
let showUnassignedLeads = ref(false);
let unassignedDate = ref([]);
let initialAsAtDate = ref('');

const quoteTypeIdEnum = page.props.quoteTypeIdEnum;
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

const tableHeader = ref([
  {
    text: 'Start Date',
    value: 'start_date',
  },
  {
    text: 'End Date',
    value: 'end_date',
  },
  {
    text: 'As At Date',
    value: 'as_at_date',
  },
  {
    text: 'Total Leads',
    value: 'total_leads',
  },
  {
    text: 'Sale Leads',
    value: 'sale_leads',
  },
  {
    text: 'Gross Conversion',
    value: 'gross_conversion',
    sortable: true,
  },
  {
    text: 'Net Conversion',
    value: 'net_conversion',
    sortable: true,
  },
]);

const displayBy = ref([
  { label: 'Advisor Name', value: 'advisor_name' },
  { label: 'Lead Source', value: 'lead_source' },
  { label: 'External Lead Source (UTM)', value: 'external_lead_source' },
]);

const includeUnassignedLeads = ref([
  { label: 'Yes', value: 'yes' },
  { label: 'No', value: 'no' },
]);

const displayByActive = ref(false);

const updateTableHeaders = () => {
  const filterCondition = filters.displayBy ?? null;
  // check if at 0 index text is empty then remove it
  if (!tableHeader.value[0].text.length) {
    tableHeader.value.splice(0, 1);
  }
  if (filterCondition && filterCondition.length > 0) {
    let condition = displayByActive.value ? 1 : 0;
    tableHeader.value.splice(0, condition, {
      text: filterCondition
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' '),
      value: filterCondition,
      sortable: true,
    });
    displayByActive.value = true;
  } else if (displayByActive.value) {
    tableHeader.value.splice(0, 1);
    displayByActive.value = false;
  }
  checkAndAddExtraEmptyColumn();
};

const checkAndAddExtraEmptyColumn = () => {
  const filterCondition = filters.displayBy ?? null;

  if (
    (!filterCondition || !filterCondition.length) &&
    filters.includeUnassignedLeads == 'yes' &&
    tableHeader.value[0].text != ''
  ) {
    tableHeader.value.splice(0, 0, {
      text: '',
      value: '',
    });
  }
};

const formatDate = dateString => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();

  return `${day}-${month}-${year}`;
};

function calculateTotalNetConversion(data) {
  let totalLeads = 0;
  let saleLeads = 0;
  let badLeads = 0;
  data.forEach(row => {
    totalLeads += Number(row.total_leads);
    saleLeads += Number(row.sale_leads);
    badLeads += Number(row.bad_leads);
  });
  const numerator = saleLeads;
  const denominator =
    (showUnassignedLeads && props.unassignedLeadsCount
      ? +totalLeads + props.unassignedLeadsCount
      : totalLeads) - badLeads;
  return denominator > 0
    ? ((numerator / denominator) * 100).toFixed(2) + ' %'
    : 'NaN';
}

function calculateTotalGrossConversion(data) {
  let totalLeads = 0;
  let saleLeads = 0;
  data.forEach(row => {
    totalLeads += Number(row.total_leads);
    saleLeads += Number(row.sale_leads);
  });
  const numerator = saleLeads;
  const denominator =
    showUnassignedLeads && props.unassignedLeadsCount
      ? +totalLeads + props.unassignedLeadsCount
      : totalLeads;
  return denominator > 0
    ? ((numerator / denominator) * 100).toFixed(2) + ' %'
    : 'NaN';
}

function calculateTotalLeads(data) {
  let totalLeads = 0;
  data.forEach(row => {
    totalLeads += Number(row.total_leads);
  });
  return showUnassignedLeads && props.unassignedLeadsCount
    ? +totalLeads + props.unassignedLeadsCount
    : totalLeads;
}

function calculateTotalSaleLeads(data) {
  let saleLeads = 0;
  data.forEach(row => {
    saleLeads += Number(row.sale_leads);
  });
  return saleLeads;
}

const onIncludeUnassignedLeadsChange = () => {
  if (filters.includeUnassignedLeads == 'no') {
    filters.createdAtDate = '';
    return;
  }

  filters.createdAtDate = filters.startEndDate ? filters.startEndDate : '';
};

const filters = reactive({
  startEndDate: [],
  lob: '',
  asAtDate: '',
  tag: '',
  displayBy: props.displayByColumn || '',
  createdAtDate: props.createdAtDate || '',
  page: 1,
  includeUnassignedLeads: props.includeUnassignedLeads || 'no',
});

function onSubmit(isValid) {
  if (!filters.lob) {
    isLobEmpty.value = true;
    isValid = false;
  } else isLobEmpty.value = false;

  if (isValid) {
    isDirty.value = false;
    filters.page = 1;
    const payLoad = cleanFilters(filters);
    router.visit('/reports/conversion-as-at', {
      method: 'get',
      data: {
        ...payLoad,
      },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => {
        loaders.table = true;
      },
      onFinish: () => {
        unassignedDate = filters.createdAtDate ?? [];
        initialAsAtDate = filters.asAtDate ?? '';
        updateShowUnassignedLeads();
        loaders.table = false;
        showTable.value = false;
        updateTableHeaders();
        onLobChange(false);
        canExportReport.value = true;
        sortBy.value = filters.displayBy ? filters.displayBy : sortBy.value;
        sortType.value = filters.displayBy ? 'asc' : sortType.value;
        nextTick(() => {
          showTable.value = true;
        });
      },
    });
  }
}

const updateShowUnassignedLeads = () => {
  showUnassignedLeads.value = filters.includeUnassignedLeads == 'yes';
};

function onReset() {
  isDirty.value = false;
  router.visit('/reports/conversion-as-at', {
    method: 'get',
    data: {
      page: 1,
    },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const downloadPdf = isValid => {
  if (isValid && canExportReport.value === true) {
    exportLoader.value = true;
    loaders.table = true;
    const payLoad = cleanFilters(filters);

    axios
      .post(
        '/reports/conversion-as-at/pdf',
        {
          ...payLoad,
        },
        {
          responseType: 'json',
        },
      )
      .then(response => {
        const link = document.createElement('a');
        let fileName = response.data.name;
        link.href = response.data.data;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        notification.success({
          title: 'Pdf report generated',
          position: 'top',
        });
      })
      .catch(error => {})
      .finally(() => {
        exportLoader.value = false;
        loaders.table = false;
      });
  } else {
    notification.error({
      title: 'Please generate report first',
      position: 'top',
    });
  }
};

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

const quoteTypes = page.props.quoteTypes;

function onLobChange(updateDisplayFilter = true) {
  if (updateDisplayFilter) filters.displayBy = '';
  canExportReport.value = false;

  const quote = quoteTypes[filters.lob];
  const displayOptions = {
    [props.quoteTypeCodes.Car]: ['team', 'sub_team', 'tiers', 'nationality'],
    [props.quoteTypeCodes.Health]: ['team'],
    [props.quoteTypeCodes.CORPLINE.toLowerCase()]: ['sub_team'],
    [props.quoteTypeCodes.GroupMedical.replace(/ /g, '')]: ['sub_team'],
    [props.quoteTypeCodes.Bike]: ['tiers', 'nationality'],
    [props.quoteTypeCodes.Travel]: ['nationality'],
    [props.quoteTypeCodes.Life]: ['team', 'nationality'],
  };

  displayBy.value = displayBy.value.filter(
    item => !['sub_team', 'tiers', 'nationality', 'team'].includes(item.value),
  );

  const optionsToAdd = displayOptions[quote] || [];
  optionsToAdd.forEach(option => {
    displayBy.value.push({
      label: option.charAt(0).toUpperCase() + option.slice(1),
      value: option,
    });
  });
}

const minDate = computed(() => {
  if (filters.startEndDate && filters.startEndDate.length > 0) {
    return filters.startEndDate[0];
  }
  return null;
});

onMounted(() => {
  unassignedDate = props.createdAtDate ?? [];
  checkAndAddExtraEmptyColumn();
  updateShowUnassignedLeads();
  document.querySelectorAll('[title]').forEach(element => {
    element.removeAttribute('title');
  });
});
</script>

<template>
  <div>
    <Head title="Conversion As At Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Conversion As At Report
    </h1>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <ComboBox
          v-model="filters.lob"
          label="Line of Business*"
          placeholder="Select LOB"
          :options="
            Object.keys(filterOptions.lobs).map(key => ({
              value: key,
              label: filterOptions.lobs[key],
            }))
          "
          class="w-full"
          :single="true"
          :rules="[isRequired]"
          :hasError="isLobEmpty"
          @update:modelValue="onLobChange"
        />

        <DatePicker
          v-model="filters.startEndDate"
          label="Advisor Assigned Date*"
          placeholder="Specify Advisor Assigned Date*"
          range
          :max-range="30"
          :maxDate="new Date()"
          :rules="[isRequired]"
          size="sm"
          model-type="yyyy-MM-dd"
        />
        <DatePicker
          v-model="filters.asAtDate"
          :disabled="!filters.startEndDate || filters.startEndDate.length === 0"
          label="As At Date*"
          placeholder="Specify As At Date"
          tooltip="Specify Transaction Approved At Date"
          size="sm"
          :rules="[isRequired]"
          model-type="yyyy-MM-dd"
          :min-date="minDate"
          :max-date="new Date()"
        />

        <ComboBox
          v-model="filters.displayBy"
          placeholder="Search by Group"
          label="Display by"
          :options="displayBy"
          class="w-full"
          :single="true"
        />
        <ComboBox
          v-if="filters.lob == props.quoteTypeIdEnum.Car"
          v-model="filters.tag"
          placeholder="SIC/PUA"
          label="SIC/PUA"
          :options="[
            { value: '', label: 'All' },
            { value: 'sic', label: 'SIC' },
            { value: 'non-sic', label: 'PUA' },
          ]"
          class="w-full"
          :single="true"
        />
        <ComboBox
          v-model="filters.includeUnassignedLeads"
          placeholder="Select Option"
          label="Include Unassigned Leads?"
          :options="includeUnassignedLeads"
          class="w-full"
          :single="true"
          @update:modelValue="onIncludeUnassignedLeadsChange"
        />
        <DatePicker
          v-if="filters.includeUnassignedLeads == 'yes'"
          v-model="filters.createdAtDate"
          label="Lead Created Date*"
          placeholder="Specify Lead Created Date"
          range
          :max-range="30"
          :maxDate="new Date()"
          :rules="[isRequired]"
          size="sm"
          model-type="yyyy-MM-dd"
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
          <x-button
            v-if="can(permissionsEnum.EXTRACT_REPORT)"
            size="sm"
            color="gray"
            :loading="exportLoader"
            @click.prevent="downloadPdf"
          >
            Download PDF
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable
      v-if="showTable"
      ref="dataTableRef"
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData || []"
      border-cell
      :rows-per-page-message="'Records per page'"
      :rows-items="[10, 25, 50, 100]"
      :rows-per-page="50"
      :empty-message="'No Records Available'"
      hide-footer
    >
      <template #item-gross_conversion="item">
        <p v-if="item.gross_conversion == 0">NaN</p>
        <p v-else>{{ item.gross_conversion }} %</p>
      </template>
      <template #item-net_conversion="item">
        <p v-if="item.net_conversion == 0">NaN</p>
        <p v-else>{{ item.net_conversion }} %</p>
      </template>
      <template #body-append>
        <tr v-if="showUnassignedLeads">
          <td class="direction-left">Unassigned Leads</td>
          <td>{{ formatDate(unassignedDate[0]) }}</td>
          <td>{{ formatDate(unassignedDate[1]) }}</td>
          <td>{{ formatDate(initialAsAtDate) }}</td>
          <td class="direction-center">
            {{ props.unassignedLeadsCount }}
          </td>
          <td class="direction-center">0</td>
          <td class="direction-center">0</td>
          <td class="direction-center">0</td>
        </tr>
        <tr v-if="reportData && reportData?.length > 0" class="total-row">
          <td class="direction-left">Total</td>
          <td></td>
          <td></td>
          <td v-if="showUnassignedLeads || displayByActive"></td>
          <td class="direction-center">
            {{ calculateTotalLeads(reportData) }}
          </td>
          <td class="direction-center">
            {{ calculateTotalSaleLeads(reportData) }}
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
      <!-- <div>
                <select
                    class="form-select text-sm border shadow-sm rounded-md border-gray-300 hover:border-gray-400 disabled:opacity-30 disabled:cursor-not-allowed"
                    @change="updateRowsPerPageSelect">
                    <option v-for="item in rowsPerPageOptions" :key="item" :selected="item === rowsPerPageActiveOption"
                        :value="item">
                        {{ item }} rows per page
                    </option>
                </select>
            </div> -->

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

    <!-- <Pagination :links="{
            next: reportData.next_page_url,
            prev: reportData.prev_page_url,
            current: reportData.current_page,
            from: reportData.from,
            to: reportData.to,
            total: reportData.total,
            last: reportData.last_page,
        }" /> -->
  </div>
</template>
