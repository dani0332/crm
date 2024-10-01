<script setup>
defineProps({
  leadStatuses: [Object, Array],
  renewalBatches: [Array, Object],
});

const page = usePage();

const filters = reactive({
  name: '',
  quote_status_id: '',
  page: 1,
});

const loader = reactive({
  table: false,
  export: false,
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'BATCH', value: 'name' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'DEADLINE DATE', value: 'deadline_date' },
];

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/renewal-batches', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
}

function onReset() {
  router.visit('/renewal-batches', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
</script>

<template>
  <div>
    <Head title="Renewal Batches" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Renewal Batches</h2>
      <div class="space-x-3">
        <Link href="renewal-batches/create">
          <x-button size="sm" color="#ff5e00" tag="div">
            Create Renewal Batch</x-button
          >
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input v-model="filters.name" name="name" label="Batch Name" />
        <x-select
          v-model="filters.quote_status_id"
          label="Quote Status"
          :options="leadStatusOptions"
          class="w-full"
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
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="renewalBatches.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-id="{ id }">
        <Link
          :href="`/renewal-batches/${id}/edit`"
          class="text-primary-500 hover:underline"
        >
          {{ id }}
        </Link>
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.code }}
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: renewalBatches.next_page_url,
        prev: renewalBatches.prev_page_url,
        current: renewalBatches.current_page,
        from: renewalBatches.from,
        to: renewalBatches.to,
      }"
    />
  </div>
</template>
