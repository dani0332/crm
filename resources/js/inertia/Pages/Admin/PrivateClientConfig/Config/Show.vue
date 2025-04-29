<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  configurations: Array,
  insurers: Array,
  carMakes: Array,
  locationAreas: Array,
});

const { isRequired, isRequiredNumber } = useRules();

const configForm = useForm({
  quote_type: '',
  department_id: '',
  segment: '',
  // Car section properties
  car_total_price: '',
  car_total_price_enabled: false,
  car_value: '',
  car_value_enabled: false,
  car_make: [],
  car_make_enabled: false,
  car_insurer: [],
  car_insurer_enabled: false,

  // Health section properties
  health_total_price: '',
  health_total_price_enabled: false,
  health_insurer: [],
  health_insurer_enabled: false,

  // Life section properties
  life_sum_assured_usd: '',
  life_sum_assured_aed: '',
  life_sum_assured_eur: '',
  life_sum_assured_gbp: '',
  life_sum_assured_enabled: false,
  life_insurer: [],
  life_insurer_enabled: false,

  // Home section properties
  home_total_price: '',
  home_total_price_enabled: false,
  home_insurer: [],
  home_insurer_enabled: false,
  home_location_area: [],
  home_location_area_enabled: false,

  // Yacht section properties
  yacht_total_price: '',
  yacht_total_price_enabled: false,
  yacht_insurer: [],
  yacht_insurer_enabled: false,
});

const loader = ref(false);
// Initialize the form with existing configurations
onMounted(() => {
  if (props.configurations && props.configurations.length > 0) {
    // Process existing configurations and populate the form
    props.configurations.forEach(config => {
      if (config.field_name === 'car_total_price') {
        configForm.car_total_price = config.value;
        configForm.car_total_price_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'car_value') {
        configForm.car_value = config.value;
        configForm.car_value_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'car_make') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.car_make = values;
        configForm.car_make_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'car_insurer') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.car_insurer = values;
        configForm.car_insurer_enabled = config.status == true ? true : false;
      }

      if (config.field_name == 'health_total_price') {
        configForm.health_total_price = config.value;
        configForm.health_total_price_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'health_insurer') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.health_insurer = values;
        configForm.health_insurer_enabled = config.status == true ? true : false;
      }

      if (config.field_name == 'life_sum_assured') {
        configForm.life_sum_assured_enabled = config.status == true ? true : false;
        if (config.currency_type_id === 1) { // AED
          configForm.life_sum_assured_aed = config.value;
        } else if (config.currency_type_id === 2) { // USD
          configForm.life_sum_assured_usd = config.value;
        } else if (config.currency_type_id === 3) { // EUR
          configForm.life_sum_assured_eur = config.value;
        } else if (config.currency_type_id === 4) { // GBP
          configForm.life_sum_assured_gbp = config.value;
        }
      } else if (config.field_name == 'life_insurer') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.life_insurer = values;
        configForm.life_insurer_enabled = config.status == true ? true : false;
      }

      if (config.field_name == 'home_total_price') {
        configForm.home_total_price = config.value;
        configForm.home_total_price_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'home_insurer') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.home_insurer = values;
        configForm.home_insurer_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'home_location_area') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.home_location_area = values;
        configForm.home_location_area_enabled = config.status == true ? true : false;
      }

      if (config.field_name == 'yacht_total_price') {
        configForm.yacht_total_price = config.value;
        configForm.yacht_total_price_enabled = config.status == true ? true : false;
      } else if (config.field_name == 'yacht_insurer') {
        // Convert comma-separated values to array of numbers
        const values = config.value ? config.value.split(',') : [];
        configForm.yacht_insurer = values;
        configForm.yacht_insurer_enabled = config.status == true ? true : false;
      }

    });
  }
});


