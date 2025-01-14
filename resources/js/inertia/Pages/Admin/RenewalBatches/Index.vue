<script setup>
defineProps({
  batches: Object,
});

const formatted = date => useDateFormat(date, 'DD-MM-YYYY').value;

const loader = reactive({
  table: false,
  filters: {
    name: false,
  },
});

const tableHeader = [
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'START DATE', value: 'start_date' },
  { text: 'END DATE', value: 'end_date' },
  { text: 'Batch Type', value: 'quote_type_id' },
];

const filters = reactive({
  name: '',
  quote_type_id: '',
  page: 1,
});

const onReset = () => {
  filters.name = '';
  filters.quote_type_id = '';
  router.visit(route('renewal-batches-list'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const onSubmit = isValid => {
  if (isValid) {
    filters.page = 1;

    router.visit(route('renewal-batches-list'), {
      method: 'get',
      data: useGenerateQueryString(filters),
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

const getRenewalBatchesName = quoteTypeId => {
  let endpoint = '/getBatchNamesByQuoteTypeId?';
  if (filters.quote_type_id) {
    endpoint = endpoint + `quote_type_id=${filters.quote_type_id}`;
    loader.filters.name = true;
  }

  // Reset filter value
  filters.name = '';

  // Clear the array
  renewalBatchNames.splice(0, renewalBatchNames.length);

  axios.get(endpoint).then(({ data }) => {
    const item = data.length > 0 ? data[0] : null;

    data.forEach(item => {
      renewalBatchNames.push({
        value: item.text,
        label: item.text,
      });
    });
    loader.filters.name = false;
  });
};

watch(() => filters.quote_type_id, getRenewalBatchesName);

const renewalBatchNames = reactive([]);

onMounted(() => {
  getRenewalBatchesName();
});
</script>
<template>
  <div>
    <Head title="Renewal Batches" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Renewal Batches</h2>
      <x-button
        size="sm"
        color="#ff5e00"
        :href="route('renewal-batches-create')"
      >
        Create Renewal Batch
      </x-button>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="flex flex-wrap grid sm:grid-cols-2 md:grid-cols-2 gap-6">
        <x-field label="Batch Type">
          <x-select
            v-model="filters.quote_type_id"
            placeholder="Search by Batch Type"
            :options="[
              { value: 1, label: 'Motor' },
              { value: -1, label: 'Non-motor' },
            ]"
            class="w-full"
          />
        </x-field>
        <ComboBox
          v-model="filters.name"
          label="Renewal Batch"
          name="name"
          placeholder="Please select Renewal Batch"
          :options="renewalBatchNames"
          :loading="loader.filters.name"
        />
      </div>
      <div class="flex justify-self-end gap-3 mb-4 mt-1">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>
    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="batches.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-id="item">
        <a
          v-if="item.quote_type_id == 1"
          :href="route('renewal-batches-edit', item)"
          class="text-primary-500 hover:underline"
        >
          {{ item.id }}
        </a>
        <span v-else>{{ item.id }}</span>
      </template>
      <template #item-start_date="{ start_date }">
        {{ start_date ? formatted(start_date) : 'N/A' }}
      </template>
      <template #item-end_date="{ end_date }">
        {{ end_date ? formatted(end_date) : 'N/A' }}
      </template>
      <template #item-quote_type_id="{ quote_type_id }">
        {{ quote_type_id == 1 ? 'Motor' : 'Non-motor' }}
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: batches.next_page_url,
        prev: batches.prev_page_url,
        current: batches.current_page,
        from: batches.from,
        to: batches.to,
        total: batches.total,
      }"
    />
  </div>
</template>
