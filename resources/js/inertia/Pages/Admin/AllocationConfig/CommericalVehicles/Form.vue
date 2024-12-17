<script setup>
const props = defineProps({
  carMakes: [Array, Object],
  commercialModels: Object,
});
const { isRequired } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const commercialForm = useForm({
  id: null,
  car_make_id: null,
  car_model_id: null,
});

const carMake = ref([]);
const carModels = ref([]);
const loader = ref(false);
const buttonLoader = ref(false);

const getCarModel = () => {
  buttonLoader.value = true;
  let carCode = carMake.value.find(
    x => x.id == commercialForm.car_make_id,
  ).code;
  axios.get(`/car-model?make_code=${carCode}`).then(response => {
    commercialForm.car_model_id = [];
    carModels.value = response.data;
    buttonLoader.value = false;
  });
};

let validationPassed = ref(false);

const makeModelError = computed(() => {
  return commercialForm.car_make_id == null && validationPassed.value;
});

const modelIdError = computed(() => {
  return commercialForm.car_model_id == null && validationPassed.value;
});

function onSubmit(isValid) {
  if (
    commercialForm.car_make_id == null ||
    commercialForm.car_model_id == null
  ) {
    validationPassed.value = true;
    return;
  } else {
    validationPassed.value = true;
  }
  if (validationPassed.value) {
    loader.value = true;
    let method = 'post';
    let url = isEdit.value
      ? route('admin.configure.commerical.vehicles.update', commercialForm.id)
      : route('admin.configure.commerical.vehicles.store');

    commercialForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          commercialForm.setError(key, errors[key]);
        });
        loader.value = false;
        return false;
      },
      onSuccess: () => {
        loader.value = false;
      },
    });
  }
}

const setInitialState = () => {
  if (isEdit.value) {
    commercialForm.id = props.carMakes.id;
    commercialForm.car_make_id = props.carMakes.id;
    commercialForm.car_model_id = props.commercialModels;
    carModels.value = props.carMakes.car_models;
    carMake.value = [props.carMakes];
    // getCarModel();
  } else {
    carMake.value = props.carMakes;
  }
};

onMounted(() => {
  setInitialState();
});
</script>
<template>
  <Head title="Configure Commercial car make & model" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      Select Car Make & Model to assign commercial status
    </h2>
    <div>
      <Link :href="route('admin.configure.commerical.vehicles')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Commercial Vehicles List
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Car Make" required>
        <ComboBox
          v-model="commercialForm.car_make_id"
          :options="
            carMake.map(x => {
              return { label: x.text, value: x.id };
            })
          "
          single
          @update:modelValue="getCarModel"
          :hasError="makeModelError"
        />
        <!-- <x-select
          class="w-full"
          :options="
            carMake.map(x => {
              return { label: x.text, value: x.id };
            })
          "
          v-model="commercialForm.car_make_id"
          :rules="[isRequired]"
          @update:modelValue="getCarModel"
        > -->
        <!-- </x-select> -->
      </x-field>
      <x-field label="Car Model" required>
        <ComboBox
          v-model="commercialForm.car_model_id"
          :options="
            carModels.map(x => {
              return { label: x.text, value: x.id };
            })
          "
          placeholder="Select Car Model "
          multiple
          :loading="buttonLoader"
          :hasError="modelIdError"
        />
        <!-- <x-select
          class="w-full"
          :options="
            carModels.map(x => {
              return { label: x.text, value: x.id };
            })
          "
          v-model="commercialForm.car_model_id"
          :rules="[isRequired]"
          placeholder="Select Car Model "
          multiple
        >
        </x-select> -->
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit" :loading="loader">
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>
