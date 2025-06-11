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
});

const emit = defineEmits(['data-update']);

const lumpsumBrackets = ref([]);
const regularBrackets = ref([]);

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

watch(
  [lumpsumBrackets, regularBrackets],
  () => {
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
  min: 0,
  max: 0,
  profiles: [],
});

// Lumpsum bracket handlers
const addLumpsumBracket = () => {
  lumpsumBrackets.value.push(createEmptyBracket());
};

const removeLumpsumBracket = index => {
  lumpsumBrackets.value.splice(index, 1);
};

// Regular bracket handlers
const addRegularBracket = () => {
  regularBrackets.value.push(createEmptyBracket());
};

const removeRegularBracket = index => {
  regularBrackets.value.splice(index, 1);
};

// Profile handlers (these will be handled by the BracketModule component internally)
const onAddProfile = () => {
  // Profile addition is handled within BracketModule
};

const onRemoveProfile = () => {
  // Profile removal is handled within BracketModule
};
</script>

<template>
  <div class="space-y-6">
    <!-- Lumpsum Brackets -->
    <BracketModule
      title="Investment Frequency - Lumpsum"
      type="Lumpsum"
      :brackets="lumpsumBrackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      @add-bracket="addLumpsumBracket"
      @remove-bracket="removeLumpsumBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />

    <!-- Regular Brackets -->
    <BracketModule
      title="Investment Frequency - Regular"
      type="Regular"
      :brackets="regularBrackets"
      :advisor-options="advisorOptions"
      :nationality-options="nationalityOptions"
      @add-bracket="addRegularBracket"
      @remove-bracket="removeRegularBracket"
      @add-profile="onAddProfile"
      @remove-profile="onRemoveProfile"
    />
  </div>
</template>
