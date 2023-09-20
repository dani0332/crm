<script setup>
const props = defineProps({
  statsArray: Object,
  headingArray: Object,
  qouteType: String,
});

const carHeaders = ref([
  { text: 'Advisor Email', value: 'email' },
  { text: 'Total Assigned', value: 'total_assigned' },
  { text: 'Paid Ecom', value: 'paid_ecom' },
  { text: 'Paid Ecom Authorised', value: 'paid_ecom_auth' },
  { text: 'Paid Ecom Captured', value: 'paid_ecom_captured' },
  { text: 'Paid Ecom Cancelled', value: 'paid_ecom_cancelled' },
  { text: 'Ecom Total', value: 'ecom_total' },
  { text: 'Ecom Conversion', value: 'ecom_conv' },
]);
</script>

<template>
  <Head title="Car Conversion" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Car Conversion
  </h1>
  <x-divider class="my-2" />
  <div v-for="(heading, key) in headingArray" :key="heading" class="my-8">
    <h1 class="text-lg my-3">{{ heading }}</h1>
    <DataTable
      table-class-name="tablefixed"
      :headers="carHeaders"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
      :items="statsArray[key]"
    >
      <template #item-email="item">
        <p>{{ item.email ?? 'UnAssigned' }}</p>
      </template>
      <template #item-ecom_conv="item">
        <p>
          {{
            Math.round(
              (item.paid_ecom_captured / item.ecom_total) * 100,
            ).toFixed(2)
          }}
        </p>
      </template>
    </DataTable>
  </div>
</template>