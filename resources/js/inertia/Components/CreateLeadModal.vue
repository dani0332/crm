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
});

const isModalOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const onConfirmCreateLead = () => {
  const leadData = {
    type: createLead.type,
    subSource: createLead.subSource,
    subSourceOption: createLead.subSourceOption,
    primaryRefId: createLead.primaryRefId,
  };

  if (createLead.type === 'referral') {
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
    tooltip: child.description || child.tooltip || `Information about ${child.text}`, // Add tooltip support
  }));
});

// Form validation
const isFormValid = computed(() => {
  if (!createLead.type) return false;

  if (createLead.type === 'ecom_lead_extension') {
    return !!createLead.primaryRefId.trim();
  }

  if (createLead.type === 'referral') {
    return !!createLead.subSource && !!createLead.subSourceOption;
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
});

// Watch for subSource change to reset subSourceOption
watch(() => createLead.subSource, () => {
  createLead.subSourceOption = '';
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
            <x-tooltip v-if="item.suffix" placement="left">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>

        <x-select
          v-if="createLead.subSource"
          v-model="createLead.subSourceOption"
          label="Sub Source Option"
          name="subSourceOption"
          :options="subSourceChildOptions"
          placeholder="Please select sub source option"
          class="w-full"
          filterable
          required
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="top">
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
