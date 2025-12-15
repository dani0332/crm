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
  profiles: {
    type: Array,
    required: true,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  businessTypeOptions: {
    type: Array,
    default: () => [],
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
});

const highlightedProfileIndex = ref(-1);
const collapsedProfiles = ref(new Set());
const isModuleCollapsed = ref(false);
const isInitialized = ref(false);

onMounted(() => {
  setTimeout(() => {
    isInitialized.value = true;
  }, 100);
});

watch(
  () => props.profiles.length,
  (newLength, oldLength) => {
    if (isInitialized.value && newLength > oldLength) {
      const newProfileIndex = newLength - 1;
      highlightedProfileIndex.value = newProfileIndex;

      nextTick(() => {
        const newProfileElement = document.querySelector(
          `[data-profile-index="${props.type.toLowerCase()}-${newProfileIndex}"]`,
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
    } else if (isInitialized.value && newLength < oldLength) {
      highlightedProfileIndex.value = -1;
    }
  },
);

const createEmptyProfile = () => ({
  advisorIds: [],
  businessTypeIds: [],
});

const addProfile = () => {
  props.profiles.push(createEmptyProfile());
};

const removeProfile = profileIndex => {
  props.profiles.splice(profileIndex, 1);
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
          <x-button size="md" color="#ff5e00" type="button" @click="addProfile">
            Add Profile
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Add a profile for this {{ type }} category.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div v-show="!isModuleCollapsed">
        <div
          v-if="profiles.length === 0"
          class="text-center py-8 text-gray-500"
        >
          No profiles configured. Click "Add Profile" to get started.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="(profile, profileIndex) in profiles"
            :key="`profile-${profileIndex}`"
            :data-profile-index="`${type.toLowerCase()}-${profileIndex}`"
            class="bg-gray-50 p-4 rounded-md border border-gray-200 transition-all duration-500"
            :class="{
              'ring-2 ring-orange-500 ring-opacity-50 bg-orange-50':
                highlightedProfileIndex === profileIndex,
              'shadow-lg': highlightedProfileIndex === profileIndex,
            }"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center space-x-2">
                <CollapseIcon
                  :is-expanded="!collapsedProfiles.has(profileIndex)"
                  size="sm"
                  @click="toggleProfile(profileIndex)"
                />
                <h6 class="text-sm font-medium text-gray-700">
                  Profile {{ profileIndex + 1 }}
                  <span
                    v-if="highlightedProfileIndex === profileIndex"
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
              v-show="!collapsedProfiles.has(profileIndex)"
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
                  v-model="profile.businessTypeIds"
                  :options="businessTypeOptions"
                  placeholder="Select business types..."
                  multiple
                  filterable
                  :disabled="viewMode"
                  class="w-full min-h-[40px]"
                  label="Business Types"
                  required
                  tooltip="Select the business types this profile applies to."
                >
                  <template
                    #content-footer
                    v-if="businessTypeOptions.length > 0 && !viewMode"
                  >
                    <ui-select-actions
                      @select-all="
                        profile.businessTypeIds = businessTypeOptions.map(
                          item => item.value,
                        )
                      "
                      @clear="profile.businessTypeIds = []"
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
</template>
