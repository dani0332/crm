<script setup>
defineProps({
  data: Object,
});

const params = useUrlSearchParams('history');

const filters = reactive({
  name: '',
  page: 1,
});

const loader = reactive({
  table: false,
});

const tableHeader = reactive([
  { text: 'ID', value: 'id' },
  { text: 'Key', value: 'key' },
  { text: 'Name', value: 'name' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
]);

const onReset = () => {
  router.visit(route('admin.commercial.keywords'), {
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

    router.visit(route('admin.commercial.keywords'), {
      method: 'get',
      data: useGenerateQueryString(filters),
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});
</script>
<template>
  <Head title="Commercial Keywords" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Commercial Keywords</h2>
    <div class="space-x-3">
      <Link :href="route('admin.commercial.keywords.create')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Add new Keyword
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="flex flex-wrap gap-6">
      <x-field class="flex-1" label="Keyword Name" required>
        <x-input class="w-full" v-model="filters.name" />
      </x-field>
      <div class="self-center mt-2 flex justify-end gap-3">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :loading="loader.table"
    :headers="tableHeader"
    :items="data.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('admin.commercial.keywords.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: data.next_page_url,
      prev: data.prev_page_url,
      current: data.current_page,
      from: data.from,
      to: data.to,
    }"
  />
</template>
