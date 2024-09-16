<script setup>
defineProps({
  embeddedProducts: Object,
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const loader = reactive({
  table: false,
  export: false,
});

const notification = useToast();

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Product name', value: 'product_name' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'Shortcode', value: 'short_code' },
  { text: 'Product Category', value: 'product_category' },
  { text: 'Display name', value: 'display_name' },
  { text: 'Product Type', value: 'product_type' },
  { text: 'Status', value: 'is_active' },
  { text: 'Last Modified Date', value: 'updated_at' },
  { text: 'Actions', value: 'actions' },
];
</script>

<template>
  <div>
    <Head title="Reports" />
    <nav class="mb-4">
      <ol class="flex gap-1">
        <li>
          <Link
            :href="route('embedded-products.index')"
            class="text-sm border-b text-gray-500"
            ><span> Embedded Products </span></Link
          >
        </li>
        <li><span class="text-gray-400">/</span></li>
        <li><span class="text-sm font-semibold"> Reports </span></li>
      </ol>
    </nav>
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Reports</h2>
    </div>

    <x-divider class="my-4" />

    <DataTable
      table-class-name=""
      :headers="tableHeader"
      :loading="loader.table"
      :items="embeddedProducts.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-company_name="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-is_active="{ is_active }">
        <div class="text-center">
          <x-icon
            :icon="is_active ? 'roundchecked' : 'roundcross'"
            :color="is_active ? 'green' : 'red'"
            size="lg"
          />
        </div>
      </template>

      <template #item-updated_at="{ updated_at }">
        <div class="text-sm text-center">{{ dateFormat(updated_at) }}</div>
      </template>

      <template #item-actions="{ id }">
        <div class="flex gap-1.5 justify-end">
          <Link :href="route('embedded-products.reports.certificates', id)">
            <x-button color="primary" size="xs" outlined> View </x-button>
          </Link>
        </div>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: embeddedProducts.next_page_url,
        prev: embeddedProducts.prev_page_url,
        current: embeddedProducts.current_page,
        from: embeddedProducts.from,
        to: embeddedProducts.to,
      }"
    />
  </div>
</template>
