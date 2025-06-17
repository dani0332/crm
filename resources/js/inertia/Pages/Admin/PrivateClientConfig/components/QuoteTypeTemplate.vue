<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import CollapseIcon from './CollapseIcon.vue';

const props = defineProps({
  quoteType: {
    type: Object,
    required: true,
  },
  fields: {
    type: Array,
    required: true,
  },
  initialConfig: {
    type: Object,
    default: () => ({ profiles: [] }),
  },
  dropdownData: {
    type: Object,
    default: () => ({}),
  },
  versionData: {
    type: Object,
    default: () => ({
      allVersions: [],
      currentVersion: null,
      isCurrentVersion: true,
    }),
  },
});

const emit = defineEmits(['versionLoaded', 'configurationSaved']);

const profiles = ref([]);
const isModuleCollapsed = ref(false);
const collapsedProfiles = ref(new Set());
const highlightedProfileIndex = ref(-1);
const loader = ref(false);
const configLoading = ref(false);
const isInitialized = ref(false);

const currentVersion = ref(null);
const selectedVersion = ref(null);
const allVersions = ref([]);
const isCurrentVersion = ref(true);

const quoteTypeCode = computed(() => props.quoteType.code);
const quoteTypeLabel = computed(() => props.quoteType.text);

const configForm = useForm({
  configurations: [],
});

const isDisabled = computed(() => !isCurrentVersion.value);

const nationalityOptions = computed(() => {
  return props.dropdownData.nationalities || [];
});

const createBlankProfile = () => {
  const profile = {
    nationalityIds: [],
  };

  props.fields.forEach(field => {
    profile[field.fieldName] = field.type === 'select_multiple' ? [] : '';
    if (field.hasCurrency) {
      profile[`${field.fieldName}_currency_id`] = 1;
    }
  });

  return profile;
};

const addProfile = () => {
  const newProfile = createBlankProfile();
  profiles.value.push(newProfile);

  const newIndex = profiles.value.length - 1;
  highlightedProfileIndex.value = newIndex;

  setTimeout(() => {
    const element = document.querySelector(
      `[data-profile-index="${quoteTypeCode.value}-${newIndex}"]`,
    );
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 100);

  setTimeout(() => {
    highlightedProfileIndex.value = -1;
  }, 3000);
};

const removeProfile = profileIndex => {
  if (profiles.value.length === 1) {
    profiles.value = [createBlankProfile()];
  } else {
    profiles.value.splice(profileIndex, 1);
  }
  collapsedProfiles.value.delete(profileIndex);
};

const toggleModule = () => {
  isModuleCollapsed.value = !isModuleCollapsed.value;
};

const toggleProfile = profileIndex => {
  if (collapsedProfiles.value.has(profileIndex)) {
    collapsedProfiles.value.delete(profileIndex);
  } else {
    collapsedProfiles.value.add(profileIndex);
  }
};

// Helper methods
const getCurrencySymbol = currencyId => {
  const currency = props.dropdownData.currencies?.find(
    c => c.value === currencyId,
  );
  return currency?.symbol || 'AED';
};

// Load specific version
const loadSpecificVersion = async version => {
  configLoading.value = true;

  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: {
          quote_type_id: props.quoteType.id,
          version: version,
        },
        headers: {
          'X-CSRF-TOKEN':
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content') || '',
        },
      },
    );

    // Update version information
    if (response.data.allVersions) {
      allVersions.value = response.data.allVersions;
    }

    if (response.data.version !== undefined) {
      currentVersion.value = response.data.version;
      selectedVersion.value = response.data.version;
      emit('versionLoaded', response.data.version);
    }

    if (response.data.isCurrentVersion !== undefined) {
      isCurrentVersion.value = response.data.isCurrentVersion;
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
      profiles.value = [createBlankProfile()];
    }
  } catch (error) {
    console.error(error);
  } finally {
    configLoading.value = false;
  }
};

// Method to change version
const changeVersion = newVersion => {
  if (newVersion === selectedVersion.value) return;
  loadSpecificVersion(newVersion);
};

