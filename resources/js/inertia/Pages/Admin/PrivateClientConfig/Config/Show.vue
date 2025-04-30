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

// Define insurance types with their respective fields
const insuranceTypes = [
  {
    id: 1,
    name: 'car',
    label: 'Car',
    quote_type_id: 1,
    fields: [
      { 
        name: 'total_price', 
        label: 'Total Price', 
        type: 'price', 
        currency: 'AED',
        currency_type_id: 1,
        operator: 'gte'
      },
      { 
        name: 'value', 
        label: 'Car Value', 
        type: 'price', 
        currency: 'AED',
        currency_type_id: 1,
        operator: 'gte'
      },
      { 
        name: 'make', 
        label: 'Car Make', 
        type: 'select_multiple', 
        options: 'carMakes',
        operator: 'in'
      },
      { 
        name: 'insurer', 
        label: 'Insurer', 
        type: 'select_multiple', 
        options: 'insurers',
        operator: 'in'
      }
    ]
  },
  {
    id: 2,
    name: 'health',
    label: 'Health',
    quote_type_id: 3,
    fields: [
      { 
        name: 'total_price', 
        label: 'Total Price', 
        type: 'price', 
        currency: 'AED',
        currency_type_id: 1,
        operator: 'gte'
      },
      { 
        name: 'insurer', 
        label: 'Insurer', 
        type: 'select_multiple', 
        options: 'insurers',
        operator: 'in'
      }
    ]
  },
  {
    id: 3,
    name: 'life',
    label: 'Life',
    quote_type_id: 4,
    fields: [
      { 
        name: 'sum_assured', 
        label: 'Sum Insured', 
        type: 'multi_currency',
        operator: 'gte',
        currencies: [
          { code: 'USD', currency_type_id: 2 },
          { code: 'AED', currency_type_id: 1 },
          { code: 'EUR', currency_type_id: 3 },
          { code: 'GBP', currency_type_id: 4 }
        ]
      },
      { 
        name: 'insurer', 
        label: 'Insurer', 
        type: 'select_multiple', 
        options: 'insurers',
        operator: 'in'
      }
    ]
  },
  {
    id: 4,
    name: 'home',
    label: 'Home',
    quote_type_id: 2,
    fields: [
      { 
        name: 'total_price', 
        label: 'Total Price', 
        type: 'price', 
        currency: 'AED',
        currency_type_id: 1,
        operator: 'gte'
      },
      { 
        name: 'insurer', 
        label: 'Insurer', 
        type: 'select_multiple', 
        options: 'insurers',
        operator: 'in'
      },
      { 
        name: 'location_area', 
        label: 'Location Area', 
        type: 'select_multiple', 
        options: 'locationAreas',
        operator: 'in'
      }
    ]
  },
  {
    id: 5,
    name: 'yacht',
    label: 'Yacht',
    quote_type_id: 7,
    fields: [
      { 
        name: 'total_price', 
        label: 'Total Price', 
        type: 'price', 
        currency: 'AED',
        currency_type_id: 1,
        operator: 'gte'
      },
      { 
        name: 'insurer', 
        label: 'Insurer', 
        type: 'select_multiple', 
        options: 'insurers',
        operator: 'in'
      }
    ]
  }
];

// Initialize the form dynamically based on insurance types
const initFormData = () => {
  let formData = {
    quote_type: '',
    department_id: '',
    segment: '',
  };
  
  // Add all fields for each insurance type
  insuranceTypes.forEach(type => {
    type.fields.forEach(field => {
      if (field.type === 'multi_currency') {
        field.currencies.forEach(currency => {
          formData[`${type.name}_${field.name}_${currency.code.toLowerCase()}`] = '';
        });
        formData[`${type.name}_${field.name}_enabled`] = false;
      } else {
        if (field.type === 'select_multiple') {
          formData[`${type.name}_${field.name}`] = [];
        } else if (field.type === 'select') {
          formData[`${type.name}_${field.name}`] = '';
        } else {
          formData[`${type.name}_${field.name}`] = '';
        }
        formData[`${type.name}_${field.name}_enabled`] = false;
      }
    });
  });
  
  return formData;
};

