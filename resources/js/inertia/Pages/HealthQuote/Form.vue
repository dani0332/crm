<script setup>
import HealthMemberDetails from '../../Components/HealthMemberDetails.vue';
import mobileDialCodeOptions from '../../Composables/countryCallingCodes.json';
import { useHealthQuoteFlags } from '../../Composables/useHealthQuoteFlags';

import { nextTick } from 'vue';
const props = defineProps({
  dropdownSource: Object,
  membersDetail: {
    type: Array,
    default: () => [],
  },
  model: String,
  emirateEnum: Object,
  quote: {
    type: Object,
    default: {},
  },
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

const HEALTH_PEC_YES = 1;
const HEALTH_PEC_NO = 2;
const { isRequired, isEmail, isNumber, maxCharacters, minAge } = useRules();

/** Latest DOB that still satisfies min age 18 — calendar opens on this month when empty. */
const maxPolicyholderDobDate = computed(() => {
  const d = new Date();
  d.setFullYear(d.getFullYear() - 18);
  return d;
});
const isEmptyField = ref(false);
const pecValidationError = ref('');
const page = usePage();
const notification = useToast();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;
const teamNamesEnum = page.props.teamNamesEnum;
const isPcpSubSourceOptionAllowed = ref(
  useHasRole(rolesEnum.Admin) || useHasAnyTeam([{ name: teamNamesEnum.PCP }]),
);

const customerTypeEnum = page.props.customerTypeEnum;
const healthInsureEnum = page.props.healthInsureEnum;
const healthPolicyHolderEnum = page.props.healthPolicyHolderEnum;
const healthCoverForEnum = page.props.healthCoverForEnum;
const memberCategoryEnum = page.props.memberCategoryEnum;
const visaCategoryEnum = page.props.visaCategoryEnum;
const salaryBandEnum = page.props.salaryBandEnum;
const customerType = computed(() => {
  return page.props.quote?.customer_type || customerTypeEnum.Individual;
});
const isCustomerTypeIndividual = computed(() => {
  return customerType.value === customerTypeEnum.Individual;
});

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const isCreate = computed(() => {
  return route().current().includes('create');
});

const initialEditCategoryId = computed(() => {
  if (route().current().includes('edit')) {
    return props.quote.member_category_id;
  }
});

let previouslySelectedCategoryId = ref(initialEditCategoryId.value);

const genderSelect = computed(() => {
  return props.dropdownSource.gender.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return (
    props.subSources?.map(source => ({
      value: source.id,
      label: source.text,
      suffix: source.description || null,
    })) || []
  );
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  const pcpOnlyOptions = ['pcp-cross-sell', 'pcp-customer-referral'];
  return (
    selectedSubSource?.childs?.map(child => ({
      value: child.id,
      label: child.text,
      suffix: child.description || null,
      disabled:
        !isPcpSubSourceOptionAllowed.value &&
        pcpOnlyOptions.includes(String(child.code)),
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' ||
    props.quote?.source === 'IMCRM'
  );
});

// Role-based permissions for sub-source fields
const canEditSubSourceFields = computed(() => {
  return hasAnyRole([
    rolesEnum.HealthManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

// Ref to access HealthMemberDetails component
const healthMemberDetailsRef = ref(null);

function parseMobileNoForInitial(raw) {
  if (!raw) {
    return { dial: '+971', national: '' };
  }
  const str = String(raw).trim();
  const digitsOnly = str.replaceAll(/\D/g, '');
  const withPlus = str.startsWith('+')
    ? `+${str.slice(1).replaceAll(/\D/g, '')}`
    : `+${digitsOnly}`;
  const dials = [...mobileDialCodeOptions.map(o => o.value)].sort(
    (a, b) => b.length - a.length,
  );
  for (const d of dials) {
    if (withPlus.startsWith(d)) {
      return {
        dial: d,
        national: withPlus.slice(d.length).replaceAll(/\D/g, ''),
      };
    }
  }

  return { dial: '+971', national: digitsOnly };
}

const initialMobile = parseMobileNoForInitial(
  isEdit.value ? props.quote?.mobile_no : null,
);

const isMobileNationalPartLength = v => {
  if (v == null || v === '') {
    return true;
  }
  const d = String(v).replaceAll(/\D/g, '');
  if (d.length < 9) {
    return 'Phone number must be at least 9 digits';
  }
  if (d.length > 10) {
    return 'Phone number must be at most 10 digits';
  }
  return true;
};

const computedMembers = computed(() => {
  const membersArray = Array.isArray(props.membersDetail)
    ? props.membersDetail
    : Object.values(props.membersDetail || {});
  return membersArray.filter(x => x.is_third_party_payer != 1) || [];
});

const policyHolderInsuredMember = computed(() => {
  return computedMembers.value?.find(
    x => x.is_insured == 1 && x.is_policy_holder == 1,
  );
});

const isIncludePolicyholder = computed(() =>
  Boolean(policyHolderInsuredMember.value),
);

const pecValue =
  policyHolderInsuredMember.value?.is_pec_marked == 1
    ? HEALTH_PEC_YES
    : HEALTH_PEC_NO;
const includePolicyholderValue = isIncludePolicyholder.value ? '1' : '0';

const quoteForm = useForm({
  modelType: '"Health"',
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_dial_code: initialMobile.dial ?? '',
  mobile_national_no: initialMobile.national ?? '',
  dob: props.quote?.dob
    ? props.quote?.dob.split('-').reverse().join('-')
    : null,
  policy_number: props.quote?.policy_number || null,
  preference: props.quote?.preference || '',
  details: props.quote?.details || '',
  marital_status_id: props.quote?.marital_status_id || null,
  cover_for_id:
    props.quote?.cover_for_id || healthCoverForEnum.INDIVIDUAL_AND_FAMILIES,
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
  pec: isEdit.value ? pecValue : null,
  // Sub-source fields from CreateLeadModal
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

  additional_notes: (() => {
    let notes = props.quote?.additional_notes || '';
    return notes;
  })(),
  health_insure_code: props.quote?.insure_code || null,
  policy_holder_code: props.quote?.policy_holder_code || null,
  include_policyholder: isEdit.value ? includePolicyholderValue : null,
  visa_category_id: props.quote?.visa_category_id || null,
  policy_holder_category_code: props.quote?.policy_holder_category_code || null,
  members: [],
  customer_type: customerType.value,
});

const {
  isIndividualAndFamilies,
  isDomesticHelper,
  isSelf_Me,
  isSelf_Other,
  isFamily_Me,
  isFamily_Other,
  isSelfAndFamily_Me,
  isSelfAndFamily_Other,
  showIncludePolicyholderField,
  showAdditionalFields,
  showMemberCategoryField,
} = useHealthQuoteFlags({
  getCoverForId: () => quoteForm.cover_for_id,
  getInsureCode: () => quoteForm.health_insure_code,
  getPolicyHolderCode: () => quoteForm.policy_holder_code,
  getIsCustomerTypeIndividual: () => isCustomerTypeIndividual.value,
  getIncludePolicyholder: () => quoteForm.include_policyholder == 1,
});

const converageInfo = computed(() => {
  let info = '';
  if (isSelf_Me.value) {
    info =
      'The customer will get health insurance coverage just for themselves. As the policyholder, they will manage and pay for the policy.';
  } else if (isSelf_Other.value) {
    info =
      'The customer will get health insurance coverage just for themselves. The family member chosen by the customer as the policyholder will manage and pay for the policy.';
  } else if (isFamily_Me.value) {
    info =
      'The customer’s family members will get health insurance coverage, but the customer will not be included. As the policyholder, the customer will manage and pay for the policy.';
  } else if (isFamily_Other.value) {
    info =
      'The customer’s family members will get health insurance coverage, but the customer will not be included. The family member chosen by the customer as the policyholder will manage and pay for the policy.';
  } else if (isSelfAndFamily_Me.value) {
    info =
      'The customer and their family members will get health insurance coverage together. As the policyholder, the customer will manage and pay for the policy';
  } else if (isSelfAndFamily_Other.value) {
    info =
      'The customer and their family members will get health insurance coverage together. The family member chosen by the customer as the policyholder will manage and pay for the policy.';
  }

  return info;
});

const emiratiNationalityIds = computed(() => {
  const list = props.dropdownSource?.nationality_id ?? [];
  return new Set(
    list
      .filter(item => {
        const text = String(item.text ?? '');
        return text.includes('Emirati') || text.includes('Emarat');
      })
      .map(item => Number(item.id)),
  );
});

const gccNationalityIds = computed(() => {
  const list = props.dropdownSource?.nationality_id ?? [];
  const gccLabels = [
    'Saudi Arabian',
    'Saudi',
    'Kuwaiti',
    'Omani',
    'Qatari',
    'Bahraini',
  ];
  return new Set(
    list
      .filter(item =>
        gccLabels.some(term => String(item.text ?? '').includes(term)),
      )
      .map(item => Number(item.id)),
  );
});

function deriveMemberCategoryIdWhenHidden() {
  const nat = quoteForm.nationality_id;
  const emi = quoteForm.emirate_of_your_visa_id;
  const natNum = nat != null && nat !== '' ? Number(nat) : null;

  if (
    natNum !== null &&
    !Number.isNaN(natNum) &&
    emiratiNationalityIds.value.has(natNum)
  ) {
    return memberCategoryEnum.UAE_NATIONAL;
  }
  if (
    natNum !== null &&
    !Number.isNaN(natNum) &&
    gccNationalityIds.value.has(natNum)
  ) {
    return memberCategoryEnum.GCC_NATIONAL;
  }
  if (emi === props.emirateEnum.DUBAI) {
    return memberCategoryEnum.EXPAT_DUBAI_VISA;
  }

  return memberCategoryEnum.EXPAT_NON_DUBAI_VISA;
}

function applyDerivedMemberCategoryIfHidden() {
  if (showMemberCategoryField.value) {
    return;
  }

  if (quoteForm.nationality_id || quoteForm.emirate_of_your_visa_id) {
    quoteForm.member_category_id = deriveMemberCategoryIdWhenHidden();
  }
}

watch(
  [
    () => !showMemberCategoryField.value,
    () => quoteForm.nationality_id,
    () => quoteForm.emirate_of_your_visa_id,
  ],
  applyDerivedMemberCategoryIfHidden,
  { immediate: true },
);

const coverForOptions = computed(() => {
  return props.dropdownSource.cover_for_id
    .filter(item => item.id !== healthCoverForEnum.MY_COMPANY)
    .map(item => ({
      value: item.id,
      label: item.text,
    }));
});

const visaCategoryOptions = computed(() => {
  return props.dropdownSource.visa_category
    .filter(item => item.health_cover_for_id === quoteForm.cover_for_id)
    .map(item => ({
      value: item.id,
      label: item.text,
    }));
});

const memberRelations = computed(() => {
  if (isDomesticHelper.value) {
    return props.dropdownSource['domestic-worker-relation'].map(item => ({
      value: item.code,
      label: item.text,
    }));
  } else {
    return props.dropdownSource['health-member-relation'].map(item => ({
      value: item.code,
      label: item.text,
    }));
  }
});

const healthRegulationAuthority = computed(() => {
  return quoteForm.emirate_of_your_visa_id === props.emirateEnum.ABU_DHABI
    ? 'DoH'
    : 'DHA';
});

const pecErrorMessage = computed(() => {
  return `Please confirm the customer's health declaration to proceed, as required under ${healthRegulationAuthority.value} regulations.`;
});

const pecRules = computed(() => {
  return [isRequired];
});

// Watch for sub_source_id changes to reset dependent fields
watch(
  () => quoteForm.sub_source_id,
  () => {
    quoteForm.sub_source_options_id = null;
  },
);

// Watch for cover_for_id changes to reset visa-related fields
watch(
  () => quoteForm.cover_for_id,
  () => {
    quoteForm.visa_category_id = null;
  },
);

watch(showAdditionalFields, isInsured => {
  if (isInsured) {
    if (quoteForm.visa_category_id === visaCategoryEnum.DEPENDENT_FAMILY) {
      quoteForm.visa_category_id = null;
    }
    if (quoteForm.salary_band_id === salaryBandEnum.NO_SALARY_DEPENDENTS_OR_CHILDREN) {
      quoteForm.salary_band_id = null;
    }
  }
});

// When include_policyholder is toggled to 1, clear policyholder personal details
watch(
  () => quoteForm.include_policyholder,
  (newVal, oldVal) => {
    if (newVal == 1 && oldVal == 0) {
      quoteForm.dob = null;
      quoteForm.gender = null;
      quoteForm.marital_status_id = null;
      quoteForm.nationality_id = null;
      quoteForm.emirate_of_your_visa_id = null;
      quoteForm.pec = null;
    }
  },
);

function validateMembers() {
  if (!isCustomerTypeIndividual.value) {
    return true;
  }

  const membersToValidate = quoteForm.members.filter(x => x.is_insured == 1);

  if (membersToValidate.length == 0) {
    notification.error({
      title: 'At least one insured member is required to create a Health Lead.',
      position: 'top',
    });

    return false;
  }

  let isValid = true;
  membersToValidate.forEach(member => {
    if (!member.visa_category_id) {
      notification.error({
        title: 'Please Fill the Visa Category for all members',
        position: 'top',
      });

      isValid = false;
    }

    if (!member.relation_code && member.is_policy_holder == 0) {
      notification.error({
        title: 'Please Fill the Relation for all members',
        position: 'top',
      });

      isValid = false;
    }
  });

  return isValid;
}

function preprocessFormData() {
  if (isDomesticHelper.value) {
    quoteForm.health_insure_code = null;
    quoteForm.policy_holder_code = null;
  }

  if (
    isDomesticHelper.value ||
    isSelf_Other.value ||
    isFamily_Me.value ||
    (quoteForm.include_policyholder != 1 &&
      (isFamily_Other.value || isSelfAndFamily_Other.value))
  ) {
    // if policy holder is not insured then add/update it to members array
    if (isCustomerTypeIndividual.value) {
      // add first member to members array
      const index = quoteForm.members.findIndex(
        m =>
          m.is_insured === 0 &&
          m.is_policy_holder === 1 &&
          m.is_principal === 0,
      );
      if (index === -1) {
        quoteForm.members.push({
          first_name: quoteForm.first_name,
          last_name: quoteForm.last_name,
          salary_band_id: quoteForm.salary_band_id,
          member_category_id: null,
          pec: quoteForm.pec,
          visa_category_id: quoteForm.visa_category_id,

          // additional fields
          dob: quoteForm.dob,
          gender: null,
          marital_status_id: null,
          nationality_id: quoteForm.nationality_id,
          emirate_of_your_visa_id: null,

          is_insured: 0,
          is_policy_holder: 1,
          is_principal: 0,
          relation_code: null,
        });
      } else {
        quoteForm.members[index] = {
          ...quoteForm.members[index],
          first_name: quoteForm.first_name,
          last_name: quoteForm.last_name,
          salary_band_id: quoteForm.salary_band_id,
          pec: quoteForm.pec,
          visa_category_id: quoteForm.visa_category_id,
        };
      }

      quoteForm.member_category_id = null;
    }
  }
}

function onSubmit(isValid) {
  quoteForm.members = isCustomerTypeIndividual.value
    ? healthMemberDetailsRef?.value?.localMembers || []
    : [];
  isEmptyField.value = false;
  pecValidationError.value = '';

  // Only validate PEC field on create page, not on edit page
  if (
    isCustomerTypeIndividual.value &&
    quoteForm.include_policyholder == 1 &&
    (quoteForm.pec == null || quoteForm.pec == undefined)
  ) {
    pecValidationError.value = pecErrorMessage.value;

    // Scroll to PEC field if validation fails
    nextTick(() => {
      const pecElement = document.querySelector('[data-pec-field]');
      if (pecElement) {
        pecElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
    return;
  }

  const isValidMembers = validateMembers();
  if (
    !isValid ||
    isEmptyField.value ||
    pecValidationError.value ||
    !isValidMembers
  )
    return;

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

  preprocessFormData();

  quoteForm.transform(data => data).submit(method, url, options);
}

const nationalityOptions = computed(() => {
  return props.dropdownSource.nationality_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const emiratesOptions = computed(() => {
  return props.dropdownSource.emirate_of_your_visa_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const salaryBandsOptions = computed(() => {
  return props.dropdownSource.salary_band_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const memberCategoriesOptions = computed(() => {
  return props.dropdownSource.member_category_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const maritalStatusOptions = computed(() => {
  return props.dropdownSource.marital_status_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const categoryChangeConfirmOpen = ref(false);
const categoryChangePending = ref(null);

function categoryFieldValuesEqual(a, b) {
  if (Object.is(a, b)) {
    return true;
  }
  if (a == null && b == null) {
    return true;
  }
  return String(a) === String(b);
}

function hasNonPolicyholderInsuredMembers() {
  const list = healthMemberDetailsRef?.value?.localMembers;
  if (!Array.isArray(list)) {
    return false;
  }

  return list.some(m => m.is_insured == 1 && m.is_policy_holder == 0);
}

function applyQuoteCategoryFieldChange(field, value) {
  quoteForm[field] = value;
  if (
    field === 'cover_for_id' &&
    value === healthCoverForEnum.DOMESTIC_HELPER
  ) {
    quoteForm.health_insure_code = null;
    quoteForm.policy_holder_code = null;
  }
}

function requestQuoteCategoryFieldUpdate(field, newValue) {
  if (categoryFieldValuesEqual(quoteForm[field], newValue)) {
    return;
  }

  if (hasNonPolicyholderInsuredMembers()) {
    categoryChangePending.value = { field, value: newValue };
    categoryChangeConfirmOpen.value = true;
    return;
  }
  applyQuoteCategoryFieldChange(field, newValue);
}

function cancelQuoteCategoryChange() {
  categoryChangeConfirmOpen.value = false;
  categoryChangePending.value = null;
}

function confirmQuoteCategoryChange() {
  const pending = categoryChangePending.value;
  if (!pending) {
    categoryChangeConfirmOpen.value = false;
    return;
  }
  const { field, value } = pending;
  categoryChangeConfirmOpen.value = false;
  categoryChangePending.value = null;
  applyQuoteCategoryFieldChange(field, value);
}

watch(categoryChangeConfirmOpen, isOpen => {
  if (!isOpen) {
    categoryChangePending.value = null;
  }
});
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

      <x-accordion show-icon>
        <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">
          <h3 class="font-semibold text-primary-800 text-lg">Lead Details</h3>
          <template #content>
            <x-divider class="mb-4 mt-1" />

            <div class="grid sm:grid-cols-2 gap-4">
              <!-- Sub-source fields (conditional display based on referral type) -->
              <x-select
                v-if="isReferralType"
                label="IMCRM SUB-SOURCE"
                v-model="quoteForm.sub_source_id"
                :options="subSourceOptions"
                class="w-full"
                placeholder="Select IMCRM SUB-SOURCE"
                filterable
                filterPlaceholder="Filter IMCRM SUB-SOURCE...."
                :disabled="!canEditSubSourceFields"
                :rules="[isRequired]"
                required
                :error="quoteForm.errors.sub_source_id"
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
                v-if="isReferralType && quoteForm.sub_source_id"
                label="SUB SOURCE OPTIONS"
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
                required
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

              <x-input
                v-model="quoteForm.policy_number"
                class="w-full"
                label="POLICY NUMBER"
              />

              <x-input
                v-model="quoteForm.details"
                class="w-full"
                label="DETAILS"
              />

              <x-input
                v-model="quoteForm.preference"
                class="w-full"
                label="PREFERENCE"
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
                class="w-full"
                rows="3"
              />

              <x-select
                :model-value="quoteForm.cover_for_id"
                :rules="[isRequired]"
                :options="coverForOptions"
                class="w-full"
                label="SELECT WHO THE HEALTH INSURANCE COVERAGE IS FOR?"
                required
                @update:model-value="
                  requestQuoteCategoryFieldUpdate('cover_for_id', $event)
                "
              />

              <x-select
                v-if="isIndividualAndFamilies && isCustomerTypeIndividual"
                :model-value="quoteForm.health_insure_code"
                :rules="[isRequired]"
                :options="
                  dropdownSource.health_insure_options.map(item => ({
                    value: item.code,
                    label: item.text,
                  }))
                "
                class="w-full"
                label="WHO WOULD THE CUSTOMER LIKE TO INSURE?"
                required
                tooltip="Select the individual(s) the customer wishes to cover under the health insurance policy (e.g., customer, spouse, child, or other eligible family members)."
                @update:model-value="
                  requestQuoteCategoryFieldUpdate('health_insure_code', $event)
                "
              />

              <x-select
                v-if="isIndividualAndFamilies && isCustomerTypeIndividual"
                :model-value="quoteForm.policy_holder_code"
                :rules="[isRequired]"
                :options="
                  dropdownSource.policy_holder_options.map(item => ({
                    value: item.code,
                    label: item.text,
                  }))
                "
                class="w-full"
                label="WHO WILL BE THE POLICYHOLDER?"
                required
                tooltip="The policyholder is the adult responsible for owning and managing the policy and paying the premium. The policyholder may or may not be an insured member."
                @update:model-value="
                  requestQuoteCategoryFieldUpdate('policy_holder_code', $event)
                "
              />
            </div>
            <div
              v-if="
                isCustomerTypeIndividual &&
                isIndividualAndFamilies &&
                converageInfo
              "
              class="mt-2 text-sm text-orange-600 border border-orange-200 bg-orange-50 rounded-md p-2"
            >
              <div><b>Please note:</b> {{ converageInfo }}</div>
            </div>
          </template>
        </x-accordion-item>
      </x-accordion>

      <x-accordion show-icon>
        <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">
          <h3 class="font-semibold text-primary-800 text-lg">
            Policyholder Details ({{
              isCustomerTypeIndividual
                ? customerTypeEnum.Individual
                : customerTypeEnum.Entity
            }})
          </h3>
          <template #content>
            <x-divider class="mb-4 mt-1" />

            <div class="grid sm:grid-cols-2 gap-4">
              <x-input
                v-model="quoteForm.first_name"
                :rules="[isRequired]"
                class="w-full"
                maxLength="20"
                :error="quoteForm.errors.first_name"
                label="POLICYHOLDER FIRST NAME"
                required
              />

              <x-input
                v-model="quoteForm.last_name"
                :rules="[isRequired]"
                class="w-full"
                maxLength="50"
                :error="quoteForm.errors.last_name"
                label="POLICYHOLDER LAST NAME"
                required
              />

              <x-select
                v-if="isCustomerTypeIndividual && !showMemberCategoryField"
                v-model="quoteForm.policy_holder_category_code"
                :rules="[isRequired]"
                :options="
                  dropdownSource.policy_holder_category.map(item => ({
                    value: item.code,
                    label: item.text,
                  }))
                "
                class="w-full"
                label="POLICYHOLDER CATEGORY"
                required
              />

              <x-select
                v-if="isCustomerTypeIndividual && showMemberCategoryField"
                v-model="quoteForm.member_category_id"
                :rules="[isRequired]"
                :options="
                  memberCategoriesOptions.filter(
                    item => item.value !== memberCategoryEnum.NEWBORN,
                  )
                "
                class="w-full"
                label="MEMBER CATEGORY"
                required
              />

              <x-select
                v-if="isCustomerTypeIndividual"
                v-model="quoteForm.visa_category_id"
                :rules="[isRequired]"
                :options="
                  props.dropdownSource.visa_category
                    .filter(
                      item =>
                        item.health_cover_for_id ===
                          healthCoverForEnum.INDIVIDUAL_AND_FAMILIES &&
                        item.id !== visaCategoryEnum.NEWBORN_BORN_IN_UAE &&
                        !(showAdditionalFields && item.id === visaCategoryEnum.DEPENDENT_FAMILY),
                    )
                    .map(item => ({
                      value: item.id,
                      label: item.text,
                    }))
                "
                class="w-full"
                label="VISA CATEGORY"
                required
              />

              <x-select
                v-if="isCustomerTypeIndividual && !isDomesticHelper"
                v-model="quoteForm.salary_band_id"
                :options="
                  salaryBandsOptions.filter(
                    item =>
                      !(showAdditionalFields && item.value === salaryBandEnum.NO_SALARY_DEPENDENTS_OR_CHILDREN),
                  )
                "
                class="w-full"
                label="SALARY"
                required
                :rules="[isRequired]"
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

              <div class="w-full">
                <div
                  class="flex flex-row flex-nowrap items-stretch gap-3 w-full min-w-0"
                >
                  <x-select
                    v-model="quoteForm.mobile_dial_code"
                    :options="mobileDialCodeOptions"
                    class="w-36 sm:w-44 shrink-0 min-w-0"
                    label="COUNTRY CODE"
                    :disabled="isEdit"
                    filterable
                    filterPlaceholder="Search code"
                  />
                  <x-input
                    v-model="quoteForm.mobile_national_no"
                    type="text"
                    maxLength="10"
                    :rules="[isRequired, isNumber, isMobileNationalPartLength]"
                    class="flex-1 min-w-0"
                    :disabled="isEdit"
                    :error="quoteForm.errors.mobile_national_no"
                    label="PHONE NUMBER"
                    required
                  />
                </div>
              </div>

              <DatePicker
                v-if="!isEdit"
                v-model="quoteForm.policy_start_date"
                :min-date="new Date()"
                class="w-full"
                label="POLICY START DATE"
                required
              />

              <x-form-group
                v-if="showIncludePolicyholderField"
                v-model="quoteForm.include_policyholder"
                :rules="[isRequired]"
                label="DO YOU WANT TO INCLUDE THE POLICYHOLDER IN THIS POLICY FOR HEALTH COVER?"
                required
                class="w-full sm:col-span-2"
              >
                <x-radio value="1" label="Yes" />
                <x-radio value="0" label="No" />
              </x-form-group>

              <div
                v-if="showAdditionalFields"
                class="grid sm:grid-cols-2 gap-4 w-full col-span-2"
              >
                <DatePicker
                  :model-value="quoteForm.dob"
                  :rules="[isRequired, minAge(18)]"
                  :max-date="maxPolicyholderDobDate"
                  :start-date="maxPolicyholderDobDate"
                  class="w-full"
                  label="DATE OF BIRTH"
                  required
                  @update:modelValue="
                    v =>
                      (quoteForm.dob = v
                        ? new Date(v).toISOString().slice(0, 10)
                        : null)
                  "
                />

                <x-select
                  v-model="quoteForm.gender"
                  :rules="[isRequired]"
                  :options="genderSelect"
                  class="w-full"
                  label="GENDER"
                  required
                />

                <x-select
                  v-model="quoteForm.marital_status_id"
                  :options="maritalStatusOptions"
                  class="w-full"
                  label="MARITAL STATUS"
                  required
                  :rules="[isRequired]"
                />

                <x-select
                  v-model="quoteForm.nationality_id"
                  :options="nationalityOptions"
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
                  :options="emiratesOptions"
                  class="w-full"
                  label="EMIRATE OF YOUR VISA"
                  required
                  tooltip="Emirates where your family residency visa is issued"
                />

                <div data-pec-field>
                  <div class="mb-3">
                    <ToolTip
                      title="Does the member need to declare any chronic or pre-existing medical conditions, pregnancy, plans to conceive, or fertility treatment?"
                      tooltip="Any ongoing or past health issues that may or may not require regular treatment or medical attention."
                      class="w-full"
                    />
                  </div>
                  <div>
                    <x-form-group v-model="quoteForm.pec">
                      <x-radio :value="HEALTH_PEC_YES" label="Yes" />
                      <x-radio :value="HEALTH_PEC_NO" label="No" />
                    </x-form-group>
                    <div
                      v-if="quoteForm.pec === HEALTH_PEC_YES"
                      class="mt-2 text-sm text-orange-600 border border-orange-200 bg-orange-50 rounded-md p-2"
                    >
                      <b>Please note:</b> Declaring a health condition doesn't
                      mean it's automatically covered. It helps us assess
                      eligibility. Premiums shown next are indicative. Your
                      advisor will confirm coverage details for any pre-existing
                      conditions.
                    </div>
                    <div
                      v-if="pecValidationError"
                      class="mt-2 text-sm text-red-600 border border-red-200 bg-red-50 rounded-md p-2"
                    >
                      {{ pecValidationError }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </x-accordion-item>
      </x-accordion>

      <HealthMemberDetails
        v-if="isCustomerTypeIndividual"
        ref="healthMemberDetailsRef"
        class="mt-4"
        :membersDetail="membersDetail"
        :quote="quote"
        :nationalities="nationalityOptions"
        :memberCategories="memberCategoriesOptions"
        :memberRelations="memberRelations"
        :emirates="emiratesOptions"
        :salaryBands="salaryBandsOptions"
        :genderOptions="genderSelect"
        :maritalStatusOptions="maritalStatusOptions"
        :visaCategoryOptions="visaCategoryOptions"
        :quoteForm="quoteForm"
        :includePolicyHolder="quoteForm.include_policyholder"
        :coverForId="quoteForm.cover_for_id"
        :healthInsureCode="quoteForm.health_insure_code"
        :policyHolderCode="quoteForm.policy_holder_code"
      />

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

    <x-modal
      v-model="categoryChangeConfirmOpen"
      size="md"
      title="Change category"
      show-close
      backdrop
    >
      <p class="text-gray-700 text-sm leading-relaxed">
        Changing the category will affect insured members you have already added
        to this application. Do you want to continue?
      </p>
      <template #actions>
        <div class="flex justify-end gap-3 flex-wrap">
          <x-button
            size="sm"
            ghost
            tabindex="-1"
            @click.prevent="cancelQuoteCategoryChange"
          >
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="red"
            @click.prevent="confirmQuoteCategoryChange"
          >
            Confirm
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
