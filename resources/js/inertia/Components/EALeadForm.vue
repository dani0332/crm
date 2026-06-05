<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  eaModelOptions: {
    type: Array,
    default: () => [],
  },
  quoteTypes: {
    type: Array,
    default: () => [],
  },
  duplicateInfo: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const { isRequired } = useRules();

const page = usePage();
const authRoles = computed(() => page.props.auth?.roles ?? []);

// Car (1), Travel (8), Health (3), and GroupMedical (102) are always excluded from collaborate (referral only).
// Life (4) requires a specific advisor role to use collaborate.
const collaborateExcludedLobs = computed(() => {
  const excluded = [1, 8, 3, 102];
  if (!authRoles.value.includes('LIFE_ADVISOR')) excluded.push(4);
  return excluded;
});

const form = reactive({ ...props.modelValue });

watch(
  () => props.modelValue,
  newVal => {
    for (const [key, value] of Object.entries(newVal)) {
      if (form[key] !== value) {
        form[key] = value;
      }
    }
  },
  { deep: true },
);

const allLobOptions = computed(() =>
  props.quoteTypes.map(qt => ({ value: qt.id, label: qt.name })),
);

const lobOptions = computed(() => {
  if (form.ea_model !== 'collaborate') return allLobOptions.value;
  return allLobOptions.value.filter(
    opt => !collaborateExcludedLobs.value.includes(opt.value),
  );
});

const isCorpline = computed(() => form.quote_type_id === 101);
const isHealthLob = computed(() => form.quote_type_id === 3);

const updateField = (field, value) => {
  form[field] = value;

  if (field === 'ea_model') {
    form.quote_type_id = null;
    form.first_name = '';
    form.last_name = '';
    form.email = '';
    form.mobile_no = '';
    form.business_type_of_insurance_id = null;
    form.health_plan_type_id = null;
  } else if (field === 'quote_type_id') {
    form.business_type_of_insurance_id = null;
    form.health_plan_type_id = null;
  }

  emit('update:modelValue', { ...form });
};
</script>

<template>
  <div class="flex flex-col gap-4">
    <x-select
      :model-value="form.ea_model"
      label="EA MODEL"
      name="eaModel"
      :options="eaModelOptions"
      placeholder="Select EA Model"
      class="w-full"
      :rules="[isRequired]"
      :required="true"
      @update:model-value="updateField('ea_model', $event)"
    />

    <x-select
      v-if="form.ea_model"
      :model-value="form.quote_type_id"
      label="LINE OF BUSINESS"
      name="quoteTypeId"
      :options="lobOptions"
      placeholder="Select Line of Business"
      class="w-full"
      filterable
      :rules="[isRequired]"
      :required="true"
      @update:model-value="updateField('quote_type_id', $event)"
    />

    <template v-if="form.quote_type_id">
      <div class="grid grid-cols-2 gap-4">
        <x-input
          :model-value="form.first_name"
          label="FIRST NAME"
          name="firstName"
          :rules="[isRequired]"
          :required="true"
          @update:model-value="updateField('first_name', $event)"
        />
        <x-input
          :model-value="form.last_name"
          label="LAST NAME"
          name="lastName"
          :rules="[isRequired]"
          :required="true"
          @update:model-value="updateField('last_name', $event)"
        />
      </div>

      <x-input
        :model-value="form.email"
        label="EMAIL ADDRESS"
        name="email"
        type="email"
        :rules="[isRequired]"
        :required="true"
        @update:model-value="updateField('email', $event)"
      />

      <x-input
        :model-value="form.mobile_no"
        label="PHONE NUMBER"
        name="mobileNo"
        :rules="[isRequired]"
        :required="true"
        @update:model-value="updateField('mobile_no', $event)"
      />

      <x-select
        v-if="isCorpline"
        :model-value="form.business_type_of_insurance_id"
        label="BUSINESS TYPE OF INSURANCE"
        name="businessTypeOfInsuranceId"
        :options="[]"
        placeholder="Select Business Type"
        class="w-full"
        :rules="[isRequired]"
        :required="true"
        @update:model-value="
          updateField('business_type_of_insurance_id', $event)
        "
      />

      <x-select
        v-if="isHealthLob"
        :model-value="form.health_plan_type_id"
        label="PLAN TYPE"
        name="healthPlanTypeId"
        :options="[]"
        placeholder="Select Plan Type"
        class="w-full"
        :rules="[isRequired]"
        :required="true"
        @update:model-value="updateField('health_plan_type_id', $event)"
      />
    </template>

    <div
      v-if="duplicateInfo"
      class="mt-2 p-4 rounded-lg border border-yellow-400 bg-yellow-50 text-yellow-800"
    >
      <p class="font-semibold">Duplicate Lead Found</p>
      <p class="text-sm mt-1">{{ duplicateInfo.message }}</p>
      <p v-if="duplicateInfo.existing_advisor" class="text-sm mt-1">
        Existing Advisor: <strong>{{ duplicateInfo.existing_advisor }}</strong>
      </p>
    </div>
  </div>
</template>
