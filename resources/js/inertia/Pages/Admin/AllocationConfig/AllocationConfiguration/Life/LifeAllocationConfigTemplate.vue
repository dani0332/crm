<script setup>
import { ref, watch, onMounted } from 'vue';
import LifeBracketModule from './LifeBracketModule.vue';

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

const type1Brackets = ref([]);
const type2Brackets = ref([]);
const type3Brackets = ref([]);
const type4Brackets = ref([]);
const validationErrors = ref({});

const initializeData = () => {
  if (props.configuration) {
    type1Brackets.value = props.configuration.type1_brackets || [];
    type2Brackets.value = props.configuration.type2_brackets || [];
    type3Brackets.value = props.configuration.type3_brackets || [];
    type4Brackets.value = props.configuration.type4_brackets || [];
  } else {
    type1Brackets.value = [];
    type2Brackets.value = [];
    type3Brackets.value = [];
    type4Brackets.value = [];
  }

  emitData();
};

const emitData = () => {
  const data = {
    type1_brackets: type1Brackets.value,
    type2_brackets: type2Brackets.value,
    type3_brackets: type3Brackets.value,
    type4_brackets: type4Brackets.value,
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
      `${type} Bracket ${bracketIndex + 1}: Minimum must be less than maximum`,
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
    type1Brackets.value.length === 0 ||
    type2Brackets.value.length === 0 ||
    type3Brackets.value.length === 0 ||
    type4Brackets.value.length === 0
  ) {
    if (type1Brackets.value.length === 0) {
      errors.push('At least one Type 1 bracket must be configured');
    }

    if (type2Brackets.value.length === 0) {
      errors.push('At least one Type 2 bracket must be configured');
    }

    if (type3Brackets.value.length === 0) {
      errors.push('At least one Type 3 bracket must be configured');
    }

    if (type4Brackets.value.length === 0) {
      errors.push('At least one Type 4 bracket must be configured');
    }
    return errors;
  }

  type1Brackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Type 1'));
  });

  type2Brackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Type 2'));
  });

  type3Brackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Type 3'));
  });

  type4Brackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Type 4'));
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
  [type1Brackets, type2Brackets, type3Brackets, type4Brackets],
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

const addType1Bracket = () => {
  type1Brackets.value.push(createEmptyBracket());
};

const removeType1Bracket = index => {
  type1Brackets.value.splice(index, 1);
};

const addType2Bracket = () => {
  type2Brackets.value.push(createEmptyBracket());
};

const removeType2Bracket = index => {
  type2Brackets.value.splice(index, 1);
};

const addType3Bracket = () => {
  type3Brackets.value.push(createEmptyBracket());
};

const removeType3Bracket = index => {
  type3Brackets.value.splice(index, 1);
};

const addType4Bracket = () => {
  type4Brackets.value.push(createEmptyBracket());
};

const removeType4Bracket = index => {
  type4Brackets.value.splice(index, 1);
};

const onAddProfile = () => {
  // Profile addition is handled within LifeBracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within LifeBracketModule
};

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="space-y-6">
    <LifeBracketModule
      title="Type 1"
      type="Type 1"
      :brackets="type1Brackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      :view-mode="viewMode"
      @add-bracket="addType1Bracket"
      @remove-bracket="removeType1Bracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <LifeBracketModule
      title="Type 2"
      type="Type 2"
      :brackets="type2Brackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      :view-mode="viewMode"
      @add-bracket="addType2Bracket"
      @remove-bracket="removeType2Bracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <LifeBracketModule
      title="Type 3"
      type="Type 3"
      :brackets="type3Brackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      :view-mode="viewMode"
      @add-bracket="addType3Bracket"
      @remove-bracket="removeType3Bracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <LifeBracketModule
      title="Type 4"
      type="Type 4"
      :brackets="type4Brackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      :view-mode="viewMode"
      @add-bracket="addType4Bracket"
      @remove-bracket="removeType4Bracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />
  </div>
</template>
