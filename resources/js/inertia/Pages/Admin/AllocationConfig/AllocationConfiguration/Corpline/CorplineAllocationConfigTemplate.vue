<script setup>
import { ref, watch, onMounted } from 'vue';
import CorplineBracketModule from './CorplineBracketModule.vue';

const props = defineProps({
  configuration: {
    type: Object,
    default: null,
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

const emit = defineEmits(['data-update']);

const valueProfiles = ref([]);
const volumeProfiles = ref([]);
const validationErrors = ref({});

const initializeData = () => {
  if (props.configuration) {
    valueProfiles.value = props.configuration.value_profiles
      ? JSON.parse(JSON.stringify(props.configuration.value_profiles))
      : [];
    volumeProfiles.value = props.configuration.volume_profiles
      ? JSON.parse(JSON.stringify(props.configuration.volume_profiles))
      : [];
  } else {
    valueProfiles.value = [];
    volumeProfiles.value = [];
  }

  emitData();
};

const emitData = () => {
  const data = {
    value_profiles: valueProfiles.value,
    volume_profiles: volumeProfiles.value,
  };
  emit('data-update', data);
};

const validateProfile = (profile, profileIndex, type) => {
  const errors = [];

  if (!profile.advisorIds || profile.advisorIds.length === 0) {
    errors.push(
      `${type} Profile ${profileIndex + 1}: At least one advisor must be selected`,
    );
  }

  if (!profile.businessTypeIds || profile.businessTypeIds.length === 0) {
    errors.push(
      `${type} Profile ${profileIndex + 1}: At least one business type must be selected`,
    );
  }

  return errors;
};

const validateAllProfiles = () => {
  const errors = [];

  if (valueProfiles.value.length === 0 && volumeProfiles.value.length === 0) {
    errors.push(
      'At least one profile must be configured in either Value or Volume section',
    );
    return errors;
  }

  valueProfiles.value.forEach((profile, index) => {
    errors.push(...validateProfile(profile, index, 'Value'));
  });

  volumeProfiles.value.forEach((profile, index) => {
    errors.push(...validateProfile(profile, index, 'Volume'));
  });

  // Check for duplicate business types across value and volume profiles
  const businessTypeDuplicates = checkBusinessTypeDuplicates();
  if (businessTypeDuplicates.length > 0) {
    errors.push(...businessTypeDuplicates);
  }

  return errors;
};

const checkBusinessTypeDuplicates = () => {
  const errors = [];
  const valueBusinessTypes = new Set();
  const duplicateBusinessTypes = new Set();

  // Collect all business types from value profiles
  valueProfiles.value.forEach(profile => {
    if (profile.businessTypeIds && Array.isArray(profile.businessTypeIds)) {
      profile.businessTypeIds.forEach(btId => {
        valueBusinessTypes.add(btId);
      });
    }
  });

  // Check volume profiles for duplicates
  volumeProfiles.value.forEach(profile => {
    if (profile.businessTypeIds && Array.isArray(profile.businessTypeIds)) {
      profile.businessTypeIds.forEach(btId => {
        if (valueBusinessTypes.has(btId)) {
          duplicateBusinessTypes.add(btId);
        }
      });
    }
  });

  // If duplicates found, get business type names and create error message
  if (duplicateBusinessTypes.size > 0) {
    const duplicateIds = Array.from(duplicateBusinessTypes);
    const duplicateNames = duplicateIds
      .map(id => {
        const option = props.businessTypeOptions.find(
          opt => opt.value === id || opt.id === id,
        );
        return option ? option.text || option.label || option.name : null;
      })
      .filter(name => name !== null);

    if (duplicateNames.length > 0) {
      errors.push(
        `The following business type(s) are assigned to both Value and Volume profiles: ${duplicateNames.join(', ')}`,
      );
      errors.push(
        'Each business type must be assigned to either Value or Volume section only.',
      );
    } else {
      errors.push(
        'Some business types are assigned to both Value and Volume profiles. Each business type must be assigned to either Value or Volume section only.',
      );
    }
  }

  return errors;
};

const validate = () => {
  const errors = validateAllProfiles();
  validationErrors.value = errors;
  return {
    isValid: errors.length === 0,
    errors: errors,
  };
};

const clearValidationErrors = () => {
  validationErrors.value = {};
};

watch(
  [valueProfiles, volumeProfiles],
  () => {
    if (Object.keys(validationErrors.value).length > 0) {
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
    }
  },
  { deep: true },
);

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="space-y-6">
    <CorplineBracketModule
      title="Value"
      type="Value"
      :profiles="valueProfiles"
      :advisor-options="advisorOptions"
      :business-type-options="businessTypeOptions"
      :view-mode="viewMode"
    />

    <CorplineBracketModule
      title="Volume"
      type="Volume"
      :profiles="volumeProfiles"
      :advisor-options="advisorOptions"
      :business-type-options="businessTypeOptions"
      :view-mode="viewMode"
    />
  </div>
</template>
