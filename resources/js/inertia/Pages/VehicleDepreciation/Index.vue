<script setup>
const tableHeaders = ref([
  { text: 'Id', value: 'provider' },
  { text: 'Car Make Id', value: 'carValue' },
  { text: 'Car Model Id', value: 'carValueUpperLimit' },
  { text: 'Insurance Provider', value: 'carValueUpperLimit' },
  { text: 'First Year', value: 'carValueUpperLimit' },
  { text: 'Second Year', value: 'carValueUpperLimit' },
  { text: 'Third Year', value: 'carValueUpperLimit' },
  { text: 'Fourth Year', value: 'carValueUpperLimit' },
  { text: 'Fifth Year', value: 'carValueUpperLimit' },
  { text: 'Sixth Year', value: 'carValueUpperLimit' },
  { text: 'Seventh Year', value: 'carValueUpperLimit' },
  { text: 'Eighth Year', value: 'carValueUpperLimit' },
  { text: 'Nineth Year', value: 'carValueUpperLimit' },
  { text: 'Tenth Year', value: 'carValueUpperLimit' },
]);
</script>
<template>
  <Head title="Vehical Depreciation" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Vehical Depreciation</h2>
    <div class="space-x-3">
      <Link :href="route('home.create')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Create Vehical Depreciation
        </x-button>
      </Link>
    </div>
  </div>
  <DataTable
    table-class-name="tablefixed"
    :loading="loader.table"
    :headers="tableHeaders"
    :items="tableData || []"
    border-cell
    hide-rows-per-page
    hide-footer
    fixed-checkbox
  >
  </DataTable>
  <Pagination
    :links="{
      next: quotes.next_page_url,
      prev: quotes.prev_page_url,
      current: quotes.current_page,
      from: quotes.from,
      to: quotes.to,
    }"
  />
</template>