<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import CollapseIcon from './CollapseIcon.vue';
import { useProfileValidation } from '../composables/useProfileValidation.js';

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

// Flash messages from Inertia
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const showFlashMessage = ref(false);

// Watch for flash messages
watch([flashSuccess, flashError], () => {
  if (flashSuccess.value || flashError.value) {
    showFlashMessage.value = true;
    // Auto-hide after 5 seconds
    setTimeout(() => {
      showFlashMessage.value = false;
    }, 5000);
  }
});

// Use validation composable
const {
  validationErrors,
  showValidationSummary,
  validateProfiles,
  clearValidationErrors,
  getFieldError,
  hasProfileErrors,
  clearFieldError,
  showValidationErrors,
} = useProfileValidation();

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
  quote_type_id: null,
  config: [],
  version: null,
  created_at: null,
  quote_type: null,
});

const isDisabled = computed(
  () => !isCurrentVersion.value || configLoading.value,
);

const nationalityOptions = computed(() => {
  return props.dropdownData.nationalities || [];
});

const createBlankProfile = () => {
  const profile = {
    nationalityIds: [],
    isDefaultCriteria: false,
  };

  props.fields.forEach(field => {
    const fieldKey =
      field.hasCurrency &&
      props.fields.filter(f => f.fieldName === field.fieldName).length > 1
        ? `${field.fieldName}_${field.currencyId}`
        : field.fieldName;

    profile[fieldKey] = field.type === 'select_multiple' ? [] : '';

    if (field.hasCheckBox) {
      profile[`${fieldKey}_isEnabled`] = true;
    }
  });

  return profile;
};

const addProfile = () => {
  const newProfile = createBlankProfile();
  profiles.value.push(newProfile);

  clearValidationErrors();

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

  clearValidationErrors();
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

const getCurrencySymbol = currencyId => {
  const currency = props.dropdownData.currencies?.find(
    c => c.value === currencyId,
  );

  return currency?.label || 'AED';
};

const loadSpecificVersion = async version => {
  configLoading.value = true;

  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: {
          quote_type_id: props.quoteType.id,
          version,
          _t: Date.now(), // Cache busting
        },
      },
    );

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

    // Load configuration data
    if (
      response.data.config &&
      response.data.config.profiles &&
      response.data.config.profiles.length > 0
    ) {
      const loadedProfiles = response.data.config.profiles.map(profile => {
        const formattedProfile = {
          nationalityIds: profile.nationalityIds || [],
          isDefaultCriteria: profile.isDefaultCriteria || false,
        };

        props.fields.forEach(field => {
          const fieldKey =
            field.hasCurrency &&
            props.fields.filter(f => f.fieldName === field.fieldName).length > 1
              ? `${field.fieldName}_${field.currencyId}`
              : field.fieldName;

          formattedProfile[fieldKey] =
            profile[fieldKey] || (field.type === 'select_multiple' ? [] : '');

          if (field.hasCheckBox) {
            formattedProfile[`${fieldKey}_isEnabled`] =
              profile[`${fieldKey}_isEnabled`] !== undefined
                ? profile[`${fieldKey}_isEnabled`]
                : true; // Default to enabled if not specified
          }
        });

        return formattedProfile;
      });

      profiles.value = loadedProfiles;
    } else {
      profiles.value = [createBlankProfile()];
    }
  } catch (error) {
    profiles.value = [createBlankProfile()];
  } finally {
    configLoading.value = false;
  }
};

const changeVersion = newVersion => {
  if (newVersion === selectedVersion.value) return;
  selectedVersion.value = newVersion;

  loadSpecificVersion(newVersion);
};

