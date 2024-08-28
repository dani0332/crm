<script setup>
const props = defineProps({
  data: Object,
});

const page = usePage();
const loader = reactive({ table: false });

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const tableHeaders = ref([
  { text: 'Ref ID', value: 'id' },
  { text: 'Insurance Provider', value: 'ip_text' },
  { text: 'First Year', value: 'first_year' },
  { text: 'Second Year', value: 'second_year' },
  { text: 'Third Year', value: 'third_year' },
  { text: 'Fourth Year', value: 'fourth_year' },
  { text: 'Fifth Year', value: 'fifth_year' },
  { text: 'Sixth Year', value: 'sixth_year' },
  { text: 'Seventh Year', value: 'seventh_year' },
  { text: 'Eighth Year', value: 'eighth_year' },
  { text: 'Ninth Year', value: 'ninth_year' },
  { text: 'Tenth Year', value: 'tenth_year' },
]);
</script>
<template>
  <Head title="Vehicle Depreciation" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Vehicle Depreciation</h2>
    <div class="space-x-3">
      <Link
        v-if="can(permissionsEnum.VehicleDepreciationCreate)"
        :href="route('vehicledepreciation.create')"
      >
        <x-button size="sm" color="#ff5e00" tag="div">
          Create Vehicle Depreciation
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <DataTable
    table-class-name="tablefixed"
    :loading="loader.table"
    :headers="tableHeaders"
    :items="props.data.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        class="text-primary-500 hover:underline"
        :href="route('vehicledepreciation.show', id)"
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
