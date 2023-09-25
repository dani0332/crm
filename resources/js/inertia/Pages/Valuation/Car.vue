<script setup>
const props = defineProps({
  carMakes: Array,
});

const carModels = ref([]);
const carTrims = ref([]);
const tableData = ref([]);

const tableHeader = ref([
  { text: 'Provider', value: 'provider' },
  { text: 'Car Value', value: 'carValue' },
  { text: 'Car Value Upper Limit', value: 'carValueUpperLimit' },
  { text: 'Car Value Lower Limit', value: 'carValueUpperLimit' },
]);

const valuationForm = useForm({
  make_code: '',
  modelId: '',
  carTrim: '',
  yearOfManufacture: new Date().getFullYear(),
});

function onSubmit() {}

function getCarModel() {
  axios
    .get(route('/valuation/car-models'), { make_code: valuationForm.make_code })
    .then(response => {
      console.log(response);
    })
    .catch(error => console.log(error));
}

function getCarTrim() {
  axios
    .get(route('/valuation/car-model-detail'), {
      modelId: valuationForm.modelId,
    })
    .then(response => {
      console.log(response);
    })
    .catch(error => console.log(error));
}
</script>
<template>
  <Head title="Car Valuation" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid grid-cols-2 gap-4">
      <x-field title="Car Make" required>
        <x-select
          v-model="valuationForm.make_code"
          :rules="[isRequired]"
          :options="
            props.carMakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          @change="getCarModel()"
          class="w-full"
        />
      </x-field>
      <x-field title="Car Model" required>
        <x-select
          v-model="valuationForm.modelId"
          :rules="[isRequired]"
          :options="
            carModels.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          @change="getCarTrim()"
        />
      </x-field>
      <x-field title="Car Trim" required>
        <x-select
          v-model="valuationForm.carTrim"
          :rules="[isRequired]"
          :options="
            carTrims.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field title="Year Of Manufacture">
        <x-input
          v-model="valuationForm.yearOfManufacture"
          type="number"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="flex justify-between gap-3 mb-4 mt-1">
      <div class="flex justify-self-end gap-3">
        <x-button size="sm" color="#ff5e00" type="submit"
          >Calculate Deprecation</x-button
        >
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </div>
  </x-form>
  <DataTable
    table-class-name="tablefixed"
    :loading="loader.table"
    :headers="tableHeader"
    :items="tableData"
    border-cell
    hide-rows-per-page
    hide-footer
    fixed-checkbox
  >
  </DataTable>
</template>