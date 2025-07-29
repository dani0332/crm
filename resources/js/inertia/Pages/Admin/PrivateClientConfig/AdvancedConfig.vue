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

const configurations = ref({});
const dropdownData = ref({});
const versionData = ref({});
const currentVersions = ref({});

const loadConfigurationForTab = async quoteTypeCode => {
  const quoteType = props.quoteTypes.find(t => t.code === quoteTypeCode);
  if (!quoteType) return;

  tabLoading.value = true;

  try {
    const response = await axios.get(
      route('admin.private-client-config.latest-by-quote-type'),
      {
        params: {
          quote_type_id: quoteType.id,
          _t: Date.now(), // Cache busting parameter
        },
      },
    );

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

  await loadConfigurationForTab(tab);
};

const handleVersionLoaded = (quoteTypeCode, version) => {
  currentVersions.value[quoteTypeCode] = version;
};

const handleConfigurationSaved = async quoteTypeCode => {
  await loadConfigurationForTab(quoteTypeCode);
};

const templateComponents = {
  [props.quoteTypeCodeEnum.Car]: CarTemplate,
  [props.quoteTypeCodeEnum.Health]: HealthTemplate,
  [props.quoteTypeCodeEnum.Life]: LifeTemplate,
  [props.quoteTypeCodeEnum.Home]: HomeTemplate,
  [props.quoteTypeCodeEnum.Yacht]: YachtTemplate,
};

const getTemplateComponent = quoteTypeCode => {
  return templateComponents[quoteTypeCode];
};

onMounted(async () => {
  await nextTick();
  if (props.quoteTypes.length > 0) {
    await setActiveTab(props.quoteTypes[0].code);
  }
});
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <div>
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="quoteType in quoteTypes"
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
              <span>{{ quoteType.text }}</span>

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

        <div class="relative">
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
                      quoteTypes.find(t => t.code === activeTab)?.text ||
                      'configuration'
                    }}...
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="space-y-6">
            <component
              :is="getTemplateComponent(activeTab)"
              v-if="activeTab && getTemplateComponent(activeTab)"
              :quote-type="quoteTypes.find(t => t.code === activeTab)"
              :initial-config="configurations[activeTab]"
              :dropdown-data="dropdownData[activeTab]"
              :version-data="versionData[activeTab]"
              @configuration-saved="handleConfigurationSaved(activeTab)"
              @version-loaded="handleVersionLoaded(activeTab, $event)"
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
