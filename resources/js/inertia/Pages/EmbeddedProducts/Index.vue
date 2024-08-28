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

const onToggle = ({ id, active }) => {
  loader.table = true;
  axios
    .post(route('embedded-products.toggle-status', id), { is_active: active })
    .then(res => {
      loader.table = false;
      if (res.data.success) {
        notification.success({
          title: res.data.message,
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Something went wrong, please try again later.',
          position: 'top',
        });
      }
    });
};
</script>

<template>
  <div>
    <Head title="Embedded Products" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Embedded Products</h2>
      <x-button
        size="sm"
        color="#ff5e00"
        :href="route('embedded-products.create')"
      >
        Create Embedded Product
      </x-button>
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

      <template #item-is_active="{ is_active, id }">
        <ItemToggler :is-active="is_active" :id="id" @toggle="onToggle" />
      </template>

      <template #item-updated_at="{ updated_at }">
        <div class="text-sm text-center">{{ dateFormat(updated_at) }}</div>
      </template>

      <template #item-actions="{ id }">
        <div class="flex gap-1.5 justify-end">
          <Link :href="route('embedded-products.edit', id)">
            <x-button color="primary" size="xs" outlined> Edit </x-button>
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
