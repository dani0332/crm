<script setup>
const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

const advisorOptions = ref([]);

const filters = reactive({
  date: null,
  line_of_bussiness: 'health',
  teams: null,
  advisors: null,
  filter_by: null,
  page: 1,
});

const corplineHeaders = reactive([
  {
    text: 'RENEWAL TERMS RECEIVED',
    value: 'renewal_terms_recevied',
    is_active: true,
    sortable: true,
  },
  {
    text: 'APPLICATION PENDING',
    value: 'application_pending',
    is_active: true,
    sortable: true,
  },
  {
    text: 'MISSING DOCUMENTS',
    value: 'missing_documents',
    is_active: true,
    sortable: true,
  },
  {
    text: 'APPLICATION SUBMITTED',
    value: 'application_submitted',
    is_active: true,
    sortable: true,
  },
]);

const healthHeaders = reactive([
  {
    text: 'RENEWAL TERMS RECEIVED',
    value: 'renewal_terms_recevied',
    is_active: true,
    sortable: true,
  },
  {
    text: 'APPLICATION PENDING',
    value: 'application_pending',
    is_active: true,
    sortable: true,
  },
  {
    text: 'MISSING DOCUMENTS',
    value: 'missing_documents',
    is_active: true,
    sortable: true,
  },
  {
    text: 'APPLICATION SUBMITTED',
    value: 'application_submitted',
    is_active: true,
    sortable: true,
  },
]);

const commonHeaders = reactive([
  {
    text: 'TEAM',
    value: 'team',
    is_active: true,
    sortable: true,
  },
  {
    text: 'NEW LEAD',
    value: 'new_lead',
    is_active: true,
    sortable: true,
  },
  {
    text: 'ALLOCATED',
    value: 'allocated',
    is_active: true,
    sortable: true,
  },
  {
    text: 'QUOTED',
    value: 'quoted',
    is_active: true,
    sortable: true,
  },
  {
    text: 'FOLLOWED UP',
    value: 'follow_up',
    is_active: true,
    sortable: true,
  },
  {
    text: 'IN NEGOTIATION',
    value: 'in_negotiation',
    is_active: true,
    sortable: true,
  },
  {
    text: 'PAYMENT PENDING',
    value: 'payment_pending',
    is_active: true,
    sortable: true,
  },
  {
    text: 'TOTAL',
    value: 'total',
    is_active: true,
    sortable: true,
  },
]);

let computedHeaders = ref([...healthHeaders, ...commonHeaders]);

watch(
  () => filters.line_of_bussiness,
  () => {
    let specificHeaders =
      filters.line_of_bussiness === 'health'
        ? healthHeaders
        : filters.line_of_bussiness === 'corpline'
        ? corplineHeaders
        : [];
    computedHeaders = [...specificHeaders, ...commonHeaders];
  },
);

const teams = ref([
  { value: 'renewal', label: 'Renewal' },
  { value: 'new_bussiness', label: 'New Bussiness' },
  { value: 'ebp', label: 'EBP' },
  { value: 'speed', label: 'Speed' },
]);

const filteredTeams = computed(() => {
  if (filters.line_of_bussiness != 'health') return teams.value.slice(0, 2);
  else return teams.value.filter(team => team.value !== 'All');
});

const onSubmit = isValid => {
  if (!isValid) return;
  filters.page = 1;
  router.visit(route('stale-leads-report'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

function onReset() {
  router.visit(route('stale-leads-report'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}
</script>
<template>
  <Head title="Pipeline Report" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Pipeline Report
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Date Range" required>
        <DatePicker
          v-model="filters.date"
          range
          :max-range="92"
          size="sm"
          placeholder="Select Date"
          model-type="yyyy-MM-dd"
        />
      </x-field>
      <x-field label="Line Of Bussiness">
        <x-select
          v-model="filters.line_of_bussiness"
          placeholder="Search by Bussiness"
          :options="[
            { value: 'health', label: 'Health' },
            { value: 'pet', label: 'Pet' },
            { value: 'cycle', label: 'Cycle' },
            { value: 'home', label: 'Home' },
            { value: 'corpline', label: 'Corpline' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field label="Teams">
        <x-select
          :modelValue="filters.teams"
          placeholder="Search by Ecommerce"
          :options="filteredTeams"
          class="w-full"
          @update:modelValue="onTeamChange($event)"
        />
      </x-field>
      <x-field
        :label="
          !filters.teams || filters.teams.length == 0
            ? `Advisors (select teams first)`
            : `Advisors`
        "
      >
        <ComboBox
          placeholder="Search by Advisor Name"
          v-model="filters.advisors"
          :options="advisorOptions"
          :select-all="filters.advisors?.length > 0"
          :deselect-all="filters.advisors?.length > 0"
          class="w-full"
        />
      </x-field>
      <x-field label="Filter By">
        <x-select
          v-model="filters.filter_by"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'total_leads', label: 'Total Leads' },
            { value: 'total_oppertunity', label: 'Total Oppertunity' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field
        label="Bussiness Insurance Type"
        v-if="filters.line_of_bussiness == 'corpline'"
      >
        <ComboBox
          placeholder="Search by Bussiness Insurance Type"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="flex gap-3 justify-end items-center">
      <column-selection v-model:columns="computedHeaders"></column-selection>
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loaders.table"
    :headers="computedHeaders"
    :items="[]"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
  </DataTable>
  <!-- <Pagination
    :links="{
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  /> -->
</template>