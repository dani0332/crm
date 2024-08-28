<script setup>
const props = defineProps({
  tminsurancetype: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const loader = reactive({ table: false });

const tableHeader = ref([
  { text: 'ID', value: 'id' },
  { text: 'Code', value: 'code' },
  { text: 'Text', value: 'text' },
  { text: 'Text Ar', value: 'text_ar' },
  { text: 'Sort Order', value: 'sort_order' },
  { text: 'Is Active', value: 'is_active' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
]);
</script>
<template>
  <Head title="TM Insurance Types" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">TM Insurance Types</h2>
    <Link
      :href="route('tminsurancetype.create')"
      v-if="can(permissionsEnum.TMInsuranceTypeCreate)"
    >
      <x-button size="sm" color="#ff5e00"> Create TM Insurance Type </x-button>
    </Link>
  </div>
  <x-divider class="my-4" />
  <DataTable
    table-class-name="tablefixed"
    :headers="tableHeader"
    :loading="loader.table"
    :items="tminsurancetype.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        class="text-primary-500 hover:underline"
        :href="route('tminsurancetype.show', id)"
      >
        {{ id }}
      </Link>
    </template>
  </DataTable>

  <Pagination
    :links="{
      next: tminsurancetype.next_page_url,
      prev: tminsurancetype.prev_page_url,
      current: tminsurancetype.current_page,
      from: tminsurancetype.from,
      to: tminsurancetype.to,
    }"
  />
</template>
