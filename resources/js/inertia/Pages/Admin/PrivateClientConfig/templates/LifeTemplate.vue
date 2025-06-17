<script setup>
import { ref } from 'vue';
import QuoteTypeTemplate from '../components/QuoteTypeTemplate.vue';

const props = defineProps({
  quoteTypeId: {
    type: Number,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['versionLoaded']);

// Life-specific field definitions
const lifeFields = [
  {
    fieldName: 'sum_insured_value',
    label: 'Sum Insured',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'insurer',
    label: 'Insurer',
    type: 'select_multiple',
    options: 'insurers',
    operator: 'in',
    hasCurrency: false,
  },
];

// Template reference for parent access
const templateRef = ref(null);

// Expose methods to parent
const getProfiles = () => {
  return templateRef.value?.getProfiles() || [];
};

const loadExistingConfig = async () => {
  if (templateRef.value?.loadExistingConfig) {
    await templateRef.value.loadExistingConfig();
  }
};

defineExpose({
  getProfiles,
  loadExistingConfig,
});
</script>

<template>
  <QuoteTypeTemplate
    ref="templateRef"
    :quote-type-id="quoteTypeId"
    quote-type-name="life"
    quote-type-label="Life"
    :fields="lifeFields"
    :disabled="disabled"
    @version-loaded="$emit('versionLoaded', $event)"
  />
</template>