const initializeProfiles = () => {
  if (
    props.initialConfig &&
    props.initialConfig.profiles &&
    props.initialConfig.profiles.length > 0
  ) {
    const loadedProfiles = props.initialConfig.profiles.map(profile => {
      const formattedProfile = {
        nationalityIds: profile.nationalityIds || [],
        isDefaultCriteria: profile.isDefaultCriteria || false,
      };

      props.fields.forEach(field => {
        const fieldKey =
          field.hasCurrency &&
          props.fields.filter(f => f.fieldName === field.fieldName).length > 1
            ? `${field.fieldName}_${field.currencyId}`
            : field.fieldName;

        formattedProfile[fieldKey] =
          profile[fieldKey] || (field.type === 'select_multiple' ? [] : '');

        if (field.hasCheckBox) {
          formattedProfile[`${fieldKey}_isEnabled`] =
            profile[`${fieldKey}_isEnabled`] !== undefined
              ? profile[`${fieldKey}_isEnabled`]
              : true; // Default to enabled if not specified
        }
      });

      return formattedProfile;
    });

    profiles.value = loadedProfiles;
  } else {
    profiles.value = [createBlankProfile()];
  }
};

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

onMounted(async () => {
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
});

const toggleDefaultCriteria = (profileIndex, newValue) => {
  const profile = profiles.value[profileIndex];

  if (newValue) {
    // If setting this profile as default, unmark all other defaults first
    profiles.value.forEach((otherProfile, otherIndex) => {
      if (otherIndex !== profileIndex && otherProfile.isDefaultCriteria) {
        otherProfile.isDefaultCriteria = false;
        // Clear validation errors for the previously default profile
        clearFieldError(otherIndex, 'isDefaultCriteria');
        clearFieldError(otherIndex, 'nationalityIds');
      }
    });

    // Set this profile as default and clear its nationalities
    profile.isDefaultCriteria = true;
    profile.nationalityIds = [];
  } else {
    // Simply unmark this profile as default
    profile.isDefaultCriteria = false;
  }

  // Clear validation errors for the current profile
  clearFieldError(profileIndex, 'isDefaultCriteria');
  clearFieldError(profileIndex, 'nationalityIds');
};

// Watch for changes in profile fields to clear errors
watch(
  profiles,
  (newProfiles, oldProfiles) => {
    if (oldProfiles && newProfiles) {
      newProfiles.forEach((profile, profileIndex) => {
        // Check nationality changes
        if (
          oldProfiles[profileIndex] &&
          JSON.stringify(profile.nationalityIds) !==
            JSON.stringify(oldProfiles[profileIndex].nationalityIds)
        ) {
          clearFieldError(profileIndex, 'nationalityIds');
        }

        // Check field changes
        props.fields.forEach(field => {
          const fieldKey = getFieldKey(field);
          if (
            oldProfiles[profileIndex] &&
            profile[fieldKey] !== oldProfiles[profileIndex][fieldKey]
          ) {
            clearFieldError(profileIndex, fieldKey);
          }
        });
      });
    }
  },
  { deep: true },
);

const getFieldKey = field => {
  if (field.hasCurrency && field.currencyId) {
    return `${field.fieldName}_${field.currencyId}`;
  }
  return field.fieldName;
};

const saveConfiguration = () => {
  // Clear previous validation errors
  clearValidationErrors();

  // Validate profiles using the validation utility
  if (
    !validateProfiles(profiles.value, props.fields, nationalityOptions.value)
  ) {
    // Show validation summary
    showValidationErrors();

    // Scroll to first error
    const firstErrorProfileIndex = Object.keys(validationErrors.value)[0];
    if (firstErrorProfileIndex !== undefined) {
      const element = document.querySelector(
        `[data-profile-index="${quoteTypeCode.value}-${firstErrorProfileIndex}"]`,
      );
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      // Expand the profile with errors
      collapsedProfiles.value.delete(parseInt(firstErrorProfileIndex));
    }

    return;
  }

  loader.value = true;

  try {
    const validProfiles = getProfiles();

    if (validProfiles.length === 0) {
      showValidationErrors();
      loader.value = false;
      return;
    }

    configForm.clearErrors();

    configForm.quote_type_id = props.quoteType.id;
    configForm.quote_type = props.quoteType.code;
    configForm.version = currentVersion.value ? currentVersion.value + 1 : 1;
    configForm.created_at = new Date().toISOString();
    configForm.config = validProfiles;

    configForm.post(route('admin.private-client-config.upsert'), {
      onSuccess: page => {
        loader.value = false;
        clearValidationErrors();

        // Use version from flash data if available, otherwise calculate
        const newVersion =
          page.props?.flash?.version ||
          (currentVersion.value ? currentVersion.value + 1 : 1);

        // Update local state immediately
        currentVersion.value = newVersion;
        selectedVersion.value = newVersion;
        isCurrentVersion.value = true;

        // Update versions list
        if (!allVersions.value.includes(newVersion)) {
          allVersions.value.unshift(newVersion); // Add to beginning
          allVersions.value.sort((a, b) => b - a); // Sort descending
        }

        // Emit events to notify parent components
        emit('versionLoaded', newVersion);
        emit('configurationSaved');
      },
      onError: errors => {
        loader.value = false;
        console.error('Save failed:', errors);
      },
    });
  } catch (error) {
    loader.value = false;
    console.error(`Error saving ${quoteTypeCode.value} configuration:`, error);
  }
};

