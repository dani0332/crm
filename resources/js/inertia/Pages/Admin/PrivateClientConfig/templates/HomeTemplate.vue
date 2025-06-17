<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['versionLoaded']);

// Dynamic dropdown data
const dropdownData = ref({
  nationalities: [],
  currencies: [],
  insurers: [],
  locationAreas: [],
});

const homeFields = [
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
  {
    fieldName: 'sub_area_id',
    label: 'Location Area',
    type: 'select_multiple',
    options: 'locationAreas',
    operator: 'in',
    hasCurrency: false,
  },
];

const createBlankProfile = () => {
  const profile = {
    nationalityIds: [],
  };

  homeFields.forEach(field => {
    if (field.type === 'select_multiple') {
      profile[field.fieldName] = [];
    } else {
      profile[field.fieldName] = '';
    }

    if (field.hasCurrency) {
      profile[`${field.fieldName}_currency_id`] = 1;
    }
  });

  return profile;
};

const profiles = ref([createBlankProfile()]);

const addProfile = async () => {
  profiles.value.push(createBlankProfile());

  // Scroll to the new profile with animation
  await nextTick();
  const profileElements = document.querySelectorAll('[data-profile-card]');
  const lastProfile = profileElements[profileElements.length - 1];

  if (lastProfile) {
    // Add highlight animation
    lastProfile.classList.add('animate-pulse', 'ring-2', 'ring-blue-400');

    // Scroll to the new profile
    lastProfile.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });

    // Remove animation after 2 seconds
    setTimeout(() => {
      lastProfile.classList.remove('animate-pulse', 'ring-2', 'ring-blue-400');
    }, 2000);
  }
};

const removeProfile = index => {
  if (profiles.value.length > 0) {
    profiles.value.splice(index, 1);

    // If no profiles left, add one blank profile
    if (profiles.value.length === 0) {
      profiles.value.push(createBlankProfile());
    }
  }
};

const loadExistingConfig = async () => {
  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: { quote_type_id: 2 }, // Home quote type ID
      },
    );

    // Update dropdown data
    if (response.data.dropdownData) {
      dropdownData.value = response.data.dropdownData;
    }

    // Emit version info
    if (response.data.version) {
      emit('versionLoaded', response.data.version);
    }

    if (response.data.config && response.data.config.profiles) {
      const loadedProfiles = response.data.config.profiles.map(profile => {
        const formattedProfile = {
          nationalityIds: profile.nationalityIds || [],
        };

        homeFields.forEach(field => {
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
    } else {
      profiles.value = [createBlankProfile()];
    }
  } catch (error) {
    console.error('Error loading existing config:', error);
  }
};

onMounted(() => {
  loadExistingConfig();
});

const getProfiles = () => {
  return profiles.value
    .filter(profile => {
      if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
        return false;
      }

      return homeFields.some(field => {
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

      homeFields.forEach(field => {
        cleanProfile[field.fieldName] = profile[field.fieldName];
        if (field.hasCurrency) {
          cleanProfile[`${field.fieldName}_currency_id`] =
            profile[`${field.fieldName}_currency_id`];
        }
      });

      return cleanProfile;
    });
};

defineExpose({
  getProfiles,
  loadExistingConfig,
});
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Home Criteria</h3>
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
      <template v-for="(profile, profileIndex) in profiles" :key="profileIndex">
        <div
          class="bg-gray-50 rounded-lg p-6 transition-all duration-300"
          data-profile-card
        >
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-medium">Profile {{ profileIndex + 1 }}</h4>
            <button
              @click="removeProfile(profileIndex)"
              class="text-red-500 hover:text-red-700 text-sm flex items-center transition-colors"
              type="button"
              :disabled="disabled"
            >
              <i class="ri-delete-bin-line mr-1"></i> Remove Profile
            </button>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Nationalities</label>
            <x-select
              v-model="profile.nationalityIds"
              :options="dropdownData.nationalities"
              placeholder="Select nationalities"
              filterable
              multiple
              :disabled="disabled"
              class="w-full"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template v-for="field in homeFields" :key="field.fieldName">
              <div class="bg-white rounded-lg p-4 border border-gray-200">
                <label class="block text-sm font-medium mb-2">
                  {{ field.label }}
                  <span v-if="field.operator === '>='"> (≥)</span>
                  <span v-if="field.operator === 'in'"> (Select multiple)</span>
                </label>

                <div class="space-y-3">
                  <template v-if="field.type === 'select_multiple'">
                    <x-select
                      v-model="profile[field.fieldName]"
                      :options="dropdownData[field.options]"
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

                  <template v-if="field.hasCurrency">
                    <div>
                      <label class="block text-xs text-gray-500 mb-1"
                        >Currency</label
                      >
                      <x-select
                        v-model="profile[`${field.fieldName}_currency_id`]"
                        :options="dropdownData.currencies"
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
