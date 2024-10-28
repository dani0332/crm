<script setup>
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();
const hasRole = role => useHasRole(role);
const convertDate = date => useConvertDate(date);
const { isRequired, isEmail, isNumber, isMobileNo } = useRules();
const isLoading = ref(false);
const props = defineProps({
  roles: Array,
  quote: Object,
  status: Function,
  buttonStatus: Function,
  countryList: Array,
  amlQuoteStatus: Number,
  nationalities: Array,
  modelType: String,
  legalStructure: Array,
  idDocumentType: Array,
  issuancePlace: Array,
  issuingAuthority: Array,
  uboRelation: Array,
  entityDetails: Object,
  industryType: Array,
  kycLogs: Array,
  uboDetails: Array,
});
const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  allowEmpty: v => true || 'This field is required',
  isPhone: v =>
    /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,10}$/im.test(v) ||
    'Phone must be valid',
  isEmiratesId: v => {
    const pattern = /^\d{3}-\d{4}-\d{7}-\d{1}$/;
    if (kycForm.id_document_type === 'emiratesId') {
      return pattern.test(v) || 'Enter the correct EID number format';
    } else {
      return true;
    }
  },
};
const uboNationality = computed(() => {
  if (props.uboDetails.length > 0) {
    const uboDetail = props.uboDetails.filter(val => {
      return (
        val.nationality.code === 'North Korean' ||
        val.nationality.code === 'Iranian'
      );
    });
    if (uboDetail.length > 0) {
      return 1;
    } else {
      return 2;
    }
  }
  return null;
});

const kycForm = reactive({
  quote_uuid: props.quote.uuid,
  customer_id: props.quote.customer_id,
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  company_name: props.entityDetails?.entity?.company_name ?? null, // props.quote.company_name
  legal_structure: props.entityDetails?.entity?.legal_structure ?? null,
  industry_type: props.entityDetails?.entity?.industry_type_code ?? null,
  country_of_corporation:
    props.entityDetails?.entity?.country_of_corporation ?? 56, //Default UAE
  registered_address: props.entityDetails?.entity?.registered_address ?? null,
  communication_address:
    props.entityDetails?.entity?.communication_address ?? null,
  mobile_number: props.quote.mobile_no,
  email: props.quote.email,
  website: props.entityDetails?.entity?.website ?? null,
  id_document_type: props.entityDetails?.entity?.id_type ?? null,
  id_number: props.entityDetails?.entity?.id_number ?? null,
  id_issue_date: convertDate(props.entityDetails?.entity?.id_issuance_date),
  id_expiry_date: convertDate(props.entityDetails?.entity?.id_expiry_date),
  place_of_issue: props.entityDetails?.entity?.issuance_place ?? null,
  issuing_authority: props.entityDetails?.entity?.id_issuance_authority ?? null,
  manager_name: props.entityDetails?.entity?.quote_member?.first_name ?? null,
  manager_nationality:
    props.entityDetails?.entity?.quote_member?.nationality_id ?? null,
  manager_dob: props.entityDetails?.entity?.quote_member?.dob ?? null,
  manager_position:
    props.entityDetails?.entity?.quote_member?.relation_code ?? null,
  pep: props.entityDetails?.entity?.pep ?? props.amlQuoteStatus,
  financial_sanctions:
    props.entityDetails?.entity?.financial_sanctions ?? props.amlQuoteStatus,
  dual_nationality:
    props.entityDetails?.entity?.dual_nationality ?? props.amlQuoteStatus,
  in_sanction_list:
    props.entityDetails?.entity?.in_sanction_list ?? props.amlQuoteStatus, // kycLogsValue.value
  in_adverse_media: props.entityDetails?.entity?.in_adverse_media ?? null,
  is_owner_pep: props.entityDetails?.entity?.is_owner_pep ?? null,
  is_controlling_pep: props.entityDetails?.entity?.is_controlling_pep ?? null,
  is_sanction_match:
    props.entityDetails?.entity?.is_sanction_match ?? props.amlQuoteStatus, // kycLogsValue.value
  in_fatf: props.entityDetails?.entity?.in_fatf ?? props.amlQuoteStatus, // kycLogsValue.value
  is_owner_high_risk:
    props.entityDetails?.entity?.is_owner_high_risk ?? uboNationality.value,
  deal_sanction_list:
    props.entityDetails?.entity?.deal_sanction_list ?? props.amlQuoteStatus, // kycLogsValue.value
  is_operation_high_risk:
    props.entityDetails?.entity?.is_operation_high_risk ?? props.amlQuoteStatus, //kycLogsValue.value
  customer_tenure: props.entityDetails?.entity?.customer_tenure ?? null,
  transaction_pattern:
    props.entityDetails?.entity?.transaction_pattern ?? 'no_changes',
  transaction_activities:
    props.entityDetails?.entity?.transaction_activities ??
    'less_expected_annual_activity',
  transaction_volume:
    props.entityDetails?.entity?.transaction_volume ?? 'less-than-3-in-month',
  mode_of_delivery: props.entityDetails?.entity?.mode_of_delivery ?? null,
  mode_of_contact: props.entityDetails?.entity?.mode_of_contact ?? null,
});

