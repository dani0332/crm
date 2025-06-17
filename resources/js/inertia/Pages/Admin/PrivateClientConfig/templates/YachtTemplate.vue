<script setup>
import { ref } from 'vue';
import QuoteTypeTemplate from '../components/QuoteTypeTemplate.vue';

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['versionLoaded']);

// Yacht-specific field definitions
const yachtFields = [
  {
    fieldName: 'price_with_vat',
    label: 'Total Price',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'insurance_provider_id',
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
    :quote-type-id="7"
    quote-type-name="yacht"
    quote-type-label="Yacht"
    :fields="yachtFields"
    :disabled="disabled"
    @version-loaded="$emit('versionLoaded', $event)"
  />
</template>