// Save configuration for this quote type only
const saveConfiguration = () => {
  loader.value = true;

  try {
    const validProfiles = getProfiles();

    if (validProfiles.length === 0) {
      alert(
        `No valid profiles to save. Please add at least one profile with nationality and criteria.`,
      );
      loader.value = false;
      return;
    }

    // Update form data with only this quote type configuration
    configForm.configurations = [
      {
        quote_type_id: props.quoteType.id,
        profiles: validProfiles,
      },
    ];

    // Send to backend
    configForm.post(route('admin.private-client-config.upsert'), {
      onSuccess: () => {
        loader.value = false;
        // Emit event to parent to reload configuration
        emit('configurationSaved');
      },
      onError: errors => {
        loader.value = false;
        console.error('Save failed:', errors);
        alert(
          `Failed to save ${quoteTypeCode.value} configuration. Please try again.`,
        );
      },
    });
  } catch (error) {
    loader.value = false;
    console.error(`Error saving ${quoteTypeCode.value} configuration:`, error);
    alert('Error occurred while saving. Please try again.');
  }
};

// Get valid profiles
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

// Initialize profiles from props
const initializeProfiles = () => {
  if (
    props.initialConfig &&
    props.initialConfig.profiles &&
    props.initialConfig.profiles.length > 0
  ) {
    const loadedProfiles = props.initialConfig.profiles.map(profile => {
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

    profiles.value = loadedProfiles;
  } else {
    profiles.value = [createBlankProfile()];
  }
};

// Initialize version data
const initializeVersionData = () => {
  if (props.versionData) {
    allVersions.value = props.versionData.allVersions || [];
    currentVersion.value = props.versionData.currentVersion;
    selectedVersion.value = props.versionData.currentVersion;
    isCurrentVersion.value = props.versionData.isCurrentVersion !== false;

    if (props.versionData.currentVersion) {
      emit('versionLoaded', props.versionData.currentVersion);
    }
  }
};

// Watch for prop changes
watch(
  () => props.initialConfig,
  () => {
    initializeProfiles();
  },
  { deep: true, immediate: true },
);

watch(
  () => props.versionData,
  () => {
    initializeVersionData();
  },
  { deep: true, immediate: true },
);

// Initialize after component mount
onMounted(async () => {
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
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
            <span
              v-if="!isCurrentVersion"
              class="ml-2 text-amber-600 font-medium"
            >
              (Historical Version)
            </span>
          </div>

          <!-- Version Selector -->
          <div
            v-if="allVersions.length > 0"
            class="flex items-center space-x-2"
          >
            <span class="text-sm text-gray-600">Version:</span>
            <x-select
              :modelValue="selectedVersion"
              :options="
                allVersions.map(v => ({ value: v, label: `Version ${v}` }))
              "
              @update:modelValue="changeVersion"
              class="w-32"
              :disabled="configLoading"
            />
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
            Criteria Configuration
          </h3>
        </div>
        <x-tooltip>
          <x-button
            size="md"
            color="#ff5e00"
            type="button"
            @click="addProfile"
            :disabled="isDisabled"
          >
            Add Profile
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Add a new profile with nationality and
              {{ quoteTypeCode }}-specific criteria.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div v-show="!isModuleCollapsed">
        <div
          v-if="profiles.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No profiles configured. Click "Add Profile" to create one.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(profile, profileIndex) in profiles"
            :key="`profile-${profileIndex}`"
            :data-profile-index="`${quoteTypeCode}-${profileIndex}`"
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
                  Profile {{ profileIndex + 1 }}
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
                :disabled="isDisabled"
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
                  :disabled="isDisabled"
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
                  Criteria Fields
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
                          :disabled="isDisabled"
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
                          :disabled="isDisabled"
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
                :disabled="loader || isDisabled"
              >
                <i class="ri-save-line mr-1"></i> Save
                {{ quoteTypeLabel }} Configuration
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  Save this {{ quoteTypeCode }} configuration and create a new
                  version.
                </span>
              </template>
            </x-tooltip>
          </div>
        </div>

        <!-- Read-only notice -->
        <div
          v-if="isDisabled"
          class="flex items-center text-amber-600 mt-4 p-3 bg-amber-50 rounded-md border border-amber-200"
        >
          <i class="ri-error-warning-line mr-2"></i>
          <span v-if="!isCurrentVersion">
            You are viewing a historical version. Configuration is read-only.
          </span>
          <span v-else-if="props.disabled">
            Configuration is currently read-only.
          </span>
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
