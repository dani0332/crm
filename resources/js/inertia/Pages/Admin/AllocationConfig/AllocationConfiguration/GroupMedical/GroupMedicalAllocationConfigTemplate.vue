<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import GroupMedicalBracketModule from './GroupMedicalBracketModule.vue';

const props = defineProps({
  configuration: {
    type: Object,
    default: null,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  planTypeOptions: {
    type: Array,
    default: () => [],
  },
  departmentOptions: {
    type: Array,
    default: () => [],
  },
  quoteType: {
    type: String,
    default: 'Group Medical',
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['data-update', 'request-save']);

const regions = [
  { key: 'auh', label: 'AUH (Branch)' },
  { key: 'non-auh', label: 'Non AUH (HQ)' },
];

const activeRegion = ref(regions[0].key);
const validationErrors = ref({
  auh: [],
  'non-auh': [],
});
const savingRegion = ref(null);
const errorBoxRef = ref(null);
const advisorOptionsCache = ref(new Map());
const advisorOptionsMap = ref({
  auh: { micro: [], nonMicro: [] },
  'non-auh': { micro: [], nonMicro: [] },
});
const regionViewMode = ref({
  auh: true,
  'non-auh': true,
});

const regionBrackets = ref({
  auh: { micro_brackets: [], non_micro_brackets: [] },
  'non-auh': { micro_brackets: [], non_micro_brackets: [] },
});

const normalizeProfile = profile => ({
  advisorIds: profile?.advisorIds ? [...profile.advisorIds] : [],
  planTypeIds: profile?.planTypeIds ? [...profile.planTypeIds] : [],
});

const normalizeBracket = bracket => ({
  employees_min: bracket?.employees_min ?? null,
  employees_max: bracket?.employees_max ?? null,
  departmentIds: bracket?.departmentIds ? [...bracket.departmentIds] : [],
  profiles: Array.isArray(bracket?.profiles)
    ? bracket.profiles.map(normalizeProfile)
    : [],
});

const cloneBrackets = brackets => {
  if (!Array.isArray(brackets)) return [];
  return brackets.map(normalizeBracket);
};

const emitData = () => {
  const payload = {};

  ['auh', 'non-auh'].forEach(regionKey => {
    const region = regionBrackets.value[regionKey] || {
      micro_brackets: [],
      non_micro_brackets: [],
    };
    // Always include every region so clearing a region (empty brackets) is sent to the backend
    payload[regionKey] = region;
  });

  emit('data-update', payload);
};

const getRegionLabel = regionKey =>
  regions.find(region => region.key === regionKey)?.label ?? regionKey;

const initializeData = async () => {
  advisorOptionsCache.value = new Map();
  advisorOptionsMap.value = {
    auh: { micro: [], nonMicro: [] },
    'non-auh': { micro: [], nonMicro: [] },
  };
  regionViewMode.value = {
    auh: props.viewMode,
    'non-auh': props.viewMode,
  };

  const config = props.configuration?.config ?? null;
  const hasRegionConfig = config && (config['auh'] || config['non-auh']);

  const nextState = {
    auh: {
      micro_brackets: cloneBrackets(config?.['auh']?.micro_brackets || []),
      non_micro_brackets: cloneBrackets(
        config?.['auh']?.non_micro_brackets || [],
      ),
    },
    'non-auh': {
      micro_brackets: cloneBrackets(
        config?.['non-auh']?.micro_brackets ||
          config?.['non_auh']?.micro_brackets ||
          [],
      ),
      non_micro_brackets: cloneBrackets(
        config?.['non-auh']?.non_micro_brackets ||
          config?.['non_auh']?.non_micro_brackets ||
          [],
      ),
    },
  };

  // Backward compatibility: fall back to legacy micro/non-micro if region keys are absent
  // if (!hasRegionConfig && props.configuration) {
  //   nextState['non-auh'].micro_brackets = cloneBrackets(
  //     props.configuration.micro_brackets ?? [],
  //   );
  //   nextState['non-auh'].non_micro_brackets = cloneBrackets(
  //     props.configuration.non_micro_brackets ?? [],
  //   );
  //   nextState.auh.micro_brackets = cloneBrackets([]);
  //   nextState.auh.non_micro_brackets = cloneBrackets([]);
  // }

  // Keep the user on the region they last interacted with if it has data; otherwise prefer the region with data; fallback to auh.
  const previouslySelected = activeRegion.value;
  const regionHasData = regionKey =>
    (nextState[regionKey]?.micro_brackets?.length ?? 0) > 0 ||
    (nextState[regionKey]?.non_micro_brackets?.length ?? 0) > 0;

  if (regionHasData(previouslySelected)) {
    activeRegion.value = previouslySelected;
  } else if (regionHasData('auh')) {
    activeRegion.value = 'auh';
  } else if (regionHasData('non-auh')) {
    activeRegion.value = 'non-auh';
  } else {
    activeRegion.value = previouslySelected || 'auh';
  }

  regionBrackets.value = nextState;
  emitData();
  await prefetchAdvisorOptions();
};

const validateBracket = (bracket, bracketIndex, type, regionLabel) => {
  const errors = [];

  if (!bracket.employees_min || bracket.employees_min <= 0) {
    errors.push(
      `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}: Minimum number of employees is required and must be greater than 0`,
    );
  }

  if (bracket.employees_min && bracket.employees_min > 99999) {
    errors.push(
      `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}: Minimum number of employees cannot exceed 99999`,
    );
  }

  if (!bracket.employees_max || bracket.employees_max <= 0) {
    errors.push(
      `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}: Maximum number of employees is required and must be greater than 0`,
    );
  }

  if (bracket.employees_max && bracket.employees_max > 99999) {
    errors.push(
      `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}: Maximum number of employees cannot exceed 99999`,
    );
  }

  if (
    bracket.employees_min &&
    bracket.employees_max &&
    parseFloat(bracket.employees_min) >= parseFloat(bracket.employees_max)
  ) {
    errors.push(
      `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}: Minimum employees must be less than maximum employees`,
    );
  }

  if (!bracket.profiles || bracket.profiles.length === 0) {
    errors.push(
      `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}: At least one advisor profile is required`,
    );
  } else {
    bracket.profiles.forEach((profile, profileIndex) => {
      if (!profile.advisorIds || profile.advisorIds.length === 0) {
        errors.push(
          `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one advisor must be selected`,
        );
      }

      if (!profile.planTypeIds || profile.planTypeIds.length === 0) {
        errors.push(
          `[${regionLabel}] ${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one plan type must be selected`,
        );
      }
    });
  }

  return errors;
};

const validateRegion = regionKey => {
  const regionLabel = getRegionLabel(regionKey);
  const { micro_brackets: micro, non_micro_brackets: nonMicro } = regionBrackets
    .value[regionKey] || { micro_brackets: [], non_micro_brackets: [] };
  const errors = [];

  const regionHasData = micro.length > 0 || nonMicro.length > 0;
  if (!regionHasData) {
    return errors;
  }

  if (micro.length === 0) {
    errors.push(
      `[${regionLabel}] At least one Micro bracket must be configured`,
    );
  }

  if (nonMicro.length === 0) {
    errors.push(
      `[${regionLabel}] At least one Non-Micro bracket must be configured`,
    );
  }

  micro.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Micro', regionLabel));
  });

  nonMicro.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Non-Micro', regionLabel));
  });

  return errors;
};

