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

const table = reactive({
  loading: false,
});

const filters = reactive({
  quote_type: null,
  date: null,
});

const getLink = (quote_uuid, quote_type_id) =>
  buildCdbidLink(quote_uuid, quote_type_id);

const onSubmit = isValid => {
  if (isValid) {
    table.loading = true;
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

const onExport = () => {
  if (filters.quote_type && filters.date) {
    window.location.href = route('buy-leads.request.tracking', {
      ...filters,
      export: true,
    });
  }
};

function onReset() {
  router.visit(route('buy-leads.request.tracking'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (table.loading = true),
    onSuccess: () => (table.loading = false),
  });
}

onMounted(() => {
  setQueryStringFilters(params, filters);
});
</script>
<template>
  <Head title="My Lead Request" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">My Lead Requests</h2>
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
          range
          :max-range="30"
          placeholder="Select Request Submitted Date"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="orange" type="submit" :loading="table.loading">
        Search
      </x-button>
      <x-button
        size="md"
        color="primary"
        type="submit"
        @click.prevent="onReset()"
      >
        Reset
      </x-button>
      <x-button
        size="md"
        color="secondary"
        @click.prevent="onExport()"
        :loading="table.loading"
        :disabled="!(filters.quote_type && filters.date)"
      >
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
  >
    <template #item-ref_id="item">
      <SanitizeHtml
        v-if="(item.ref_id, item.quote_type_id)"
        :html="getLink(item.ref_id, item.quote_type_id)"
        class="text-primary-500 hover:underline"
        :key="item.ref_id"
      />
    </template>
  </DataTable>
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
