<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  configurations: Array,
  insurers: Array,
  carMakes: Array,
  locationAreas: Array,
  allVersions: Array,
  selectedVersion: Number,
  isCurrentVersion: Boolean,
});

const page = usePage();

const { isRequired } = useRules();
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;

// Custom validation for numbers with commas
const isRequiredNumber = (value) => {
  if (!value) return 'This field is required';
  // Remove commas and check if it's a valid number
  const num = Number(value.toString().replace(/,/g, ''));
  return !isNaN(num) ? true : 'This field must be a number';
};

// Function to change version
const changeVersion = version => {
  window.location.href = route('admin.private-client-config.show', { version });
};

// Helper function to chunk array into groups
const chunkArray = (array, size) => {
  return Array.from({ length: Math.ceil(array.length / size) }, (_, i) =>
    array.slice(i * size, i * size + size),
  );
};

// Computed property to check if viewing the latest version
const isCurrentVersion = props.isCurrentVersion;

// Create a group check for sum assured fields
const sumAssuredEnabled = ref(false);

// Helper function to validate at least one sum assured field is filled
const validateAtLeastOneSum = value => {
  if (!sumAssuredEnabled.value) return true; // If not enabled, no validation needed

  // Check if we have at least one filled Sum Insured field
  const hasValue =
    configForm['life_sum_insured_value_usd'] ||
    configForm['life_sum_insured_value_aed'] ||
    configForm['life_sum_insured_value_gbp'] ||
    configForm['life_sum_insured_value_eur'];

  if (!hasValue) {
    return 'At least one Sum Insured field must be filled';
  }

  // Validate that all filled fields are numeric
  const fields = {
    USD: configForm['life_sum_insured_value_usd'],
    AED: configForm['life_sum_insured_value_aed'],
    GBP: configForm['life_sum_insured_value_gbp'],
    EUR: configForm['life_sum_insured_value_eur'],
  };

  // Check each field, if it has a value, validate it's numeric
  for (const [currency, fieldValue] of Object.entries(fields)) {
    if (fieldValue) {
      // Remove commas and check if value is numeric
      const num = Number(fieldValue.toString().replace(/,/g, ''));
      if (isNaN(num)) {
        return `Sum Insured (${currency}) must be a number`;
      }
    }
  }

  return true;
};

