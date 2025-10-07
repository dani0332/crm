<script setup>
const page = usePage();
const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  model: String,
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

const isEdit = computed(() => (props.quote.uuid ? true : false));

const maxValidation = maxValue => {
  return value => {
    const isValid = value <= maxValue;
    return isValid || `Value must be less than or equal to ${maxValue}.`;
  };
};

const quoteForm = useForm({
  modelType: '"Business"',
  gender: props.quote.gender,
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  source: props.quote.source,
  premium: props.quote.premium,
  company_name: props.quote.business_company_name,
  company_address: props.quote.business_company_address,
  number_of_employees: props.quote.number_of_employees,
  business_type_of_insurance_id: props.quote.business_type_of_insurance_id,
  group_medical_type_id: props.selectedGmType,
  brief_details: props.quote.brief_details,
  // Additional notes (append partner name like Life)
  additional_notes: (() => {
    let text = props.quote?.additional_notes || '';
    const partnerName = props.leadSourceParams?.partnerName;
    if (partnerName) {
      if (text) {
        text = `${text}, ${partnerName}`;
      } else {
        text = partnerName;
      }
    }
    return text;
  })(),
  // Sub-source fields
  sub_source_id: parseInt(props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0) || null,
  sub_source_options_id: parseInt(props.quote?.sub_source_options_id || props.leadSourceParams?.subSourceOption || 0) || null,
  primary_ref_id: props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',
});

const { isRequired, emptyOrDecimal, isNumber, isEmail, isMobileNo } =
  useRules();
// Sub-source options like Life
const subSourceOptions = computed(() => {
  return (props.subSources || []).map(item => ({
    value: item.id,
    label: item.text,
    suffix: item.description || item.tooltip || `Information about ${item.text}`,
  }));
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(source => source.id == quoteForm.sub_source_id);
  return selectedSubSource?.childs?.map(option => ({
    value: option.id,
    label: option.text,
    code: option.code,
    suffix: option.description || option.tooltip || `Information about ${option.text}`,
  })) || [];
});

const isReferralType = computed(() => {
  return props.leadSourceParams?.type === 'referral' || quoteForm.source === 'IMCRM';
});

const isEcomLeadExtension = computed(() => {
  return props.leadSourceParams?.type === 'ecom_lead_extension' ||
    (quoteForm.sub_source_id === null && quoteForm.sub_source_options_id === null && quoteForm.primary_ref_id);
});

const rolesEnum = page.props.rolesEnum;
const canEditSubSourceFields = computed(() => {
  return useHasAnyRole([rolesEnum.CorplineManager, rolesEnum.Admin, rolesEnum.LeadPool]);
});

const showPartnerNameField = computed(() => {
  const selectedOption = subSourceOptionOptions.value.find(
    option => option.value === quoteForm.sub_source_options_id
  );
  return selectedOption?.code === 'other-clubs-or-campaigns';
});

// Watchers to reset and maintain notes with partner name
watch(() => quoteForm.sub_source_id, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    quoteForm.sub_source_options_id = null;
    quoteForm.partner_name = '';
  }
});

watch(() => quoteForm.sub_source_options_id, () => {
  if (!showPartnerNameField.value) {
    quoteForm.partner_name = '';
  }
});

watch(() => quoteForm.partner_name, (newValue, oldValue) => {
  if (!showPartnerNameField.value) return;
  if (oldValue) {
    const oldPattern = new RegExp(`(, ${oldValue.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')}|${oldValue.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')}, |${oldValue.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')})`, 'g');
    quoteForm.additional_notes = (quoteForm.additional_notes || '').replace(oldPattern, '').trim();
    quoteForm.additional_notes = quoteForm.additional_notes.replace(/,\s*,/g, ',').replace(/^,\s*|,\s*$/g, '');
  }
  if (newValue) {
    if (quoteForm.additional_notes) {
      quoteForm.additional_notes = `${quoteForm.additional_notes}, ${newValue}`;
    } else {
      quoteForm.additional_notes = newValue;
    }
  }
});

const isEmptyField = ref(false);

//businessInsuranceTypeOptions

const businessInsuranceTypeOptions = computed(() => {
  return Object.keys(props.dropdownSource.business_type_of_insurance_id).map(
    status => ({
      value: props.dropdownSource.business_type_of_insurance_id[status].id,
      label: props.dropdownSource.business_type_of_insurance_id[status].text,
    }),
  );
});

