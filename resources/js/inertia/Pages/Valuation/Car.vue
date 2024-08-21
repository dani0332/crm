<script setup>
const props = defineProps({
  carMakes: Array,
});

const notification = useToast();

const { isRequired } = useRules();
const carModels = ref([]);
const carTrims = ref([]);
const tableData = ref([]);

const loader = reactive({ table: false, carModel: false, trimloading: false });

const tableHeader = ref([
  { text: 'Provider', value: 'providerName' },
  { text: 'Car Value', value: 'carValue' },
  { text: 'Car Value Upper Limit', value: 'carValueUpperLimit' },
  { text: 'Car Value Lower Limit', value: 'carValueLowerLimit' },
]);

const valuationForm = useForm({
  make_code: null,
  modelId: null,
  carTrim: null,
  yearOfManufacture: new Date().getFullYear(),
});

const error = ref(false);

const makeCodeError = computed(() => {
  return (valuationForm.make_code == null && error.value) ?? false;
});

const carIdError = computed(() => {
  return (valuationForm.modelId == null && error.value) ?? false;
});

const carTrimError = computed(() => {
  return (valuationForm.carTrim == null && error.value) ?? false;
});

function onSubmit(isValid) {
  if (
    valuationForm.make_code == null ||
    valuationForm.modelId == null ||
    valuationForm.carTrim == null
  )
    error.value = true;
  else {
    loader.table = true;
    axios
      .post(route('valuation.calculate'), {
        carModelDetailId: valuationForm.carTrim,
        yearOfManufacture: valuationForm.yearOfManufacture,
      })
      .then(response => {
        tableData.value = response.data;
        notification.success({
          title: 'Success',
          position: 'top',
        });
      })
      .catch(error => {
        notification.error({
          title: "Error! Can't Calculate Depreciation.",
          position: 'top',
        });
      })
      .finally(() => (loader.table = false));
  }
}

const getCarModel = e => {
  loader.carModel = true;
  axios
    .get(route('valuation.carmodels', { make_code: valuationForm.make_code }))
    .then(response => {
      carModels.value = response.data;
    })
    .catch(error =>
      notification.error({
        title: 'Error',
        position: 'top',
      }),
    )
    .finally(() => (loader.carModel = false));
};

function getCarTrim() {
  loader.trimloading = true;
  axios
    .get(route('valuation.carmodeldetail', { modelId: valuationForm.modelId }))
    .then(response => {
      carTrims.value = response.data;
    })
    .catch(error =>
      notification.error({
        title: 'Error',
        position: 'top',
      }),
    )
    .finally(() => (loader.trimloading = false));
}

const onReset = () => {
  valuationForm.make_code = null;
  valuationForm.modelId = null;
  valuationForm.carTrim = null;
  valuationForm.yearOfManufacture = new Date().getFullYear();
};
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
        <ComboBox
          :single="true"
          v-model="valuationForm.make_code"
          placeholder="Search by Car Make"
          :options="
            props.carMakes.map(item => ({
              value: item.code,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          @update:modelValue="getCarModel($event)"
          :hasError="makeCodeError"
        />
      </x-field>
      <x-field label="Car Model" required>
        <ComboBox
          :single="true"
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
          :loading="loader.carModel"
          :hasError="carIdError"
        />
      </x-field>
      <x-field label="Car Trim" required>
        <ComboBox
          v-model="valuationForm.carTrim"
          :rules="[isRequired]"
          :options="
            carTrims.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :single="true"
          class="w-full"
          :loading="loader.trimloading"
          :hasError="carTrimError"
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
