<script setup>
const props = defineProps({
  roles: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;

const loader = reactive({
  table: false,
});

const tableHeader = ref([
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
]);

const filters = reactive({
  name: '',
  page: 1,
});

const getRoleByName = () => {
  filters.page = 1;

  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' || filters[key].length === 0) && delete filters[key],
  );
  router.visit(route('roles.index'), {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

watchDebounced(
  () => filters.name,
  () => {
    getRoleByName();
  },
  { debounce: 1000, maxWait: 5000 },
);
</script>
<template>
  <Head title="Roles List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Roles</h2>
    <div class="space-x-3" v-if="can(permissionsEnum.RoleCreate)">
      <Link :href="route('roles.create')">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Role </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form :auto-focus="false">
    <div class="grid sm:grid-cols-1 md:grid-cols-1 gap-4">
      <x-field label="NAME" required>
        <x-input class="w-full" v-model="filters.name" />
      </x-field>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.roles.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('roles.show', id)"
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
      next: props.roles.next_page_url,
      prev: props.roles.prev_page_url,
      current: props.roles.current_page,
      from: props.roles.from,
      to: props.roles.to,
    }"
  />
</template>
