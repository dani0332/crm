<script setup>
import { ref, nextTick, watch, onMounted } from 'vue';
import CollapseIcon from '../Savings/components/CollapseIcon.vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
  brackets: {
    type: Array,
    required: true,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  planTypeOptions: {
    type: Array,
    default: () => [],
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'add-bracket',
  'remove-bracket',
  'add-profile',
  'remove-profile',
]);

const highlightedBracketIndex = ref(-1);
const highlightedProfileKey = ref('');
const collapsedBrackets = ref(new Set());
const collapsedProfiles = ref(new Set());
const isModuleCollapsed = ref(false);
const isInitialized = ref(false);

onMounted(() => {
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
});

watch(
  () => props.brackets.length,
  (newLength, oldLength) => {
    if (isInitialized.value && newLength > oldLength) {
      const newBracketIndex = newLength - 1;
      highlightedBracketIndex.value = newBracketIndex;

      nextTick(() => {
        const newBracketElement = document.querySelector(
          `[data-bracket-index="${props.type.toLowerCase()}-${newBracketIndex}"]`,
        );
        if (newBracketElement) {
          newBracketElement.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
          });
        }
      });

      setTimeout(() => {
        highlightedBracketIndex.value = -1;
      }, 2000);
    } else if (isInitialized.value && newLength < oldLength) {
      highlightedBracketIndex.value = -1;
    }
  },
);

