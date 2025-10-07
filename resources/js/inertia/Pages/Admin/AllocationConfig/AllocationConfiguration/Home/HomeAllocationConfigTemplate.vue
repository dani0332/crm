<script setup>
import { ref, watch, onMounted } from 'vue';
import HomeBracketModule from './HomeBracketModule.vue';

const props = defineProps({
  configuration: {
    type: Object,
    default: null,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['data-update']);

const valueBrackets = ref([]);
const volumeBrackets = ref([]);
const validationErrors = ref({});

const initializeData = () => {
  if (props.configuration) {
    valueBrackets.value = props.configuration.value_brackets || [];
    volumeBrackets.value = props.configuration.volume_brackets || [];
  } else {
    valueBrackets.value = [];
    volumeBrackets.value = [];
  }

  emitData();
};

const emitData = () => {
  const data = {
    value_brackets: valueBrackets.value,
    volume_brackets: volumeBrackets.value,
  };
  emit('data-update', data);
};

const validateBracket = (bracket, bracketIndex, type) => {
  const errors = [];

  if (!bracket.contents_min || bracket.contents_min <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Contents minimum value is required and must be greater than 0`,
    );
  }

  if (!bracket.contents_max || bracket.contents_max <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Contents maximum value is required and must be greater than 0`,
    );
  }

  if (
    bracket.contents_min &&
    bracket.contents_max &&
    parseFloat(bracket.contents_min) >= parseFloat(bracket.contents_max)
  ) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Contents minimum must be less than maximum`,
    );
  }

  if (!bracket.building_min || bracket.building_min <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Building minimum value is required and must be greater than 0`,
    );
  }

  if (!bracket.building_max || bracket.building_max <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Building maximum value is required and must be greater than 0`,
    );
  }

  if (
    bracket.building_min &&
    bracket.building_max &&
    parseFloat(bracket.building_min) >= parseFloat(bracket.building_max)
  ) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Building minimum must be less than maximum`,
    );
  }

  if (!bracket.profiles || bracket.profiles.length === 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: At least one advisor profile is required`,
    );
  } else {
    bracket.profiles.forEach((profile, profileIndex) => {
      if (!profile.advisorIds || profile.advisorIds.length === 0) {
        errors.push(
          `${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one advisor must be selected`,
        );
      }

      if (!profile.locationIds || profile.locationIds.length === 0) {
        errors.push(
          `${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one location must be selected`,
        );
      }
    });
  }

  return errors;
};

const validateAllBrackets = () => {
  const errors = [];

  if (valueBrackets.value.length === 0 || volumeBrackets.value.length === 0) {
    if (valueBrackets.value.length === 0) {
      errors.push('At least one Value bracket must be configured');
    }

    if (volumeBrackets.value.length === 0) {
      errors.push('At least one Volume bracket must be configured');
    }
    return errors;
  }

  valueBrackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Value'));
  });

  volumeBrackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Volume'));
  });

  return errors;
};

const validate = () => {
  const errors = validateAllBrackets();
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
  [valueBrackets, volumeBrackets],
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

const createEmptyBracket = () => ({
  contents_min: null,
  contents_max: null,
  building_min: null,
  building_max: null,
  profiles: [],
});

const addValueBracket = () => {
  valueBrackets.value.push(createEmptyBracket());
};

const removeValueBracket = index => {
  valueBrackets.value.splice(index, 1);
};

const addVolumeBracket = () => {
  volumeBrackets.value.push(createEmptyBracket());
};

const removeVolumeBracket = index => {
  volumeBrackets.value.splice(index, 1);
};

const onAddProfile = () => {
  // Profile addition is handled within HomeBracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within HomeBracketModule
};

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="space-y-6">
    <HomeBracketModule
      title="Value Bracket"
      type="Value"
      :brackets="valueBrackets"
      :advisor-options="advisorOptions"
      :view-mode="viewMode"
      @add-bracket="addValueBracket"
      @remove-bracket="removeValueBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <HomeBracketModule
      title="Volume Bracket"
      type="Volume"
      :brackets="volumeBrackets"
      :advisor-options="advisorOptions"
      :view-mode="viewMode"
      @add-bracket="addVolumeBracket"
      @remove-bracket="removeVolumeBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />
  </div>
</template>