// genderOptions

const genderOptions = [
  {
    value: 'Male',
    label: 'Male',
  },
  {
    value: 'FS',
    label: 'Female-Single',
  },
  {
    value: 'FM',
    label: 'Female-Married',
  },
];

function onSubmit(isValid) {
  if (!isValid) return;

  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('business.update', props.quote.uuid)
    : route('business.store');

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
    <Head :title="isEdit ? 'Update' : 'Create' + ' Business Quote'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Update' : 'Create' }} Business Quote Lead
      </h2>
      <div class="space-x-4">
        <Link v-if="isEdit" :href="route('business.show', props.quote.uuid)">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link :href="route('business.index')">
          <x-button size="sm" color="#ff5e00" tag="div"> Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Lead Source Fields - Only show when type is referral -->
        <x-select
          v-if="isReferralType && !isEcomLeadExtension"
          label="IMCRM SUB-SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select IMCRM SUB-SOURCE"
          filterable
          filterPlaceholder="Filter IMCRM SUB-SOURCE...."
          :disabled="!canEditSubSourceFields"
          :rules="[isRequired]"
          :required="subSourceOptions.length > 0"
          :error="quoteForm.errors.sub_source_id"
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
          v-if="isReferralType && quoteForm.sub_source_id && !isEcomLeadExtension"
          label="SUB SOURCE OPTION"
          v-model="quoteForm.sub_source_options_id"
          :options="subSourceOptionOptions"
          class="w-full"
          placeholder="Select Sub Source Option"
          filterable
          filterPlaceholder="Filter Sub Source Option...."
          :disabled="!canEditSubSourceFields"
          :rules="subSourceOptionOptions.length > 0 ? [isRequired] : []"
          :required="subSourceOptionOptions.length > 0"
          :error="quoteForm.errors.sub_source_options_id"
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

        <x-input
          v-if="isEcomLeadExtension"
          label="PRIMARY REF ID"
          required
          v-model="quoteForm.primary_ref_id"
          class="w-full"
          type="text"
          placeholder="Enter Primary Ref ID"
          :rules="[isRequired]"
          :error="quoteForm.errors.primary_ref_id"
          :disabled="!canEditSubSourceFields"
        />

        <x-input
          v-if="showPartnerNameField"
          label="PARTNER NAME"
          required
          v-model="quoteForm.partner_name"
          class="w-full"
          type="text"
          placeholder="Enter Partner Name"
          :rules="[isRequired]"
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
          required
          label="FIRST NAME"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
          maxLength="50"
          required
          label="LAST NAME"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          :disabled="isEdit"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
          required
          label="EMAIL"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :disabled="isEdit"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
          required
          label="MOBILE NUMBER"
        />

        <x-input
          v-model="quoteForm.company_name"
          type="text"
          class="w-full"
          :error="quoteForm.errors.company_name"
          label="COMPANY NAME"
        />

        <x-input
          v-model="quoteForm.company_address"
          type="text"
          class="w-full"
          :error="quoteForm.errors.company_address"
          label="COMPANY ADDRESS"
        />

        <x-input
          v-model="quoteForm.number_of_employees"
          type="number"
          :rules="[isRequired, isNumber, maxValidation(2147483645)]"
          class="w-full"
          :error="quoteForm.errors.number_of_employees"
          required
          label="NUMBER OF EMPLOYEES"
        />

        <x-select
          v-model="quoteForm.business_type_of_insurance_id"
          :options="businessInsuranceTypeOptions"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.business_type_of_insurance_id"
          required
          label="BUSINESS INSURANCE TYPE"
        />

        <x-select
          v-model="quoteForm.gender"
          :options="genderOptions"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.gender"
          required
          label="GENDER"
        />

        <x-input
          v-model="quoteForm.premium"
          type="number"
          :rules="[emptyOrDecimal]"
          class="w-full"
          :error="quoteForm.errors.premium"
          required
          label="PRICE"
        />

        <x-textarea
          v-model="quoteForm.brief_details"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.brief_details"
          required
          label="BRIEF DETAILS"
        />

        <x-textarea
          v-model="quoteForm.additional_notes"
          class="w-full"
          :error="quoteForm.errors.additional_notes"
          label="ADDITIONAL NOTES"
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
