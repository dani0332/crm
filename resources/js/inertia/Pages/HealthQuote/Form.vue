<script setup>
const props = defineProps({
  dropdownSource: Object,
  model: String,
  genderOptions: Object,
  branchOptions: Object,
  quote: {
    type: Object,
    default: {},
  },
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

const { isRequired, isEmail, isMobileNo } = useRules();
const isEmptyField = ref(false);
const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const initialEditCategoryId = computed(() => {
  if (route().current().includes('edit')) {
    return props.quote.member_category_id;
  }
});

let previouslySelectedCategoryId = ref(initialEditCategoryId.value);

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const branchName = computed(() => {
  if (!quoteForm.emirate_of_your_visa_id || !props.branchOptions) {
    return '';
  }

  const branchMapping = props.branchOptions.find(
    item => item.id === quoteForm.emirate_of_your_visa_id,
  );

  return branchMapping ? branchMapping.branch : '';
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return props.subSources?.map(source => ({
    value: source.id,
    label: source.text,
  })) || [];
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(source => source.id == quoteForm.sub_source_id);
  return selectedSubSource?.childs?.map(child => ({
    value: child.id,
    label: child.text,
  })) || [];
});

const isReferralType = computed(() => {
  return props.leadSourceParams?.type === 'referral' || props.quote?.source === 'IMCRM';
});

const isEcomLeadExtension = computed(() => {
  if (props.leadSourceParams?.type === 'ecom_lead_extension') return true;
  if (!quoteForm.sub_source_id && !quoteForm.sub_source_options_id && quoteForm.primary_ref_id) {
    return true;
  }
  return false;
});

const showPartnerNameField = computed(() => {
  if (!quoteForm.sub_source_options_id) return false;
  const selectedSubSource = props.subSources?.find(source => source.id == quoteForm.sub_source_id);
  if (!selectedSubSource?.childs) return false;
  const selectedSubSourceOption = selectedSubSource.childs.find(child => child.id == quoteForm.sub_source_options_id);
  return selectedSubSourceOption?.code === 'other-clubs-or-campaigns';
});

// Role-based permissions for sub-source fields
const canEditSubSourceFields = computed(() => {
  return hasAnyRole([rolesEnum.HealthManager, rolesEnum.Admin, rolesEnum.LeadPool]);
});

const quoteForm = useForm({
  modelType: '"Health"',
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob
    ? props.quote?.dob.split('-').reverse().join('-')
    : null,
  policy_number: props.quote?.policy_number || null,
  preference: props.quote?.preference || '',
  details: props.quote?.details || '',
  marital_status_id: props.quote?.marital_status_id || null,
  cover_for_id: props.quote?.cover_for_id || null,
  nationality_id: props.quote?.nationality_id || null,
  lead_type_id: props.quote?.lead_type_id || null,
  emirate_of_your_visa_id: props.quote?.emirate_of_your_visa_id || null,
  salary_band_id: props.quote?.salary_band_id || null,
  member_category_id: props.quote?.member_category_id || null,
  gender: props.quote?.gender || null,
  currently_insured_with_id: props.quote?.currently_insured_with_id || null,
  policy_start_date: props.quote?.policy_start_date || null,
  is_ebp_renewal: props.quote?.is_ebp_renewal || null,
  is_ecommerce: props.quote?.is_ecommerce || null,
  has_dental: props.quote?.has_dental || null,
  has_worldwide_cover: props.quote?.has_worldwide_cover || null,
  has_home: props.quote?.has_home || null,
  plan_type_id: props.quote?.health_plan_type_id || null,
  // Sub-source fields from CreateLeadModal
  sub_source_id: parseInt(props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0) || null,
  sub_source_options_id: parseInt(props.quote?.sub_source_options_id || props.leadSourceParams?.subSourceOption || 0) || null,
  primary_ref_id: props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',
  additional_notes: (() => {
    let notes = props.quote?.additional_notes || '';
    const partnerName = props.leadSourceParams?.partnerName;
    if (partnerName) {
      notes = notes ? `${notes}, ${partnerName}` : partnerName;
    }
    return notes;
  })(),
});

const memberCategorySalaryMapping = {
  'Investor or Partner': 2,
  'Golden visa': 2,
  'Self-employed or Freelancer': 2,
  'Domestic worker': 1,
  'Dependent spouse': 2,
  'Dependent child': 2,
  'Dependent parent': 2,
  'Dependent sibling or Other relatives': 2,
  'Employee with salary AED 4000 and below': 1,
  'Employee with salary above AED 4000': 2,
};

const salaryBrandMapping = {
  1: 'AED 4000 and below',
  2: 'More than AED 4000',
};

const selectedSalaryBand = computed(() => {
  return route().current().includes('edit');
});

watch(
  () => quoteForm.member_category_id,
  (newValue, oldValue) => {
    if (newValue) {
      if (
        !isEdit.value ||
        (isEdit.value &&
          (newValue !== initialEditCategoryId.value ||
            (newValue === initialEditCategoryId.value &&
              newValue !== previouslySelectedCategoryId.value)))
      ) {
        //fetch category text
        const selectedCategory = props.dropdownSource.member_category_id.find(
          option => option.id === newValue,
        );

        // fetch salary band id based on category text
        const salaryBandId = memberCategorySalaryMapping[selectedCategory.text];

        // if quote status is Transaction Approved do not auto-popualte salary band automatically
        if (props.quote.quote_status_id != 15) {
          quoteForm.salary_band_id = salaryBandId;
        }
        previouslySelectedCategoryId.value = newValue;
      }
    }
  },
  { immediate: true },
);

// Watch for sub_source_id changes to reset dependent fields
watch(() => quoteForm.sub_source_id, (newValue) => {
  quoteForm.sub_source_options_id = '';
  quoteForm.partner_name = '';
  if (!newValue) {
    quoteForm.primary_ref_id = '';
  }
});

// Watch for sub_source_options_id changes to reset primary_ref_id if needed
watch(() => quoteForm.sub_source_options_id, (newValue) => {
  quoteForm.partner_name = '';
  if (!newValue) {
    quoteForm.primary_ref_id = '';
  }
});

// Watch for partner name changes to update additional_notes
watch(() => quoteForm.partner_name, (newValue, oldValue) => {
  if (!showPartnerNameField.value) return;

  // Remove old partner name from additional_notes if it exists
  if (oldValue) {
    const oldPattern = new RegExp(`(, ${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}|${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}, |${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'g');
    quoteForm.additional_notes = quoteForm.additional_notes.replace(oldPattern, '').trim();
    // Clean up any double commas or leading/trailing commas
    quoteForm.additional_notes = quoteForm.additional_notes.replace(/,\s*,/g, ',').replace(/^,\s*|,\s*$/g, '');
  }

  // Add new partner name to additional_notes
  if (newValue) {
    if (quoteForm.additional_notes) {
      quoteForm.additional_notes = `${quoteForm.additional_notes}, ${newValue}`;
    } else {
      quoteForm.additional_notes = newValue;
    }
  }
});

function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) return;

  quoteForm.clearErrors();

  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('health.update', props.quote.uuid)
    : route('health.store');

  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
  };

  quoteForm.submit(method, url, options);
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Health' : 'Create Health'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Health
      </h2>
      <div>
        <Link :href="route('health.index')">
          <x-button size="sm" color="#1d83bc" tag="div"> Health List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-alert
          v-if="quoteForm.errors.length > 0"
          color="error"
          class="sm:col-span-2"
        >
          <ul class="list-disc list-inside">
            <li v-for="error in quoteForm.errors" :key="error">
              {{ error }}
            </li>
          </ul>
        </x-alert>

         <!-- Sub-source fields (conditional display based on referral type) -->
         <x-select
           v-if="isReferralType && !isEcomLeadExtension"
           label="SUB SOURCE"
           v-model="quoteForm.sub_source_id"
           :options="subSourceOptions"
           :error="quoteForm.errors.sub_source_id"
           :disabled="!canEditSubSourceFields"
           class="w-full"
         />

         <x-select
           v-if="isReferralType && quoteForm.sub_source_id && !isEcomLeadExtension"
           label="SUB SOURCE OPTION"
           v-model="quoteForm.sub_source_options_id"
           :options="subSourceOptionOptions"
           :error="quoteForm.errors.sub_source_options_id"
           :disabled="!canEditSubSourceFields"
           :rules="subSourceOptionOptions.length > 0 ? [isRequired] : []"
           :required="subSourceOptionOptions.length > 0"
           class="w-full"
           placeholder="Select Sub Source Option"
           filterable
           filterPlaceholder="Filter Sub Source Option...."
         />

         <x-input
           v-if="isEcomLeadExtension"
           label="PRIMARY REF ID"
           v-model="quoteForm.primary_ref_id"
           :error="quoteForm.errors.primary_ref_id"
           :disabled="!canEditSubSourceFields"
           :rules="isEcomLeadExtension ? [isRequired] : []"
           :required="isEcomLeadExtension"
           class="w-full"
         />

         <x-input
           v-if="showPartnerNameField"
           label="PARTNER NAME"
           v-model="quoteForm.partner_name"
           :error="quoteForm.errors.partner_name"
           :disabled="!canEditSubSourceFields"
           :rules="[isRequired]"
           required
           class="w-full"
           placeholder="Enter Partner Name"
         />

         <x-input
          v-model="quoteForm.first_name"
          :rules="[isRequired]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
          label="FIRST NAME"
          required
        />

        <x-input
          v-model="quoteForm.last_name"
          :rules="[isRequired]"
          class="w-full"
          maxLength="50"
          :error="quoteForm.errors.last_name"
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

        <DatePicker
          v-model="quoteForm.dob"
          :rules="[isRequired]"
          class="w-full"
          label="DATE OF BIRTH"
          required
        />

        <x-input
          v-model="quoteForm.policy_number"
          class="w-full"
          label="POLICY NUMBER"
        />

        <x-select
          v-model="quoteForm.cover_for_id"
          :rules="[isRequired]"
          :options="
            dropdownSource.cover_for_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="WHO WOULD YOU LIKE COVER FOR?"
          required
        />

        <x-select
          v-model="quoteForm.nationality_id"
          :options="
            dropdownSource.nationality_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[isRequired]"
          class="w-full"
          label="NATIONALITY"
          required
          filterable
          filter-placeholder="Search by nationality"
        />

        <x-select
          v-model="quoteForm.emirate_of_your_visa_id"
          :rules="[isRequired]"
          :options="
            dropdownSource.emirate_of_your_visa_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="EMIRATE OF YOUR VISA"
          required
        />

        <x-input
          :model-value="branchName"
          class="w-full"
          label="BRANCH"
          disabled
        />

        <x-input
          v-model="quoteForm.preference"
          class="w-full"
          label="PREFERENCE"
        />

        <x-input v-model="quoteForm.details" class="w-full" label="DETAILS" />

        <x-select
          v-model="quoteForm.lead_type_id"
          :options="
            dropdownSource.lead_type_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="LEAD TYPE"
        />

        <x-select
          v-if="!isEdit"
          v-model="quoteForm.currently_insured_with_id"
          :options="
            dropdownSource.currently_insured_with_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="CURRENTLY INSURED WITH"
        />

        <x-select
          v-if="!isEdit"
          v-model="quoteForm.marital_status_id"
          :options="
            dropdownSource.marital_status_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="MARITAL STATUS"
        />

        <x-select
          v-model="quoteForm.member_category_id"
          :rules="[isRequired]"
          :options="
            dropdownSource.member_category_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="MEMBER CATEGORY"
          required
        />

        <x-select
          v-model="quoteForm.salary_band_id"
          :options="
            dropdownSource.salary_band_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="SALARY BAND"
        />

        <x-select
          v-model="quoteForm.gender"
          :rules="[isRequired]"
          :options="genderSelect"
          class="w-full"
          label="GENDER"
          required
        />

        <x-input
          v-if="!isEdit"
          v-model="quoteForm.policy_start_date"
          class="w-full"
          label="POLICY START DATE"
        />

        <x-select
          v-model="quoteForm.plan_type_id"
          :options="
            dropdownSource.plan_type_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          placeholder="Select plan type"
          :rules="[isRequired]"
          label="TYPE OF PLAN"
        />

        <div class="grid grid-cols-2 gap-2">
          <x-checkbox
            v-model="quoteForm.is_ebp_renewal"
            label="IS EBP RENEWAL"
            color="primary"
            class="w-full"
          />

          <x-checkbox
            v-model="quoteForm.has_dental"
            label="DENTAL"
            color="primary"
          />

          <x-checkbox
            v-model="quoteForm.has_worldwide_cover"
            label="WORLDWIDE COVER"
            color="primary"
          />

          <x-checkbox
            v-model="quoteForm.has_home"
            label="HOME COUNTRY COVER"
            color="primary"
          />
         </div>



        <x-textarea
          v-model="quoteForm.additional_notes"
          label="ADDITIONAL NOTES"
          :error="quoteForm.errors.additional_notes"
          :disabled="!canEditSubSourceFields"
          class="w-full sm:col-span-2"
          rows="3"
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
