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

// Helper function to chunk array into groups
const chunkArray = (array, size) => {
  return Array.from({ length: Math.ceil(array.length / size) }, (_, i) =>
    array.slice(i * size, i * size + size)
  );
};

// Create a group check for sum assured fields
const sumAssuredEnabled = ref(false);

// Helper function to validate at least one sum assured field is filled
const validateAtLeastOneSum = value => {
  if (!sumAssuredEnabled.value) return true; // If not enabled, no validation needed
  
  const hasValue = 
    configForm['life_sum_assured_usd'] || 
    configForm['life_sum_assured_aed'] || 
    configForm['life_sum_assured_gpb'] || 
    configForm['life_sum_assured_eur'];
  
  return !!hasValue || 'At least one Sum Insured field must be filled';
};

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
        operator: '>='
      },
      {
        name: 'value',
        label: 'Car Value',
        type: 'price',
        currency: 'AED',
        operator: '>='
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
        operator: '>='
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
        name: 'sum_assured_usd',
        label: 'Sum Insured',
        currency: 'USD',
        type: 'numeric',
        operator: '>=',
        currency_type_id: 1,
      },
      {
        name: 'sum_assured_aed',
        type: 'numeric',
        currency: 'AED',
        operator: '>=',
        currency_type_id: 1,
      },
      {
        name: 'sum_assured_gpb',
        type: 'numeric',
        currency: 'GBP',
        operator: '>=',
        currency_type_id: 1,
      },
      {
        name: 'sum_assured_eur',
        type: 'numeric',
        currency: 'EUR',
        operator: '>=',
        currency_type_id: 1
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
        operator: '>='
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
        operator: '>='
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
  let formData = {};

  // Add all fields for each insurance type
  insuranceTypes.forEach(type => {
    type.fields.forEach(field => {
      if (field.type === 'select_multiple') {
        formData[`${type.name}_${field.name}`] = [];
      } else if (field.type === 'select') {
        formData[`${type.name}_${field.name}`] = '';
      } else {
        formData[`${type.name}_${field.name}`] = '';
      }
      formData[`${type.name}_${field.name}_enabled`] = false;

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
          if (config.field_name === `${type.name}_${field.name}`) {
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

    // Loop through insurance types and their fields to create configurations
    insuranceTypes.forEach(type => {
      type.fields.forEach(field => {
        const isEnabled = configForm[`${type.name}_${field.name}_enabled`];
        let value = configForm[`${type.name}_${field.name}`];

        // Format value based on field type
        if (field.type === 'select_multiple' && Array.isArray(value)) {
          value = value.length > 0 ? value.join(',') : '';
        }

        // Only add configuration if value is not blank
        if (value !== '' && value !== null && value !== undefined) {
          // Create configuration object
          const configItem = {
            quote_type_id: type.quote_type_id,
            name: `${type.label} ${field.label}`,
            field_name: `${type.name}_${field.name}`,
            operator: field.operator,
            value: value,
            currency_type_id: field.currency_type_id || null,
            status: isEnabled ? 1 : 0
          };

          configurations.push(configItem);
        }
      });
    });
    
    // Send data to server
    configForm.transform(data => ({
      ...data,
      configurations
    })).post(route('admin.private-client-config.upsert'), {
      onSuccess: () => loader.value = false,
      onError: () => loader.value = false
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
    <!-- Loop through each insurance type -->
    <template v-for="(insuranceType, index) in insuranceTypes" :key="insuranceType.id">
      <!-- Insurance Type Title -->
      <p class="font-medium">{{ insuranceType.label }}</p>
      <div class="grid grid-cols-1 gap-4">
        <!-- Special handling for Life insurance Sum Assured fields -->
        <template v-if="insuranceType.name === 'life'">
          <div class="grid grid-cols-1 gap-4 mb-4">
            <!-- Common heading for all sum assured fields -->
            <div class="flex items-center mb-2">
              <input type="checkbox" v-model="sumAssuredEnabled" class="mr-2 h-4 w-4" 
                @change="e => {
                  // Set all sum assured fields to the same enabled state
                  configForm['life_sum_assured_usd_enabled'] = e.target.checked;
                  configForm['life_sum_assured_aed_enabled'] = e.target.checked;
                  configForm['life_sum_assured_gpb_enabled'] = e.target.checked;
                  configForm['life_sum_assured_eur_enabled'] = e.target.checked;
                }" />
              <p class="mr-2">Sum Insured</p>
              <p class="text-gray-500">(At least one required)</p>
            </div>
            
            <!-- Grid for sum assured fields -->
            <div class="grid grid-cols-4 gap-4">
              <div>
                <x-input v-model="configForm['life_sum_assured_usd']" class="!mb-0"
                  :rules="sumAssuredEnabled ? [validateAtLeastOneSum] : []">
                  <template #suffix>
                    <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                      <span>USD</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div>
                <x-input v-model="configForm['life_sum_assured_aed']" class="!mb-0" :rules="sumAssuredEnabled ? [validateAtLeastOneSum] : []">
                  <template #suffix>
                    <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                      <span>AED</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div>
                <x-input v-model="configForm['life_sum_assured_gpb']" class="!mb-0" :rules="sumAssuredEnabled ? [validateAtLeastOneSum] : []">
                  <template #suffix>
                    <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                      <span>GBP</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div>
                <x-input v-model="configForm['life_sum_assured_eur']" class="!mb-0" :rules="sumAssuredEnabled ? [validateAtLeastOneSum] : []">
                  <template #suffix>
                    <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                      <span>EUR</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div></div>
            </div>
          </div>
        </template>
        
        <!-- For each group of 2 fields, create a row -->
        <template v-for="(fieldsChunk, chunkIndex) in chunkArray(insuranceType.fields, 2)"
          :key="`${insuranceType.name}_chunk_${chunkIndex}`">
          <div class="grid grid-cols-2 gap-4">
            <!-- Loop through each field in the chunk -->
            <template v-for="(field, fieldIndex) in fieldsChunk"
              :key="`${insuranceType.name}_${field.name}_${fieldIndex}`">
              <!-- Skip sum assured fields in life insurance as they're handled separately -->
              <template v-if="!(insuranceType.name === 'life' && field.name.startsWith('sum_assured_'))">
                <div v-if="field.type !== 'select_multiple'">
                  <div v-if="field.label" class="flex items-center mb-2">
                    <input type="checkbox" v-model="configForm[`${insuranceType.name}_${field.name}_enabled`]"
                      class="mr-2 h-4 w-4" />
                    <p class="mr-2">{{ field.label }}</p>
                    <p v-if="field.operator === '>='" class="text-gray-500">(Greater than or equal to)</p>
                  </div>
                  <div v-else></div>
                  <x-input v-model="configForm[`${insuranceType.name}_${field.name}`]" class="!mb-0"
                    :rules="configForm[`${insuranceType.name}_${field.name}_enabled`] ? [isRequiredNumber] : []">
                    <template #suffix v-if="field.currency">
                      <div class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400">
                        <span>{{ field.currency }}</span>
                      </div>
                    </template>
                  </x-input>
                </div>

                <!-- Selection fields -->
                <div v-else-if="field.type === 'select_multiple'">
                  <div class="flex items-center mb-2">
                    <input type="checkbox" v-model="configForm[`${insuranceType.name}_${field.name}_enabled`]"
                      class="mr-2 h-4 w-4" />
                    <p>{{ field.label }}</p>
                  </div>
                  <x-select :placeholder="`Select ${field.label}`" :options="props[field.options]" filterable multiple
                    v-model="configForm[`${insuranceType.name}_${field.name}`]">
                  </x-select>
                </div>
              </template>
            </template>
          </div>
        </template>
      </div>

      <!-- Add divider between insurance types unless it's the last one -->
      <x-divider class="my-4" v-if="index < insuranceTypes.length - 1" />
    </template>
    <AuditLogs :quoteType="'PrivateClientConfig'" />
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit" :loading="loader" :disabled="loader">
        Update
      </x-button>
    </div>
  </x-form>
</template>