const getProfiles = () => {
  return profiles.value
    .filter(profile => {
      // Default profiles are valid even without nationalities
      if (profile.isDefaultCriteria) {
        // For default profiles, just check if at least one field has a value
        return props.fields.some(field => {
          const value = profile[field.fieldName];
          if (Array.isArray(value)) {
            return value.length > 0;
          }
          return value && value.toString().trim() !== '';
        });
      }

      // Non-default profiles need nationalities AND at least one field filled
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
        nationalityIds: profile.nationalityIds || [], // Ensure empty array for default profiles
        isDefaultCriteria: profile.isDefaultCriteria || false,
      };

      props.fields.forEach(field => {
        cleanProfile[field.fieldName] = profile[field.fieldName];
        if (field.hasCheckBox) {
          cleanProfile[`${field.fieldName}_isEnabled`] =
            profile[`${field.fieldName}_isEnabled`];
        }
      });

      return cleanProfile;
    });
};
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <!-- Flash Messages -->
      <div
        v-if="showFlashMessage && (flashSuccess || flashError)"
        class="mb-6 p-4 rounded-md border"
        :class="{
          'bg-green-50 border-green-200': flashSuccess,
          'bg-red-50 border-red-200': flashError,
        }"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <i
              class="text-lg"
              :class="{
                'ri-check-circle-line text-green-400': flashSuccess,
                'ri-error-warning-line text-red-400': flashError,
              }"
            ></i>
          </div>
          <div class="ml-3 flex-1">
            <p
              class="text-sm font-medium"
              :class="{
                'text-green-800': flashSuccess,
                'text-red-800': flashError,
              }"
            >
              {{ flashSuccess || flashError }}
            </p>
          </div>
          <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
              <button
                type="button"
                class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="{
                  'bg-green-50 text-green-400 hover:bg-green-100 focus:ring-offset-green-50 focus:ring-green-600':
                    flashSuccess,
                  'bg-red-50 text-red-400 hover:bg-red-100 focus:ring-offset-red-50 focus:ring-red-600':
                    flashError,
                }"
                @click="showFlashMessage = false"
              >
                <span class="sr-only">Dismiss</span>
                <i class="ri-close-line text-sm"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Server-side Form Errors -->
      <div
        v-if="configForm.hasErrors"
        class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <i class="ri-error-warning-line text-red-400 text-lg"></i>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800">
              Please fix the following errors:
            </h3>
            <div class="mt-2 text-sm text-red-700">
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="(error, field) in configForm.errors" :key="field">
                  {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>
          </div>
          <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
              <button
                type="button"
                class="inline-flex bg-red-50 rounded-md p-1.5 text-red-400 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600"
                @click="configForm.clearErrors()"
              >
                <span class="sr-only">Dismiss</span>
                <i class="ri-close-line text-sm"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="currentVersion"
        class="mb-4 p-3 rounded-md border"
        :class="{
          'bg-blue-50 border-blue-200': isCurrentVersion,
          'bg-amber-50 border-amber-200': !isCurrentVersion,
        }"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center text-sm">
            <i
              class="ri-information-line mr-2"
              :class="{
                'text-blue-700': isCurrentVersion,
                'text-amber-700': !isCurrentVersion,
              }"
            ></i>
            <span
              :class="{
                'text-blue-700': isCurrentVersion,
                'text-amber-700': !isCurrentVersion,
              }"
            >
              <span v-if="isCurrentVersion">
                Currently viewing {{ quoteTypeLabel }} configuration version
                {{ currentVersion }} (Active Version)
              </span>
              <span v-else>
                Viewing {{ quoteTypeLabel }} configuration version
                {{ currentVersion }} (Historical Version - Read Only)
              </span>
            </span>
          </div>

          <div
            v-if="allVersions.length > 0"
            class="flex items-center space-x-2"
          >
            <!-- Loading indicator for version change -->
            <div
              v-if="configLoading"
              class="flex items-center space-x-2 text-blue-600"
            >
              <svg
                class="animate-spin h-4 w-4"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              <span class="text-sm">Loading...</span>
            </div>

            <div v-else class="flex items-center space-x-2">
              <span class="text-sm text-gray-600">Version:</span>
              <x-select
                :modelValue="selectedVersion"
                :options="
                  allVersions.map(v => ({
                    value: v,
                    label: `Version ${v}${v === Math.max(...allVersions) ? ' (Latest)' : ''}`,
                  }))
                "
                @update:modelValue="changeVersion"
                class="w-40"
                :disabled="configLoading"
              />
            </div>
          </div>
        </div>

        <!-- Version History Info -->
        <div v-if="!isCurrentVersion" class="mt-2 text-xs text-amber-600">
          <i class="ri-lock-line mr-1"></i>
          This is a historical version and cannot be modified. Switch to the
          latest version to make changes.
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
            :disabled="isDisabled || configLoading"
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
        <!-- Loading overlay for version changes -->
        <div
          v-if="configLoading"
          class="relative bg-white border border-gray-200 rounded-lg p-8"
        >
          <div class="flex items-center justify-center py-12">
            <div class="text-center">
              <svg
                class="animate-spin mx-auto h-8 w-8 text-blue-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              <p class="mt-2 text-sm text-gray-600">
                Loading {{ quoteTypeLabel }} configuration version
                {{ selectedVersion }}...
              </p>
            </div>
          </div>
        </div>

        <!-- Configuration content (hidden when loading) -->
        <div v-else>
          <!-- Validation Summary -->
          <div
            v-if="showValidationSummary"
            class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <i class="ri-error-warning-line text-red-400 text-lg"></i>
              </div>
              <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800">
                  Please fix the following validation errors:
                </h3>
                <div class="mt-2 text-sm text-red-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <template
                      v-for="(profileErrors, profileIndex) in validationErrors"
                      :key="profileIndex"
                    >
                      <li class="font-medium">
                        Profile {{ parseInt(profileIndex) + 1 }}:
                      </li>
                      <ul class="list-disc pl-5 space-y-1">
                        <li
                          v-for="(error, fieldName) in profileErrors"
                          :key="fieldName"
                        >
                          {{ error }}
                        </li>
                      </ul>
                    </template>
                    <li v-if="Object.keys(validationErrors).length === 0">
                      No valid profiles to save. Please ensure at least one
                      profile has:
                      <br />• Either specific nationalities selected OR marked
                      as default criteria <br />• At least one field with a
                      value
                    </li>
                  </ul>
                </div>
              </div>
              <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                  <button
                    type="button"
                    class="inline-flex bg-red-50 rounded-md p-1.5 text-red-400 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600"
                    @click="showValidationSummary = false"
                  >
                    <span class="sr-only">Dismiss</span>
                    <i class="ri-close-line text-sm"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

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
              class="border rounded-lg p-4 transition-all duration-500"
              :class="{
                'ring-2 ring-orange-500 ring-opacity-50 bg-orange-50':
                  highlightedProfileIndex === profileIndex,
                'shadow-lg': highlightedProfileIndex === profileIndex,
                'border-red-300 bg-red-50': hasProfileErrors(profileIndex),
                'border-gray-200': !hasProfileErrors(profileIndex),
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
                      v-if="profile.isDefaultCriteria"
                      class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                      <i class="ri-star-line mr-1"></i>
                      Default Criteria
                    </span>
                    <span
                      v-if="highlightedProfileIndex === profileIndex"
                      class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse"
                    >
                      New!
                    </span>
                    <span
                      v-if="hasProfileErrors(profileIndex)"
                      class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                    >
                      <i class="ri-error-warning-line mr-1"></i>
                      Has Errors
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
                <!-- Default Criteria Toggle -->
                <div class="border border-blue-200 rounded-lg p-3 bg-blue-50">
                  <label class="flex items-center space-x-2 cursor-pointer">
                    <x-checkbox
                      :modelValue="profile.isDefaultCriteria"
                      @update:modelValue="
                        toggleDefaultCriteria(profileIndex, $event)
                      "
                      :disabled="isDisabled"
                      class="flex-shrink-0"
                    />
                    <div class="flex-1">
                      <span class="text-sm font-medium text-blue-800">
                        Set as Default Criteria
                      </span>
                      <p class="text-xs text-blue-600 mt-1">
                        Default criteria will be used when no specific
                        nationality match is found. Only one default criteria is
                        allowed per quote type.
                      </p>
                    </div>
                  </label>
                  <div
                    v-if="getFieldError(profileIndex, 'isDefaultCriteria')"
                    class="mt-2 text-sm text-red-600"
                  >
                    {{ getFieldError(profileIndex, 'isDefaultCriteria') }}
                  </div>
                </div>

                <div v-if="!profile.isDefaultCriteria">
                  <x-select
                    v-model="profile.nationalityIds"
                    :options="nationalityOptions"
                    placeholder="Select nationalities..."
                    multiple
                    filterable
                    class="w-full min-h-[40px]"
                    :class="{
                      'border-red-300': getFieldError(
                        profileIndex,
                        'nationalityIds',
                      ),
                    }"
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
                  <div
                    v-if="getFieldError(profileIndex, 'nationalityIds')"
                    class="mt-1 text-sm text-red-600"
                  >
                    {{ getFieldError(profileIndex, 'nationalityIds') }}
                  </div>
                </div>

                <div
                  v-else
                  class="p-3 bg-amber-50 border border-amber-200 rounded-md"
                >
                  <div class="flex items-center">
                    <i class="ri-information-line text-amber-600 mr-2"></i>
                    <span class="text-sm text-amber-800">
                      This profile will be used as default criteria for all
                      nationalities not covered by specific profiles.
                    </span>
                  </div>
                </div>

                <!-- Single Card for All Fields -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                  <h5 class="text-sm font-medium text-gray-700 mb-4">
                    Criteria Fields
                  </h5>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template v-for="field in fields" :key="field.fieldName">
                      <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                          <template v-if="field.hasCheckBox">
                            <label
                              class="flex items-center space-x-2 cursor-pointer"
                              :class="{
                                'cursor-not-allowed': isDisabled,
                              }"
                            >
                              <x-checkbox
                                v-model="
                                  profile[`${getFieldKey(field)}_isEnabled`]
                                "
                                :disabled="isDisabled"
                                class="flex-shrink-0"
                              />
                              <span
                                class="text-sm font-medium select-none"
                                :class="{
                                  'text-gray-700':
                                    profile[`${getFieldKey(field)}_isEnabled`],
                                  'text-gray-400':
                                    !profile[`${getFieldKey(field)}_isEnabled`],
                                }"
                              >
                                {{ field.label }}
                                <span
                                  v-if="field.hasCurrency && field.currencyId"
                                  class="text-xs text-gray-500"
                                >
                                  ({{ getCurrencySymbol(field.currencyId) }})
                                </span>
                                <span
                                  v-if="
                                    field.isRequired &&
                                    profile[`${getFieldKey(field)}_isEnabled`]
                                  "
                                  class="text-red-500"
                                  >*</span
                                >
                                <span v-if="field.operator === '>='">
                                  (Greater than or equal to)</span
                                >
                                <span v-if="field.operator === '<='">
                                  (Less than or equal to)</span
                                >
                                <span v-if="field.operator === 'in'">
                                  (Multiple selection)</span
                                >
                              </span>
                            </label>
                          </template>
                          <template v-else>
                            <label
                              class="block text-sm font-medium text-gray-700"
                            >
                              {{ field.label }}
                              <span
                                v-if="field.hasCurrency && field.currencyId"
                                class="text-xs text-gray-500"
                              >
                                ({{ getCurrencySymbol(field.currencyId) }})
                              </span>
                              <span v-if="field.isRequired" class="text-red-500"
                                >*</span
                              >
                              <span v-if="field.operator === '>='">
                                (Greater than or equal to)</span
                              >
                              <span v-if="field.operator === '<='">
                                (Less than or equal to)</span
                              >
                              <span v-if="field.operator === 'in'"
                                >(Multiple selection)</span
                              >
                            </label>
                          </template>
                        </div>

                        <!-- Field Value -->
                        <template v-if="field.type === 'select_multiple'">
                          <x-select
                            v-model="profile[getFieldKey(field)]"
                            :options="dropdownData[field.options]"
                            :placeholder="`Select ${field.label.toLowerCase()}...`"
                            multiple
                            filterable
                            class="w-full min-h-[40px]"
                            :class="{
                              'border-red-300': getFieldError(
                                profileIndex,
                                getFieldKey(field),
                              ),
                              'opacity-50':
                                field.hasCheckBox &&
                                !profile[`${getFieldKey(field)}_isEnabled`],
                            }"
                            :disabled="
                              isDisabled ||
                              (field.hasCheckBox &&
                                !profile[`${getFieldKey(field)}_isEnabled`])
                            "
                            :tooltip="`Select ${field.label.toLowerCase()} options for this profile.`"
                          >
                            <template
                              #content-footer
                              v-if="dropdownData[field.options]?.length > 0"
                            >
                              <ui-select-actions
                                @select-all="
                                  profile[getFieldKey(field)] = dropdownData[
                                    field.options
                                  ].map(item => item.value)
                                "
                                @clear="profile[getFieldKey(field)] = []"
                              />
                            </template>
                          </x-select>
                        </template>
                        <template v-else>
                          <x-input
                            v-model="profile[getFieldKey(field)]"
                            :placeholder="`Enter ${field.label.toLowerCase()}`"
                            type="text"
                            class="!mb-0"
                            :class="{
                              'border-red-300': getFieldError(
                                profileIndex,
                                getFieldKey(field),
                              ),
                              'opacity-50':
                                field.hasCheckBox &&
                                !profile[`${getFieldKey(field)}_isEnabled`],
                            }"
                            :disabled="
                              isDisabled ||
                              (field.hasCheckBox &&
                                !profile[`${getFieldKey(field)}_isEnabled`])
                            "
                            :tooltip="`Set the minimum ${field.label.toLowerCase()} for this profile.`"
                          >
                            <!-- Currency suffix for fields with currency -->
                            <template v-if="field.hasCurrency" #suffix>
                              <div
                                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                              >
                                <span>{{
                                  getCurrencySymbol(field.currencyId)
                                }}</span>
                              </div>
                            </template>
                          </x-input>
                        </template>

                        <!-- Error message for this field -->
                        <div
                          v-if="getFieldError(profileIndex, getFieldKey(field))"
                          class="text-sm text-red-600"
                        >
                          {{ getFieldError(profileIndex, getFieldKey(field)) }}
                        </div>
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
            class="flex items-center justify-between mt-4 p-4 bg-amber-50 rounded-md border border-amber-200"
          >
            <div class="flex items-center text-amber-700">
              <i class="ri-lock-line mr-2"></i>
              <div>
                <div class="font-medium">Configuration is Read-Only</div>
                <div class="text-sm mt-1">
                  <span v-if="!isCurrentVersion">
                    You are viewing version {{ currentVersion }}. Only the
                    latest version can be modified.
                  </span>
                  <span v-else-if="props.disabled">
                    This configuration is currently locked for editing.
                  </span>
                </div>
              </div>
            </div>
            <div v-if="!isCurrentVersion && allVersions.length > 0">
              <x-button
                size="sm"
                color="#059669"
                @click="changeVersion(Math.max(...allVersions))"
                :disabled="configLoading"
              >
                <i class="ri-edit-line mr-1"></i>
                Switch to Latest Version
              </x-button>
            </div>
          </div>
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
