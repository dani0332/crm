<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import CollapseIcon from './components/CollapseIcon.vue';
import CarTemplate from './templates/CarTemplate.vue';
import HealthTemplate from './templates/HealthTemplate.vue';
import LifeTemplate from './templates/LifeTemplate.vue';
import HomeTemplate from './templates/HomeTemplate.vue';
import YachtTemplate from './templates/YachtTemplate.vue';

const props = defineProps({
  configurations: Array,
  insurers: Array,
  carMakes: Array,
  locationAreas: Array,
  nationalities: Array,
  currencies: Array,
  allVersions: Array,
  selectedVersion: Number,
  isCurrentVersion: Boolean,
});

const activeTab = ref('car');
const loader = ref(false);
const tabLoading = ref(false);
const isModuleCollapsed = ref(false);

// Per quote-type version tracking
const quoteTypeVersions = ref({
  car: null,
  health: null,
  life: null,
  home: null,
  yacht: null,
});

// Create refs for each template to access their data
const carTemplateRef = ref(null);
const healthTemplateRef = ref(null);
const lifeTemplateRef = ref(null);
const homeTemplateRef = ref(null);
const yachtTemplateRef = ref(null);

const quoteTypes = [
  {
    id: 1,
    name: 'car',
    label: 'Car',
    quote_type_id: 1,
    component: CarTemplate,
    ref: carTemplateRef,
  },
  {
    id: 2,
    name: 'health',
    label: 'Health',
    quote_type_id: 3,
    component: HealthTemplate,
    ref: healthTemplateRef,
  },
  {
    id: 3,
    name: 'life',
    label: 'Life',
    quote_type_id: 4,
    component: LifeTemplate,
    ref: lifeTemplateRef,
  },
  {
    id: 4,
    name: 'home',
    label: 'Home',
    quote_type_id: 2,
    component: HomeTemplate,
    ref: homeTemplateRef,
  },
  {
    id: 5,
    name: 'yacht',
    label: 'Yacht',
    quote_type_id: 7,
    component: YachtTemplate,
    ref: yachtTemplateRef,
  },
];

const setActiveTab = async tab => {
  if (tabLoading.value) return; // Prevent multiple clicks

  tabLoading.value = true;
  activeTab.value = tab;
  isModuleCollapsed.value = false; // Expand when switching tabs

  try {
    // Wait for next tick to ensure component is mounted
    await nextTick();

    // Load configuration for the selected quote type
    const quoteType = quoteTypes.find(qt => qt.name === tab);
    if (
      quoteType &&
      quoteType.ref.value &&
      quoteType.ref.value.loadExistingConfig
    ) {
      await quoteType.ref.value.loadExistingConfig();
    }
  } catch (error) {
    console.error('Error loading tab configuration:', error);
  } finally {
    tabLoading.value = false;
  }
};

const toggleModule = () => {
  isModuleCollapsed.value = !isModuleCollapsed.value;
};

// Initialize first tab on mount
onMounted(async () => {
  await nextTick();
  await setActiveTab('car');
});

const configForm = useForm({
  configurations: [],
});

// Function to collect all profiles from all templates
const collectAllProfiles = () => {
  const allConfigurations = [];

  quoteTypes.forEach(quoteType => {
    const templateRef = quoteType.ref.value;
    if (templateRef && templateRef.getProfiles) {
      const profiles = templateRef.getProfiles();

      // Only add configuration if there are profiles
      if (profiles.length > 0) {
        const configItem = {
          quote_type_id: quoteType.quote_type_id,
          profiles: profiles,
        };

        allConfigurations.push(configItem);
      }
    }
  });

  return allConfigurations;
};

