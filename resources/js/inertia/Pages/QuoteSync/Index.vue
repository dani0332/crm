<script setup>
defineProps({
  logs: Object,
  count: Object,
  quote_types: Object,
  quote_sync_status: Object,
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
const params = useUrlSearchParams('history');
const serverOptions = ref({
  page: 1,
  sortBy: 'id',
  sortType: 'desc',
});

const loader = reactive({
  table: false,
  export: false,
});

const filters = reactive({
  distinct: '0',
  quote_type: '',
  uuid: '',
  is_synced: '0',
  synced_at: '',
  created_at: '',
});

const page = usePage();

const tableHeader = [
  { text: 'ID', value: 'id', sortable: true },
  { text: 'Quote Type', value: 'quote_type_id', sortable: true },
  { text: 'UUID', value: 'quote_uuid' },
  { text: 'Is Synced', value: 'is_synced', sortable: true },
  { text: 'Status', value: 'status' },
  { text: 'Synced At', value: 'synced_at', sortable: true },
  { text: 'Created At', value: 'created_at', sortable: true },
  { text: 'Fields', value: 'updated_fields' },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  router.visit(route('admin.quotesync'), {
    method: 'get',
    data: { page: 1 },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      loader.table = true;
    },
  });
}

function filterLogs(isValid) {
  if (!isValid) {
    return;
  }

  serverOptions.value.page = 1;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit(route('admin.quotesync'), {
    method: 'get',
    data: {
      ...filters,
      ...serverOptions.value,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      loader.table = true;
    },
  });
}

function syncStuckEntries() {
  if (!confirm('Are you sure you want to sync stuck entries?')) {
    return;
  }

  router.visit(route('admin.quotesync.sync-stuck-entries'), {
    method: 'post',
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      filterLogs(true);
    },
    onBefore: () => {
      loader.table = true;
    },
  });
}

function syncFailedEntries() {
  if (!confirm('Are you sure you want to sync failed entries?')) {
    return;
  }

  router.visit(route('admin.quotesync.sync-failed-entries'), {
    method: 'post',
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      filterLogs(true);
    },
    onBefore: () => {
      loader.table = true;
    },
  });
}

function setQueryFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryFilters();
});

watch(
  serverOptions,
  value => {
    filterLogs(true);
  },
  { deep: true },
);

const quoteTypesOptions = computed(() => {
  return [
    { value: '', label: 'Select Quote Type' },
    ...Object.keys(page.props.quote_types).map(id => ({
      label: page.props.quote_types[id],
      value: id,
    })),
  ];
});

const quoteSyncStatusOptions = computed(() => {
  return [
    { value: '', label: 'Select Status' },
    ...Object.keys(page.props.quote_sync_status).map(id => ({
      label: page.props.quote_sync_status[id],
      value: id,
    })),
  ];
});

const isSyncedOptions = computed(() => {
  return [
    { value: '', label: 'Select All' },
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
  ];
});

const distinctOptions = computed(() => {
  return [
    { value: '', label: 'Select All' },
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
  ];
});
</script>

<template>
  <div>
    <Head title="Quote Sync" />

    <x-divider class="my-4" />
    <x-form @submit="filterLogs" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-field label="Quote type">
          <x-select
            v-model="filters.quote_type"
            placeholder="Select Quote Type"
            :options="quoteTypesOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="UUID">
          <x-input
            v-model="filters.uuid"
            type="search"
            name="first_name"
            class="w-full"
            placeholder="Type here"
          />
        </x-field>
        <x-field label="Is Synced?">
          <x-select
            v-model="filters.is_synced"
            placeholder="Select Is Synced?"
            :options="isSyncedOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="Status">
          <x-select
            v-model="filters.status"
            placeholder="Select Status"
            :options="quoteSyncStatusOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="Synced At">
          <DatePicker
            v-model="filters.synced_at"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
            range
            max-range="7"
          />
        </x-field>
        <x-field label="Created At">
          <DatePicker
            v-model="filters.created_at"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
            range
            max-range="7"
          />
        </x-field>
        <x-field label="Distinct">
          <x-select
            v-model="filters.distinct"
            placeholder="Select Distinct"
            :options="distinctOptions"
            class="w-full"
          />
        </x-field>
      </div>
      <div class="flex justify-between">
        <div class="font-bold pt-4">Total: {{ count }}</div>
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters"
            >Reset</x-button
          >
          <x-button size="sm" color="error" @click.prevent="syncStuckEntries"
            >Sync Stuck Entries</x-button
          >
          <x-button size="sm" color="error" @click.prevent="syncFailedEntries"
            >Sync Failed Entries</x-button
          >
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" />

    <DataTable
      v-model:server-options="serverOptions"
      table-class-name="compact text-wrap"
      :headers="tableHeader"
      :loading="loader.table"
      :items="logs.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-id="{ id }">
        <Link
          :href="route('admin.quotesync.show', id)"
          class="text-primary-500 hover:underline"
        >
          {{ id }}
        </Link>
      </template>

      <template #item-is_synced="{ is_synced }">
        <div class="text-center">
          <x-icon
            :icon="is_synced ? 'roundchecked' : 'roundcross'"
            :color="is_synced ? 'green' : 'red'"
            size="lg"
          />
        </div>
      </template>

      <template #item-status="{ status_name }">
        <div class="text-center">
          <div class="text-sm text-center">{{ status_name }}</div>
        </div>
      </template>

      <template #item-quote_type_id="{ quote_type }">
        <div class="text-sm text-center">{{ quote_type }}</div>
      </template>

      <!-- <template #item-actions="{ id }">
        <div class="flex gap-1.5 justify-end">
          <Link :href="route('admin.quotesync.edit', id)">
          <x-button color="primary" size="xs" outlined> View </x-button>
          </Link>
        </div>
      </template> -->
    </DataTable>

    <Pagination
      :links="{
        next: logs.next_page_url,
        prev: logs.prev_page_url,
        current: logs.current_page,
        from: logs.from,
        to: logs.to,
      }"
    />
  </div>
</template>
