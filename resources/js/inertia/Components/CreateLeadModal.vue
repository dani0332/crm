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

const { isRequired } = useRules();
const PARTNER_NAME_MAX_LENGTH = 50;

const leadForm = useForm({
  type: '',
  sub_source_id: null,
  sub_source_options_id: null,
  primary_ref_id: '',
  partner_name: '',
});

const isModalOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const onConfirmCreateLead = (isValid) => {
  if (!isValid) return;

  const leadData = {
    type: leadForm.type,
    subSourceId: leadForm.sub_source_id,
    subSourceOptionsId: leadForm.sub_source_options_id,
    primaryRefId: leadForm.primary_ref_id,
    partnerName: leadForm.partner_name,
  };

  if (leadForm.type === 'referral' || leadForm.type === 'ecom_lead_extension') {
    router.get(route(props.routeName), leadData);
  }

  emit('confirmed', leadData);
  isModalOpen.value = false;
  resetForm();
};

const onCancel = () => {
  isModalOpen.value = false;
  resetForm();
};

const resetForm = () => {
  leadForm.type = '';
  leadForm.sub_source_id = null;
  leadForm.sub_source_options_id = null;
  leadForm.primary_ref_id = '';
  leadForm.partner_name = '';
  if (typeof leadForm.reset === 'function') leadForm.reset();
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
  if (!leadForm.sub_source_id) return [];

  const selectedSource = props.subSources.find(source => source.id == leadForm.sub_source_id);
  if (!selectedSource || !selectedSource.childs) return [];

  return selectedSource.childs.map(child => ({
    value: child.id,
    label: child.text,
    code: child.code,
    suffix: child.description || child.tooltip || `Information about ${child.text}`, // Add tooltip support
  }));
});

// Check if Partner name field should be shown
const showPartnerNameField = computed(() => {
  if (!leadForm.sub_source_options_id) return false;
  const selected = subSourceChildOptions.value.find(option => option.value == leadForm.sub_source_options_id);
  return selected?.code === 'other-clubs-or-campaigns';
});

const validatePartnerNameMax = value => {
  if (!value) return true;
  const trimmed = String(value).trim();
  return trimmed.length <= PARTNER_NAME_MAX_LENGTH || `Must be <= ${PARTNER_NAME_MAX_LENGTH} characters`;
};

// Overall validity handled by x-form via :rules and submit callback

// Watch for modal close to reset form
watch(isModalOpen, (newValue) => {
  if (!newValue) {
    resetForm();
  }
});

// Watch for type change to reset dependent fields
watch(() => leadForm.type, () => {
  leadForm.sub_source_id = null;
  leadForm.sub_source_options_id = null;
  leadForm.primary_ref_id = '';
  leadForm.partner_name = '';
});

// Watch for subSource change to reset subSourceOption
watch(() => leadForm.sub_source_id, () => {
  leadForm.sub_source_options_id = null;
  leadForm.partner_name = '';
});

// Watch for subSourceOption change to reset partnerName
watch(() => leadForm.sub_source_options_id, () => {
  if (!showPartnerNameField.value) {
    leadForm.partner_name = '';
  }
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
    <x-form id="createLeadForm" @submit="onConfirmCreateLead" :auto-focus="false">
      <div class="w-full grid md:grid-cols-2 gap-5">
        <p class="text-md font-bold text-gray-500">
          Select reason to create manual lead <span class="error">*</span>
        </p>
      </div>

      <div class="flex w-full flex-col gap-5 mt-4 mb-4">
        <x-form-group v-model="leadForm.type">
          <x-radio value="referral" label="Referral" />
          <x-radio value="early_renewal" label="Early Renewal" />
          <x-radio value="payment_status" label="Payment Status" />
          <x-radio value="ecom_lead_extension" label="ECOM Lead Extension" />
        </x-form-group>

        <!-- Conditional dropdowns for referral option -->
        <div v-if="leadForm.type === 'referral'" class="flex flex-col gap-4">
          <x-select
            v-model="leadForm.sub_source_id"
            label="Sub Source"
            name="subSource"
            :options="subSourceOptions"
            placeholder="Please select sub source"
            class="w-full"
            filterable
            :rules="[isRequired]"
            :required="true"
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
            v-if="leadForm.sub_source_id && subSourceChildOptions.length > 0"
            v-model="leadForm.sub_source_options_id"
            label="Sub Source Option"
            name="subSourceOption"
            :options="subSourceChildOptions"
            placeholder="Please select sub source option"
            class="w-full"
            filterable
            :rules="subSourceChildOptions.length > 0 ? [isRequired] : []"
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
        <div v-if="leadForm.type === 'ecom_lead_extension'" class="flex flex-col gap-4">
        <x-input
            v-model="leadForm.primary_ref_id"
          label="Primary Ref Id"
          name="primaryRefId"
          placeholder="Enter Primary Ref Id"
          class="w-full"
            :rules="[isRequired]"
          required
        />
      </div>

      <!-- Conditional input for Partner Name when other-clubs-or-campaigns is selected -->
      <div v-if="showPartnerNameField" class="flex flex-col gap-4">
        <x-input
          v-model="leadForm.partner_name"
          label="Partner Name"
          name="partnerName"
          placeholder="Enter Partner Name"
          class="w-full"
            :rules="showPartnerNameField ? [isRequired, validatePartnerNameMax] : []"
            required
            maxlength="50"
            :error="leadForm.errors.partner_name"
            :tooltip="'Name of campaign, event or club'"
          />
        </div>
      </div>

    </x-form>

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
        type="submit"
        form="createLeadForm"
      >
        Confirm
      </x-button>
    </template>
  </x-modal>
</template>
