<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import CollapseIcon from './CollapseIcon.vue';

const props = defineProps({
  quoteTypeId: {
    type: Number,
    required: true,
  },
  quoteTypeName: {
    type: String,
    required: true,
  },
  quoteTypeLabel: {
    type: String,
    required: true,
  },
  fields: {
    type: Array,
    required: true,
  },
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
  carMakes: [],
  insurers: [],
  locationAreas: [],
});

// UI state management
const highlightedProfileIndex = ref(-1);
const isInitialized = ref(false);
const collapsedProfiles = ref(new Set());
const isModuleCollapsed = ref(false);
const currentVersion = ref(null);
const loader = ref(false);
const configLoading = ref(false);

// Form for saving
const configForm = useForm({
  configurations: [],
});

// Computed properties
const nationalityOptions = computed(
  () => dropdownData.value.nationalities || [],
);

const createBlankProfile = () => {
  const profile = {
    nationalityIds: [],
  };

  // Add all fields to the profile
  props.fields.forEach(field => {
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

// Watch for profile additions to trigger animations
watch(
  () => profiles.value.length,
  (newLength, oldLength) => {
    if (isInitialized.value && newLength > oldLength) {
      highlightedProfileIndex.value = newLength - 1;
      isModuleCollapsed.value = false; // Expand module when adding profiles

      nextTick(() => {
        const newProfileElement = document.querySelector(
          `[data-profile-index="${props.quoteTypeName}-${newLength - 1}"]`,
        );
        if (newProfileElement) {
          newProfileElement.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
          });
        }
      });

      setTimeout(() => {
        highlightedProfileIndex.value = -1;
      }, 2000);
    }
  },
);

const addProfile = async () => {
  profiles.value.push(createBlankProfile());
};

const removeProfile = index => {
  profiles.value.splice(index, 1);

  // If no profiles left, add one blank profile
  if (profiles.value.length === 0) {
    profiles.value.push(createBlankProfile());
  }
};

const toggleProfile = profileIndex => {
  if (collapsedProfiles.value.has(profileIndex)) {
    collapsedProfiles.value.delete(profileIndex);
  } else {
    collapsedProfiles.value.add(profileIndex);
  }
};

const toggleModule = () => {
  isModuleCollapsed.value = !isModuleCollapsed.value;
};

// Get currency symbol by ID
const getCurrencySymbol = currencyId => {
  const currency = dropdownData.value.currencies?.find(
    c => c.value === currencyId,
  );
  return currency?.symbol || 'AED';
};

// Load existing configuration when component mounts
const loadExistingConfig = async () => {
  configLoading.value = true;

  try {
    const routeUrl = route('admin.private-client-config.latest-by-quote-type');

    const response = await axios.get(routeUrl, {
      params: { quote_type_id: props.quoteTypeId },
      headers: {
        'X-CSRF-TOKEN':
          document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '',
      },
    });

    // Update dropdown data
    if (response.data.dropdownData) {
      dropdownData.value = response.data.dropdownData;
    }

    // Set current version
    if (response.data.version) {
      currentVersion.value = response.data.version;
      emit('versionLoaded', response.data.version);
    }

    if (response.data.config && response.data.config.profiles) {
      const loadedProfiles = response.data.config.profiles.map(profile => {
        const formattedProfile = {
          nationalityIds: profile.nationalityIds || [],
        };

        props.fields.forEach(field => {
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
      // No config found, reset to blank profile
      profiles.value = [createBlankProfile()];
    }
  } catch (error) {
    console.error(
      `Error loading existing config for ${props.quoteTypeName}:`,
      error,
    );
  } finally {
    configLoading.value = false;
  }
};

// Save configuration for this quote type only
const saveConfiguration = () => {
  loader.value = true;

  try {
    const validProfiles = getProfiles();

    if (validProfiles.length === 0) {
      alert(
        `No valid ${props.quoteTypeName} profiles to save. Please add at least one profile with nationality and criteria.`,
      );
      loader.value = false;
      return;
    }

    // Update form data with only this quote type configuration
    configForm.configurations = [
      {
        quote_type_id: props.quoteTypeId,
        profiles: validProfiles,
      },
    ];

    // Send to backend
    configForm.post(route('admin.private-client-config.upsert'), {
      onSuccess: () => {
        loader.value = false;
        // Reload the configuration to get the new version
        loadExistingConfig();
      },
      onError: errors => {
        loader.value = false;
        console.error('Save failed:', errors);
        alert(
          `Failed to save ${props.quoteTypeName} configuration. Please try again.`,
        );
      },
    });
  } catch (error) {
    loader.value = false;
    console.error(`Error saving ${props.quoteTypeName} configuration:`, error);
    alert('Error occurred while saving. Please try again.');
  }
};

// Expose method to parent component
const getProfiles = () => {
  return profiles.value
    .filter(profile => {
      // Check if profile has nationality and at least one field filled
      if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
        return false;
      }

      return props.fields.some(field => {
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

      props.fields.forEach(field => {
        cleanProfile[field.fieldName] = profile[field.fieldName];
        if (field.hasCurrency) {
          cleanProfile[`${field.fieldName}_currency_id`] =
            profile[`${field.fieldName}_currency_id`];
        }
      });

      return cleanProfile;
    });
};

// Initialize after component mount
onMounted(async () => {
  // Only initialize, don't load data automatically
  // Data will be loaded when tab becomes active
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
});

// Expose the method to parent
defineExpose({
  getProfiles,
  loadExistingConfig,
});
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <!-- Version Info Header -->
      <div
        v-if="currentVersion"
        class="mb-4 p-3 bg-blue-50 rounded-md border border-blue-200"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center text-sm text-blue-700">
            <i class="ri-information-line mr-2"></i>
            <span
              >Currently viewing {{ quoteTypeLabel }} configuration version
              {{ currentVersion }}</span
            >
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
          <CollapseIcon
            :isExpanded="!isModuleCollapsed"
            size="md"
            @click="toggleModule"
          />
          <h3 class="text-lg font-medium text-gray-900">
            {{ quoteTypeLabel }} Criteria Configuration
          </h3>
        </div>
        <x-tooltip>
          <x-button
            size="md"
            color="#ff5e00"
            type="button"
            @click="addProfile"
            :disabled="disabled"
          >
            Add {{ quoteTypeLabel }} Profile
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Add a new {{ quoteTypeName }} profile with nationality and
              {{ quoteTypeName }}-specific criteria.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div v-show="!isModuleCollapsed">
        <div
          v-if="profiles.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No {{ quoteTypeName }} profiles configured. Click "Add
          {{ quoteTypeLabel }} Profile" to create one.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(profile, profileIndex) in profiles"
            :key="`profile-${profileIndex}`"
            :data-profile-index="`${quoteTypeName}-${profileIndex}`"
            class="border border-gray-200 rounded-lg p-4 transition-all duration-500"
            :class="{
              'ring-2 ring-orange-500 ring-opacity-50 bg-orange-50':
                highlightedProfileIndex === profileIndex,
              'shadow-lg': highlightedProfileIndex === profileIndex,
            }"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-2">
                <CollapseIcon
                  :isExpanded="!collapsedProfiles.has(profileIndex)"
                  size="md"
                  @click="toggleProfile(profileIndex)"
                />
                <h4 class="text-md font-medium text-gray-800">
                  {{ quoteTypeLabel }} Profile {{ profileIndex + 1 }}
                  <span
                    v-if="highlightedProfileIndex === profileIndex"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse"
                  >
                    New!
                  </span>
                </h4>
              </div>
              <x-button
                size="sm"
                color="error"
                outlined
                type="button"
                :disabled="disabled"
                @click="removeProfile(profileIndex)"
              >
                <svg
                  class="h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                  />
                </svg>
              </x-button>
            </div>

            <div
              v-show="!collapsedProfiles.has(profileIndex)"
              class="space-y-6"
            >
              <!-- Nationalities Section -->
              <div>
                <x-select
                  v-model="profile.nationalityIds"
                  :options="nationalityOptions"
                  placeholder="Select nationalities..."
                  multiple
                  filterable
                  class="w-full min-h-[40px]"
                  label="Nationalities"
                  required
                  tooltip="Select the nationalities of customers this profile applies to."
                  :disabled="disabled"
                >
                  <template
                    #content-footer
                    v-if="nationalityOptions.length > 0"
                  >
                    <ui-select-actions
                      @select-all="
                        profile.nationalityIds = nationalityOptions.map(
                          item => item.value,
                        )
                      "
                      @clear="profile.nationalityIds = []"
                    />
                  </template>
                </x-select>
              </div>

              <!-- Single Card for All Fields -->
              <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <h5 class="text-sm font-medium text-gray-700 mb-4">
                  {{ quoteTypeLabel }} Criteria Fields
                </h5>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <template v-for="field in fields" :key="field.fieldName">
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ field.label }}
                        <span v-if="field.operator === '>='"> (≥)</span>
                        <span v-if="field.operator === 'in'">
                          (Multiple selection)</span
                        >
                      </label>

                      <!-- Field Value -->
                      <template v-if="field.type === 'select_multiple'">
                        <x-select
                          v-model="profile[field.fieldName]"
                          :options="dropdownData[field.options]"
                          :placeholder="`Select ${field.label.toLowerCase()}...`"
                          multiple
                          filterable
                          class="w-full min-h-[40px]"
                          :disabled="disabled"
                          :tooltip="`Select ${field.label.toLowerCase()} options for this profile.`"
                        >
                          <template
                            #content-footer
                            v-if="dropdownData[field.options]?.length > 0"
                          >
                            <ui-select-actions
                              @select-all="
                                profile[field.fieldName] = dropdownData[
                                  field.options
                                ].map(item => item.value)
                              "
                              @clear="profile[field.fieldName] = []"
                            />
                          </template>
                        </x-select>
                      </template>
                      <template v-else>
                        <x-input
                          v-model="profile[field.fieldName]"
                          :placeholder="`Enter ${field.label.toLowerCase()}`"
                          type="text"
                          class="!mb-0"
                          :disabled="disabled"
                          :tooltip="`Set the minimum ${field.label.toLowerCase()} for this profile.`"
                        >
                          <!-- Currency suffix for fields with currency -->
                          <template v-if="field.hasCurrency" #suffix>
                            <div
                              class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                            >
                              <span>{{
                                getCurrencySymbol(
                                  profile[`${field.fieldName}_currency_id`],
                                )
                              }}</span>
                            </div>
                          </template>
                        </x-input>
                      </template>
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Save Button Section -->
        <div class="mt-6 pt-4 border-t border-gray-200">
          <div class="flex justify-end">
            <x-tooltip>
              <x-button
                size="md"
                color="#059669"
                @click="saveConfiguration"
                :loading="loader"
                :disabled="loader || disabled"
              >
                <i class="ri-save-line mr-1"></i> Save
                {{ quoteTypeLabel }} Configuration
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  Save this {{ quoteTypeName }} configuration and create a new
                  version.
                </span>
              </template>
            </x-tooltip>
          </div>
        </div>

        <!-- Read-only notice -->
        <div
          v-if="disabled"
          class="flex items-center text-amber-600 mt-4 p-3 bg-amber-50 rounded-md border border-amber-200"
        >
          <i class="ri-error-warning-line mr-2"></i>
          <span
            >You are viewing a historical version. Configuration is
            read-only.</span
          >
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-tooltip-content {
  @apply text-sm text-white;
}
</style>
