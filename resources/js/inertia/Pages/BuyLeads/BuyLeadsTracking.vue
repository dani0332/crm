<script setup>
const props = defineProps({
  lobs: Array,
  list: Object,
});

const { isRequired } = useRules();
const params = useUrlSearchParams('history');

const tableHeader = reactive([
  { text: 'Ref-Id', value: 'ref_id' },
  { text: 'Line Of Business', value: 'quote_type.code' },
  { text: 'Department', value: 'department' },
  { text: 'Requested Date', value: 'requested_date' },
  { text: 'Lead Cost', value: 'cost' },
]);

const table = ref({
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
      onBefore: () => (table.loading = true),
      onFinish: () => (table.loading = false),
    });
  }
};

onMounted(() => {
  setQueryStringFilters(params, filters);
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
          :rules="[isRequired]"
        ></x-select>
      </x-field>
      <x-field label="Requested Date" required>
        <DatePicker
          v-model="filters.date"
          name="created_at_start"
          format="dd-MM-yyyy"
          :rules="[isRequired]"
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
    :loading="table.loading"
    :headers="tableHeader"
    :items="list.data != null ? list.data : []"
    border-cell
    hide-rows-per-page
    hide-footer
  ></DataTable>
  <Pagination
    :links="{
      next: list?.next_page_url,
      prev: list?.prev_page_url,
      current: list?.current_page,
      from: list?.from,
      to: list?.to,
    }"
  />
</template>
