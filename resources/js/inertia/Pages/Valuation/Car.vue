<script setup>
import { computed } from 'vue';

const props = defineProps({
  carMakes: Array,
  dropdownSource: Object,
});

const notification = useToast();

const { isRequired } = useRules();
const carModels = ref([]);
const carTrims = ref([]);
const tableData = ref([]);

const loader = reactive({ table: false, carModel: false, trimloading: false });

const CarHeader = ref([
  { text: 'Provider', value: 'providerName' },
  { text: 'Car Value', value: 'carValue' },
  { text: 'Car Value Upper Limit', value: 'carValueUpperLimit' },
  { text: 'Car Value Lower Limit', value: 'carValueLowerLimit' },
]);

const bikeHeader = ref([
  { text: 'Provider', value: 'providerName' },
  { text: 'Bike Value', value: 'bikeValue' },
  { text: 'Bike Value Upper Limit', value: 'bikeValueUpperLimit' },
  { text: 'Bike Value Lower Limit', value: 'bikeValueLowerLimit' },
]);

const computedHeader = computed(() => {
  if (valuationForm.quoteType === 'CAR') {
    return CarHeader.value;
  } else {
    return bikeHeader.value;
  }
});

const valuationForm = useForm({
  quoteType: 'CAR',
  make_code: null,
  modelId: null,
  carTrim: null,
  yearOfManufacture: new Date().getFullYear(),
});

const error = ref(false);

const getMake = computed(() => {
  if (valuationForm.quoteType === 'CAR') {
    return props.carMakes.map(item => ({
      value: item.code,
      label: item.text,
    }));
  } else {
    return props.dropdownSource.bike_make_id.map(item => ({
      value: item.id,
      label: item.text,
    }));
  }
});

const computedModels = computed(() => {
  return carModels.value.map(item => ({ value: item.id, label: item.text }));
});

const makeCodeError = computed(() => {
  return (valuationForm.make_code == null && error.value) ?? false;
});

const carIdError = computed(() => {
  return (valuationForm.modelId == null && error.value) ?? false;
});

const carTrimError = computed(() => {
  return (valuationForm.carTrim == null && error.value) ?? false;
});

const bikeMakeOptions = computed(() => {
  return props.dropdownSource.bike_make_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
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

const getBikeModel = (initial = false) => {
  axios
    .get(`/bike-model-by-id?id=${valuationForm.make_code}`)
    .then(({ data }) => {
      carModels.value = [...data];
    });
};

const getModelBasedOnQuote = () => {
  if (valuationForm.quoteType === 'CAR') {
    getCarModel();
  } else {
    getBikeModel();
  }
};
</script>
<template>
  <Head title="Calculate Vehicle Valuation (Car & Bike)" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      Calculate Vehicle Valuation (Car & Bike)
    </h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid grid-cols-2 gap-4">
      <x-field label="Quote Type" required>
        <ComboBox
          :single="true"
          v-model="valuationForm.quoteType"
          placeholder="Search by Quote Type"
          :options="[
            { value: 'CAR', label: 'Car' },
            { value: 'BIKE', label: 'Bike' },
          ]"
          :rules="[isRequired]"
          :hasError="makeCodeError"
        />
      </x-field>
      <x-field label="Make" required>
        <ComboBox
          :single="true"
          v-model="valuationForm.make_code"
          placeholder="Search by Make"
          :options="getMake"
          :rules="[isRequired]"
          @update:modelValue="getModelBasedOnQuote($event)"
          :hasError="makeCodeError"
        />
      </x-field>
      <x-field label="Model" required>
        <ComboBox
          :single="true"
          v-model="valuationForm.modelId"
          :rules="[isRequired]"
          :options="computedModels"
          class="w-full"
          @update:modelValue="getCarTrim($event)"
          :loading="loader.carModel"
          :hasError="carIdError"
        />
      </x-field>
      <x-field label="Trim" required>
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
    :headers="computedHeader"
    :items="tableData"
    border-cell
    hide-rows-per-page
    hide-footer
  >
  </DataTable>
</template>
