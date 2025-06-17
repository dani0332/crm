<script setup>
import QuoteTypeTemplate from '../components/QuoteTypeTemplate.vue';

const props = defineProps({
  quoteType: {
    type: Object,
    required: true,
  },
  initialConfig: {
    type: Object,
    default: () => ({ profiles: [] }),
  },
  dropdownData: {
    type: Object,
    default: () => ({}),
  },
  versionData: {
    type: Object,
    default: () => ({
      allVersions: [],
      currentVersion: null,
      isCurrentVersion: true,
    }),
  },
});

const emit = defineEmits(['versionLoaded', 'configurationSaved']);

// Life-specific field definitions
const lifeFields = [
  {
    fieldName: 'sum_insured_value',
    label: 'Sum Insured',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
    currencyId: 1,
    isRequired: true,
    hasCheckBox: true,
  },
  {
    fieldName: 'sum_insured_value',
    label: 'Sum Insured',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
    currencyId: 2,
    isRequired: true,
    hasCheckBox: true,
  },
  {
    fieldName: 'sum_insured_value',
    label: 'Sum Insured',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
    currencyId: 3,
    isRequired: true,
    hasCheckBox: true,
  },
  {
    fieldName: 'sum_insured_value',
    label: 'Sum Insured',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
    currencyId: 4,
    isRequired: true,
    hasCheckBox: true,
  },
  {
    fieldName: 'insurer',
    label: 'Insurer',
    type: 'select_multiple',
    options: 'insurers',
    operator: 'in',
    hasCurrency: false,
    isRequired: true,
    hasCheckBox: true,
  },
];
</script>

<template>
  <QuoteTypeTemplate
    :quote-type="quoteType"
    :fields="lifeFields"
    :initial-config="initialConfig"
    :dropdown-data="dropdownData"
    :version-data="versionData"
    @version-loaded="$emit('versionLoaded', $event)"
    @configuration-saved="$emit('configurationSaved')"
  />
</template>
