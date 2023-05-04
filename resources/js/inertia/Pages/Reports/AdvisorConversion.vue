<script setup>
defineProps({
  reportData: Object,
  filterOptions: Object,
});

const loaders = reactive({
  table: false,
});

const totalLeads = reactive({
  modal: false,
  loader: false,
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

const page = usePage();
const params = useUrlSearchParams('history');

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
    value: 'manual_created_bad_leads',
  },
  {
    text: 'Gross Conversion',
    value: 'gross_conversion',
  },
  {
    text: 'Net Conversion',
    value: 'net_conversion',
  },
];

function calculateGrossConversion(item) {
  if (item) {
    const totalLeadsCount = item.total_leads;
    const manualCreated = item.manual_created;
    const saleLeads = item.sale_leads;
    const createdSaleLeads = item.created_sale_leads;
    const numerator = saleLeads - createdSaleLeads;
    const denominator = totalLeadsCount - manualCreated;
    if (denominator > 0) {
      return parseFloat((numerator / denominator) * 100).toFixed(2) + ' %';
    } else {
      return 'NaN';
    }
  } else {
    return 'undefined';
  }
}

function calculateNetConversion(row) {
  const totalLeads = row.total_leads;
  const manualCreated = row.manual_created;
  const badLeads = row.bad_leads;
  const manualCreatedBadLeads = row.manual_created_bad_leads;
  const saleLeads = row.sale_leads;
  const createdSaleLeads = row.created_sale_leads;
  const numerator = saleLeads - createdSaleLeads;
  const denominator =
    totalLeads - manualCreated - (badLeads - manualCreatedBadLeads);
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
  page: 1,
});

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit('/reports/advisor-conversion', {
      method: 'get',
      data: filters,
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
  router.visit('/reports/advisor-conversion', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const currentTypeTitle = computed(() => {
  if (totalLeads.current == 'new_leads') {
    return 'New Leads';
  } else if (totalLeads.current == 'not_interested') {
    return 'Not Interested';
  } else if (totalLeads.current == 'in_progress') {
    return 'In Progress';
  } else if (totalLeads.current == 'bad_leads') {
    return 'Bad Leads';
  } else if (totalLeads.current == 'sale_leads') {
    return 'Sale Leads';
  } else if (totalLeads.current == 'created_sale_leads') {
    return 'Created Sale Leads';
  } else if (totalLeads.current == 'afia_renewals_count') {
    return 'IM Renewals';
  } else if (totalLeads.current == 'manual_created') {
    return 'Manual Created';
  } else {
    return 'Total Leads';
  }
});

function onFetchAdvisorAssignedLeads(item, type) {
  totalLeads.current = type;
  totalLeads.modal = true;

  totalLeads.loader = true;
  totalLeads.data = {};

  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' || filters[key].length === 0) && delete filters[key],
  );

  axios
    .post(`/reports/fetch-advisor-assigned-leads-data`, {
      ...filters,
      page: 1,
      leadType: type,
      quote_batch_id: item.quote_batch_id,
      advisorId: item.advisorId,
    })
    .then(response => {
      totalLeads.data = response.data;
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      totalLeads.loader = false;
    });
}

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      if (key == 'advisorAssignedDates[]') {
        filters.advisorAssignedDates = params[key];
      } else {
        filters[key] = params[key];
      }
    } else {
      filters[key] = params[key];
    }
  }
}

// const can = permission => useCan(permission);
// const permissionsEnum = page.props.permissionsEnum;

onMounted(() => {
  setQueryStringFilters();
  filters.advisorAssignedDates = [new Date(), new Date()];
});
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
          :max-range="365"
          size="sm"
          model-type="yyyy-MM-dd"
        />

        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
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
        />

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
        />

        <ComboBox
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
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
      ref="dataTable"
      table-class-name="tablefixed"
      :loading="loaders.table"
      :headers="tableHeader"
      :items="reportData.data || []"
      :rows-per-page="15"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-gross_conversion="item">
        {{ calculateGrossConversion(item) }}
      </template>
      <template #item-net_conversion="item">
        {{ calculateNetConversion(item) }}
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
    </DataTable>

    <Pagination
      :links="{
        next: reportData.next_page_url,
        prev: reportData.prev_page_url,
        current: reportData.current_page,
        from: reportData.from,
        to: reportData.to,
      }"
    />

    <x-modal v-model="totalLeads.modal" size="xl" show-close backdrop>
      <template #header>
        {{ currentTypeTitle }}
        <span class="lining-nums">{{ totalLeads.data?.total }}</span>
      </template>
      <section>
        <DataTable
          table-class-name="tablefixed"
          :loading="totalLeads.loader"
          :headers="totalLeads.tableHeader"
          :items="totalLeads.data.data || []"
          border-cell
          :rows-per-page="15"
          hide-rows-per-page
          hide-footer
        ></DataTable>
        <!-- TODO: Add new pagination -->
        <!-- <Pagination
          v-show="totalLeads.data?.total > 10"
          :links="{
            next: totalLeads.data.next_page_url,
            prev: totalLeads.data.prev_page_url,
            current: totalLeads.data.current_page,
            from: totalLeads.data.from,
            to: totalLeads.data.to,
          }"
        /> -->
      </section>
    </x-modal>
  </div>
</template>
