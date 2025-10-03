<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  routeName: {
    type: String,
    required: true,
  },
  subSources: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue', 'confirmed']);

const createLead = reactive({
  type: '',
  subSource: '',
  subSourceOption: '',
  primaryRefId: '',
  partnerName: '',
});

const isModalOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const onConfirmCreateLead = () => {
  const leadData = {
    type: createLead.type,
    subSourceId: createLead.subSource,
    subSourceOptionsId: createLead.subSourceOption,
    primaryRefId: createLead.primaryRefId,
    partnerName: createLead.partnerName,
  };

  if (createLead.type === 'referral' || createLead.type === 'ecom_lead_extension') {
    router.get(route(props.routeName), leadData);
  }

  emit('confirmed', leadData);
  isModalOpen.value = false;

  // Reset form
  resetForm();
};

const onCancel = () => {
  isModalOpen.value = false;
  resetForm();
};

const resetForm = () => {
  createLead.type = '';
  createLead.subSource = '';
  createLead.subSourceOption = '';
  createLead.primaryRefId = '';
  createLead.partnerName = '';
};

// Computed properties for dropdown options
const subSourceOptions = computed(() => {
  const options = props.subSources?.map(source => ({
    value: source.id,
    label: source.text,
    suffix: source.description || source.tooltip || `Information about ${source.text}`, // Use suffix for tooltip data
  }));
  return options;
});

const subSourceChildOptions = computed(() => {
  if (!createLead.subSource) return [];

  const selectedSource = props.subSources.find(source => source.id == createLead.subSource);
  if (!selectedSource || !selectedSource.childs) return [];

  return selectedSource.childs.map(child => ({
    value: child.id,
    label: child.text,
    suffix: child.description || child.tooltip || `Information about ${child.text}`, // Add tooltip support
  }));
});

// Check if Partner name field should be shown
const showPartnerNameField = computed(() => {
  if (!createLead.subSourceOption) return false;
  
  const selectedSubSource = props.subSources?.find(source => source.id == createLead.subSource);
  if (!selectedSubSource?.childs) return false;
  
  const selectedSubSourceOption = selectedSubSource.childs.find(child => child.id == createLead.subSourceOption);
  return selectedSubSourceOption?.code === 'other-clubs-or-campaigns';
});

// Form validation
const isFormValid = computed(() => {
  if (!createLead.type) return false;

  if (createLead.type === 'ecom_lead_extension') {
    return !!createLead.primaryRefId.trim();
  }

  if (createLead.type === 'referral') {
    // subSource is always required for referral type
    if (!createLead.subSource) return false;
    
    // subSourceOption is only required if there are child options available
    if (subSourceChildOptions.value.length > 0 && !createLead.subSourceOption) {
      return false;
    }
    
    // partnerName is required when showPartnerNameField is true
    if (showPartnerNameField.value && !createLead.partnerName.trim()) {
      return false;
    }
    
    return true;
  }

  return true; // For other types, just type selection is enough
});

// Watch for modal close to reset form
watch(isModalOpen, (newValue) => {
  if (!newValue) {
    resetForm();
  }
});

// Watch for type change to reset dependent fields
watch(() => createLead.type, () => {
  createLead.subSource = '';
  createLead.subSourceOption = '';
  createLead.primaryRefId = '';
  createLead.partnerName = '';
});

// Watch for subSource change to reset subSourceOption
watch(() => createLead.subSource, () => {
  createLead.subSourceOption = '';
  createLead.partnerName = '';
});

// Watch for subSourceOption change to reset partnerName
watch(() => createLead.subSourceOption, () => {
  createLead.partnerName = '';
});
</script>

<template>
  <x-modal
    v-model="isModalOpen"
    size="lg"
    title="Create Lead"
    show-close
    backdrop
  >
    <div class="w-full grid md:grid-cols-2 gap-5">
      <p class="text-md font-bold text-gray-500">
        Select reason to create manual lead <span class="error">*</span>
      </p>
    </div>

    <div class="flex w-full flex-col gap-5 mt-4 mb-4">
      <x-form-group v-model="createLead.type">
        <x-radio value="referral" label="Referral" />
        <x-radio value="early_renewal" label="Early Renewal" />
        <x-radio value="payment_status" label="Payment Status" />
        <x-radio value="ecom_lead_extension" label="ECOM Lead Extension" />
      </x-form-group>

      <!-- Conditional dropdowns for referral option -->
      <div v-if="createLead.type === 'referral'" class="flex flex-col gap-4">
        <x-select
          v-model="createLead.subSource"
          label="Sub Source"
          name="subSource"
          :options="subSourceOptions"
          placeholder="Please select sub source"
          class="w-full"
          filterable
          required
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>

        <x-select
          v-if="createLead.subSource && subSourceChildOptions.length > 0"
          v-model="createLead.subSourceOption"
          label="Sub Source Option"
          name="subSourceOption"
          :options="subSourceChildOptions"
          placeholder="Please select sub source option"
          class="w-full"
          filterable
          :required="subSourceChildOptions.length > 0"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>
      </div>

      <!-- Conditional input for ECOM lead extension -->
      <div v-if="createLead.type === 'ecom_lead_extension'" class="flex flex-col gap-4">
        <x-input
          v-model="createLead.primaryRefId"
          label="Primary Ref Id"
          name="primaryRefId"
          placeholder="Enter Primary Ref Id"
          class="w-full"
          required
        />
      </div>

      <!-- Conditional input for Partner Name when other-clubs-or-campaigns is selected -->
      <div v-if="showPartnerNameField" class="flex flex-col gap-4">
        <x-input
          v-model="createLead.partnerName"
          label="Partner Name"
          name="partnerName"
          placeholder="Enter Partner Name"
          class="w-full"
          required
        />
      </div>

    </div>

    <template #actions>
      <x-button
        ghost
        tabindex="-1"
        size="md"
        type="button"
        @click.prevent="onCancel"
      >
        Cancel
      </x-button>
      <x-button
        size="md"
        color="emerald"
        type="button"
        :disabled="!isFormValid"
        @click.prevent="onConfirmCreateLead"
      >
        Confirm
      </x-button>
    </template>
  </x-modal>
</template>
