<script setup>
console.log('hello');
const props = defineProps({
  departments: Object,
});
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
const params = useUrlSearchParams('history');

const tableHeader = ref([
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'ACTIVE', value: 'is_active' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
]);
const loader = reactive({
  table: false,
});

const filters = reactive({
  name: '',
  page: 1,
});

const onReset = () => {
  router.visit(route('departments.index'), {
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

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(route('departments.index'), {
      method: 'get',
      data: filters,
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
  <Head title="Department List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Departments</h2>
    <div class="space-x-3">
      <Link
        :href="route('departments.create')"
        v-if="can(permissionsEnum.DEPARTMENT_CREATE)"
      >
        <x-button size="sm" color="#ff5e00" tag="div">
          Create Department
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-1 md:grid-cols-1 gap-4">
      <x-field label="NAME" required>
        <x-input class="w-full" v-model="filters.name" />
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
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.departments.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('departments.show', id)"
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
    <template #item-created_at="{ created_at }">
      {{ created_at ? dateFormat(created_at) : 'N/A' }}
    </template>
    <template #item-updated_at="{ updated_at }">
      {{ updated_at ? dateFormat(updated_at) : 'N/A' }}
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.departments.next_page_url,
      prev: props.departments.prev_page_url,
      current: props.departments.current_page,
      from: props.departments.from,
      to: props.departments.to,
    }"
  />
</template>
