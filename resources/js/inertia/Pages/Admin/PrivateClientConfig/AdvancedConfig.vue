<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
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

const activeTab = ref('car');
const tabLoading = ref(false);
const isModuleCollapsed = ref(false);

// Create refs for each template to access their data
const carTemplateRef = ref(null);
const healthTemplateRef = ref(null);
const lifeTemplateRef = ref(null);
const homeTemplateRef = ref(null);
const yachtTemplateRef = ref(null);

// Track versions for each quote type
const quoteTypeVersions = ref({
  car: null,
  health: null,
  life: null,
  home: null,
  yacht: null,
});

// Handle version loaded events
const handleVersionLoaded = (quoteTypeName, version) => {
  quoteTypeVersions.value[quoteTypeName] = version;
};

// Create dynamic tab items based on backend quoteTypes
const tabItems = computed(() => {
  if (!props.quoteTypes || !props.quoteTypeCodeEnum) return [];

  return props.quoteTypes
    .map(quoteType => {
      let templateName = '';
      let templateLabel = quoteType.text;

      // Map quote type short codes to our template names using enum
      if (quoteType.short_code === props.quoteTypeCodeEnum.CAR) {
        templateName = 'car';
      } else if (quoteType.short_code === props.quoteTypeCodeEnum.HEALTH) {
        templateName = 'health';
      } else if (quoteType.short_code === props.quoteTypeCodeEnum.LIFE) {
        templateName = 'life';
      } else if (quoteType.short_code === props.quoteTypeCodeEnum.HOME) {
        templateName = 'home';
      } else if (quoteType.short_code === props.quoteTypeCodeEnum.YACHT) {
        templateName = 'yacht';
      }

      return {
        name: templateName,
        label: templateLabel,
        quoteTypeId: quoteType.id,
      };
    })
    .filter(item => item.name); // Only include mapped templates
});

const setActiveTab = async tab => {
  if (tabLoading.value) return; // Prevent multiple clicks

  tabLoading.value = true;
  activeTab.value = tab;
  isModuleCollapsed.value = false; // Expand when switching tabs

  try {
    // Wait for next tick to ensure component is mounted
    await nextTick();

    // Small delay to ensure component is fully rendered
    await new Promise(resolve => setTimeout(resolve, 100));

    // Load configuration for the selected quote type using direct refs
    let templateRef = null;
    switch (tab) {
      case 'car':
        templateRef = carTemplateRef.value;
        break;
      case 'health':
        templateRef = healthTemplateRef.value;
        break;
      case 'life':
        templateRef = lifeTemplateRef.value;
        break;
      case 'home':
        templateRef = homeTemplateRef.value;
        break;
      case 'yacht':
        templateRef = yachtTemplateRef.value;
        break;
    }

    if (templateRef && templateRef.loadExistingConfig) {
      await templateRef.loadExistingConfig();
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
  // Load the first available tab from dynamic data
  if (tabItems.value.length > 0) {
    await setActiveTab(tabItems.value[0].name);
  }
});
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
      </div>

      <div v-show="!isModuleCollapsed">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="quoteType in tabItems"
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
            <template v-for="quoteType in tabItems" :key="quoteType.name">
              <div v-if="activeTab === quoteType.name">
                <CarTemplate
                  v-if="quoteType.name === 'car'"
                  ref="carTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.name, $event)"
                />
                <HealthTemplate
                  v-else-if="quoteType.name === 'health'"
                  ref="healthTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.name, $event)"
                />
                <LifeTemplate
                  v-else-if="quoteType.name === 'life'"
                  ref="lifeTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.name, $event)"
                />
                <HomeTemplate
                  v-else-if="quoteType.name === 'home'"
                  ref="homeTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.name, $event)"
                />
                <YachtTemplate
                  v-else-if="quoteType.name === 'yacht'"
                  ref="yachtTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.name, $event)"
                />
              </div>
            </template>
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