const isNationalityEmpty = ref(false);
const isPositionEmpty = ref(false);
const isIssuingAuthorityEmpty = ref(false);

const onKycSubmit = isValid => {
  if (!kycForm.manager_nationality) isNationalityEmpty.value = true;
  else isNationalityEmpty.value = false;

  if (!kycForm.manager_position) isPositionEmpty.value = true;
  else isPositionEmpty.value = false;

  if (!kycForm.issuing_authority) isIssuingAuthorityEmpty.value = true;
  else isIssuingAuthorityEmpty.value = false;

  if (!isValid) return;

  if (confirm('Are you sure you want to create and save the document?')) {
    isLoading.value = true;
    axios
      .post(`/${props.modelType}/upload-entity-kycdoc`, kycForm)
      .then(response => {
        if (response.data.success) {
          notification.success({
            title: 'KYC Document uploaded.',
            position: 'top',
          });
          router.reload({
            replace: true,
            preserveScroll: true,
            preserveState: true,
          });
        } else {
          notification.error({
            title: response.data.message,
            position: 'top',
          });
        }
      })
      .catch(error => {
        console.error(error.response.data);
      })
      .finally(() => (isLoading.value = false));
  }
};

const transactionPatternOptions = [
  {
    value: 'count_pattern_changes',
    label: 'Yes - Count Pattern Changes',
  },
  {
    value: 'behaviour_changes',
    label: 'Yes - Behaviour Changes',
  },
  {
    value: 'business_model_changes',
    label: 'Yes - Business Model Changes',
  },
  {
    value: 'no_changes',
    label: 'No Changes',
  },
  {
    value: 'not_applicable',
    label: 'Not Applicable',
  },
];
const transactionActivitiesOptions = [
  {
    value: 'less_expected_annual_activity',
    label: 'Less than expected Annual Activity',
  },
  {
    value: 'near_expected_annual_activity',
    label: 'Near to expected Annual Activity',
  },
  {
    value: 'more_expected_annual_activity',
    label: 'More than expected Annual Activity',
  },
];
const transactionVolumeOptions = [
  {
    value: 'less-than-3-in-month',
    label: 'Less than 3 transactions in a month',
  },
  {
    value: '4-7-in-month',
    label: '4 to 7 transactions in a month',
  },
  {
    value: 'more-than-8-in-month',
    label: 'More than 8 transactions in a month',
  },
];
const deliveryModOptions = [
  {
    value: 'mod-delivery-car',
    label: 'Company Authorised Representative',
  },
  {
    value: 'mod-delivery-atp',
    label: 'Authorised Third party',
  },
  {
    value: 'mod-delivery-unkown',
    label: 'Unknown',
  },
  {
    value: 'mod-delivery-pse',
    label: 'Policy sent via email',
  },
  {
    value: 'mod-delivery-psc',
    label: 'Policy sent via courier',
  },
  {
    value: 'mod-delivery-cco',
    label: 'Collected by customer from office',
  },
];
const contactModOptions = [
  {
    value: 'email',
    label: 'Email',
  },
  {
    value: 'phone',
    label: 'Phone',
  },
  {
    value: 'phoneandemail',
    label: 'Phone and Email',
  },
  {
    value: 'walkin',
    label: 'Walk-in',
  },
];

