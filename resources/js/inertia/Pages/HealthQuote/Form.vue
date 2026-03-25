<script setup>
import HealthMemberDetails from '../../Components/HealthMemberDetails.vue';
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

const { isRequired, isEmail, isMobileNo, maxCharacters, minAge } = useRules();
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
const customerType = computed(() => {
  return page.props.quote?.customer_type || customerTypeEnum.Individual;
});
const isCustomerTypeIndividual = computed(() => {
  return customerType.value === customerTypeEnum.Individual;
});

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

const computedMembers = computed(() => {
  const membersArray = Array.isArray(props.membersDetail) 
    ? props.membersDetail 
    : Object.values(props.membersDetail || {});
  return membersArray.filter(x => !x.is_third_party_payer == 1) || [];
});

const policyHolderInsuredMember = computed(() => {
  return computedMembers.value?.find(
    x => x.is_insured == 1 && x.is_policy_holder == 1
  );
});

const isIncludePolicyholder = computed(() => {
  return policyHolderInsuredMember.value ? true : false;
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
  cover_for_id: props.quote?.cover_for_id || healthCoverForEnum.INDIVIDUAL_AND_FAMILIES,
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
  pec: isEdit.value ? policyHolderInsuredMember.value?.is_pec_marked == 1 ? 1 : 2 : null,
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
  include_policyholder: isEdit.value ? (isIncludePolicyholder.value ? '1' : '0') : null,
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
} = useHealthQuoteFlags({
  getCoverForId: () => quoteForm.cover_for_id,
  getInsureCode: () => quoteForm.health_insure_code,
  getPolicyHolderCode: () => quoteForm.policy_holder_code,
});

const showIncludePolicyholderField = computed(() => {
  return isCustomerTypeIndividual.value && (isFamily_Other.value || isSelfAndFamily_Other.value);
});

const showAdditionalFields = computed(() => {
  return isCustomerTypeIndividual.value && (isSelf_Me.value || isSelfAndFamily_Me.value || (quoteForm.include_policyholder == 1 && showIncludePolicyholderField.value));
});

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
    if(!member.visa_category_id) {
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

  if(isDomesticHelper.value) {
    quoteForm.health_insure_code = null;
    quoteForm.policy_holder_code = null;
  }

  if(isDomesticHelper.value ||
    isSelf_Other.value ||
    isFamily_Me.value ||
    (quoteForm.include_policyholder != 1 && (isFamily_Other.value || isSelfAndFamily_Other.value))) {
      
      if (isCustomerTypeIndividual.value) {
          // add first member to members array
        const index = quoteForm.members.findIndex(
          m => m.is_insured === 0 && m.is_policy_holder === 1 && m.is_principal === 0
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
      }

    quoteForm.member_category_id = null;
  } else {
    quoteForm.policy_holder_category_code = null;
  }
}

function onSubmit(isValid) {

  quoteForm.members = isCustomerTypeIndividual.value
    ? (healthMemberDetailsRef?.value?.localMembers || [])
    : [];
  isEmptyField.value = false;
  pecValidationError.value = '';

  // Only validate PEC field on create page, not on edit page
  if (isCustomerTypeIndividual.value &&
  quoteForm.include_policyholder == 1 &&
  (quoteForm.pec == null || quoteForm.pec == undefined)) {
    
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
  if (!isValid || isEmptyField.value || pecValidationError.value || !isValidMembers) return;

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

  quoteForm.transform(data => {
    preprocessFormData();
    return data;
  }).submit(method, url, options);
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

        <x-input v-model="quoteForm.details" class="w-full" label="DETAILS" />

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
          v-model="quoteForm.cover_for_id"
          :rules="[isRequired]"
          :options="coverForOptions"
          class="w-full sm:col-span-2"
          label="Select who the health insurance coverage is for?"
          required
          :disabled="isEdit"
        />

        <x-select
          v-if='isIndividualAndFamilies'
          v-model="quoteForm.health_insure_code"
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
        />

        <x-select
          v-if='isIndividualAndFamilies'
          v-model="quoteForm.policy_holder_code"
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
        />

        <x-input
          v-model="quoteForm.policy_number"
          class="w-full"
          label="POLICY NUMBER"
        />

        <x-input
          v-model="quoteForm.preference"
          class="w-full"
          label="PREFERENCE"
        />

        <x-textarea
          v-model="quoteForm.additional_notes"
          label="ADDITIONAL NOTES"
          :error="quoteForm.errors.additional_notes"
          :disabled="!canEditSubSourceFields"
          class="w-full sm:col-span-2"
          rows="3"
        />

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
          v-if='!showAdditionalFields'
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
          v-if='showAdditionalFields'
          v-model="quoteForm.member_category_id"
          :rules="[isRequired]"
          :options="memberCategoriesOptions"
          class="w-full"
          label="MEMBER CATEGORY"
          required
        />

        <x-select
          v-model="quoteForm.visa_category_id"
          :rules="[isRequired]"
          :options="
            [
              { value: '', label: 'Select Visa Category' },
              ...visaCategoryOptions
            ]
          "
          class="w-full"
          label="VISA CATEGORY"
          required
        />

        <x-select
          v-model="quoteForm.salary_band_id"
          :options="salaryBandsOptions"
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

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.mobile_no"
          label="PHONE NUMBER"
          required
        />

        <DatePicker
          v-if="!isEdit"
          v-model="quoteForm.policy_start_date"
          :min-date="new Date()"
          class="w-full"
          label="POLICY START DATE"
          required
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

        <div v-if="showAdditionalFields" class="grid sm:grid-cols-2 gap-4 w-full col-span-2">
          <DatePicker
            v-model="quoteForm.dob"
            :rules="[isRequired, minAge(18)]"
            :max-date="new Date()"
            class="w-full"
            label="DATE OF BIRTH"
            required
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
                <x-radio :value="1" label="Yes" />
                <x-radio :value="2" label="No" />
              </x-form-group>
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
      :policyHolderCode="quoteForm.policy_holder_code" />

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
