<script setup>
const props = defineProps({
  tmleadstatus: Object,
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
  <Head title="TM Lead Status" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">TM Lead Status</h2>
    <Link
      :href="route('tmleadstatus.create')"
      v-if="can(permissionsEnum.TMLeadStatusCreate)"
    >
      <x-button size="sm" color="#ff5e00"> Create TM Lead Status </x-button>
    </Link>
  </div>
  <x-divider class="my-4" />
  <DataTable
    table-class-name="tablefixed"
    :headers="tableHeader"
    :loading="loader.table"
    :items="tmleadstatus.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        class="text-primary-500 hover:underline"
        :href="route('tmleadstatus.show', id)"
      >
        {{ id }}
      </Link>
    </template>
  </DataTable>

  <Pagination
    :links="{
      next: tmleadstatus.next_page_url,
      prev: tmleadstatus.prev_page_url,
      current: tmleadstatus.current_page,
      from: tmleadstatus.from,
      to: tmleadstatus.to,
    }"
  />
</template>
