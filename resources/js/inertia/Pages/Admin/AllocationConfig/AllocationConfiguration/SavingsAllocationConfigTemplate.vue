<template>
  <div class="space-y-6">
    <!-- Savings Specific Configuration -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h3 class="text-lg font-medium text-blue-900 mb-2">
        Savings Investment Configuration
      </h3>
      <p class="text-sm text-blue-700">
        Configure allocation rules for Savings products with Lumpsum and Regular
        investment brackets.
      </p>
    </div>

    <!-- Lumpsum Investment Brackets -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
      <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            Lumpsum Investment Brackets
          </h3>
          <button
            type="button"
            @click="addLumpsumBracket"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
          >
            <svg
              class="h-4 w-4 mr-2"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 4.5v15m7.5-7.5h-15"
              />
            </svg>
            Add Lumpsum Bracket
          </button>
        </div>

        <div
          v-if="lumpsumBrackets.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No lumpsum brackets configured. Click "Add Lumpsum Bracket" to create
          one.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(bracket, bracketIndex) in lumpsumBrackets"
            :key="`lumpsum-${bracketIndex}`"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-md font-medium text-gray-800">
                Lumpsum Bracket {{ bracketIndex + 1 }}
              </h4>
              <button
                type="button"
                @click="removeLumpsumBracket(bracketIndex)"
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
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                  />
                </svg>
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Minimum Amount (AED) <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="bracket.min"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Maximum Amount (AED) <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="bracket.max"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
            </div>

            <!-- Allocation Profiles for this bracket -->
            <div class="border-t pt-4">
              <div class="flex items-center justify-between mb-4">
                <h5 class="text-sm font-medium text-gray-700">
                  Advisor Allocation Profiles
                </h5>
                <button
                  type="button"
                  @click="addProfile(bracket)"
                  class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                >
                  <svg
                    class="h-3 w-3 mr-1"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4.5v15m7.5-7.5h-15"
                    />
                  </svg>
                  Add Profile
                </button>
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
                  :key="`lumpsum-profile-${bracketIndex}-${profileIndex}`"
                  class="bg-gray-50 p-4 rounded-md"
                >
                  <div class="flex items-center justify-between mb-3">
                    <h6 class="text-sm font-medium text-gray-600">
                      Profile {{ profileIndex + 1 }}
                    </h6>
                    <button
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

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <x-field label="Advisors" required>
                        <x-select
                          v-model="profile.advisorIds"
                          :options="advisorOptions"
                          placeholder="Select advisors..."
                          multiple
                          filterable
                          class="w-full min-h-[40px]"
                        >
                          <template
                            #content-footer
                            v-if="advisorOptions.length > 0"
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
                      </x-field>
                    </div>
                    <div>
                      <x-field label="Nationalities" required>
                        <x-select
                          v-model="profile.nationalityIds"
                          :options="nationalityOptions"
                          placeholder="Select nationalities..."
                          multiple
                          filterable
                          class="w-full min-h-[40px]"
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
                      </x-field>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Regular Investment Brackets -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
      <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            Regular Investment Brackets
          </h3>
          <button
            type="button"
            @click="addRegularBracket"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
          >
            <svg
              class="h-4 w-4 mr-2"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 4.5v15m7.5-7.5h-15"
              />
            </svg>
            Add Regular Bracket
          </button>
        </div>

        <div
          v-if="regularBrackets.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No regular brackets configured. Click "Add Regular Bracket" to create
          one.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(bracket, bracketIndex) in regularBrackets"
            :key="`regular-${bracketIndex}`"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-md font-medium text-gray-800">
                Regular Bracket {{ bracketIndex + 1 }}
              </h4>
              <button
                type="button"
                @click="removeRegularBracket(bracketIndex)"
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
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                  />
                </svg>
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Minimum Amount (AED) <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="bracket.min"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Maximum Amount (AED) <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="bracket.max"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
            </div>

            <!-- Allocation Profiles for this bracket -->
            <div class="border-t pt-4">
              <div class="flex items-center justify-between mb-4">
                <h5 class="text-sm font-medium text-gray-700">
                  Advisor Allocation Profiles
                </h5>
                <button
                  type="button"
                  @click="addProfile(bracket)"
                  class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                >
                  <svg
                    class="h-3 w-3 mr-1"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4.5v15m7.5-7.5h-15"
                    />
                  </svg>
                  Add Profile
                </button>
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
                  :key="`regular-profile-${bracketIndex}-${profileIndex}`"
                  class="bg-gray-50 p-4 rounded-md"
                >
                  <div class="flex items-center justify-between mb-3">
                    <h6 class="text-sm font-medium text-gray-600">
                      Profile {{ profileIndex + 1 }}
                    </h6>
                    <button
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

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <x-field label="Advisors" required>
                        <x-select
                          v-model="profile.advisorIds"
                          :options="advisorOptions"
                          placeholder="Select advisors..."
                          multiple
                          filterable
                          class="w-full min-h-[40px]"
                        >
                          <template
                            #content-footer
                            v-if="advisorOptions.length > 0"
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
                      </x-field>
                    </div>
                    <div>
                      <x-field label="Nationalities" required>
                        <x-select
                          v-model="profile.nationalityIds"
                          :options="nationalityOptions"
                          placeholder="Select nationalities..."
                          multiple
                          filterable
                          class="w-full min-h-[40px]"
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
                      </x-field>
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

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';

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
});

const emit = defineEmits(['data-update']);

// Internal state management
const lumpsumBrackets = ref([]);
const regularBrackets = ref([]);

// Initialize data from configuration
const initializeData = () => {
  if (props.configuration) {
    lumpsumBrackets.value = props.configuration.lumpsum_brackets || [];
    regularBrackets.value = props.configuration.regular_brackets || [];
  } else {
    lumpsumBrackets.value = [];
    regularBrackets.value = [];
  }

  // Emit initial data
  emitData();
};

// Emit data to parent
const emitData = () => {
  const data = {
    lumpsum_brackets: lumpsumBrackets.value,
    regular_brackets: regularBrackets.value,
  };
  emit('data-update', data);
};

// Watch for changes and emit data
watch(
  [lumpsumBrackets, regularBrackets],
  () => {
    emitData();
  },
  { deep: true },
);

// Initialize on mount
onMounted(() => {
  initializeData();
});

// Watch for configuration changes (in case of external updates)
watch(
  () => props.configuration,
  newConfig => {
    if (newConfig) {
      initializeData();
    }
  },
  { deep: true },
);

const createEmptyBracket = () => ({
  min: 0,
  max: 0,
  profiles: [],
});

const createEmptyProfile = () => ({
  advisorIds: [],
  nationalityIds: [],
});

// Lumpsum Bracket Methods
const addLumpsumBracket = () => {
  lumpsumBrackets.value.push(createEmptyBracket());
};

const removeLumpsumBracket = index => {
  lumpsumBrackets.value.splice(index, 1);
};

// Regular Bracket Methods
const addRegularBracket = () => {
  regularBrackets.value.push(createEmptyBracket());
};

const removeRegularBracket = index => {
  regularBrackets.value.splice(index, 1);
};

// Profile Methods (shared between lumpsum and regular)
const addProfile = bracket => {
  bracket.profiles.push(createEmptyProfile());
};

const removeProfile = (bracket, profileIndex) => {
  bracket.profiles.splice(profileIndex, 1);
};
</script>
