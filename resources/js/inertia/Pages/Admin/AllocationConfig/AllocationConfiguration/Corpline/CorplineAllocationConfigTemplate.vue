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
  teamOptions: {
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
    valueBrackets.value = props.configuration.value_brackets
      ? JSON.parse(JSON.stringify(props.configuration.value_brackets))
      : [];
    volumeBrackets.value = props.configuration.volume_brackets
      ? JSON.parse(JSON.stringify(props.configuration.volume_brackets))
      : [];
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

      if (!profile.teamIds || profile.teamIds.length === 0) {
        errors.push(
          `${type} Bracket ${bracketIndex + 1}, Profile ${profileIndex + 1}: At least one team must be selected`,
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
  // Profile addition is handled within CorplineBracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within CorplineBracketModule
};

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
      :brackets="valueBrackets"
      :advisor-options="advisorOptions"
      :team-options="teamOptions"
      :view-mode="viewMode"
      @add-bracket="addValueBracket"
      @remove-bracket="removeValueBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <CorplineBracketModule
      title="Volume"
      type="Volume"
      :brackets="volumeBrackets"
      :advisor-options="advisorOptions"
      :team-options="teamOptions"
      :view-mode="viewMode"
      @add-bracket="addVolumeBracket"
      @remove-bracket="removeVolumeBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />
  </div>
</template>
