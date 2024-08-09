<script setup>
import { usePagination, useRowsPerPage } from 'use-vue3-easy-data-table';

const props = defineProps({
  reportData: Array,
  filterOptions: Object,
  quoteTypes: Object,
  displayByColumn: String,
  quoteTypeCodes: Object,
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

const displayByActive = ref(false);

const updateTableHeaders = () => {
  const filterCondition = filters.displayBy ?? null;
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
  const denominator = totalLeads - badLeads;
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
  const denominator = totalLeads;
  return denominator > 0
    ? ((numerator / denominator) * 100).toFixed(2) + ' %'
    : 'NaN';
}

const filters = reactive({
  startEndDate: [],
  lob: '',
  asAtDate: '',
  tag: '',
  displayBy: props.displayByColumn || '',
  page: 1,
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
  if (updateDisplayFilter) {
    filters.displayBy = '';
  }
  canExportReport.value = false;
  let quote = quoteTypes[filters.lob];

  displayBy.value = displayBy.value.filter(
    item =>
      item.value !== 'sub_team' &&
      item.value !== 'tiers' &&
      item.value !== 'nationality' &&
      item.value !== 'team',
  );

  if (quote == props.quoteTypeCodes.Car) {
    displayBy.value.push({ label: 'Team', value: 'team' });
    displayBy.value.push({ label: 'Sub Team', value: 'sub_team' });
    displayBy.value.push({ label: 'Tiers', value: 'tiers' });
    displayBy.value.push({ label: 'Nationality', value: 'nationality' });
  }

  if (quote == props.quoteTypeCodes.Health) {
    displayBy.value.push({ label: 'Team', value: 'team' });
  }

  if (
    (quote &&
      quote.toLowerCase() == props.quoteTypeCodes.CORPLINE.toLowerCase()) ||
    quote == props.quoteTypeCodes.GroupMedical.replace(/ /g, '')
  ) {
    displayBy.value.push({ label: 'Sub Team', value: 'sub_team' });
  }

  if (quote == props.quoteTypeCodes.Bike) {
    displayBy.value.push({ label: 'Tiers', value: 'tiers' });
    displayBy.value.push({ label: 'Nationality', value: 'nationality' });
  }

  if (
    quote == props.quoteTypeCodes.Travel ||
    quote == props.quoteTypeCodes.Health ||
    quote == props.quoteTypeCodes.Life
  ) {
    displayBy.value.push({ label: 'Nationality', value: 'nationality' });
  }
}

const minDate = computed(() => {
  if (filters.startEndDate && filters.startEndDate.length > 0) {
    return filters.startEndDate[0];
  }
  return null;
});
</script>

<template>
  <div>
    <Head title="Advisor Conversion Report" />
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
          label="Date Range Selection*"
          placeholder="Specify Start & End Date"
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
          placeholder="Select 'As At' Date"
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
          v-if="filters.lob == quoteTypeIdEnum.Car"
          v-model="filters.tag"
          placeholder="SIC/PUA Filter"
          label="SIC/PUA Filter"
          :options="[
            { value: '', label: 'Select' },
            { value: 'sic', label: 'Filter by SIC Leads' },
            { value: 'non-sic', label: 'Filter by PUA Leads' },
          ]"
          class="w-full"
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
      :sort-by="sortBy"
      :sort-type="sortType"
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
      </template>
      <template #body-append>
        <tr v-if="reportData && reportData?.length > 0" class="total-row">
          <td class="direction-left">Total</td>
          <td></td>
          <td></td>
          <td v-if="displayByActive"></td>
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
