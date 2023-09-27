<script setup>
const props = defineProps({
  carMakes: Array,
});

const { isRequired } = useRules();
const carModels = ref([]);
const carTrims = ref([]);
const tableData = ref([]);
const isloading = ref(false);
const trimloading = ref(false);

const tableHeader = ref([
  { text: 'Provider', value: 'provider' },
  { text: 'Car Value', value: 'carValue' },
  { text: 'Car Value Upper Limit', value: 'carValueUpperLimit' },
  { text: 'Car Value Lower Limit', value: 'carValueLowerLimit' },
]);

const valuationForm = useForm({
  make_code: null,
  modelId: '',
  carTrim: '',
  yearOfManufacture: new Date().getFullYear(),
});

function onSubmit(isValid) {
  if (isValid) {
  }
}

const getCarModel = e => {
  isloading.value = true;
  axios
    .get('/valuation/car-models', { make_code: valuationForm.make_code })
    .then(response => {
      carModels.value = response.data;
    })
    .catch(error => console.log(error))
    .finally(() => (isloading.value = false));
};

function getCarTrim() {
  trimloading.value = true;
  axios
    .get('/valuation/car-model-detail', {
      modelId: valuationForm.modelId,
    })
    .then(response => {
      carTrims.value = response.data;
    })
    .catch(error => console.log(error))
    .finally(() => (trimloading.value = false));
}

const onReset = () => valuationForm.reset();
</script>
<template>
  <Head title="Car Valuation" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Calculate Vehicle Valuation</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid grid-cols-2 gap-4">
      <x-field label="Car Make" required>
        <x-select
          :rules="[isRequired]"
          :options="
            props.carMakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          v-model="valuationForm.make_code"
          @update:modelValue="getCarModel($event)"
          class="w-full"
        />
      </x-field>
      <x-field label="Car Model" required>
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
          @update:modelValue="getCarTrim($event)"
          :loading="isloading"
        />
      </x-field>
      <x-field label="Car Trim" required>
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
      <x-field label="Year Of Manufacture" required>
        <x-input
          v-model="valuationForm.yearOfManufacture"
          type="number"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
    </div>
    <div class="flex justify-end gap-3 mb-4 mt-1">
      <div class="flex justify-end gap-3">
        <x-button size="sm" color="#ff5e00" type="submit"
          >Calculate Deprecation</x-button
        >
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </div>
  </x-form>
  <!-- :loading="loader.table" -->
  <DataTable
    table-class-name="tablefixed"
    :headers="tableHeader"
    :items="tableData"
    border-cell
    hide-rows-per-page
    hide-footer
    fixed-checkbox
  >
  </DataTable>
</template>