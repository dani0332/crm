<script setup>
const props = defineProps({
  quadrants: Object,
});

const params = useUrlSearchParams('history');

const loader = ref({
  table: false,
});

const filters = reactive({
  name: '',
  page: 1,
});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Name', value: 'name' },
  { text: 'Updated At', value: 'updated_at' },
  { text: 'Tire Name', value: 'quad_tiers' },
  { text: 'Quad Users', value: 'quad_users' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Active', value: 'is_active' },
];

function onSubmit(isValid) {
  filters.page = 1;
  router.visit(route('quadrant-innertia.index'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('quadrant-innertia.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

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
  <Head title="Quadrants List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Quadrants List</h2>
    <div class="space-x-3">
      <Link :href="route('quadrant-innertia.create')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Create Quadrants
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-1 gap-4">
      <x-field label="Quadrant Name" class="w-full">
        <x-input v-model="filters.name" type="text" class="w-full" />
      </x-field>
    </div>
    <div class="flex justify-end gap-3">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :headers="tableHeader"
    :items="quadrants.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
    fixed-checkbox
  >
    <template #item-id="{ id }">
      <Link
        :href="route('quadrant-innertia.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>
    <template #item-is_active="{ is_active }">
      <div class="text-center">
        <x-tag size="sm" :color="is_active ? 'success' : 'error'">
          {{ is_active ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: quadrants.next_page_url,
      prev: quadrants.prev_page_url,
      current: quadrants.current_page,
      from: quadrants.from,
      to: quadrants.to,
    }"
  />
</template>