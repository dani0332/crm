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

// Car-specific field definitions
const carFields = [
  {
    fieldName: 'price_with_vat',
    label: 'Total Price',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'car_value',
    label: 'Car Value',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
  },
  {
    fieldName: 'car_make_id',
    label: 'Car Make',
    type: 'select_multiple',
    options: 'carMakes',
    operator: 'in',
    hasCurrency: false,
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
    quote-type-name="car"
    quote-type-label="Car"
    :fields="carFields"
    :disabled="disabled"
    @version-loaded="$emit('versionLoaded', $event)"
  />
</template>