const setRegionErrors = (regionKey, errors) => {
  validationErrors.value = {
    ...validationErrors.value,
    [regionKey]: errors,
  };
};

const validate = regionKey => {
  if (regionKey) {
    const errors = validateRegion(regionKey);
    setRegionErrors(regionKey, errors);
    return { isValid: errors.length === 0, errors };
  }

  const auhErrors = validateRegion('auh');
  const nonAuhErrors = validateRegion('non-auh');
  const hasAnyRegion =
    (regionBrackets.value['auh']?.micro_brackets?.length || 0) > 0 ||
    (regionBrackets.value['auh']?.non_micro_brackets?.length || 0) > 0 ||
    (regionBrackets.value['non-auh']?.micro_brackets?.length || 0) > 0 ||
    (regionBrackets.value['non-auh']?.non_micro_brackets?.length || 0) > 0;

  if (!hasAnyRegion) {
    const message =
      'Please configure at least one Micro or Non-Micro bracket for AUH or Non-AUH.';
    setRegionErrors('auh', [message]);
    setRegionErrors('non-auh', [message]);
    return { isValid: false, errors: [message] };
  }

  setRegionErrors('auh', auhErrors);
  setRegionErrors('non-auh', nonAuhErrors);

  const errors = [...auhErrors, ...nonAuhErrors];

  return {
    isValid: errors.length === 0,
    errors,
  };
};

