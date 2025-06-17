<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';
import CollapseIcon from '../components/CollapseIcon.vue';

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
});

// UI state management
const highlightedProfileIndex = ref(-1);
const isInitialized = ref(false);
const collapsedProfiles = ref(new Set());
const isModuleCollapsed = ref(false);

const lifeFields = [
  {
    fieldName: 'sum_insured_value',
    label: 'Sum Insured',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'insurer',
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

  lifeFields.forEach(field => {
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

// Watch for profile additions to trigger animations
watch(
  () => profiles.value.length,
  (newLength, oldLength) => {
    if (isInitialized.value && newLength > oldLength) {
      highlightedProfileIndex.value = newLength - 1;
      isModuleCollapsed.value = false; // Expand module when adding profiles

      nextTick(() => {
        const newProfileElement = document.querySelector(
          `[data-profile-index="life-${newLength - 1}"]`,
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

const loadExistingConfig = async () => {
  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: { quote_type_id: 4 }, // Life quote type ID
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

        lifeFields.forEach(field => {
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

const getProfiles = () => {
  return profiles.value
    .filter(profile => {
      if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
        return false;
      }

      return lifeFields.some(field => {
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

      lifeFields.forEach(field => {
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
onMounted(() => {
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
});

defineExpose({
  getProfiles,
  loadExistingConfig,
});
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
          <CollapseIcon
            :is-expanded="!isModuleCollapsed"
            size="md"
            @click="toggleModule"
          />
          <h3 class="text-lg font-medium text-gray-900">
            Life Criteria Configuration
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
            Add Life Profile
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Add a new life profile with nationality and life insurance
              criteria.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div v-show="!isModuleCollapsed">
        <div
          v-if="profiles.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No life profiles configured. Click "Add Life Profile" to create one.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(profile, profileIndex) in profiles"
            :key="`profile-${profileIndex}`"
            :data-profile-index="`life-${profileIndex}`"
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
                  :is-expanded="!collapsedProfiles.has(profileIndex)"
                  size="md"
                  @click="toggleProfile(profileIndex)"
                />
                <h4 class="text-md font-medium text-gray-800">
                  Life Profile {{ profileIndex + 1 }}
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
              class="space-y-4"
            >
              <!-- Nationalities Section -->
              <div class="mb-6">
                <x-select
                  v-model="profile.nationalityIds"
                  :options="dropdownData.nationalities"
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
                    v-if="dropdownData.nationalities.length > 0"
                  >
                    <ui-select-actions
                      @select-all="
                        profile.nationalityIds = dropdownData.nationalities.map(
                          item => item.value,
                        )
                      "
                      @clear="profile.nationalityIds = []"
                    />
                  </template>
                </x-select>
              </div>

              <!-- Life Fields Section -->
              <div class="border-t pt-4">
                <h5 class="text-sm font-medium text-gray-700 mb-4">
                  Life Insurance Criteria Fields
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <template v-for="field in lifeFields" :key="field.fieldName">
                    <div
                      class="bg-gray-50 p-4 rounded-md border border-gray-200"
                    >
                      <label
                        class="block text-sm font-medium mb-2 text-gray-700"
                      >
                        {{ field.label }}
                        <span v-if="field.operator === '>='"> (≥)</span>
                        <span v-if="field.operator === 'in'">
                          (Multiple selection)</span
                        >
                      </label>

                      <div class="space-y-3">
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
                          />
                        </template>

                        <!-- Currency (only for numeric fields) -->
                        <template v-if="field.hasCurrency">
                          <div>
                            <label class="block text-xs text-gray-500 mb-1"
                              >Currency</label
                            >
                            <x-select
                              v-model="
                                profile[`${field.fieldName}_currency_id`]
                              "
                              :options="dropdownData.currencies"
                              placeholder="Select currency..."
                              filterable
                              :disabled="disabled"
                              size="sm"
                              class="w-full"
                            />
                          </div>
                        </template>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
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
