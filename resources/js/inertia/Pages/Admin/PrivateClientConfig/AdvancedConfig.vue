<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
  activeTab.value = tab;

  // Load configuration for the selected quote type
  const quoteType = quoteTypes.find(qt => qt.name === tab);
  if (
    quoteType &&
    quoteType.ref.value &&
    quoteType.ref.value.loadExistingConfig
  ) {
    await quoteType.ref.value.loadExistingConfig();
  }
};

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
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">
        Private Client Configuration (Advanced)
      </h2>
      <x-button
        color="emerald"
        @click="saveConfiguration"
        :loading="loader"
        :disabled="loader || !isCurrentVersion"
      >
        <i class="ri-save-line mr-1"></i> Save All
      </x-button>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="quoteType in quoteTypes"
          :key="quoteType.name"
          @click="setActiveTab(quoteType.name)"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === quoteType.name
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
          ]"
        >
          {{ quoteType.label }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-lg shadow p-6">
      <template v-for="quoteType in quoteTypes" :key="quoteType.name">
        <div v-if="activeTab === quoteType.name">
          <component
            :is="quoteType.component"
            :ref="quoteType.ref"
            :insurers="insurers"
            :carMakes="carMakes"
            :locationAreas="locationAreas"
            :nationalities="nationalities"
            :currencies="currencies"
            :disabled="!isCurrentVersion"
          />
        </div>
      </template>
    </div>

    <!-- Version Info (if not current version) -->
    <div v-if="!isCurrentVersion" class="flex items-center text-amber-600 mt-4">
      <i class="ri-error-warning-line mr-1"></i>
      <span
        >You are viewing a historical version. Configuration is read-only.</span
      >
    </div>
  </div>
</template>

<style scoped>
/* Add any additional styling if needed */
</style>
