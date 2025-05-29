<script setup>
const props = defineProps({
  permissions: Object,
  roles: Array,
  filters: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;

const loader = reactive({
  table: false,
});

const tableHeader = ref([
  { text: 'ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
]);

const filters = reactive({
  name: props.filters?.name || '',
  role_id: props.filters?.role_id || '',
  page: 1,
});

const filterPermissions = () => {
  filters.page = 1;

  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' || filters[key].length === 0) && delete filters[key],
  );
  router.visit(route('permissions.index'), {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const resetFilters = () => {
  filters.name = '';
  filters.role_id = '';
  filterPermissions();
};

const updateFilter = (field, val) =>
  (filters[field] = !val || filters[field] === val ? null : val);
</script>
<template>
  <Head title="Permissions List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Permissions</h2>
    <!-- <div class="space-x-3" v-if="can(permissionsEnum.PermissionCreate)">
      <Link :href="route('permissions.create')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Create Permission
        </x-button>
      </Link>
    </div> -->
  </div>
  <x-divider class="my-4" />
  <x-form :auto-focus="false" @submit="filterPermissions">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="NAME">
        <x-input
          class="w-full"
          v-model="filters.name"
          placeholder="Search by permission name"
        />
      </x-field>
      <x-field label="ROLE">
        <x-select
          :modelValue="filters.role_id"
          :options="[
            { label: 'All Roles', value: '' },
            ...roles.map(role => ({ label: role.name, value: role.id })),
          ]"
          filterable
          placeholder="Search by role"
          @update:modelValue="val => updateFilter('role_id', val)"
        >
        </x-select>
      </x-field>
    </div>
    <div class="flex justify-end gap-3 mt-4">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="resetFilters">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.permissions.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('permissions.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
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
      next: props.permissions.next_page_url,
      prev: props.permissions.prev_page_url,
      current: props.permissions.current_page,
      from: props.permissions.from,
      to: props.permissions.to,
    }"
  />
</template>
