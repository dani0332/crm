<script setup>
defineProps({
  embeddedProducts: Object,
});

const page = usePage();
const loader = reactive({
  table: false,
  export: false,
});

onMounted(() => {});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'Product name', value: 'product_name' },
  { text: 'Shortcode', value: 'short_code' },
  { text: 'Display name', value: 'display_name' },
  { text: 'Product Type', value: 'product_type' },
  { text: 'Description', value: 'description' },
  { text: 'Logic', value: 'logic' },
  { text: 'Removal Confirmation', value: 'removal_confirmation' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Embedded Products" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Embedded Products</h2>
      <x-button size="sm" color="#ff5e00" href="/embedded-products/create">
        Create Embedded Product
      </x-button>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="embeddedProducts.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-id="{ id }">
        <Link
          :href="`/embedded-products/${id}`"
          class="text-primary-500 hover:underline"
        >
          {{ id }}
        </Link>
      </template>

      <template #item-company_name="{ insuranceprovider }">
        {{ insuranceprovider?.text }}
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
