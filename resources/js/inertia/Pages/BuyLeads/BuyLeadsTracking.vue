<script setup>
const props = defineProps({
  lobs: Array,
  list: Object,
});
const tableHeader = reactive([
  { text: 'Ref-Id', value: 'uuid' },
  { text: 'Line Of Business', value: 'line_of_business' },
  { text: 'Department', value: 'department' },
  { text: 'Lead Costs', value: 'lead_cost' },
  { text: 'Lead Costs', value: 'lead_cost' },
  { text: 'Requested Date', value: 'requested_date' },
]);

const table = ref({
  data: props.list || [],
  loading: false,
});

const filters = reactive({
  quote_type: null,
  date: null,
});

const onSubmit = isValid => {
  if (isValid) {
    filters.page = 1;
    router.visit(route('buy-leads.request.tracking'), {
      method: 'get',
      data: { ...filters },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onFinish: () => (loader.table = false),
    });
  }
};

onMounted(() => {
  // setQueryStringFilters(filters);
});
</script>
<template>
  <Head title="My Leads Request" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">My Leads Requests</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Line Of Business" required>
        <x-select
          placeholder="Select Line Of Business"
          :options="props.lobs || []"
          filterable
          v-model="filters.quote_type"
        ></x-select>
      </x-field>
      <x-field label="Requested Date" required>
        <DatePicker
          v-model="filters.date"
          name="created_at_start"
          format="dd-MM-yyyy"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="orange" type="submit"> Search </x-button>
      <x-button size="md" color="primary" type="submit"> Submit </x-button>
      <x-button size="md" color="secondary" type="submit">
        Download PDF
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :loading="table.loader"
    :headers="tableHeader"
    :items="table.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  ></DataTable>
</template>
