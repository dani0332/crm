<script setup>
defineProps({
  data: {
    type: Array,
    default: () => [],
  },
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const selectedItems = ref([]);

const epTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Product Reference ID',
      value: 'code',
    },
    {
      text: 'Product / Service',
      value: 'product.embedded_product.display_name',
    },
    {
      text: 'Price with VAT',
      value: 'price_with_vat',
    },
    {
      text: 'EP Status',
      value: 'ep_status',
    },
    {
      text: 'Last Updated Date',
      value: 'updated_at',
    },
    {
      text: 'Payment Status',
      value: 'payment_status.text',
    },
    {
      text: 'Actions',
      value: 'actions',
    },
  ],
});

const ppDoc = str => {
  const doc = JSON.parse(str);
  return doc[0]?.path !== '' ? usePage().props.cdnPath + doc[0]?.path : '';
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">Embedded Products</h3>
      <div class="flex flex-wrap gap-3">
        <x-button v-if="selectedItems.length > 0" size="sm">
          Copy Payment Link
        </x-button>
      </div>
    </div>

    <DataTable
      v-model:items-selected="selectedItems"
      table-class-name="tablefixed"
      :headers="epTable.columns"
      :items="data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-ep_status="{ ep_status }"> N/A </template>

      <template #item-updated_at="{ updated_at }">
        {{ dateFormat(updated_at) }}
      </template>

      <template #item-actions="item">
        <div class="flex flex-col gap-1">
          <x-button size="xs" color="emerald"> Send Documents </x-button>
          <x-button size="xs" color="#ff5e00"> Download Certificate </x-button>
          <x-button
            size="xs"
            color="primary"
            :href="ppDoc(item.product.embedded_product.company_documents)"
            target="_blank"
            :disabled="
              ppDoc(item.product.embedded_product.company_documents) === ''
            "
          >
            Download Product Wordings
          </x-button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
