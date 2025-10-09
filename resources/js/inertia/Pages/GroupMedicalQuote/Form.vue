<script setup>
const page = usePage();
const props = defineProps({
  businessInsuranceType: Object,
  quote: Object,
  gmTypes: Object,
  selectedGmType: Object,
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

const notification = useToast();
const isEdit = computed(() => (props.quote.uuid ? true : false));
const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const formFields = computed(() => {
  return Object.keys(props.fields).map(field => ({
    value: field,
    label: props.fields[field].label,
  }));
});

const maxValidation = maxValue => {
  return value => {
    const isValid = value <= maxValue;
    return isValid || `Value must be less than or equal to ${maxValue}.`;
  };
};

const quoteForm = useForm({
  modelType: '"Business"',
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  source: props.quote.source,
  premium: props.quote.premium,
  company_name: props.quote.company_name,
  number_of_employees: props.quote.number_of_employees,
  business_type_of_insurance_id: props.quote.business_type_of_insurance_id,
  group_medical_type_id: props.selectedGmType ?? '',
  brief_details: props.quote.brief_details,
  // Additional notes and sub-source fields
  additional_notes: (() => {
    let text = props.quote?.additional_notes || '';
    const partnerName = props.leadSourceParams?.partnerName;
    if (partnerName) {
      if (text) text = `${text}, ${partnerName}`;
      else text = partnerName;
    }
    return text;
  })(),
  sub_source_id:
    parseInt(
      props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0,
    ) || null,
  sub_source_options_id:
    parseInt(
      props.quote?.sub_source_options_id ||
        props.leadSourceParams?.subSourceOption ||
        0,
    ) || null,
  primary_ref_id:
    props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',
});

const {
  isRequired,
  emptyOrDecimal,
  isNumber,
  isEmail,
  isMobileNo,
  maxCharacters,
} = useRules();

// Sub-source options and flags like Life
const subSourceOptions = computed(() => {
  return (props.subSources || []).map(item => ({
    value: item.id,
    label: item.text,
    suffix:
      item.description || item.tooltip || `Information about ${item.text}`,
  }));
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  return (
    selectedSubSource?.childs?.map(option => ({
      value: option.id,
      label: option.text,
      code: option.code,
      suffix:
        option.description ||
        option.tooltip ||
        `Information about ${option.text}`,
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' || quoteForm.source === 'IMCRM'
  );
});

const isEcomLeadExtension = computed(() => {
  return (
    props.leadSourceParams?.type === 'ecom_lead_extension' ||
    (quoteForm.sub_source_id === null &&
      quoteForm.sub_source_options_id === null &&
      quoteForm.primary_ref_id)
  );
});

const rolesEnum = page.props.rolesEnum;
const canEditSubSourceFields = computed(() => {
  return useHasAnyRole([
    rolesEnum.GMManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

const showPartnerNameField = computed(() => {
  const selectedOption = subSourceOptionOptions.value.find(
    option => option.value === quoteForm.sub_source_options_id,
  );
  return selectedOption?.code === 'other-clubs-or-campaigns';
});

watch(
  () => quoteForm.sub_source_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteForm.sub_source_options_id = null;
      quoteForm.partner_name = '';
    }
  },
);
watch(
  () => quoteForm.sub_source_options_id,
  () => {
    if (!showPartnerNameField.value) quoteForm.partner_name = '';
  },
);
watch(
  () => quoteForm.partner_name,
  (newValue, oldValue) => {
    if (!showPartnerNameField.value) return;
    if (oldValue) {
    const oldPattern = new RegExp(`(, ${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}|${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}, |${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'g');
    quoteForm.additional_notes = (quoteForm.additional_notes || '').replace(oldPattern, '').trim();
    quoteForm.additional_notes = quoteForm.additional_notes.replace(/,\s*,/g, ',').replace(/^,\s*|,\s*$/g, '');
    }
    if (newValue) {
      if (quoteForm.additional_notes)
        quoteForm.additional_notes = `${quoteForm.additional_notes}, ${newValue}`;
      else quoteForm.additional_notes = newValue;
    }
  },
);

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (!isValid) return;
  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('amt.update', props.quote.uuid)
    : route('amt.store');

  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
    onStart: () => {
      quoteForm.clearErrors();
    },
  };
  quoteForm.submit(method, url, options);
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Group Medical' : 'Create Group Medical'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Update' : 'Create' }} Group Medical Lead
      </h2>
      <div class="space-x-4">
        <Link v-if="isEdit" :href="route('amt.show', props.quote.uuid)">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link :href="route('amt.index')">
          <x-button size="sm" color="#ff5e00" tag="div"> Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Sub Source Fields -->
        <x-select
          v-if="isReferralType && !isEcomLeadExtension"
          label="IMCRM SUB-SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select IMCRM SUB-SOURCE"
          filterable
          :disabled="!canEditSubSourceFields"
          :rules="[isRequired]"
          :required="subSourceOptions.length > 0"
          :error="quoteForm.errors.sub_source_id"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>{{ item.suffix }}</template>
            </x-tooltip>
          </template>
        </x-select>

        <x-select
          v-if="
            isReferralType && quoteForm.sub_source_id && !isEcomLeadExtension
          "
          label="SUB SOURCE OPTION"
          v-model="quoteForm.sub_source_options_id"
          :options="subSourceOptionOptions"
          class="w-full"
          placeholder="Select Sub Source Option"
          filterable
          :disabled="!canEditSubSourceFields"
          :rules="subSourceOptionOptions.length > 0 ? [isRequired] : []"
          :required="subSourceOptionOptions.length > 0"
          :error="quoteForm.errors.sub_source_options_id"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>{{ item.suffix }}</template>
            </x-tooltip>
          </template>
        </x-select>

        <x-input
          v-if="isEcomLeadExtension"
          label="PRIMARY REF ID"
          required
          v-model="quoteForm.primary_ref_id"
          class="w-full"
          type="text"
          placeholder="Enter Primary Ref ID"
          :rules="[isRequired]"
          :tooltip="'ID of the original ECOM lead'"
          :error="quoteForm.errors.primary_ref_id"
          :disabled="!canEditSubSourceFields"
        />

        <x-input
          v-if="showPartnerNameField"
          label="PARTNER NAME"
          :required="canEditSubSourceFields"
          v-model="quoteForm.partner_name"
          class="w-full"
          maxlength="50"
          type="text"
          placeholder="Enter Partner Name"
          :tooltip="'Name of campaign, event or club'"
          :rules="canEditSubSourceFields ? [isRequired, maxCharacters(50)] : []"
          :error="quoteForm.errors.partner_name"
          :disabled="!canEditSubSourceFields"
        />
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
          maxLength="20"
          label="FIRST NAME"
          required
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
          maxLength="20"
          label="LAST NAME"
          required
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.email"
          label="EMAIL"
          required
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.mobile_no"
          label="MOBILE NUMBER"
          required
        />

        <x-input
          v-model="quoteForm.company_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.company_name"
          label="COMPANY NAME"
          required
        />

        <x-input
          v-model="quoteForm.number_of_employees"
          type="number"
          :rules="[isRequired, isNumber, maxValidation(2147483645)]"
          class="w-full"
          :error="quoteForm.errors.number_of_employees"
          label="NUMBER OF EMPLOYEES"
          required
        />

        <x-select
          v-model="quoteForm.business_type_of_insurance_id"
          :options="
            businessInsuranceType.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.business_type_of_insurance_id"
          label="Business Insurance Type"
          required
        />

        <x-textarea
          v-model="quoteForm.brief_details"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.brief_details"
          label="BRIEF DETAILS"
          required
        />

        <x-input
          v-model="quoteForm.premium"
          type="number"
          class="w-full"
          :rules="[emptyOrDecimal]"
          :error="quoteForm.errors.premium"
          label="PRICE"
        />

        <x-select
          v-if="isEdit"
          v-model="quoteForm.group_medical_type_id"
          :options="
            gmTypes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.group_medical_type_id"
          label="Group Medical Type"
          required
        />
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
