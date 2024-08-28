<script setup>
const props = defineProps({
  dataTmLeads: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const loader = reactive({ table: false });

const tableHeader = ref([
  { text: 'ID', value: 'id' },
  { text: 'FILE', value: 'file_name' },
  { text: 'GOOD', value: 'good' },
  { text: 'SUBMITTED BY', value: 'user_name' },
  { text: 'CREATED AT', value: 'created_at' },
]);
</script>
<template>
  <Head title="Upload TM Leads" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Upload TM Leads</h2>
    <Link
      :href="route('tmuploadlead-create')"
      v-if="can(permissionsEnum.TMUploadLeadsCreate)"
    >
      <x-button size="sm" color="#ff5e00"> Upload TM Leads List </x-button>
    </Link>
  </div>
  <x-divider class="my-4" />
  <DataTable
    table-class-name="tablefixed"
    :headers="tableHeader"
    :loading="loader.table"
    :items="dataTmLeads.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        class="text-primary-500 hover:underline"
        :href="route('tmuploadlead-show', id)"
      >
        {{ id }}
      </Link>
    </template>
  </DataTable>

  <Pagination
    :links="{
      next: dataTmLeads.next_page_url,
      prev: dataTmLeads.prev_page_url,
      current: dataTmLeads.current_page,
      from: dataTmLeads.from,
      to: dataTmLeads.to,
    }"
  />
</template>