// Save function that creates new version
const saveConfiguration = () => {
  loader.value = true;

  try {
    // Collect all profiles from all templates
    const configurations = collectAllProfiles();

    if (configurations.length === 0) {
      alert('No configurations to save. Please add at least one profile.');
      loader.value = false;
      return;
    }

    // Update form data
    configForm.configurations = configurations;

    // Send to backend
    configForm.post(route('admin.private-client-config.upsert'), {
      onSuccess: () => {
        loader.value = false;
        // After successful save, redirect to the latest version
        if (window.location.search.includes('version=')) {
          window.location.href = route('admin.private-client-config.advanced');
        } else {
          window.location.reload();
        }
      },
      onError: errors => {
        loader.value = false;
        console.error('Save failed:', errors);
        // You can add user-friendly error handling here
      },
    });
  } catch (error) {
    loader.value = false;
    console.error('Error collecting configurations:', error);
    alert('Error occurred while preparing data. Please try again.');
  }
};

// Update quote type version when config is loaded
const updateQuoteTypeVersion = (quoteTypeName, version) => {
  quoteTypeVersions.value[quoteTypeName] = version;
};

// Get current quote type version
const getCurrentQuoteTypeVersion = () => {
  return quoteTypeVersions.value[activeTab.value];
};
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-2">
          <CollapseIcon
            :isExpanded="!isModuleCollapsed"
            size="md"
            @click="toggleModule"
          />
          <h2 class="text-xl font-medium text-gray-900">
            Private Client Configuration (Advanced)
          </h2>
        </div>
        <x-tooltip>
          <x-button
            size="md"
            color="#059669"
            @click="saveConfiguration"
            :loading="loader"
            :disabled="loader || !isCurrentVersion"
          >
            <i class="ri-save-line mr-1"></i> Save All Configurations
          </x-button>
          <template #tooltip>
            <span class="custom-tooltip-content">
              Save all quote type configurations and create a new version.
            </span>
          </template>
        </x-tooltip>
      </div>

      <div v-show="!isModuleCollapsed">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="quoteType in quoteTypes"
              :key="quoteType.name"
              @click="setActiveTab(quoteType.name)"
              :disabled="tabLoading"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm relative transition-colors flex items-center space-x-2',
                activeTab === quoteType.name
                  ? 'border-orange-500 text-orange-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                tabLoading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
              ]"
            >
              <span>{{ quoteType.label }}</span>

              <!-- Version indicator per tab -->
              <span
                v-if="quoteTypeVersions[quoteType.name]"
                class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full"
              >
                v{{ quoteTypeVersions[quoteType.name] }}
              </span>

              <!-- Loading indicator -->
              <div
                v-if="tabLoading && activeTab === quoteType.name"
                class="absolute -bottom-2 left-1/2 transform -translate-x-1/2"
              >
                <div
                  class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"
                ></div>
              </div>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="relative">
          <!-- Loading overlay -->
          <div
            v-if="tabLoading"
            class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg"
          >
            <div class="flex items-center space-x-2">
              <div
                class="animate-spin rounded-full h-6 w-6 border-b-2 border-orange-600"
              ></div>
              <span class="text-gray-600 font-medium"
                >Loading configuration...</span
              >
            </div>
          </div>

          <div class="space-y-6">
            <template v-for="quoteType in quoteTypes" :key="quoteType.name">
              <div v-if="activeTab === quoteType.name">
                <!-- Version info for current tab -->
                <div
                  v-if="getCurrentQuoteTypeVersion()"
                  class="mb-4 p-3 bg-blue-50 rounded-md border border-blue-200"
                >
                  <div class="flex items-center text-sm text-blue-700">
                    <i class="ri-information-line mr-2"></i>
                    <span
                      >Currently viewing {{ quoteType.label }} configuration
                      version {{ getCurrentQuoteTypeVersion() }}</span
                    >
                  </div>
                </div>

                <component
                  :is="quoteType.component"
                  :ref="quoteType.ref"
                  :disabled="!isCurrentVersion"
                  @versionLoaded="
                    version => updateQuoteTypeVersion(quoteType.name, version)
                  "
                />
              </div>
            </template>
          </div>
        </div>

        <!-- Version Info (if not current version) -->
        <div
          v-if="!isCurrentVersion"
          class="flex items-center text-amber-600 mt-6 p-3 bg-amber-50 rounded-md border border-amber-200"
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
