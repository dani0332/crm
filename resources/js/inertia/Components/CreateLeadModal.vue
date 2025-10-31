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
  teamNamesEnum: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'confirmed']);

const { isRequired } = useRules();

const leadForm = useForm({
  type: '',
  sub_source_id: null,
  sub_source_options_id: null,
});

const isModalOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

// Team checks
const isPCPTeam = useHasAnyTeam([{ name: props.teamNamesEnum?.PCP }]);

const onConfirmCreateLead = isValid => {
  if (!isValid) return;

  const leadData = {
    type: leadForm.type,
    subSourceId: leadForm.sub_source_id,
    subSourceOptionsId: leadForm.sub_source_options_id,
  };

  if (leadForm.type === 'referral') {
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
  if (typeof leadForm.reset === 'function') leadForm.reset();
};

// Computed properties for dropdown options
const subSourceOptions = computed(() => {
  const options = props.subSources?.map(source => ({
    value: source.id,
    label: source.text,
    suffix:
      source.description || null, // Use suffix for tooltip data
  }));
  return options;
});

const subSourceChildOptions = computed(() => {
  if (!leadForm.sub_source_id) return [];

  const selectedSource = props.subSources.find(
    source => source.id == leadForm.sub_source_id,
  );
  if (!selectedSource || !selectedSource.childs) return [];

  const pcpOnlyOptions = ['pcp-cross-sell', 'pcp-customer-referral'];
  return selectedSource.childs.map(child => ({
    value: child.id,
    label: child.text,
    code: child.code,
    suffix:
      child.description || null , // Add tooltip support
    disabled: !isPCPTeam.value && pcpOnlyOptions.includes(String(child.code)),
  }));
});

// Overall validity handled by x-form via :rules and submit callback

// Watch for modal close to reset form
watch(isModalOpen, newValue => {
  if (!newValue) {
    resetForm();
  }
});

// Watch for type change to reset dependent fields
watch(
  () => leadForm.type,
  () => {
    leadForm.sub_source_id = null;
    leadForm.sub_source_options_id = null;
  },
);

// Watch for subSource change to reset subSourceOption
watch(
  () => leadForm.sub_source_id,
  () => {
    leadForm.sub_source_options_id = null;
  },
);

</script>

<template>
  <x-modal
    v-model="isModalOpen"
    size="lg"
    title="Create Lead"
    show-close
    backdrop
  >
    <x-form
      id="createLeadForm"
      @submit="onConfirmCreateLead"
      :auto-focus="false"
    >
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
        </x-form-group>

        <!-- Conditional dropdowns for referral option -->
        <div v-if="leadForm.type === 'referral'" class="flex flex-col gap-4">
          <x-select
            v-model="leadForm.sub_source_id"
            label="IMCRM SUB-SOURCE"
            name="subSource"
            :options="subSourceOptions"
            placeholder="Please select IMCRM SUB-SOURCE"
            class="w-full"
            filterable
            :rules="[isRequired]"
            :required="true"
            tooltip="Manually created lead in IMCRM"
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
            label="SUB SOURCE OPTIONS"
            name="subSourceOption"
            :options="subSourceChildOptions"
            placeholder="Please select sub source option"
            class="w-full"
            filterable
            :rules="subSourceChildOptions.length > 0 ? [isRequired] : []"
            :required="subSourceChildOptions.length > 0"
            tooltip="Type of referral lead"
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
      <x-button size="md" color="emerald" type="submit" form="createLeadForm">
        Confirm
      </x-button>
    </template>
  </x-modal>
</template>
