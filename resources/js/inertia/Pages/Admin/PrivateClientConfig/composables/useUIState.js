import { ref, computed } from 'vue';

export function useUIState(props, isCurrentVersion, configLoading) {
  const isModuleCollapsed = ref(false);
  const collapsedProfiles = ref(new Set());
  const isInitialized = ref(false);

  const isDisabled = computed(
    () => !isCurrentVersion.value || configLoading.value,
  );

  const nationalityOptions = computed(() => {
    return props.dropdownData.nationalities || [];
  });

  const quoteTypeCode = computed(() => props.quoteType.code);
  const quoteTypeLabel = computed(() => props.quoteType.text);

  const toggleModule = () => {
    isModuleCollapsed.value = !isModuleCollapsed.value;
  };

  const toggleProfile = profileIndex => {
    if (collapsedProfiles.value.has(profileIndex)) {
      collapsedProfiles.value.delete(profileIndex);
    } else {
      collapsedProfiles.value.add(profileIndex);
    }
  };

  const getCurrencySymbol = currencyId => {
    const currency = props.dropdownData.currencies?.find(
      c => c.value === currencyId,
    );
    return currency?.label || 'AED';
  };

  const initialize = () => {
    setTimeout(() => {
      isInitialized.value = true;
    }, 100);
  };

  return {
    isModuleCollapsed,
    collapsedProfiles,
    isInitialized,
    isDisabled,
    nationalityOptions,
    quoteTypeCode,
    quoteTypeLabel,
    toggleModule,
    toggleProfile,
    getCurrencySymbol,
    initialize,
  };
}