watch(
  () => props.brackets.map(bracket => bracket.profiles?.length || 0),
  (newProfileCounts, oldProfileCounts) => {
    if (!isInitialized.value) return;

    newProfileCounts.forEach((newCount, bracketIndex) => {
      const oldCount = oldProfileCounts?.[bracketIndex] || 0;
      if (newCount > oldCount) {
        const profileIndex = newCount - 1;
        const profileKey = `${props.type.toLowerCase()}-${bracketIndex}-${profileIndex}`;
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
    });
  },
  { deep: true },
);

const handleInput = (event, bracket, field) => {
  let value = event.target.value.replace(/[^0-9]/g, '');
  if (value === '') {
    bracket[field] = null;
  } else {
    bracket[field] = parseInt(value, 10);
  }
};

const handleBlur = (event, bracket, field) => {
  if (bracket[field] !== null && bracket[field] !== '') {
    bracket[field] = parseInt(bracket[field], 10);
  }
};

const createEmptyProfile = () => ({
  advisorIds: [],
  planTypeIds: [],
});

const addBracket = () => {
  emit('add-bracket');
};

const removeBracket = index => {
  emit('remove-bracket', index);
};

const addProfile = bracket => {
  bracket.profiles.push(createEmptyProfile());
  emit('add-profile');
};

const removeProfile = (bracket, profileIndex) => {
  bracket.profiles.splice(profileIndex, 1);
  emit('remove-profile');
};

const toggleBracket = bracketIndex => {
  if (collapsedBrackets.value.has(bracketIndex)) {
    collapsedBrackets.value.delete(bracketIndex);
  } else {
    collapsedBrackets.value.add(bracketIndex);
  }
};

const toggleProfile = (bracketIndex, profileIndex) => {
  const profileKey = `${bracketIndex}-${profileIndex}`;
  if (collapsedProfiles.value.has(profileKey)) {
    collapsedProfiles.value.delete(profileKey);
  } else {
    collapsedProfiles.value.add(profileKey);
  }
};

const toggleModule = () => {
  isModuleCollapsed.value = !isModuleCollapsed.value;
};
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
          <CollapseIcon
            :is-expanded="!isModuleCollapsed"
            @click="toggleModule"
          />
          <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        </div>
        <x-tooltip v-if="!viewMode">
          <x-button size="md" color="#ff5e00" type="button" @click="addBracket">
            Create new price bracket
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Add a bracket for this {{ type }} category.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div v-show="!isModuleCollapsed">
        <div
          v-if="brackets.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No brackets configured. Click "Create new price bracket" to get
          started.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(bracket, bracketIndex) in brackets"
            :key="`bracket-${bracketIndex}`"
            :data-bracket-index="`${type.toLowerCase()}-${bracketIndex}`"
            class="border border-gray-200 rounded-lg p-4 transition-all duration-500"
            :class="{
              'ring-2 ring-orange-500 ring-opacity-50 bg-orange-50':
                highlightedBracketIndex === bracketIndex,
              'shadow-lg': highlightedBracketIndex === bracketIndex,
            }"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-2">
                <CollapseIcon
                  :is-expanded="!collapsedBrackets.has(bracketIndex)"
                  size="sm"
                  @click="toggleBracket(bracketIndex)"
                />
                <h4 class="text-md font-medium text-gray-700">
                  Bracket {{ bracketIndex + 1 }}
                  <span
                    v-if="highlightedBracketIndex === bracketIndex"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse"
                  >
                    New!
                  </span>
                </h4>
              </div>
              <x-button
                v-if="!viewMode"
                size="sm"
                color="error"
                outlined
                type="button"
                @click="removeBracket(bracketIndex)"
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
              v-show="!collapsedBrackets.has(bracketIndex)"
              class="space-y-4"
            >
              <!-- Number of Employees Section -->
              <div class="border-b pb-4">
                <h5 class="text-sm font-medium text-gray-700 mb-3">
                  Number of Employees
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <x-input
                      v-model="bracket.employees_min"
                      class="!mb-0 mt-1"
                      required
                      :disabled="viewMode"
                      @input="handleInput($event, bracket, 'employees_min')"
                      @blur="handleBlur($event, bracket, 'employees_min')"
                      placeholder="1"
                      label="Minimum"
                      tooltip="Set the minimum number of employees for this bracket."
                    />
                  </div>
                  <div>
                    <x-input
                      v-model="bracket.employees_max"
                      class="!mb-0 mt-1"
                      required
                      :disabled="viewMode"
                      @input="handleInput($event, bracket, 'employees_max')"
                      @blur="handleBlur($event, bracket, 'employees_max')"
                      placeholder="50"
                      label="Maximum"
                      tooltip="Set the maximum number of employees for this bracket."
                    />
                  </div>
                </div>
              </div>

              <!-- Advisor Allocation Profiles -->
              <div class="border-t pt-4">
                <div class="flex items-center justify-between mb-4">
                  <h5 class="text-sm font-medium text-gray-700">
                    Advisor Allocation Profiles
                  </h5>
                  <x-tooltip v-if="!viewMode">
                    <x-button
                      size="sm"
                      color="#ff5e00"
                      type="button"
                      @click="addProfile(bracket)"
                    >
                      Add Profile
                    </x-button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        Set who gets the lead – based on employee count and plan
                        type.
                      </span>
                    </template>
                  </x-tooltip>
                </div>

                <div
                  v-if="bracket.profiles.length === 0"
                  class="text-center py-4 text-gray-400 text-sm"
                >
                  No advisor profiles configured for this bracket.
                </div>

                <div v-else class="space-y-4">
                  <div
                    v-for="(profile, profileIndex) in bracket.profiles"
                    :key="`profile-${bracketIndex}-${profileIndex}`"
                    :data-profile-key="`${type.toLowerCase()}-${bracketIndex}-${profileIndex}`"
                    class="bg-gray-50 p-4 rounded-md transition-all duration-500"
                    :class="{
                      'ring-2 ring-orange-500 ring-opacity-50 bg-orange-50':
                        highlightedProfileKey ===
                        `${type.toLowerCase()}-${bracketIndex}-${profileIndex}`,
                      'shadow-lg':
                        highlightedProfileKey ===
                        `${type.toLowerCase()}-${bracketIndex}-${profileIndex}`,
                    }"
                  >
                    <div class="flex items-center justify-between mb-3">
                      <div class="flex items-center space-x-2">
                        <CollapseIcon
                          :is-expanded="
                            !collapsedProfiles.has(
                              `${bracketIndex}-${profileIndex}`,
                            )
                          "
                          size="sm"
                          @click="toggleProfile(bracketIndex, profileIndex)"
                        />
                        <h6 class="text-sm font-medium text-gray-600">
                          Profile {{ profileIndex + 1 }}
                          <span
                            v-if="
                              highlightedProfileKey ===
                              `${type.toLowerCase()}-${bracketIndex}-${profileIndex}`
                            "
                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse"
                          >
                            New!
                          </span>
                        </h6>
                      </div>
                      <button
                        v-if="!viewMode"
                        type="button"
                        @click="removeProfile(bracket, profileIndex)"
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
                      v-show="
                        !collapsedProfiles.has(
                          `${bracketIndex}-${profileIndex}`,
                        )
                      "
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
                          v-model="profile.planTypeIds"
                          :options="planTypeOptions"
                          placeholder="Select plan types..."
                          multiple
                          filterable
                          :disabled="viewMode"
                          class="w-full min-h-[40px]"
                          label="Plan Type"
                          required
                          tooltip="Select the plan types this profile applies to."
                        >
                          <template
                            #content-footer
                            v-if="planTypeOptions.length > 0 && !viewMode"
                          >
                            <ui-select-actions
                              @select-all="
                                profile.planTypeIds = planTypeOptions.map(
                                  item => item.value,
                                )
                              "
                              @clear="profile.planTypeIds = []"
                            />
                          </template>
                        </x-select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
