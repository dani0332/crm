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

// Health-specific field definitions
const healthFields = [
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
    :quote-type-id="quoteTypeId"
    quote-type-name="health"
    quote-type-label="Health"
    :fields="healthFields"
    :disabled="disabled"
    @version-loaded="$emit('versionLoaded', $event)"
  />
</template>