const onSubmit = isValid => {
  if (isValid) {
    loader.value = true;

    // Prepare configurations array
    const configurations = [];

    // Car section
    if (configForm.car_total_price_enabled && configForm.car_total_price) {
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Total Price',
        field_name: 'car_total_price',
        operator: 'gte',
        value: configForm.car_total_price,
        currency_type_id: 1, // AED
        status: 1
      });
    }

    if (configForm.car_value_enabled && configForm.car_value) {
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Value',
        field_name: 'car_value',
        operator: 'gte',
        value: configForm.car_value,
        currency_type_id: 1, // AED
        status: 1
      });
    }

    if (configForm.car_make_enabled && configForm.car_make.length > 0) {
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Make',
        field_name: 'car_make',
        operator: 'in',
        value: configForm.car_make.join(','),
        status: 1
      });
    }

    if (configForm.car_insurer_enabled && configForm.car_insurer.length > 0) {
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Insurer',
        field_name: 'car_insurer',
        operator: 'in',
        value: configForm.car_insurer.join(','),
        status: 1
      });
    }

    // Health section
    if (configForm.health_total_price_enabled && configForm.health_total_price) {
      configurations.push({
        quote_type_id: 3, // Health
        name: 'Health Total Price',
        field_name: 'health_total_price',
        operator: 'gte',
        value: configForm.health_total_price,
        currency_type_id: 1, // AED
        status: 1
      });
    }

    if (configForm.health_insurer_enabled && configForm.health_insurer.length > 0) {
      configurations.push({
        quote_type_id: 3, // Health
        name: 'Health Insurer',
        field_name: 'health_insurer',
        operator: 'in',
        value: configForm.health_insurer.join(','),
        status: 1
      });
    }

    // Life section
    if (configForm.life_sum_assured_enabled) {
      if (configForm.life_sum_assured_usd) {
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (USD)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: configForm.life_sum_assured_usd,
          currency_type_id: 2, // USD
          status: 1
        });
      }

      if (configForm.life_sum_assured_aed) {
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (AED)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: configForm.life_sum_assured_aed,
          currency_type_id: 1, // AED
          status: 1
        });
      }

      if (configForm.life_sum_assured_eur) {
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (EUR)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: configForm.life_sum_assured_eur,
          currency_type_id: 3, // EUR
          status: 1
        });
      }

      if (configForm.life_sum_assured_gbp) {
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (GBP)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: configForm.life_sum_assured_gbp,
          currency_type_id: 4, // GBP
          status: 1
        });
      }
    }

    if (configForm.life_insurer_enabled && configForm.life_insurer.length > 0) {
      configurations.push({
        quote_type_id: 4, // Life
        name: 'Life Insurer',
        field_name: 'life_insurer',
        operator: 'in',
        value: configForm.life_insurer.join(','),
        status: 1
      });
    }

    // Home section
    if (configForm.home_total_price_enabled && configForm.home_total_price) {
      configurations.push({
        quote_type_id: 2, // Home
        name: 'Home Total Price',
        field_name: 'home_total_price',
        operator: 'gte',
        value: configForm.home_total_price,
        currency_type_id: 1, // AED
        status: 1
      });
    }

    if (configForm.home_insurer_enabled && configForm.home_insurer.length > 0) {
      configurations.push({
        quote_type_id: 2, // Home
        name: 'Home Insurer',
        field_name: 'home_insurer',
        operator: 'in',
        value: configForm.home_insurer.join(','),
        status: 1
      });
    }

    if (configForm.home_location_area_enabled && configForm.home_location_area.length > 0) {
      configurations.push({
        quote_type_id: 2, // Home
        name: 'Home Location Area',
        field_name: 'home_location_area',
        operator: 'in',
        value: configForm.home_location_area.join(','),
        status: 1
      });
    }

    // Yacht section
    if (configForm.yacht_total_price_enabled && configForm.yacht_total_price) {
      configurations.push({
        quote_type_id: 7, // Yacht
        name: 'Yacht Total Price',
        field_name: 'yacht_total_price',
        operator: 'gte',
        value: configForm.yacht_total_price,
        currency_type_id: 1, // AED
        status: 1
      });
    }

    if (configForm.yacht_insurer_enabled && configForm.yacht_insurer.length > 0) {
      configurations.push({
        quote_type_id: 7, // Yacht
        name: 'Yacht Insurer',
        field_name: 'yacht_insurer',
        operator: 'in',
        value: configForm.yacht_insurer.join(','),
        status: 1
      });
    }

    // Send data to server
    configForm.submit('post', route('admin.private-client-config.update'), {
      data: { configurations },
      onError: errors => {
        loader.value = false;
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
      onSuccess: response => {
        loader.value = false;
        notification.success({
          title: 'Configuration updated successfully',
          position: 'top',
        });
      },
    });
  }
};