const countryList = computed(() => {
  return props.countryList.map(nat => ({
    value: nat.id,
    label: nat.country_name,
  }));
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const legalStructureOptions = computed(() => {
  return props.legalStructure?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const industryTypeOptions = computed(() => {
  return props.industryType?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const placeOfIssuanceOptions = computed(() => {
  return props.issuancePlace?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const issuingAuthorityOptions = computed(() => {
  return props.issuingAuthority?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const uboRelationOptions = computed(() => {
  return props.uboRelation.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const documentTypeOptions = computed(() => {
  return props.idDocumentType.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const complianceDisable = reactive({
  isDisable: true,
});

const complianceRules = computed(() => {
  return can(permissionsEnum.AMLDecisionUpdate) ||
    can(permissionsEnum.AMLDecisionUpdateTrueMatch)
    ? [rules.isRequired]
    : [];
});

onMounted(() => {
  complianceDisable.isDisable = !(
    can(permissionsEnum.AMLDecisionUpdate) ||
    can(permissionsEnum.AMLDecisionUpdateTrueMatch)
  );
});
</script>

<template>
  <x-form @submit="onKycSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-input
        v-model="quote.customer_id"
        label="Customer ID"
        placeholder="Customer ID"
        class="w-full disabled"
        :disabled="true"
        :rules="[isRequired]"
      />

      <x-input
        v-model="kycForm.first_name"
        label="First Name"
        placeholder="First Name"
        class="w-full"
        :rules="[isRequired]"
      />

      <x-input
        v-model="kycForm.last_name"
        label="Last Name"
        placeholder="Last Name"
        class="w-full"
        :rules="[isRequired]"
      />

      <x-input
        v-model="kycForm.company_name"
        label="Employer / Company name"
        placeholder="Employer / Company name"
        :rules="[rules.isRequired]"
        :disabled="true"
      />

      <div>
        <x-tooltip placement="right">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Legal structure
          </label>
          <template #tooltip>
            Please select the legal structure of the company</template
          >
        </x-tooltip>
        <x-select
          v-model="kycForm.legal_structure"
          :options="legalStructureOptions"
          placeholder="Legal structure"
          class="w-full"
          :single="true"
          :rules="[isRequired]"
        />
      </div>

      <x-select
        v-model="kycForm.industry_type"
        label="Industry type"
        :options="industryTypeOptions"
        placeholder="Industry type"
        :single="true"
        :rules="[isRequired]"
      />

      <ComboBox
        v-model="kycForm.country_of_corporation"
        label="Country of corporation"
        :options="countryList"
        placeholder="Country of corporation"
        :single="true"
        :rules="[isRequired]"
      />
    </div>
    <div class="grid md:grid-cols-2">
      <x-input
        v-model="kycForm.registered_address"
        label="RESIDENT ADDRESS"
        placeholder="RESIDENT ADDRESS"
        :rules="[isRequired]"
      />
    </div>

    <div class="grid md:grid-cols-2">
      <x-input
        v-model="kycForm.communication_address"
        label="Communication Address"
        placeholder="Communication Address"
        :rules="[isRequired]"
      />
    </div>

    <div class="grid md:grid-cols-4 gap-4">
      <x-input
        v-model="kycForm.mobile_number"
        label="Mobile number"
        placeholder="Mobile number"
        type="number"
        :rules="[isRequired]"
        :disabled="true"
      />

      <x-input
        v-model="kycForm.email"
        label="Email"
        placeholder="Email"
        type="email"
        :rules="[isRequired]"
        :disabled="true"
      />

      <div>
        <x-tooltip placement="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Website
          </label>
          <template #tooltip>
            Please enter the official website of the entity here
          </template>
        </x-tooltip>
        <x-input
          v-model="kycForm.website"
          placeholder="Website"
          class="w-full"
          type="text"
        />
      </div>

      <div>
        <x-tooltip placement="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            ID / Document Type
          </label>
          <template #tooltip>
            Please specify the type of ID received from the customer
          </template>
        </x-tooltip>
        <x-select
          v-model="kycForm.id_document_type"
          :options="documentTypeOptions"
          placeholder="ID / Document Type"
          class="w-full"
          :single="true"
          :rules="[isRequired]"
        />
      </div>

      <x-input
        v-model="kycForm.id_number"
        label="Id number"
        :placeholder="
          kycForm.id_document_type === 'emiratesId'
            ? 'xxx-xxxx-xxxxxxx-x'
            : 'ID number'
        "
        :rules="[isRequired, rules.isEmiratesId]"
      />

      <div>
        <x-tooltip placement="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            ID / Document Issue Date
          </label>
          <template #tooltip>
            Please specify the issuance date of the ID collected
          </template>
        </x-tooltip>
        <DatePicker
          v-model="kycForm.id_issue_date"
          class="w-full"
          :rules="[isRequired]"
        />
      </div>

      <div>
        <x-tooltip placement="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            ID / Document Expiry Date
          </label>
          <template #tooltip>
            Please specify the Expiry date of the ID collected
          </template>
        </x-tooltip>
        <DatePicker
          v-model="kycForm.id_expiry_date"
          class="w-full"
          :rules="[isRequired]"
        />
      </div>

      <div>
        <x-tooltip placement="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Place of issue
          </label>
          <template #tooltip>
            Please select the Emirates of Registration as per Trade License
          </template>
        </x-tooltip>
        <x-select
          v-model="kycForm.place_of_issue"
          :options="placeOfIssuanceOptions"
          placeholder="Place of issue"
          class="w-full"
          :single="true"
          :rules="[isRequired]"
        />
      </div>
    </div>

    <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
      <div>
        <x-tooltip placement="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            ID issuing authority
          </label>
          <template #tooltip>
            Please select the license issuing authority as per trade license
          </template>
        </x-tooltip>
        <ComboBox
          v-model="kycForm.issuing_authority"
          :options="issuingAuthorityOptions"
          placeholder="ID issuing authority"
          :single="true"
          :hasError="isIssuingAuthorityEmpty"
        />
      </div>
      <div>
        <x-tooltip position="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Customer Tenure
          </label>
          <template #tooltip> Customer Tenure </template>
        </x-tooltip>
        <x-input
          v-model="kycForm.customer_tenure"
          placeholder="Customer Tenure"
          class="w-full"
          type="number"
          :rules="[isRequired]"
        />
      </div>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
      <div>
        <x-tooltip position="bottom">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Mode of contact
          </label>
          <template #tooltip>
            Please select the Emirates of Registration as per Trade License
          </template>
        </x-tooltip>
        <x-select
          v-model="kycForm.mode_of_contact"
          :options="contactModOptions"
          placeholder="Mode of contact"
          class="w-full"
          :single="true"
          :rules="[isRequired]"
        />
      </div>
      <div>
        <label
          class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
        >
          Mode of Delivery
        </label>
        <x-select
          v-model="kycForm.mode_of_delivery"
          :options="deliveryModOptions"
          placeholder="Mode of Delivery"
          class="w-full"
          :single="true"
        />
      </div>
      <div></div>
    </div>

    <div class="grid md:grid-cols-1 gap-4 mb-5">
      <h3 class="font-bold text-black-800 text-center">
        UBO and Manager details
      </h3>
    </div>

    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-input
        v-model="kycForm.manager_name"
        label="Name"
        placeholder="Name"
        :rules="[isRequired]"
      />

      <ComboBox
        v-model="kycForm.manager_nationality"
        label="Nationality"
        :options="nationalityOptions"
        placeholder="Nationality"
        :single="true"
        :hasError="isNationalityEmpty"
      />

      <DatePicker
        v-model="kycForm.manager_dob"
        label="Date of birth"
        :rules="[isRequired]"
      />

      <ComboBox
        v-model="kycForm.manager_position"
        label="Position"
        :options="uboRelationOptions"
        placeholder="Position"
        :single="true"
        :hasError="isPositionEmpty"
      />
    </div>

    <div class="grid md:grid-cols-1 gap-4 mb-5">
      <h3 class="font-bold text-black-800 text-center">
        For compliance use only
      </h3>
    </div>

    <div class="flex justify-between gap-4 mb-1">
      <div class="grid md:grid-cols-2">
        <x-label> Is the customer a PEP? </x-label>
        <x-form-group v-model="kycForm.pep" :rules="complianceRules">
          <x-radio
            :value="1"
            label="Yes"
            :disabled="complianceDisable.isDisable"
          />
          <x-radio
            :value="2"
            label="No"
            :disabled="complianceDisable.isDisable"
          />
        </x-form-group>
      </div>
    </div>

    <div class="flex justify-between gap-4 mb-1">
      <div class="grid md:grid-cols-2 mt-3">
        <x-label>
          Is the customer or business subjected to financial sanctions / or
          connected with prescribed terrorist organizations?
        </x-label>
        <x-form-group
          v-model="kycForm.financial_sanctions"
          :rules="complianceRules"
        >
          <x-radio
            :value="1"
            label="Yes"
            :disabled="complianceDisable.isDisable"
          />
          <x-radio
            :value="2"
            label="No"
            :disabled="complianceDisable.isDisable"
          />
        </x-form-group>
      </div>
    </div>

    <div class="flex justify-between gap-4 mb-1">
      <x-label> Does the customer have dual nationality </x-label>
      <div class="grid md:grid-cols-2">
        <x-form-group
          v-model="kycForm.dual_nationality"
          :rules="complianceRules"
        >
          <x-radio
            :value="1"
            label="Yes"
            :disabled="complianceDisable.isDisable"
          />
          <x-radio
            v-model="kycForm.dual_nationality"
            :value="2"
            label="No"
            :disabled="complianceDisable.isDisable"
          />
        </x-form-group>
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label>
        Does the Company name or Subsidiary/Affiliate entities feature in any
        sanction list?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.in_sanction_list"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.in_sanction_list"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Is There A Sanction Match On The Owner/Partners/Bod, Senior Management,
        Group Company, Holding Company Or Related Company Names?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.is_sanction_match"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.is_sanction_match"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Does the company have any subsidiary, affiliate, branch, or
        group/holding company in FATF-listed high-risk monitored
        jurisdiction?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.in_fatf"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.in_fatf"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Does the customer intend to deal with any country listed in the
        Sanctions List?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.deal_sanction_list"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.deal_sanction_list"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Do the customer or subsidiary/ affiliate entities have operations in
        any High-Risk Countries?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.is_operation_high_risk"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.is_operation_high_risk"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Is the owner/ Shareholder/ Partner/Director of the company from
        High-Risk countries?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.is_owner_high_risk"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.is_owner_high_risk"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4 mt-4">
      <div>
        <label
          class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
        >
          Transaction Volume
        </label>
        <x-select
          v-model="kycForm.transaction_volume"
          :options="transactionVolumeOptions"
          placeholder="Transaction Volume"
          class="w-full"
          :single="true"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
      <div>
        <label
          class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
        >
          Transaction Activities
        </label>
        <x-select
          v-model="kycForm.transaction_activities"
          :options="transactionActivitiesOptions"
          placeholder="Transaction Activities"
          class="w-full"
          :single="true"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
      <div>
        <label
          class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
        >
          Transaction Pattern changes
        </label>
        <x-select
          v-model="kycForm.transaction_pattern"
          :options="transactionPatternOptions"
          placeholder="Transaction Pattern"
          class="w-full"
          :single="true"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>

    <div class="flex justify-center gap-3 mt-7">
      <x-button
        :loading="isLoading"
        size="sm"
        color="orange"
        type="submit"
        class="px-6"
      >
        Save
      </x-button>
    </div>
  </x-form>
</template>