const configForm = useForm(initFormData());

const loader = ref(false);

// Initialize the form with existing configurations
onMounted(() => {
  if (props.configurations && props.configurations.length > 0) {
    // Process existing configurations and populate the form
    props.configurations.forEach(config => {
      insuranceTypes.forEach(type => {
        type.fields.forEach(field => {
          if (field.type === 'multi_currency' && config.field_name === `${type.name}_${field.name}`) {
            configForm[`${type.name}_${field.name}_enabled`] = config.status == true;
            
            field.currencies.forEach(currency => {
              if (config.currency_type_id === currency.currency_type_id) {
                configForm[`${type.name}_${field.name}_${currency.code.toLowerCase()}`] = config.value;
              }
            });
          } 
          else if (config.field_name === `${type.name}_${field.name}`) {
            if (field.type === 'select_multiple') {
              const values = config.value ? config.value.split(',') : [];
              configForm[`${type.name}_${field.name}`] = values;
            } else {
              configForm[`${type.name}_${field.name}`] = config.value;
            }
            
            configForm[`${type.name}_${field.name}_enabled`] = config.status == true;
          }
        });
      });
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
    } else if (configForm.car_total_price_enabled) {
      // Push with empty value if enabled but not filled in
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Total Price',
        field_name: 'car_total_price',
        operator: 'gte',
        value: '',
        currency_type_id: 1, // AED
        status: 1
      });
    } else {
      // Push with status 0 if not enabled
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Total Price',
        field_name: 'car_total_price',
        operator: 'gte',
        value: configForm.car_total_price || '',
        currency_type_id: 1, // AED
        status: 0
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
    } else if (configForm.car_value_enabled) {
      // Push with empty value if enabled but not filled in
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Value',
        field_name: 'car_value',
        operator: 'gte',
        value: '',
        currency_type_id: 1, // AED
        status: 1
      });
    } else {
      // Push with status 0 if not enabled
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Value',
        field_name: 'car_value',
        operator: 'gte',
        value: configForm.car_value || '',
        currency_type_id: 1, // AED
        status: 0
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Make',
        field_name: 'car_make',
        operator: 'in',
        value: configForm.car_make.length > 0 ? configForm.car_make.join(',') : '',
        status: configForm.car_make_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 1, // Car
        name: 'Car Insurer',
        field_name: 'car_insurer',
        operator: 'in',
        value: configForm.car_insurer.length > 0 ? configForm.car_insurer.join(',') : '',
        status: configForm.car_insurer_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled
      configurations.push({
        quote_type_id: 3, // Health
        name: 'Health Total Price',
        field_name: 'health_total_price',
        operator: 'gte',
        value: configForm.health_total_price || '',
        currency_type_id: 1, // AED
        status: configForm.health_total_price_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 3, // Health
        name: 'Health Insurer',
        field_name: 'health_insurer',
        operator: 'in',
        value: configForm.health_insurer.length > 0 ? configForm.health_insurer.join(',') : '',
        status: configForm.health_insurer_enabled ? 1 : 0
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
      } else {
        // Push with empty value if enabled but not filled in
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (USD)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: '',
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
      } else {
        // Push with empty value if enabled but not filled in
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (AED)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: '',
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
      } else {
        // Push with empty value if enabled but not filled in
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (EUR)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: '',
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
      } else {
        // Push with empty value if enabled but not filled in
        configurations.push({
          quote_type_id: 4, // Life
          name: 'Life Sum Assured (GBP)',
          field_name: 'life_sum_assured',
          operator: 'gte',
          value: '',
          currency_type_id: 4, // GBP
          status: 1
        });
      }
    } else {
      // If not enabled, push all with status 0
      configurations.push({
        quote_type_id: 4, // Life
        name: 'Life Sum Assured (USD)',
        field_name: 'life_sum_assured',
        operator: 'gte',
        value: configForm.life_sum_assured_usd || '',
        currency_type_id: 2, // USD
        status: 0
      });
      
      configurations.push({
        quote_type_id: 4, // Life
        name: 'Life Sum Assured (AED)',
        field_name: 'life_sum_assured',
        operator: 'gte',
        value: configForm.life_sum_assured_aed || '',
        currency_type_id: 1, // AED
        status: 0
      });
      
      configurations.push({
        quote_type_id: 4, // Life
        name: 'Life Sum Assured (EUR)',
        field_name: 'life_sum_assured',
        operator: 'gte',
        value: configForm.life_sum_assured_eur || '',
        currency_type_id: 3, // EUR
        status: 0
      });
      
      configurations.push({
        quote_type_id: 4, // Life
        name: 'Life Sum Assured (GBP)',
        field_name: 'life_sum_assured',
        operator: 'gte',
        value: configForm.life_sum_assured_gbp || '',
        currency_type_id: 4, // GBP
        status: 0
      });
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 4, // Life
        name: 'Life Insurer',
        field_name: 'life_insurer',
        operator: 'in',
        value: configForm.life_insurer.length > 0 ? configForm.life_insurer.join(',') : '',
        status: configForm.life_insurer_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled
      configurations.push({
        quote_type_id: 2, // Home
        name: 'Home Total Price',
        field_name: 'home_total_price',
        operator: 'gte',
        value: configForm.home_total_price || '',
        currency_type_id: 1, // AED
        status: configForm.home_total_price_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 2, // Home
        name: 'Home Insurer',
        field_name: 'home_insurer',
        operator: 'in',
        value: configForm.home_insurer.length > 0 ? configForm.home_insurer.join(',') : '',
        status: configForm.home_insurer_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 2, // Home
        name: 'Home Location Area',
        field_name: 'home_location_area',
        operator: 'in',
        value: configForm.home_location_area.length > 0 ? configForm.home_location_area.join(',') : '',
        status: configForm.home_location_area_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled
      configurations.push({
        quote_type_id: 7, // Yacht
        name: 'Yacht Total Price',
        field_name: 'yacht_total_price',
        operator: 'gte',
        value: configForm.yacht_total_price || '',
        currency_type_id: 1, // AED
        status: configForm.yacht_total_price_enabled ? 1 : 0
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
    } else {
      // Push with status 0 if not enabled or empty
      configurations.push({
        quote_type_id: 7, // Yacht
        name: 'Yacht Insurer',
        field_name: 'yacht_insurer',
        operator: 'in',
        value: configForm.yacht_insurer.length > 0 ? configForm.yacht_insurer.join(',') : '',
        status: configForm.yacht_insurer_enabled ? 1 : 0
      });
    }

    // Send data to server
    configForm.submit('post', route('admin.private-client-config.upsert'), {
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
</script>
<template>
  <Head title="Private client tag configuration" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Private Client Configuration</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <!-- Car section -->
    <p class="font-medium">Car</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Car Row 1: Total Price and Car Make -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Car Total Price -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.car_total_price_enabled" 
              class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input 
            v-model="configForm.car_total_price" 
            class="!mb-0" 
            :rules="configForm.car_total_price_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- Car Make -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.car_make_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Car Make</p>
          </div>
          <x-select 
            placeholder="Select Car Make" 
            :options="props.carMakes" 
            filterable 
            multiple
            v-model="configForm.car_make">
          </x-select>
        </div>
      </div>
      
      <!-- Car Row 2: Car Value and Insurer -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Car Value -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.car_value_enabled" 
              class="mr-2 h-4 w-4" />
            <p class="mr-2">Car Value</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input 
            v-model="configForm.car_value" 
            class="!mb-0" 
            :rules="configForm.car_value_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- Car Insurer -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.car_insurer_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select 
            placeholder="Select Insurer" 
            :options="props.insurers" 
            filterable 
            multiple
            v-model="configForm.car_insurer">
          </x-select>
        </div>
      </div>
    </div>
    
    <x-divider class="my-4" />
    
    <!-- Health section -->
    <p class="font-medium">Health</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Health Row: Total Price and Insurer -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Health Total Price -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.health_total_price_enabled" 
              class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input 
            v-model="configForm.health_total_price" 
            class="!mb-0" 
            :rules="configForm.health_total_price_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- Health Insurer -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.health_insurer_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select 
            placeholder="Select Insurer" 
            :options="props.insurers" 
            filterable 
            multiple
            v-model="configForm.health_insurer">
          </x-select>
        </div>
      </div>
    </div>
    
    <x-divider class="my-4" />
    
    <!-- Life section -->
    <p class="font-medium">Life</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Life Row 1: Sum Insured Currencies -->
      <div class="grid grid-cols-4 gap-4">
        <!-- USD -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.life_sum_assured_enabled" 
              class="mr-2 h-4 w-4" />
            <p class="mr-2">Sum Insured</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input 
            v-model="configForm.life_sum_assured_usd" 
            class="!mb-0" 
            :rules="configForm.life_sum_assured_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>USD</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- AED -->
        <div>
          <div class="mb-2">
            <p class="text-gray-500 opacity-0">placeholder</p>
          </div>
          <x-input 
            v-model="configForm.life_sum_assured_aed" 
            class="!mb-0" 
            :rules="configForm.life_sum_assured_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- EUR -->
        <div>
          <div class="mb-2">
            <p class="text-gray-500 opacity-0">placeholder</p>
          </div>
          <x-input 
            v-model="configForm.life_sum_assured_eur" 
            class="!mb-0" 
            :rules="configForm.life_sum_assured_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>EUR</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- GBP -->
        <div>
          <div class="mb-2">
            <p class="text-gray-500 opacity-0">placeholder</p>
          </div>
          <x-input 
            v-model="configForm.life_sum_assured_gbp" 
            class="!mb-0" 
            :rules="configForm.life_sum_assured_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>GBP</span>
              </div>
            </template>
          </x-input>
        </div>
      </div>
      
      <!-- Life Row 2: Insurer -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Life Insurer -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.life_insurer_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select 
            placeholder="Select Insurer" 
            :options="props.insurers" 
            filterable 
            multiple
            v-model="configForm.life_insurer">
          </x-select>
        </div>
      </div>
    </div>
    
    <x-divider class="my-4" />
    
    <!-- Home section -->
    <p class="font-medium">Home</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Home Row 1: Total Price and Insurer -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Home Total Price -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.home_total_price_enabled" 
              class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input 
            v-model="configForm.home_total_price" 
            class="!mb-0" 
            :rules="configForm.home_total_price_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- Home Insurer -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.home_insurer_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select 
            placeholder="Select Insurer" 
            :options="props.insurers" 
            filterable 
            multiple
            v-model="configForm.home_insurer">
          </x-select>
        </div>
      </div>
      
      <!-- Home Row 2: Location Area -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Home Location Area -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.home_location_area_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Location Area</p>
          </div>
          <x-select 
            placeholder="Select Location Area" 
            :options="props.locationAreas" 
            filterable 
            multiple
            v-model="configForm.home_location_area">
          </x-select>
        </div>
      </div>
    </div>
    
    <x-divider class="my-4" />
    
    <!-- Yacht section -->
    <p class="font-medium">Yacht</p>
    <div class="grid grid-cols-1 gap-4">
      <!-- Yacht Row: Total Price and Insurer -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Yacht Total Price -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.yacht_total_price_enabled" 
              class="mr-2 h-4 w-4" />
            <p class="mr-2">Total Price</p>
            <p class="text-gray-500">(Greater than or equal to)</p>
          </div>
          <x-input 
            v-model="configForm.yacht_total_price" 
            class="!mb-0" 
            :rules="configForm.yacht_total_price_enabled ? [isRequiredNumber] : []">
            <template #suffix>
              <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                <span>AED</span>
              </div>
            </template>
          </x-input>
        </div>
        
        <!-- Yacht Insurer -->
        <div>
          <div class="flex items-center mb-2">
            <input type="checkbox" 
              v-model="configForm.yacht_insurer_enabled" 
              class="mr-2 h-4 w-4" />
            <p>Insurer</p>
          </div>
          <x-select 
            placeholder="Select Insurer" 
            :options="props.insurers" 
            filterable 
            multiple
            v-model="configForm.yacht_insurer">
          </x-select>
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