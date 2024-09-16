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

let stats = ref({ ...props.statsArray });
const tranformArray = () => {
  Object.keys(props.headingArray).map(key => {
    let array = stats.value[key];
    array = array.map(item => {
      return {
        ...item,
        ecom_conv: Math.round(
          (+item.paid_ecom_captured / +item.ecom_total) * 100,
        ).toFixed(2),
      };
    });
    stats.value[key] = array;
  });
};

const calculateTotalEcomConv = data => {
  let key = 'paid_ecom_captured';
  let totalPaidEcomCaputed = data.reduce((accumulator, currentItem) => {
    if (key in currentItem) {
      let value = currentItem[key] != null ? currentItem[key] : 0;
      accumulator += +parseFloat(value.toString().replace(/,/g, '')) || 0;
    }
    return accumulator;
  }, 0);

  let ecom_total_key = 'ecom_total';
  let totalEcom = data.reduce((accumulator, currentItem) => {
    if (ecom_total_key in currentItem) {
      let value =
        currentItem[ecom_total_key] != null ? currentItem[ecom_total_key] : 0;
      accumulator += +parseFloat(value.toString().replace(/,/g, '')) || 0;
    }
    return accumulator;
  }, 0);

  return ((totalPaidEcomCaputed / totalEcom) * 100).toFixed(2);
};

const calculateTotalSum = useCalculateTotalSum;

const isIntegerColumn = key => {
  // Add logic to determine if the column contains an integer
  // For example, check if the key corresponds to an integer column
  return [
    'total_assigned',
    'paid_ecom',
    'paid_ecom_auth',
    'paid_ecom_captured',
    'paid_ecom_cancelled',
    'ecom_total',
    'ecom_conv',
  ].includes(key);
};

onMounted(() => {
  tranformArray();
});
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
      hide-footer
      :items="stats[key]"
      :rows-per-page="500"
    >
      <template #item-email="item">
        <p>{{ item.email ?? 'UnAssigned' }}</p>
      </template>
      <!-- <template #item-ecom_conv="item">
        <p>
          {{
            Math.round(
              (+item.paid_ecom_captured / +item.ecom_total) * 100,
            ).toFixed(2)
          }}
        </p>
      </template> -->
      <template #body-append>
        <tr class="total-row">
          <td class="direction-left">Total</td>
          <td
            v-for="header in carHeaders.slice(1, carHeaders.length)"
            :key="header.value"
            class="direction-center"
          >
            {{
              isIntegerColumn(header.value) && header.value != 'ecom_conv'
                ? calculateTotalSum(stats[key], header.value)
                : calculateTotalEcomConv(stats[key], header.value) + '%'
            }}
          </td>
        </tr>
      </template>
    </DataTable>
  </div>
</template>