// Create individual validation rules for each sum assured field
const validateIfEnabled = fieldName => value => {
  // If the field's enabled checkbox is checked, make the field required
  if (configForm[`${fieldName}_enabled`]) {
    return value ? true : 'This field is required';
  }
  return true;
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
        name: 'premium',
        uiName: 'car_premium',
        label: 'Total Price',
        type: 'price',
        currency: 'AED',
        operator: '>=',
      },
      {
        name: 'car_value',
        uiName: 'car_value',
        label: 'Car Value',
        type: 'price',
        currency: 'AED',
        operator: '>=',
      },
      {
        name: 'car_make_id',
        uiName: 'car_make_id',
        label: 'Car Make',
        type: 'select_multiple',
        options: 'carMakes',
        operator: 'in',
      },
      {
        name: 'insurance_provider_id',
        uiName: 'car_insurer',
        label: 'Insurer',
        type: 'select_multiple',
        options: 'insurers',
        operator: 'in',
      },
    ],
  },
  {
    id: 2,
    name: 'health',
    label: 'Health',
    quote_type_id: 3,
    fields: [
      {
        name: 'premium',
        uiName: 'health_premium',
        label: 'Total Price',
        type: 'price',
        currency: 'AED',
        operator: '>=',
      },
      {
        name: 'insurance_provider_id',
        uiName: 'health_insurer',
        label: 'Insurer',
        type: 'select_multiple',
        options: 'insurers',
        operator: 'in',
      },
    ],
  },
  {
    id: 3,
    name: 'life',
    label: 'Life',
    quote_type_id: 4,
    fields: [
      {
        name: 'sum_insured_value',
        uiName: 'sum_insured_value_usd',
        label: 'Sum Insured (USD)',
        currency: 'USD',
        type: 'numeric',
        operator: '>=',
        currency_type_id: 1,
      },
      {
        name: 'sum_insured_value',
        uiName: 'sum_insured_value_aed',
        label: 'Sum Insured (AED)',
        currency: 'AED',
        type: 'numeric',
        operator: '>=',
        currency_type_id: 2,
      },
      {
        name: 'sum_insured_value',
        uiName: 'sum_insured_value_gbp',
        label: 'Sum Insured (GBP)',
        currency: 'GBP',
        type: 'numeric',
        operator: '>=',
        currency_type_id: 3,
      },
      {
        name: 'sum_insured_value',
        uiName: 'sum_insured_value_eur',
        label: 'Sum Insured (EUR)',
        currency: 'EUR',
        type: 'numeric',
        operator: '>=',
        currency_type_id: 4,
      },
      {
        name: 'insurer',
        uiName: 'life_insurer',
        label: 'Insurer',
        type: 'select_multiple',
        options: 'insurers',
        operator: 'in',
      },
    ],
  },
  {
    id: 4,
    name: 'home',
    label: 'Home',
    quote_type_id: 2,
    fields: [
      {
        name: 'premium',
        uiName: 'home_premium',
        label: 'Total Price',
        type: 'price',
        currency: 'AED',
        operator: '>=',
      },
      {
        name: 'insurance_provider_id',
        uiName: 'home_insurer',
        label: 'Insurer',
        type: 'select_multiple',
        options: 'insurers',
        operator: 'in',
      },
      {
        name: 'sub_area_id',
        uiName: 'home_sub_area_id',
        label: 'Location Area',
        type: 'select_multiple',
        options: 'locationAreas',
        operator: 'in',
      },
    ],
  },
  {
    id: 5,
    name: 'yacht',
    label: 'Yacht',
    quote_type_id: 7,
    fields: [
      {
        name: 'premium',
        uiName: 'yacht_premium',
        label: 'Total Price',
        type: 'price',
        currency: 'AED',
        operator: '>=',
      },
      {
        name: 'insurance_provider_id',
        uiName: 'yacht_insurer',
        label: 'Insurer',
        type: 'select_multiple',
        options: 'insurers',
        operator: 'in',
      },
    ],
  },
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
          // For life insurance sum_insured_value, we need to match by field name and currency
          if (
            type.name === 'life' &&
            field.name === 'sum_insured_value' &&
            config.field_name === field.name
          ) {
            // Match by currency_type_id
            if (config.currency_type_id === field.currency_type_id) {
              const formFieldName = `${type.name}_${field.uiName || field.name}`;
              configForm[formFieldName] = config.value;
              configForm[`${formFieldName}_enabled`] = config.status == true;

              if (config.status) {
                sumAssuredEnabled.value = true;
              }
            }
          }
          // For all other fields
          else if (
            config.field_name === field.name &&
            config.quote_type_id === type.quote_type_id
          ) {
            const formFieldName = `${type.name}_${field.uiName || field.name}`;

            if (field.type === 'select_multiple') {
              // Split the value string into an array of IDs
              const values = config.value ? config.value.split(',').map(v => parseInt(v.trim())) : [];
              configForm[formFieldName] = values;
            } else {
              configForm[formFieldName] = config.value;
            }

            configForm[`${formFieldName}_enabled`] = config.status == true;
          }
        });
      });
    });
  }
});

// Add number formatting functions
const formatNumber = (value) => {
  if (!value) return '';
  // Remove any existing commas and convert to number
  const num = Number(value.toString().replace(/,/g, ''));
  if (isNaN(num)) return value;
  // Format with commas
  return num.toLocaleString();
};

const parseNumber = (value) => {
  if (!value) return '';
  // Remove commas and return the number as string
  return value.toString().replace(/,/g, '');
};

