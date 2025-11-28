<script setup>
import { ref, watch, onMounted } from 'vue';
import BracketModule from './BracketModule.vue';

const props = defineProps({
  configuration: {
    type: Object,
    default: null,
  },
  advisorOptions: {
    type: Array,
    default: () => [],
  },
  nationalityOptions: {
    type: Array,
    default: () => [],
  },
  viewMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['data-update']);

const lumpsumBrackets = ref([]);
const regularBrackets = ref([]);
const validationErrors = ref({});

const initializeData = () => {
  if (props.configuration) {
    lumpsumBrackets.value = props.configuration.lumpsum_brackets || [];
    regularBrackets.value = props.configuration.regular_brackets || [];
  } else {
    lumpsumBrackets.value = [];
    regularBrackets.value = [];
  }

  emitData();
};

const emitData = () => {
  const data = {
    lumpsum_brackets: lumpsumBrackets.value,
    regular_brackets: regularBrackets.value,
  };
  emit('data-update', data);
};

const validateBracket = (bracket, bracketIndex, type) => {
  const errors = [];

  if (!bracket.min || bracket.min <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Minimum amount is required and must be greater than 0`,
    );
  }

  if (!bracket.max || bracket.max <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Maximum amount is required and must be greater than 0`,
    );
  }

  if (
    bracket.min &&
    bracket.max &&
    parseFloat(bracket.min) >= parseFloat(bracket.max)
  ) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Minimum amount must be less than maximum amount`,
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

      if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
        errors.push(
          `${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one nationality must be selected`,
        );
      }
    });
  }

  return errors;
};

const validateAllBrackets = () => {
  const errors = [];

  if (
    lumpsumBrackets.value.length === 0 ||
    regularBrackets.value.length === 0
  ) {
    if (lumpsumBrackets.value.length === 0) {
      errors.push('At least one Lumpsum bracket must be configured');
    }

    if (regularBrackets.value.length === 0) {
      errors.push('At least one Regular bracket must be configured');
    }
    return errors;
  }

  lumpsumBrackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Lumpsum'));
  });

  regularBrackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Regular'));
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
  [lumpsumBrackets, regularBrackets],
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
  min: null,
  max: null,
  profiles: [],
});

const addLumpsumBracket = () => {
  lumpsumBrackets.value.push(createEmptyBracket());
};

const removeLumpsumBracket = index => {
  lumpsumBrackets.value.splice(index, 1);
};

const addRegularBracket = () => {
  regularBrackets.value.push(createEmptyBracket());
};

const removeRegularBracket = index => {
  regularBrackets.value.splice(index, 1);
};

const onAddProfile = () => {
  // Profile addition is handled within BracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within BracketModule
};

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="space-y-6">
    <BracketModule
      title="Investment Frequency - Lumpsum"
      type="Lumpsum"
      :brackets="lumpsumBrackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      :view-mode="viewMode"
      @add-bracket="addLumpsumBracket"
      @remove-bracket="removeLumpsumBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <BracketModule
      title="Investment Frequency - Regular"
      type="Regular"
      :brackets="regularBrackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      :view-mode="viewMode"
      @add-bracket="addRegularBracket"
      @remove-bracket="removeRegularBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />
  </div>
</template>
