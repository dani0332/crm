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
  { text: 'Product name', value: 'product_name' },
  { text: 'Company Name', value: 'company_name' },
  { text: 'Product Category', value: 'product_category' },
  { text: 'Shortcode', value: 'short_code' },
  { text: 'Display name', value: 'display_name' },
  { text: 'Product Type', value: 'product_type' },
  { text: 'Active', value: 'is_active' },
  { text: 'Actions', value: 'actions' },
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
          :href="route('embedded-products.edit', id)"
          class="text-primary-500 hover:underline"
        >
          {{ id }}
        </Link>
      </template>

      <template #item-company_name="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-is_active="{ is_active }">
        <div class="text-center">
          <x-tag size="sm" :color="is_active ? 'success' : 'error'">
            {{ is_active ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>

      <template #item-actions="{ id }">
        <div class="flex gap-1.5 justify-end">
          <!-- <Link :href="route('embedded-products.show', id)">
            <x-button tag="div" size="xs" outlined> View </x-button>
          </Link> -->
          <Link :href="route('embedded-products.edit', id)">
            <x-button color="primary" size="xs" outlined> Edit </x-button>
          </Link>
          <!-- <x-button
                            color="red"
                            size="xs"
                            outlined
                            @click.prevent="
                                deleteAction.id = id;
                                showDeleteModal = true;
                            "
                       >
                            Delete
                        </x-button> -->
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