const onSubmit = isValid => {
  if (isValid) {
    loader.value = true;

    // Prepare configurations array
    const configurations = [];

    // Loop through insurance types and their fields to create configurations
    insuranceTypes.forEach(type => {
      type.fields.forEach(field => {
        const formFieldName = `${type.name}_${field.uiName || field.name}`;
        const isEnabled = configForm[`${formFieldName}_enabled`];
        let value = configForm[formFieldName];

        // Format value based on field type
        if (field.type === 'select_multiple' && Array.isArray(value)) {
          // Ensure all values are properly converted to strings
          value = value.map(v => v.toString()).join(',');
        }

        // Create configuration object
        const configItem = {
          quote_type_id: type.quote_type_id,
          name: `${type.label} ${field.label}`,
          field_name: field.name,
          operator: field.operator,
          value: value,
          currency_type_id: field.currency_type_id || null,
          status: isEnabled ? 1 : 0,
        };

        configurations.push(configItem);
      });
    });

    // Send data to server
    configForm
      .transform(data => ({
        ...data,
        configurations,
      }))
      .post(route('admin.private-client-config.upsert'), {
        onSuccess: () => {
          loader.value = false;
          // After successful save, redirect to the latest version
          if (window.location.search.includes('version=')) {
            window.location.href = route('admin.private-client-config.show');
          }
        },
        onError: () => (loader.value = false),
      });
  }
};
</script>
<template>
  <Head title="Private client tag configuration" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Private Client Configuration</h2>
  </div>

  <!-- Version selector -->
  <div
    v-if="hasAnyRole([rolesEnum.Engineering])"
    class="bg-gray-50 p-4 rounded-md my-4"
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <span class="font-medium">Criteria Version:</span>
        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{
          selectedVersion
        }}</span>
      </div>
      <div class="flex items-center">
        <span class="mr-2">Select Version:</span>
        <x-select
          :modelValue="selectedVersion"
          :options="allVersions.map(v => ({ value: v, label: `Version ${v}` }))"
          @update:modelValue="changeVersion"
          class="w-40"
        />
      </div>
    </div>
  </div>

  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <!-- Loop through each insurance type -->
    <template
      v-for="(insuranceType, index) in insuranceTypes"
      :key="insuranceType.id"
    >
      <!-- Insurance Type Title -->
      <p class="font-medium">{{ insuranceType.label }}</p>
      <div class="grid grid-cols-1 gap-4">
        <!-- Special handling for Life insurance Sum Assured fields -->
        <template v-if="insuranceType.name === 'life'">
          <div class="grid grid-cols-1 gap-4 mb-4">
            <!-- Common heading for all sum assured fields -->
            <div class="flex items-center mb-2">
              <input
                type="checkbox"
                v-model="sumAssuredEnabled"
                class="mr-2 h-4 w-4"
                @change="
                  e => {
                    // Set all sum assured fields to the same enabled state
                    configForm['life_sum_insured_value_usd_enabled'] =
                      e.target.checked;
                    configForm['life_sum_insured_value_aed_enabled'] =
                      e.target.checked;
                    configForm['life_sum_insured_value_gbp_enabled'] =
                      e.target.checked;
                    configForm['life_sum_insured_value_eur_enabled'] =
                      e.target.checked;
                  }
                "
                :disabled="!isCurrentVersion"
              />
              <p class="mr-2">Sum Insured</p>
              <p class="text-gray-500">(At least one required)</p>
            </div>

            <!-- Grid for sum assured fields -->
            <div class="grid grid-cols-4 gap-4">
              <div>
                <x-input
                  :modelValue="formatNumber(configForm['life_sum_insured_value_usd'])"
                  @update:modelValue="val => configForm['life_sum_insured_value_usd'] = parseNumber(val)"
                  class="!mb-0"
                  :rules="[validateAtLeastOneSum]"
                  :disabled="!isCurrentVersion"
                >
                  <template #suffix>
                    <div
                      class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                    >
                      <span>USD</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div>
                <x-input
                  :modelValue="formatNumber(configForm['life_sum_insured_value_aed'])"
                  @update:modelValue="val => configForm['life_sum_insured_value_aed'] = parseNumber(val)"
                  class="!mb-0"
                  :rules="[validateAtLeastOneSum]"
                  :disabled="!isCurrentVersion"
                >
                  <template #suffix>
                    <div
                      class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                    >
                      <span>AED</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div>
                <x-input
                  :modelValue="formatNumber(configForm['life_sum_insured_value_gbp'])"
                  @update:modelValue="val => configForm['life_sum_insured_value_gbp'] = parseNumber(val)"
                  class="!mb-0"
                  :rules="[validateAtLeastOneSum]"
                  :disabled="!isCurrentVersion"
                >
                  <template #suffix>
                    <div
                      class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                    >
                      <span>GBP</span>
                    </div>
                  </template>
                </x-input>
              </div>
              <div>
                <x-input
                  :modelValue="formatNumber(configForm['life_sum_insured_value_eur'])"
                  @update:modelValue="val => configForm['life_sum_insured_value_eur'] = parseNumber(val)"
                  class="!mb-0"
                  :rules="[validateAtLeastOneSum]"
                  :disabled="!isCurrentVersion"
                >
                  <template #suffix>
                    <div
                      class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                    >
                      <span>EUR</span>
                    </div>
                  </template>
                </x-input>
              </div>
            </div>
          </div>
        </template>

        <!-- For each group of 2 fields, create a row -->
        <template
          v-for="(fieldsChunk, chunkIndex) in chunkArray(
            insuranceType.fields,
            2,
          )"
          :key="`${insuranceType.name}_chunk_${chunkIndex}`"
        >
          <div class="grid grid-cols-2 gap-4">
            <!-- Loop through each field in the chunk -->
            <template
              v-for="(field, fieldIndex) in fieldsChunk"
              :key="`${insuranceType.name}_${field.name}_${fieldIndex}`"
            >
              <!-- Skip sum assured fields in life insurance as they're handled separately -->
              <template
                v-if="
                  !(
                    insuranceType.name === 'life' &&
                    field.name === 'sum_insured_value'
                  )
                "
              >
                <div v-if="field.type !== 'select_multiple'">
                  <div v-if="field.label" class="flex items-center mb-2">
                    <input
                      type="checkbox"
                      v-model="
                        configForm[
                          `${insuranceType.name}_${field.uiName || field.name}_enabled`
                        ]
                      "
                      class="mr-2 h-4 w-4"
                      :disabled="!isCurrentVersion"
                    />
                    <p class="mr-2">{{ field.label }}</p>
                    <p v-if="field.operator === '>='" class="text-gray-500">
                      (Greater than or equal to)
                    </p>
                  </div>
                  <div v-else></div>
                  <x-input
                    :modelValue="formatNumber(configForm[`${insuranceType.name}_${field.uiName || field.name}`])"
                    @update:modelValue="val => configForm[`${insuranceType.name}_${field.uiName || field.name}`] = parseNumber(val)"
                    class="!mb-0"
                    :rules="
                      configForm[
                        `${insuranceType.name}_${field.uiName || field.name}_enabled`
                      ]
                        ? [isRequired, isRequiredNumber]
                        : []
                    "
                    :disabled="!isCurrentVersion"
                  >
                    <template #suffix v-if="field.currency">
                      <div
                        class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                      >
                        <span>{{ field.currency }}</span>
                      </div>
                    </template>
                  </x-input>
                </div>

                <!-- Selection fields -->
                <div v-else-if="field.type === 'select_multiple'">
                  <div class="flex items-center mb-2">
                    <input
                      type="checkbox"
                      v-model="
                        configForm[
                          `${insuranceType.name}_${field.uiName || field.name}_enabled`
                        ]
                      "
                      class="mr-2 h-4 w-4"
                      :disabled="!isCurrentVersion"
                    />
                    <p>{{ field.label }}</p>
                  </div>
                  <x-select
                    :placeholder="`Select ${field.label}`"
                    :options="props[field.options]"
                    filterable
                    multiple
                    v-model="
                      configForm[
                        `${insuranceType.name}_${field.uiName || field.name}`
                      ]
                    "
                    :rules="
                      configForm[
                        `${insuranceType.name}_${field.uiName || field.name}_enabled`
                      ]
                        ? [
                            v =>
                              v && v.length ? true : 'This field is required',
                          ]
                        : []
                    "
                    :disabled="!isCurrentVersion"
                  >
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
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="loader"
        :disabled="loader || !isCurrentVersion"
      >
        Save
      </x-button>
    </div>
  </x-form>

  <!-- Warning when viewing historical version -->
  <div v-if="!isCurrentVersion" class="flex items-center text-amber-600 ml-2">
    <i class="ri-error-warning-line mr-1"></i>
    <span>You are viewing a historical version. Form fields are disabled.</span>
  </div>
</template>
