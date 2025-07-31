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

// Home-specific field definitions
const homeFields = [
  {
    fieldName: 'price_with_vat',
    label: 'Total Price',
    type: 'numeric',
    operator: '>=',
    hasCurrency: true,
    currencyId: 1,
    isRequired: true,
    hasCheckBox: true,
  },
  {
    fieldName: 'insurance_provider_id',
    label: 'Insurer',
    type: 'select_multiple',
    options: 'insurers',
    operator: 'in',
    hasCurrency: false,
    isRequired: true,
    hasCheckBox: true,
  },
  {
    fieldName: 'sub_area_id',
    label: 'Location Area',
    type: 'select_multiple',
    options: 'locationAreas',
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
    :fields="homeFields"
    :initial-config="initialConfig"
    :dropdown-data="dropdownData"
    :version-data="versionData"
    @version-loaded="$emit('versionLoaded', $event)"
    @configuration-saved="$emit('configurationSaved')"
  />
  <AuditLogs :quoteType="'PrivateClientConfig'" :quoteTypeId="quoteType.id" />
</template>
