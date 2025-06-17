<script setup>
import { ref } from 'vue';

const props = defineProps({
  insurers: Array,
  carMakes: Array,
  nationalities: Array,
  currencies: Array,
  disabled: {
    type: Boolean,
    default: false,
  },
});

const carFields = [
  {
    fieldName: 'price_with_vat',
    label: 'Total Price',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'car_value',
    label: 'Car Value',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'car_make_id',
    label: 'Car Make',
    type: 'select_multiple',
    options: 'carMakes',
    operator: 'in',
    hasCurrency: false,
  },
  {
    fieldName: 'insurance_provider_id',
    label: 'Insurer',
    type: 'select_multiple',
    options: 'insurers',
    operator: 'in',
    hasCurrency: false,
  },
];

const createBlankProfile = field => ({
  fieldName: field.fieldName,
  operator: field.operator,
  value: field.type === 'select_multiple' ? [] : '',
  currencyTypeId: field.hasCurrency ? 1 : null, // Default to first currency
  nationalityIds: [],
});

const profiles = ref([]);

// Initialize profiles for each field
carFields.forEach(field => {
  profiles.value.push(createBlankProfile(field));
});

const addProfile = field => {
  profiles.value.push(createBlankProfile(field));
};

const removeProfile = index => {
  if (profiles.value.length > carFields.length) {
    profiles.value.splice(index, 1);
  }
};

const getProfilesForField = fieldName => {
  return profiles.value.filter(profile => profile.fieldName === fieldName);
};

const getFieldConfig = fieldName => {
  return carFields.find(field => field.fieldName === fieldName);
};

// Expose method to parent component
const getProfiles = () => {
  return profiles.value.filter(profile => {
    // Only return profiles that have some data
    if (Array.isArray(profile.value)) {
      return profile.value.length > 0 && profile.nationalityIds.length > 0;
    }
    return profile.value && profile.nationalityIds.length > 0;
  });
};

// Expose the method to parent
defineExpose({
  getProfiles,
});
</script>

<template>
  <div>
    <h3 class="text-lg font-semibold mb-4">Car Criteria</h3>
    <div class="space-y-8">
      <!-- Loop through each field type -->
      <template v-for="field in carFields" :key="field.fieldName">
        <div class="bg-gray-50 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-medium">{{ field.label }}</h4>
            <x-button
              size="sm"
              color="primary"
              @click="addProfile(field)"
              type="button"
              :disabled="disabled"
            >
              <i class="ri-add-line mr-1"></i> Add Profile
            </x-button>
          </div>

          <!-- Profiles for this field -->
          <div class="space-y-4">
            <template
              v-for="(profile, profileIndex) in getProfilesForField(
                field.fieldName,
              )"
              :key="profileIndex"
            >
              <div
                class="bg-white rounded-lg shadow p-4 border border-gray-200"
              >
                <div class="flex justify-between items-center mb-4">
                  <span class="text-sm font-medium text-gray-600"
                    >Profile {{ profileIndex + 1 }}</span
                  >
                  <button
                    v-if="getProfilesForField(field.fieldName).length > 1"
                    @click="removeProfile(profiles.indexOf(profile))"
                    class="text-red-500 hover:text-red-700 text-sm flex items-center"
                    type="button"
                    :disabled="disabled"
                  >
                    <i class="ri-delete-bin-line mr-1"></i> Remove
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <!-- Nationalities -->
                  <div>
                    <label class="block text-sm font-medium mb-1"
                      >Nationalities</label
                    >
                    <x-select
                      v-model="profile.nationalityIds"
                      :options="props.nationalities"
                      placeholder="Select nationalities"
                      filterable
                      multiple
                      :disabled="disabled"
                    />
                  </div>

                  <!-- Value -->
                  <div>
                    <label class="block text-sm font-medium mb-1">
                      Value
                      {{
                        field.operator === '>='
                          ? '(Greater than or equal to)'
                          : ''
                      }}
                    </label>
                    <template v-if="field.type === 'select_multiple'">
                      <x-select
                        v-model="profile.value"
                        :options="props[field.options]"
                        :placeholder="`Select ${field.label}`"
                        filterable
                        multiple
                        :disabled="disabled"
                      />
                    </template>
                    <template v-else>
                      <x-input
                        v-model="profile.value"
                        :placeholder="field.label"
                        type="text"
                        :disabled="disabled"
                      />
                    </template>
                  </div>

                  <!-- Currency (only for numeric fields) -->
                  <div v-if="field.hasCurrency">
                    <label class="block text-sm font-medium mb-1"
                      >Currency</label
                    >
                    <x-select
                      v-model="profile.currencyTypeId"
                      :options="props.currencies"
                      placeholder="Select currency"
                      filterable
                      :disabled="disabled"
                    />
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
/* Add any additional styling if needed */
</style>
