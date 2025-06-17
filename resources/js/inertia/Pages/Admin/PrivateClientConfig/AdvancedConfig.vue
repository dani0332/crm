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

const activeTab = ref(null);
const tabLoading = ref(false);
const isModuleCollapsed = ref(false);

const carTemplateRef = ref(null);
const healthTemplateRef = ref(null);
const lifeTemplateRef = ref(null);
const homeTemplateRef = ref(null);
const yachtTemplateRef = ref(null);

const quoteTypeVersions = ref({
  [props.quoteTypeCodeEnum.Car]: null,
  [props.quoteTypeCodeEnum.Health]: null,
  [props.quoteTypeCodeEnum.Life]: null,
  [props.quoteTypeCodeEnum.Home]: null,
  [props.quoteTypeCodeEnum.Yacht]: null,
});

const handleVersionLoaded = (quoteTypeCode, version) => {
  quoteTypeVersions.value[quoteTypeCode] = version;
};

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

const setActiveTab = async tab => {
  if (tabLoading.value) return;

  tabLoading.value = true;
  activeTab.value = tab;
  isModuleCollapsed.value = false;

  try {
    await nextTick();

    await new Promise(resolve => setTimeout(resolve, 100));

    let templateRef = null;
    switch (tab) {
      case props.quoteTypeCodeEnum.Car:
        templateRef = carTemplateRef.value;
        break;
      case props.quoteTypeCodeEnum.Health:
        templateRef = healthTemplateRef.value;
        break;
      case props.quoteTypeCodeEnum.Life:
        templateRef = lifeTemplateRef.value;
        break;
      case props.quoteTypeCodeEnum.Home:
        templateRef = homeTemplateRef.value;
        break;
      case props.quoteTypeCodeEnum.Yacht:
        templateRef = yachtTemplateRef.value;
        break;
    }

    console.log(templateRef, tab);

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
    await setActiveTab(tabItems.value[0].code);
  }
});

// Add reactive data for current state
const isCurrentVersion = ref(true);
const allVersions = ref([]);
const selectedVersion = ref(null);
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
            <template v-for="quoteType in tabItems" :key="quoteType.code">
              <div v-if="activeTab === quoteType.code">
                <CarTemplate
                  v-if="quoteType.code === quoteTypeCodeEnum.Car"
                  ref="carTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.code, $event)"
                />
                <HealthTemplate
                  v-else-if="quoteType.code === quoteTypeCodeEnum.Health"
                  ref="healthTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.code, $event)"
                />
                <LifeTemplate
                  v-else-if="quoteType.code === quoteTypeCodeEnum.Life"
                  ref="lifeTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.code, $event)"
                />
                <HomeTemplate
                  v-else-if="quoteType.code === quoteTypeCodeEnum.Home"
                  ref="homeTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.code, $event)"
                />
                <YachtTemplate
                  v-else-if="quoteType.code === quoteTypeCodeEnum.Yacht"
                  ref="yachtTemplateRef"
                  :quote-type-id="quoteType.quoteTypeId"
                  :disabled="!isCurrentVersion"
                  @version-loaded="handleVersionLoaded(quoteType.code, $event)"
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
