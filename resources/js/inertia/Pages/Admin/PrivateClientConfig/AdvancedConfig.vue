<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import axios from 'axios';
import CollapseIcon from './components/CollapseIcon.vue';
import CarTemplate from './templates/CarTemplate.vue';
import HealthTemplate from './templates/HealthTemplate.vue';
import LifeTemplate from './templates/LifeTemplate.vue';
import HomeTemplate from './templates/HomeTemplate.vue';
import YachtTemplate from './templates/YachtTemplate.vue';

const props = defineProps({
  quoteTypes: Array,
  quoteTypeCodeEnum: Object,
});

const activeTab = ref(null);
const tabLoading = ref(false);
const isModuleCollapsed = ref(false);

// Configuration state
const configurations = ref({});
const dropdownData = ref({});
const versionData = ref({});
const currentVersions = ref({});

const tabItems = computed(() => {
  if (!props.quoteTypes || !props.quoteTypeCodeEnum) return [];

  return props.quoteTypes
    .map(quoteType => {
      let templateLabel = quoteType.text;

      return {
        label: templateLabel,
        code: quoteType.code,
        quoteTypeId: quoteType.id,
      };
    })
    .filter(item => item.code);
});

// Load configuration for a specific quote type
const loadConfigurationForTab = async quoteTypeCode => {
  const quoteType = tabItems.value.find(t => t.code === quoteTypeCode);
  if (!quoteType) return;

  tabLoading.value = true;

  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: { quote_type_id: quoteType.quoteTypeId },
        headers: {
          'X-CSRF-TOKEN':
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content') || '',
        },
      },
    );

    // Store configuration data
    configurations.value[quoteTypeCode] = response.data.config || {
      profiles: [],
    };
    dropdownData.value[quoteTypeCode] = response.data.dropdownData || {};
    versionData.value[quoteTypeCode] = {
      allVersions: response.data.allVersions || [],
      currentVersion: response.data.version,
      isCurrentVersion: response.data.isCurrentVersion !== false,
    };
    currentVersions.value[quoteTypeCode] = response.data.version;
  } catch (error) {
    console.error('Error loading configuration:', error);
    // Set empty defaults on error
    configurations.value[quoteTypeCode] = { profiles: [] };
    dropdownData.value[quoteTypeCode] = {};
    versionData.value[quoteTypeCode] = {
      allVersions: [],
      currentVersion: null,
      isCurrentVersion: true,
    };
  } finally {
    tabLoading.value = false;
  }
};

const setActiveTab = async tab => {
  if (tabLoading.value) return;

  activeTab.value = tab;
  isModuleCollapsed.value = false;

  // Always load fresh configuration data on tab change
  await loadConfigurationForTab(tab);
};

const handleVersionLoaded = (quoteTypeCode, version) => {
  currentVersions.value[quoteTypeCode] = version;
};

const handleConfigurationSaved = async quoteTypeCode => {
  // Reload configuration after save
  await loadConfigurationForTab(quoteTypeCode);
};

const toggleModule = () => {
  isModuleCollapsed.value = !isModuleCollapsed.value;
};

// Initialize first tab on mount
onMounted(async () => {
  await nextTick();
  if (tabItems.value.length > 0) {
    await setActiveTab(tabItems.value[0].code);
  }
});
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div v-show="!isModuleCollapsed">
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="quoteType in tabItems"
              :key="quoteType.code"
              @click="setActiveTab(quoteType.code)"
              :disabled="tabLoading"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm relative transition-colors flex items-center space-x-2',
                activeTab === quoteType.code
                  ? 'border-orange-500 text-orange-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                tabLoading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
              ]"
            >
              <span>{{ quoteType.label }}</span>

              <div
                v-if="tabLoading && activeTab === quoteType.code"
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
          <!-- Loading Card -->
          <div
            v-if="tabLoading"
            class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
          >
            <div class="p-6 bg-white border-b border-gray-200">
              <div class="flex items-center justify-center py-8">
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
                    Loading
                    {{
                      tabItems.find(t => t.code === activeTab)?.label ||
                      'configuration'
                    }}...
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Content -->
          <div v-else class="space-y-6">
            <CarTemplate
              v-if="activeTab === quoteTypeCodeEnum.Car"
              :quote-type-id="
                tabItems.find(t => t.code === quoteTypeCodeEnum.Car)
                  ?.quoteTypeId
              "
              :initial-config="configurations[quoteTypeCodeEnum.Car]"
              :dropdown-data="dropdownData[quoteTypeCodeEnum.Car]"
              :version-data="versionData[quoteTypeCodeEnum.Car]"
              @configuration-saved="
                handleConfigurationSaved(quoteTypeCodeEnum.Car)
              "
              @version-loaded="
                handleVersionLoaded(quoteTypeCodeEnum.Car, $event)
              "
            />

            <HealthTemplate
              v-if="activeTab === quoteTypeCodeEnum.Health"
              :quote-type-id="
                tabItems.find(t => t.code === quoteTypeCodeEnum.Health)
                  ?.quoteTypeId
              "
              :initial-config="configurations[quoteTypeCodeEnum.Health]"
              :dropdown-data="dropdownData[quoteTypeCodeEnum.Health]"
              :version-data="versionData[quoteTypeCodeEnum.Health]"
              @configuration-saved="
                handleConfigurationSaved(quoteTypeCodeEnum.Health)
              "
              @version-loaded="
                handleVersionLoaded(quoteTypeCodeEnum.Health, $event)
              "
            />

            <LifeTemplate
              v-if="activeTab === quoteTypeCodeEnum.Life"
              :quote-type-id="
                tabItems.find(t => t.code === quoteTypeCodeEnum.Life)
                  ?.quoteTypeId
              "
              :initial-config="configurations[quoteTypeCodeEnum.Life]"
              :dropdown-data="dropdownData[quoteTypeCodeEnum.Life]"
              :version-data="versionData[quoteTypeCodeEnum.Life]"
              @configuration-saved="
                handleConfigurationSaved(quoteTypeCodeEnum.Life)
              "
              @version-loaded="
                handleVersionLoaded(quoteTypeCodeEnum.Life, $event)
              "
            />

            <HomeTemplate
              v-if="activeTab === quoteTypeCodeEnum.Home"
              :quote-type-id="
                tabItems.find(t => t.code === quoteTypeCodeEnum.Home)
                  ?.quoteTypeId
              "
              :initial-config="configurations[quoteTypeCodeEnum.Home]"
              :dropdown-data="dropdownData[quoteTypeCodeEnum.Home]"
              :version-data="versionData[quoteTypeCodeEnum.Home]"
              @configuration-saved="
                handleConfigurationSaved(quoteTypeCodeEnum.Home)
              "
              @version-loaded="
                handleVersionLoaded(quoteTypeCodeEnum.Home, $event)
              "
            />

            <YachtTemplate
              v-if="activeTab === quoteTypeCodeEnum.Yacht"
              :quote-type-id="
                tabItems.find(t => t.code === quoteTypeCodeEnum.Yacht)
                  ?.quoteTypeId
              "
              :initial-config="configurations[quoteTypeCodeEnum.Yacht]"
              :dropdown-data="dropdownData[quoteTypeCodeEnum.Yacht]"
              :version-data="versionData[quoteTypeCodeEnum.Yacht]"
              @configuration-saved="
                handleConfigurationSaved(quoteTypeCodeEnum.Yacht)
              "
              @version-loaded="
                handleVersionLoaded(quoteTypeCodeEnum.Yacht, $event)
              "
            />
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