const enterEditMode = regionKey => {
  regionViewMode.value = {
    ...regionViewMode.value,
    [regionKey]: false,
  };
};

const cancelRegionEdit = async regionKey => {
  await initializeData();
  regionViewMode.value = {
    ...regionViewMode.value,
    [regionKey]: true,
  };
};

const saveRegion = async regionKey => {
  const validation = validate(regionKey);

  if (!validation.isValid) {
    // keep region in edit mode and show errors inline
    await nextTick();
    if (errorBoxRef.value) {
      errorBoxRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    return;
  }

  savingRegion.value = regionKey;
  // ensure payload is up to date
  emitData();
  emit('request-save', { regionKey, isValid: true });
};

const clearSavingState = () => {
  savingRegion.value = null;
};

const clearValidationErrors = () => {
  validationErrors.value = {
    auh: [],
    'non-auh': [],
  };
};

const createEmptyBracket = () => ({
  employees_min: null,
  employees_max: null,
  departmentIds: [],
  profiles: [],
});

const setAdvisorOptions = (regionKey, bracketType, bracketIndex, options) => {
  const typeKey = bracketType === 'micro_brackets' ? 'micro' : 'nonMicro';
  const region = advisorOptionsMap.value[regionKey] ?? {
    micro: [],
    nonMicro: [],
  };
  const typeOptions = [...(region[typeKey] || [])];
  typeOptions[bracketIndex] = options;

  advisorOptionsMap.value = {
    ...advisorOptionsMap.value,
    [regionKey]: {
      ...region,
      [typeKey]: typeOptions,
    },
  };
};

const getAdvisorOptions = (regionKey, bracketType, bracketIndex) => {
  const typeKey = bracketType === 'micro_brackets' ? 'micro' : 'nonMicro';
  return (
    advisorOptionsMap.value?.[regionKey]?.[typeKey]?.[bracketIndex] ||
    props.advisorOptions ||
    []
  );
};

const getCacheKey = departmentIds => {
  if (!departmentIds || departmentIds.length === 0) {
    return 'all';
  }
  return [...departmentIds].sort((a, b) => a - b).join('-');
};

const advisorOptionKey = value => String(value);

const normalizeAdvisorId = raw => {
  if (raw === null || raw === undefined || raw === '') {
    return raw;
  }
  if (typeof raw === 'number' && Number.isFinite(raw)) {
    return raw;
  }
  if (
    typeof raw === 'string' &&
    raw.trim() !== '' &&
    !Number.isNaN(Number(raw))
  ) {
    const n = Number(raw);
    if (Number.isSafeInteger(n)) {
      return n;
    }
  }

  return raw;
};

const findAdvisorOptionInCaches = rawId => {
  const key = advisorOptionKey(rawId);
  for (const opts of advisorOptionsCache.value.values()) {
    const hit = (opts || []).find(o => advisorOptionKey(o.value) === key);
    if (hit) {
      return {
        value: hit.value,
        label: hit.label ?? String(hit.value),
      };
    }
  }

  return null;
};

/**
 * Department filter only narrows which advisors appear in the picker; it must not
 * remove already-selected advisors. Merge current fetch results with options for
 * any profile advisorIds not returned by the active department filter (labels from
 * page props or prior department fetches in advisorOptionsCache).
 */
const mergeAdvisorOptionsWithSelectedProfiles = (fetchedOptions, bracket) => {
  const map = new Map();

  (fetchedOptions || []).forEach(opt => {
    map.set(advisorOptionKey(opt.value), {
      value: opt.value,
      label: opt.label ?? String(opt.value),
    });
  });

  const defaultById = new Map(
    (props.advisorOptions ?? []).map(o => [
      advisorOptionKey(o.value),
      { value: o.value, label: o.label ?? String(o.value) },
    ]),
  );

  bracket.profiles?.forEach(profile => {
    (profile.advisorIds || []).forEach(rawId => {
      const id = normalizeAdvisorId(rawId);
      const key = advisorOptionKey(id);
      if (map.has(key)) {
        return;
      }
      const fromDefault = defaultById.get(key);
      if (fromDefault) {
        map.set(key, fromDefault);

        return;
      }
      const fromCache = findAdvisorOptionInCaches(id);
      if (fromCache) {
        map.set(key, fromCache);

        return;
      }
      map.set(key, {
        value: id,
        label: `Advisor (${id})`,
      });
    });
  });

  return Array.from(map.values());
};

const alignProfileAdvisorIdsToOptions = (mergedOptions, bracket) => {
  if (!bracket?.profiles?.length || !mergedOptions?.length) {
    return;
  }

  bracket.profiles.forEach(profile => {
    if (!Array.isArray(profile.advisorIds) || profile.advisorIds.length === 0) {
      return;
    }
    profile.advisorIds = profile.advisorIds.map(rawId => {
      const key = advisorOptionKey(rawId);
      const match = mergedOptions.find(o => advisorOptionKey(o.value) === key);
      return match ? match.value : normalizeAdvisorId(rawId);
    });
  });
};

const fetchAdvisorsForDepartments = async (departmentIds, regionKey) => {
  const cacheKey = `${regionKey || 'all'}-${getCacheKey(departmentIds)}`;
  if (advisorOptionsCache.value.has(cacheKey)) {
    return advisorOptionsCache.value.get(cacheKey);
  }

  try {
    if (!departmentIds || departmentIds.length === 0) {
      const defaultOptions = props.advisorOptions ?? [];
      advisorOptionsCache.value.set(cacheKey, defaultOptions);
      return defaultOptions;
    }

    const response = await axios.post('/advisors/by-quote-type', {
      quote_type: props.quoteType || 'GroupMedical',
      department_ids: departmentIds,
    });

    const options =
      response.data?.success && Array.isArray(response.data?.data)
        ? response.data.data.map(advisor => ({
            value: advisor.id,
            label: advisor.name,
          }))
        : [];

    advisorOptionsCache.value.set(cacheKey, options);
    return options;
  } catch (error) {
    console.error('Error fetching advisors by department:', error);
    return [];
  }
};

const hydrateAdvisorOptionsForBracket = async (
  regionKey,
  bracketType,
  bracketIndex,
) => {
  const bracket =
    regionBrackets.value[regionKey]?.[bracketType]?.[bracketIndex];
  if (!bracket) {
    return;
  }

  const typeKey = bracketType === 'micro_brackets' ? 'micro' : 'nonMicro';
  const previousOptions =
    advisorOptionsMap.value?.[regionKey]?.[typeKey]?.[bracketIndex] ?? null;

  const stableWhileLoading = mergeAdvisorOptionsWithSelectedProfiles(
    previousOptions ?? props.advisorOptions ?? [],
    bracket,
  );
  setAdvisorOptions(regionKey, bracketType, bracketIndex, stableWhileLoading);
  alignProfileAdvisorIdsToOptions(stableWhileLoading, bracket);

  const options = await fetchAdvisorsForDepartments(
    bracket.departmentIds,
    regionKey,
  );
  const mergedOptions = mergeAdvisorOptionsWithSelectedProfiles(
    options,
    bracket,
  );
  setAdvisorOptions(regionKey, bracketType, bracketIndex, mergedOptions);
  alignProfileAdvisorIdsToOptions(mergedOptions, bracket);
};

const handleDepartmentChange = async (
  regionKey,
  bracketType,
  bracketIndex,
  departmentIds,
) => {
  const bracket =
    regionBrackets.value[regionKey]?.[bracketType]?.[bracketIndex];
  if (!bracket) return;

  bracket.departmentIds = departmentIds;
  await hydrateAdvisorOptionsForBracket(regionKey, bracketType, bracketIndex);
};

const prefetchAdvisorOptions = async () => {
  for (const region of regions) {
    const regionKey = region.key;
    for (const bracketType of ['micro_brackets', 'non_micro_brackets']) {
      const brackets = regionBrackets.value[regionKey]?.[bracketType] || [];
      for (let i = 0; i < brackets.length; i += 1) {
        // eslint-disable-next-line no-await-in-loop
        await hydrateAdvisorOptionsForBracket(regionKey, bracketType, i);
      }
    }
  }
};

const addBracket = async (regionKey, bracketType) => {
  const target = regionBrackets.value[regionKey][bracketType];
  target.push(createEmptyBracket());
  const newIndex = target.length - 1;
  await hydrateAdvisorOptionsForBracket(regionKey, bracketType, newIndex);
};

const removeBracket = (regionKey, bracketType, index) => {
  const target = regionBrackets.value[regionKey][bracketType];
  target.splice(index, 1);
};

const onAddProfile = () => {
  // Profile addition is handled within GroupMedicalBracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within GroupMedicalBracketModule
};

watch(
  () => regionBrackets.value,
  () => {
    const hasErrors = Object.values(validationErrors.value || {}).some(
      errs => errs?.length > 0,
    );
    if (hasErrors) {
      clearValidationErrors();
    }
    emitData();
  },
  { deep: true },
);

onMounted(() => {
  initializeData();
});

watch(
  () => props.configuration,
  newConfig => {
    if (newConfig) {
      initializeData();
      clearSavingState();
    }
  },
  { deep: true },
);

defineExpose({
  validate,
  clearValidationErrors,
  clearSavingState,
});
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white border border-gray-200 rounded-lg px-4 sm:px-6">
      <div
        class="flex border-b border-gray-200 gap-6 sm:gap-10"
        role="tablist"
        aria-label="Allocation region"
      >
        <button
          v-for="region in regions"
          :key="region.key"
          type="button"
          role="tab"
          :aria-selected="activeRegion === region.key"
          class="-mb-px py-3.5 text-sm font-medium border-b-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 rounded-t"
          :class="
            activeRegion === region.key
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'
          "
          @click="activeRegion = region.key"
        >
          {{ region.label }}
        </button>
      </div>
    </div>

    <div
      v-if="(validationErrors[activeRegion] || []).length > 0"
      class="bg-red-50 border border-red-200 text-red-700 rounded-md p-3 text-sm"
      ref="errorBoxRef"
    >
      <ul class="list-disc ml-4 space-y-1">
        <li v-for="(err, idx) in validationErrors[activeRegion]" :key="idx">
          {{ err }}
        </li>
      </ul>
    </div>

    <GroupMedicalBracketModule
      title="Micro"
      type="Micro"
      :brackets="regionBrackets[activeRegion].micro_brackets"
      :advisor-options-resolver="
        bracketIndex =>
          getAdvisorOptions(activeRegion, 'micro_brackets', bracketIndex)
      "
      :plan-type-options="planTypeOptions"
      :department-options="departmentOptions"
      :view-mode="regionViewMode[activeRegion]"
      @add-bracket="addBracket(activeRegion, 'micro_brackets')"
      @remove-bracket="removeBracket(activeRegion, 'micro_brackets', $event)"
      @department-change="
        ({ bracketIndex, departmentIds }) =>
          handleDepartmentChange(
            activeRegion,
            'micro_brackets',
            bracketIndex,
            departmentIds,
          )
      "
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <GroupMedicalBracketModule
      title="Non-Micro"
      type="Non-Micro"
      :brackets="regionBrackets[activeRegion].non_micro_brackets"
      :advisor-options-resolver="
        bracketIndex =>
          getAdvisorOptions(activeRegion, 'non_micro_brackets', bracketIndex)
      "
      :plan-type-options="planTypeOptions"
      :department-options="departmentOptions"
      :view-mode="regionViewMode[activeRegion]"
      @add-bracket="addBracket(activeRegion, 'non_micro_brackets')"
      @remove-bracket="
        removeBracket(activeRegion, 'non_micro_brackets', $event)
      "
      @department-change="
        ({ bracketIndex, departmentIds }) =>
          handleDepartmentChange(
            activeRegion,
            'non_micro_brackets',
            bracketIndex,
            departmentIds,
          )
      "
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <div class="flex items-center gap-3 justify-end">
      <template v-if="regionViewMode[activeRegion]">
        <x-button
          size="sm"
          color="#ff5e00"
          type="button"
          @click="enterEditMode(activeRegion)"
        >
          Edit {{ getRegionLabel(activeRegion) }}
        </x-button>
      </template>
      <template v-else>
        <x-button
          size="sm"
          color="secondary"
          outlined
          type="button"
          @click="cancelRegionEdit(activeRegion)"
        >
          Cancel
        </x-button>
        <x-button
          size="sm"
          color="emerald"
          type="button"
          :loading="savingRegion === activeRegion"
          :disabled="savingRegion === activeRegion"
          @click="saveRegion(activeRegion)"
        >
          Save {{ getRegionLabel(activeRegion) }}
        </x-button>
      </template>
    </div>
  </div>
</template>
