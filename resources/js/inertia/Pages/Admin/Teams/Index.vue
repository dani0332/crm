<script setup>
const props = defineProps({
  teams: Object,
});

const filters = reactive({
  email: '',
  name: '',
  page: 1,
});

const loader = reactive({
  table: false,
});

const tableHeader = reactive([
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'TYPE', value: 'type' },
  { text: 'PARENT', value: 'parent' },
  { text: 'ACTIVE', value: 'is_active' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
]);

const onReset = () => {
  router.visit(route('team.index'), {
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

    router.visit(route('team.index'), {
      method: 'get',
      data: useGenerateQueryString(filters),
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};
</script>
<template>
  <Head title="Teams" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Teams List</h2>
    <div class="space-x-3">
      <Link :href="route('team.create')">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Teams </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="flex flex-wrap gap-6">
      <x-field class="flex-1" label="TEAM NAME" required>
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
    :items="props.teams.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('team.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>
    <template #item-is_active="{ is_active }">
      <div class="text-center">
        <x-tag size="sm" :color="is_active == 'True' ? 'success' : 'error'">
          {{ is_active == 'True' ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
    <template #item-parent="{ parent }">
      <div class="break-words w-60">
        {{ parent?.name ?? 'N/A' }}
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.teams.next_page_url,
      prev: props.teams.prev_page_url,
      current: props.teams.current_page,
      from: props.teams.from,
      to: props.teams.to,
    }"
  />
</template>