const computedVolumeText = computed(() => {
  return configForm.quote_type == 'Health' ? `Entry level` : 'Volume';
});

const computedValueText = computed(() => {
  return configForm.quote_type == 'Health' ? `Good` : 'Value';
});
</script>
<template>

  <Head title="Private client tag configuration" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Private Client Configuration</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <p class="font-medium">Car</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Car Make and Total Price in single row -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Total Price Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.car_total_price_enabled" class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input v-model="configForm.car_total_price" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>

        <!-- Car Make Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.car_make_enabled" class="mr-2 h-4 w-4" />
            <p>Car Make</p>
          </div>
          <x-select placeholder="Select Car Make" :options="props.carMakes" filterable multiple
            v-model="configForm.car_make"></x-select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <!-- Car Value Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.car_value_enabled" class="mr-2 h-4 w-4" />
            <p class="mr-2">Car Value</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input v-model="configForm.car_value" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>

        <!-- Car Insurer Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.car_insurer_enabled" class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select placeholder="Select Insurer" :options="props.insurers" filterable multiple
            v-model="configForm.car_insurer"></x-select>
        </div>
      </div>
    </div>

    <p class="font-medium">Health</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Total Price and Insurer in single row -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Total Price Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.health_total_price_enabled" class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input v-model="configForm.health_total_price" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>

        <!-- Health Insurer Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.health_insurer_enabled" class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select placeholder="Select Insurer" :options="props.insurers" filterable multiple
            v-model="configForm.health_insurer"></x-select>
        </div>
      </div>
    </div>

    <p class="font-medium">Life</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Total Price and Insurer in single row -->
      <div class="grid grid-cols-4 gap-4">
        <!-- Sum Insured Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.life_sum_assured_enabled" class="mr-2 h-4 w-4" />
            <p class="mr-2">Sum Insured</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input v-model="configForm.life_sum_assured_usd" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>USD</span>
              </div>
            </template>
          </x-input>
        </div>

        <div>
          <x-input v-model="configForm.life_sum_assured_aed" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>


        <div>
          <x-input v-model="configForm.life_sum_assured_eur" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>EUR</span>
              </div>
            </template>
          </x-input>
        </div>


        <div>
          <x-input v-model="configForm.life_sum_assured_gbp" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>GBP</span>
              </div>
            </template>
          </x-input>
        </div>
      </div>

    </div>

    <p class="font-medium">Home</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Total Price , Insurer and area in single row -->
      <div class="grid grid-cols-3 gap-4">
        <!-- Total Price Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.home_total_price_enabled" class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input v-model="configForm.home_total_price" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>

        <!-- Home Insurer Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.home_insurer_enabled" class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select placeholder="Select Insurer" :options="props.insurers" filterable multiple
            v-model="configForm.home_insurer"></x-select>
        </div>

        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.home_location_area_enabled" class="mr-2 h-4 w-4" />
            <p>Location Area</p>
          </div>
          <x-select placeholder="Select Location Area" :options="props.locationAreas" filterable multiple
            v-model="configForm.home_location_area"></x-select>
        </div>
      </div>
    </div>

    <p class="font-medium">Yacht</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Total Price and Insurer in single row -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Total Price Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.yacht_total_price_enabled" class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input v-model="configForm.yacht_total_price" class="!mb-0" :rules="[isRequiredNumber]">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>

        <!-- Yacht Insurer Field -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" v-model="configForm.yacht_insurer_enabled" class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select placeholder="Select Insurer" :options="props.insurers" filterable multiple
            v-model="configForm.yacht_insurer"></x-select>
        </div>
      </div>
    </div>


    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit" :loading="loader" :disabled="loader">
        Update
      </x-button>
    </div>
  </x-form>
</template>
