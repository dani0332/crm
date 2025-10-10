<script setup>
import { ref, watch, onMounted } from 'vue';
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
  viewMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['data-update']);

const microBrackets = ref([]);
const nonMicroBrackets = ref([]);
const validationErrors = ref({});

const initializeData = () => {
  if (props.configuration) {
    microBrackets.value = props.configuration.micro_brackets
      ? JSON.parse(JSON.stringify(props.configuration.micro_brackets))
      : [];
    nonMicroBrackets.value = props.configuration.non_micro_brackets
      ? JSON.parse(JSON.stringify(props.configuration.non_micro_brackets))
      : [];
  } else {
    microBrackets.value = [];
    nonMicroBrackets.value = [];
  }

  emitData();
};

const emitData = () => {
  const data = {
    micro_brackets: microBrackets.value,
    non_micro_brackets: nonMicroBrackets.value,
  };
  emit('data-update', data);
};

const validateBracket = (bracket, bracketIndex, type) => {
  const errors = [];

  if (!bracket.employees_min || bracket.employees_min <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Minimum number of employees is required and must be greater than 0`,
    );
  }

  if (!bracket.employees_max || bracket.employees_max <= 0) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Maximum number of employees is required and must be greater than 0`,
    );
  }

  if (
    bracket.employees_min &&
    bracket.employees_max &&
    parseFloat(bracket.employees_min) >= parseFloat(bracket.employees_max)
  ) {
    errors.push(
      `${type} Bracket ${bracketIndex + 1}: Minimum employees must be less than maximum employees`,
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

      if (!profile.planTypeIds || profile.planTypeIds.length === 0) {
        errors.push(
          `${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one plan type must be selected`,
        );
      }
    });
  }

  return errors;
};

const validateAllBrackets = () => {
  const errors = [];

  if (microBrackets.value.length === 0 || nonMicroBrackets.value.length === 0) {
    if (microBrackets.value.length === 0) {
      errors.push('At least one Micro bracket must be configured');
    }

    if (nonMicroBrackets.value.length === 0) {
      errors.push('At least one Non-Micro bracket must be configured');
    }
    return errors;
  }

  microBrackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Micro'));
  });

  nonMicroBrackets.value.forEach((bracket, index) => {
    errors.push(...validateBracket(bracket, index, 'Non-Micro'));
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
  [microBrackets, nonMicroBrackets],
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
  employees_min: null,
  employees_max: null,
  profiles: [],
});

const addMicroBracket = () => {
  microBrackets.value.push(createEmptyBracket());
};

const removeMicroBracket = index => {
  microBrackets.value.splice(index, 1);
};

const addNonMicroBracket = () => {
  nonMicroBrackets.value.push(createEmptyBracket());
};

const removeNonMicroBracket = index => {
  nonMicroBrackets.value.splice(index, 1);
};

const onAddProfile = () => {
  // Profile addition is handled within GroupMedicalBracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within GroupMedicalBracketModule
};

defineExpose({
  validate,
  clearValidationErrors,
});
</script>

<template>
  <div class="space-y-6">
    <GroupMedicalBracketModule
      title="Micro"
      type="Micro"
      :brackets="microBrackets"
      :advisor-options="advisorOptions"
      :plan-type-options="planTypeOptions"
      :view-mode="viewMode"
      @add-bracket="addMicroBracket"
      @remove-bracket="removeMicroBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <GroupMedicalBracketModule
      title="Non-Micro"
      type="Non-Micro"
      :brackets="nonMicroBrackets"
      :advisor-options="advisorOptions"
      :plan-type-options="planTypeOptions"
      :view-mode="viewMode"
      @add-bracket="addNonMicroBracket"
      @remove-bracket="removeNonMicroBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />
  </div>
</template>
