<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  insurers: Array,
  nationalities: Array,
  currencies: Array,
  disabled: {
    type: Boolean,
    default: false,
  },
});

const healthFields = [
  {
    fieldName: 'price_with_vat',
    label: 'Total Price',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
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

const createBlankProfile = () => {
  const profile = {
    nationalityIds: [],
  };

  // Add all health fields to the profile
  healthFields.forEach(field => {
    if (field.type === 'select_multiple') {
      profile[field.fieldName] = [];
    } else {
      profile[field.fieldName] = '';
    }

    if (field.hasCurrency) {
      profile[`${field.fieldName}_currency_id`] = 1; // Default to first currency
    }
  });

  return profile;
};

const profiles = ref([createBlankProfile()]);

const addProfile = () => {
  profiles.value.push(createBlankProfile());
};

const removeProfile = index => {
  if (profiles.value.length > 1) {
    profiles.value.splice(index, 1);
  }
};

// Load existing configuration when component mounts
const loadExistingConfig = async () => {
  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: { quote_type_id: 3 }, // Health quote type ID
      },
    );

    if (response.data.config && response.data.config.profiles) {
      const loadedProfiles = response.data.config.profiles.map(profile => {
        const formattedProfile = {
          nationalityIds: profile.nationalityIds || [],
        };

        healthFields.forEach(field => {
          formattedProfile[field.fieldName] =
            profile[field.fieldName] ||
            (field.type === 'select_multiple' ? [] : '');
          if (field.hasCurrency) {
            formattedProfile[`${field.fieldName}_currency_id`] =
              profile[`${field.fieldName}_currency_id`] || 1;
          }
        });

        return formattedProfile;
      });

      profiles.value =
        loadedProfiles.length > 0 ? loadedProfiles : [createBlankProfile()];
    }
  } catch (error) {
    console.error('Error loading existing config:', error);
  }
};

onMounted(() => {
  loadExistingConfig();
});

// Expose method to parent component
const getProfiles = () => {
  return profiles.value
    .filter(profile => {
      // Check if profile has nationality and at least one field filled
      if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
        return false;
      }

      return healthFields.some(field => {
        const value = profile[field.fieldName];
        if (Array.isArray(value)) {
          return value.length > 0;
        }
        return value && value.toString().trim() !== '';
      });
    })
    .map(profile => {
      const cleanProfile = {
        nationalityIds: profile.nationalityIds,
      };

      healthFields.forEach(field => {
        cleanProfile[field.fieldName] = profile[field.fieldName];
        if (field.hasCurrency) {
          cleanProfile[`${field.fieldName}_currency_id`] =
            profile[`${field.fieldName}_currency_id`];
        }
      });

      return cleanProfile;
    });
};

// Expose the method to parent
defineExpose({
  getProfiles,
  loadExistingConfig,
});
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Health Criteria</h3>
      <x-button
        size="sm"
        color="primary"
        @click="addProfile()"
        type="button"
        :disabled="disabled"
      >
        <i class="ri-add-line mr-1"></i> Add Profile
      </x-button>
    </div>

    <div class="space-y-6">
      <!-- Loop through each profile -->
      <template v-for="(profile, profileIndex) in profiles" :key="profileIndex">
        <div class="bg-gray-50 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-medium">Profile {{ profileIndex + 1 }}</h4>
            <button
              v-if="profiles.length > 1"
              @click="removeProfile(profileIndex)"
              class="text-red-500 hover:text-red-700 text-sm flex items-center"
              type="button"
              :disabled="disabled"
            >
              <i class="ri-delete-bin-line mr-1"></i> Remove Profile
            </button>
          </div>

          <!-- Nationalities (applies to whole profile) -->
          <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Nationalities</label>
            <x-select
              v-model="profile.nationalityIds"
              :options="props.nationalities"
              placeholder="Select nationalities"
              filterable
              multiple
              :disabled="disabled"
              class="w-full"
            />
          </div>

          <!-- All Health Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template v-for="field in healthFields" :key="field.fieldName">
              <div class="bg-white rounded-lg p-4 border border-gray-200">
                <label class="block text-sm font-medium mb-2">
                  {{ field.label }}
                  <span v-if="field.operator === '>='"> (≥)</span>
                  <span v-if="field.operator === 'in'"> (Select multiple)</span>
                </label>

                <div class="space-y-3">
                  <!-- Field Value -->
                  <template v-if="field.type === 'select_multiple'">
                    <x-select
                      v-model="profile[field.fieldName]"
                      :options="props[field.options]"
                      :placeholder="`Select ${field.label}`"
                      filterable
                      multiple
                      :disabled="disabled"
                    />
                  </template>
                  <template v-else>
                    <x-input
                      v-model="profile[field.fieldName]"
                      :placeholder="field.label"
                      type="text"
                      :disabled="disabled"
                    />
                  </template>

                  <!-- Currency (only for numeric fields) -->
                  <template v-if="field.hasCurrency">
                    <div>
                      <label class="block text-xs text-gray-500 mb-1"
                        >Currency</label
                      >
                      <x-select
                        v-model="profile[`${field.fieldName}_currency_id`]"
                        :options="props.currencies"
                        placeholder="Select currency"
                        filterable
                        :disabled="disabled"
                        size="sm"
                      />
                    </div>
                  </template>
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
