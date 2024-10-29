<script setup>
const props = defineProps({
  carmakes: Array,
  carmodels: Array,
  insuranceProviders: Array,
  vehicledepreciation: Object,
});

const { isRequired } = useRules();
const error = ref(false);
const carModels = ref(props.carmodels);
const loader = reactive({ table: false, carModel: false, trimloading: false });

const makeCodeError = computed(() => {
  return (deprecationForm.car_make_id == null && error.value) ?? false;
});

const carIdError = computed(() => {
  return (deprecationForm.car_model_id == null && error.value) ?? false;
});

const providerNameError = computed(() => {
  return (
    (deprecationForm.insurance_provider_value == null && error.value) ?? false
  );
});

const getCarModel = e => {
  let carMake = props.carmakes.find(x => x.id == e);
  loader.carModel = true;
  axios
    .get(route('valuation.carmodels', { make_code: carMake.code }))
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

const deprecationForm = useForm({
  car_make_id: props.vehicledepreciation?.car_make_id ?? null,
  insurance_provider_value:
    props.vehicledepreciation?.insurance_provider_id ??
    props.vehicledepreciation?.insurance_provider_value ??
    null,
  car_model_id: props.vehicledepreciation?.car_model_id ?? null,
  first_year: props.vehicledepreciation?.first_year ?? null,
  second_year: props.vehicledepreciation?.second_year ?? null,
  third_year: props.vehicledepreciation?.third_year ?? null,
  fourth_year: props.vehicledepreciation?.fourth_year ?? null,
  fifth_year: props.vehicledepreciation?.fifth_year ?? null,
  sixth_year: props.vehicledepreciation?.sixth_year ?? null,
  seventh_year: props.vehicledepreciation?.seventh_year ?? null,
  eighth_year: props.vehicledepreciation?.eighth_year ?? null,
  ninth_year: props.vehicledepreciation?.ninth_year ?? null,
  tenth_year: props.vehicledepreciation?.tenth_year ?? null,
});

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const onSubmit = isValid => {
  if (
    deprecationForm.car_make_id == null ||
    deprecationForm.car_model_id == null ||
    deprecationForm.insurance_provider_value == null
  )
    error.value = true;
  else {
    deprecationForm.clearErrors();

    const method = isEdit.value ? 'put' : 'post';
    const url = isEdit.value
      ? route('vehicledepreciation.update', props.vehicledepreciation.id)
      : route('vehicledepreciation.store');

    const options = {
      onError: errors => {
        deprecationForm.setError(errors);
      },
    };

    deprecationForm.submit(method, url, options);
  }
};

onMounted(() => {
  if (isEdit.value) getCarModel(deprecationForm.car_make_id);
});
</script>
<template>
  <Head title="Create Vehicle Depreciation" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Vehicle Depreciation</h2>
    <div class="space-x-3">
      <Link :href="route('vehicledepreciation.index')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Depreciation List
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form class="my-4" @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-3 gap-4">
      <x-field label="Car Make">
        <ComboBox
          :single="true"
          v-model="deprecationForm.car_make_id"
          :options="
            props.carmakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          placeholder="Search by Car Make"
          :rules="[isRequired]"
          :hasError="makeCodeError"
          @update:modelValue="getCarModel($event)"
        />
      </x-field>
      <x-field label="Car Model">
        <ComboBox
          :single="true"
          v-model="deprecationForm.car_model_id"
          :options="
            carModels.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          class="w-full"
          :hasError="carIdError"
          :loading="loader.carModel"
        />
      </x-field>
      <x-field label="Insurance Provider">
        <ComboBox
          :single="true"
          v-model="deprecationForm.insurance_provider_value"
          :options="
            props.insuranceProviders.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          :hasError="providerNameError"
          class="w-full"
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="First Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.first_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Second Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.second_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Third Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.third_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Fourth Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.fourth_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Fifth Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.fifth_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Sixth Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.sixth_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Seventh Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.seventh_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Eighth Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.eighth_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Ninth Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.ninth_year"
          type="number"
          class="w-full"
        />
      </x-field>
      <x-field label="Tenth Year" required>
        <x-input
          :rules="[isRequired]"
          v-model="deprecationForm.tenth_year"
          type="number"
          class="w-full"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3">
      <x-button type="submit" size="md" color="emerald">{{
        isEdit ? 'Update' : 'Save'
      }}</x-button>
    </div>
  </x-form>
</template>
