<script setup>
const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

const filters = reactive({
  date: null,
  line_of_bussiness: null,
  teams: null,
  advisor: null,
  filter_by: null,
  page: 1,
});

const tableHeaders = ref([
  {
    text: 'LEAD CODE',
    value: 'uuid',
    is_active: true,
  },
  {
    text: 'Name',
    value: 'first_name',
    is_active: true,
  },
  {
    text: 'Lead Source',
    value: 'source',
    is_active: true,
  },
  {
    text: 'Lead Status',
    value: 'quoteStatus',
    is_active: true,
  },
  {
    text: 'Payment Status',
    value: 'payment_status_id',
    is_active: true,
  },
  {
    text: 'IS ECOMMERCE',
    value: 'is_ecommerce',
    is_active: true,
  },
  {
    text: 'ASSIGNED TO',
    value: 'advisor',
    is_active: true,
  },
  {
    text: 'CREATED AT',
    value: 'created_at',
    is_active: true,
  },
  {
    text: 'LAST MODIFIED',
    value: 'updated_at',
    is_active: true,
  },
  {
    text: 'TIER',
    value: 'tier',
    is_active: true,
  },
  {
    text: 'RECEIVED FROM DEVICE',
    value: 'device',
    is_active: true,
  },
]);

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
  <Head title="Stale Leads Report" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Stale Leads Report
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Date Range" required>
        <DatePicker
          v-model="filters.date"
          :max="90"
          placeholder="Select Date"
          size="sm"
          model-type="yyyy-MM-dd"
          :range="true"
        />
      </x-field>
      <x-field label="Line Of Bussiness">
        <x-select
          v-model="filters.line_of_bussiness"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'All', label: 'Renewal' },
            { value: 'Yes', label: 'New Bussiness' },
            { value: 'No', label: 'EBP' },
            { value: 'No', label: 'Speed' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field label="Teams">
        <x-select
          v-model="filters.teams"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'All', label: 'Renewal' },
            { value: 'Yes', label: 'New Bussiness' },
            { value: 'No', label: 'EBP' },
            { value: 'No', label: 'Speed' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field label="Advisor">
        <x-select
          v-model="filters.advisor"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'All', label: 'Renewal' },
            { value: 'Yes', label: 'New Bussiness' },
            { value: 'No', label: 'EBP' },
            { value: 'No', label: 'Speed' },
          ]"
          class="w-full"
        />
      </x-field>
      <x-field label="Filter By">
        <x-select
          v-model="filters.filter_by"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'All', label: 'Renewal' },
            { value: 'Yes', label: 'New Bussiness' },
            { value: 'No', label: 'EBP' },
            { value: 'No', label: 'Speed' },
          ]"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="flex gap-3 justify-end items-center">
      <column-selection v-model:columns="tableHeaders"></column-selection>
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
    :headers="tableHeaders"
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