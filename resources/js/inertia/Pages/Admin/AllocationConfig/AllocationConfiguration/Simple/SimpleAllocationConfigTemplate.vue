<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import CollapseIcon from '../Savings/components/CollapseIcon.vue';

const props = defineProps({
  configuration: {
    type: Object,
    default: null,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  nationalityOptions: {
    type: Array,
    default: () => [],
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
  lobName: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['data-update']);

const bracket = ref({
  profiles: [],
});
const validationErrors = ref({});
const highlightedProfileKey = ref('');
const collapsedProfiles = ref(new Set());
const isInitialized = ref(false);

const initializeData = () => {
  if (props.configuration && props.configuration.bracket1) {
    bracket.value = JSON.parse(JSON.stringify(props.configuration.bracket1));
  } else {
    bracket.value = {
      profiles: [],
    };
  }

  emitData();
};

const emitData = () => {
  const data = {
    bracket1: bracket.value,
  };
  emit('data-update', data);
};

const validateBracket = () => {
  const errors = [];

  if (!bracket.value.profiles || bracket.value.profiles.length === 0) {
    errors.push(
      `${props.lobName} Bracket: At least one advisor profile is required`,
    );
  } else {
    bracket.value.profiles.forEach((profile, profileIndex) => {
      if (!profile.advisorIds || profile.advisorIds.length === 0) {
        errors.push(
          `${props.lobName} Bracket, Profile ${profileIndex + 1}: At least one advisor must be selected`,
        );
      }

      if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
        errors.push(
          `${props.lobName} Bracket, Profile ${profileIndex + 1}: At least one nationality must be selected`,
        );
      }
    });
  }

  return errors;
};

const validate = () => {
  const errors = validateBracket();
  validationErrors.value = errors;
  return {
    isValid: errors.length === 0,
    errors: errors,
  };
};

const clearValidationErrors = () => {
  validationErrors.value = {};
};

watch(
  bracket,
  () => {
    if (Object.keys(validationErrors.value).length > 0) {
      clearValidationErrors();
    }
    emitData();
  },
  { deep: true },
);

onMounted(() => {
  initializeData();
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
});

watch(
  () => props.configuration,
  newConfig => {
    if (newConfig) {
      initializeData();
    }
  },
  { deep: true },
);

watch(
  () => bracket.value.profiles?.length,
  (newLength, oldLength) => {
    if (isInitialized.value && newLength > oldLength) {
      const profileIndex = newLength - 1;
      const profileKey = `profile-${profileIndex}`;
      highlightedProfileKey.value = profileKey;

      nextTick(() => {
        const newProfileElement = document.querySelector(
          `[data-profile-key="${profileKey}"]`,
        );
        if (newProfileElement) {
          newProfileElement.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
          });
        }
      });

      setTimeout(() => {
        highlightedProfileKey.value = '';
      }, 2000);
    }
  },
);

const createEmptyProfile = () => ({
  advisorIds: [],
  nationalityIds: [],
});

const addProfile = () => {
  bracket.value.profiles.push(createEmptyProfile());
};

const removeProfile = profileIndex => {
  bracket.value.profiles.splice(profileIndex, 1);
};

const toggleProfile = profileIndex => {
  const profileKey = `${profileIndex}`;
  if (collapsedProfiles.value.has(profileKey)) {
    collapsedProfiles.value.delete(profileKey);
  } else {
    collapsedProfiles.value.add(profileKey);
  }
};

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">
          {{ lobName }} Allocation - Bracket 1
        </h3>
        <x-tooltip v-if="!viewMode">
          <x-button size="sm" color="#ff5e00" type="button" @click="addProfile">
            Add Profile
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Set who gets the lead for {{ lobName }} insurance.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div
        v-if="bracket.profiles.length === 0"
        class="text-center py-8 text-gray-500"
      >
        No advisor profiles configured for {{ lobName }} bracket. Click "Add
        Profile" to create one.
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="(profile, profileIndex) in bracket.profiles"
          :key="`profile-${profileIndex}`"
          :data-profile-key="`profile-${profileIndex}`"
          class="bg-gray-50 p-4 rounded-md transition-all duration-500"
          :class="{
            'ring-2 ring-orange-500 ring-opacity-50 bg-orange-50':
              highlightedProfileKey === `profile-${profileIndex}`,
            'shadow-lg': highlightedProfileKey === `profile-${profileIndex}`,
          }"
        >
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
              <CollapseIcon
                :is-expanded="!collapsedProfiles.has(`${profileIndex}`)"
                size="sm"
                @click="toggleProfile(profileIndex)"
              />
              <h6 class="text-sm font-medium text-gray-600">
                Profile {{ profileIndex + 1 }}
                <span
                  v-if="highlightedProfileKey === `profile-${profileIndex}`"
                  class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse"
                >
                  New!
                </span>
              </h6>
            </div>
            <button
              v-if="!viewMode"
              type="button"
              @click="removeProfile(profileIndex)"
              class="text-red-600 hover:text-red-800"
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
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <div
            v-show="!collapsedProfiles.has(`${profileIndex}`)"
            class="grid grid-cols-1 md:grid-cols-2 gap-4"
          >
            <div>
              <x-select
                v-model="profile.advisorIds"
                :options="advisorOptions"
                placeholder="Select advisors..."
                multiple
                filterable
                :disabled="viewMode"
                class="w-full min-h-[40px]"
                label="Advisors"
                required
                tooltip="Select one or more advisors or managers eligible to receive leads in this profile."
              >
                <template
                  #content-footer
                  v-if="advisorOptions.length > 0 && !viewMode"
                >
                  <ui-select-actions
                    @select-all="
                      profile.advisorIds = advisorOptions.map(
                        item => item.value,
                      )
                    "
                    @clear="profile.advisorIds = []"
                  />
                </template>
              </x-select>
            </div>
            <div>
              <x-select
                v-model="profile.nationalityIds"
                :options="nationalityOptions"
                placeholder="Select nationalities..."
                multiple
                filterable
                :disabled="viewMode"
                class="w-full min-h-[40px]"
                label="Nationalities"
                required
                tooltip="Select the nationalities of customers this profile applies to."
              >
                <template
                  #content-footer
                  v-if="nationalityOptions.length > 0 && !viewMode"
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
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

