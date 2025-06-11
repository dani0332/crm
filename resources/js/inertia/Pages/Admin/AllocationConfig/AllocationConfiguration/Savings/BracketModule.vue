<script setup>
import { ref } from 'vue';

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
  nationalityOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits([
  'add-bracket',
  'remove-bracket',
  'add-profile',
  'remove-profile',
]);

// Validation functions
const validatePositiveNumber = value => {
  const num = parseFloat(value);
  return !isNaN(num) && num >= 0;
};

const formatNumberInput = event => {
  let value = event.target.value;

  // Allow empty value (user can clear the field completely)
  if (value === '') {
    return '';
  }

  // Remove any non-numeric characters except decimal point
  value = value.replace(/[^0-9.]/g, '');

  // Allow empty value after character removal
  if (value === '') {
    return '';
  }

  // Ensure only one decimal point
  const parts = value.split('.');
  if (parts.length > 2) {
    value = parts[0] + '.' + parts.slice(1).join('');
  }
  // Limit to 2 decimal places
  if (parts[1] && parts[1].length > 2) {
    value = parts[0] + '.' + parts[1].substring(0, 2);
  }

  event.target.value = value;
  return value;
};

const handleMinInput = (event, bracket) => {
  const formattedValue = formatNumberInput(event);
  // Allow empty values, don't force to 0 immediately
  bracket.min = formattedValue;
};

const handleMaxInput = (event, bracket) => {
  const formattedValue = formatNumberInput(event);
  // Allow empty values, don't force to 0 immediately
  bracket.max = formattedValue;
};

const handleMinBlur = (event, bracket) => {
  const value = event.target.value;
  // Convert to number on blur, default to 0 if empty
  bracket.min = value === '' ? 0 : parseFloat(value);
};

const handleMaxBlur = (event, bracket) => {
  const value = event.target.value;
  // Convert to number on blur, default to 0 if empty
  bracket.max = value === '' ? 0 : parseFloat(value);
};

const createEmptyProfile = () => ({
  advisorIds: [],
  nationalityIds: [],
});

const addBracket = () => {
  emit('add-bracket');
};

const removeBracket = index => {
  emit('remove-bracket', index);
};

const addProfile = bracket => {
  bracket.profiles.push(createEmptyProfile());
  emit('add-profile', bracket);
};

const removeProfile = (bracket, profileIndex) => {
  bracket.profiles.splice(profileIndex, 1);
  emit('remove-profile', bracket, profileIndex);
};
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">
          {{ title }}
        </h3>
        <x-button size="md" color="#ff5e00" type="button" @click="addBracket">
          Create new price bracket
        </x-button>
      </div>

      <div v-if="brackets.length === 0" class="text-center py-8 text-gray-500">
        No {{ type.toLowerCase() }} brackets configured. Click "Create new price
        bracket" to create one.
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="(bracket, bracketIndex) in brackets"
          :key="`bracket-${bracketIndex}`"
          class="border border-gray-200 rounded-lg p-4"
        >
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-md font-medium text-gray-800">
              {{ type }} (Bracket {{ bracketIndex + 1 }})
            </h4>
            <x-button
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

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Minimum Amount <span class="text-red-500">*</span>
              </label>
              <x-input
                v-model="bracket.min"
                class="!mb-0 mt-1"
                required
                @input="handleMinInput($event, bracket)"
                @blur="handleMinBlur($event, bracket)"
                placeholder="0.00"
              >
                <template #suffix>
                  <div
                    class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                  >
                    <span>USD</span>
                  </div>
                </template>
              </x-input>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Maximum Amount <span class="text-red-500">*</span>
              </label>
              <x-input
                v-model="bracket.max"
                class="!mb-0 mt-1"
                required
                @input="handleMaxInput($event, bracket)"
                @blur="handleMaxBlur($event, bracket)"
                placeholder="0.00"
              >
                <template #suffix>
                  <div
                    class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
                  >
                    <span>USD</span>
                  </div>
                </template>
              </x-input>
            </div>
          </div>

          <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-4">
              <h5 class="text-sm font-medium text-gray-700">
                Advisor Allocation Profiles
              </h5>
              <x-button
                size="sm"
                color="#ff5e00"
                type="button"
                @click="addProfile(bracket)"
              >
                Add Profile
              </x-button>
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
</template>
