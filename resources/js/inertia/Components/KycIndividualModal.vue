<script setup>
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();
const hasRole = role => useHasRole(role);
const convertDate = date => useConvertDate(date);
const { isRequired, isEmail, isNumber, isMobileNo } = useRules();
const isLoading = ref(false);
const rolesEnum = page.props.rolesEnum;
const props = defineProps({
  roles: Array,
  quote: Object,
  status: Function,
  buttonStatus: Function,
  countryList: Array,
  amlQuoteStatus: Number,
  nationalities: Array,
  modelType: String,
  idDocumentType: Array,
  modeOfContact: Array,
  modeOfDelivery: Array,
  professionalTitle: Array,
  employmentSectors: Array,
  residentialStatus: Array,
  companyPosition: Array,
  customerDetails: Object,
  kycLogs: Array,
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
  nameCheck: v => {
    const pattern = /^[a-zA-Z0-9\s]+$/;
    if (v == null || v == '') return true;
    return pattern.test(v) || 'Special characters are not allowed in Name';
  },
  isEmiratesId: v => {
    const pattern = /^\d{3}-\d{4}-\d{7}-\d{1}$/;
    if (kycForm.id_type === 'emiratesId') {
      return pattern.test(v) || 'Enter the correct EID number format';
    } else {
      return true;
    }
  },
};

const kycLogsValue = computed(() => {
  if (props.kycLogs) {
    const logsStatus = props.kycLogs.filter(val => {
      return val.decision === 'rejected' || val.decision === 'Escalated';
    });
    if (logsStatus.length > 0) {
      return 1;
    } else {
      return 2;
    }
  }
  return 2;
});
const incomeSource = computed(() => {
  if (props.customerDetails?.detail?.source_of_income === 'employed') {
    return 2;
  } else if (props.customerDetails?.detail?.source_of_income === 'business') {
    return 1;
  }
  return null;
});

const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';

const kycForm = reactive({
  quote_uuid: props.quote.uuid,
  customer_id: props.quote.customer_id,
  first_name:
    props.quote?.customer.insured_first_name ?? props.quote.first_name,
  last_name: props.quote?.customer.insured_last_name ?? props.quote.last_name,
  dob: dateFormat(props.quote.dob) || '',
  nationality_id: props.quote.nationality_id ?? null,
  country_of_residence:
    props.customerDetails?.detail?.country_of_residence ?? 56,
  place_of_birth: props.customerDetails?.detail?.place_of_birth ?? null,
  resident_status:
    props.customerDetails?.detail?.residential_status ?? 'uaeResident',
  residential_address:
    props.customerDetails?.detail?.residential_address ?? null,
  mobile_number: props.quote.mobile_no,
  email: props.quote.email,
  customer_tenure: props.customerDetails?.detail?.customer_tenure ?? null,
  id_type: props.customerDetails?.detail?.id_type ?? 'emiratesId',
  id_number:
    props.customerDetails?.detail?.id_number ??
    props.customerDetails?.emirates_id_number ??
    null,
  id_issue_date: convertDate(props.customerDetails?.detail?.id_issuance_date),
  id_expiry_date: convertDate(
    props.customerDetails?.detail?.id_expiry_date ??
      props.customerDetails?.emirates_id_expiry_date,
  ),
  mode_of_contact:
    props.customerDetails?.detail?.mode_of_contact ?? 'phoneAndEmail',
  mode_of_delivery:
    props.customerDetails?.detail?.mode_of_delivery ?? 'mod-delivery-pse',
  income_source: props.customerDetails?.detail?.source_of_income ?? null,
  company_name: props.customerDetails?.detail?.employer_company_name ?? null,
  professional_title: props.customerDetails?.detail?.job_title ?? null,
  employment_sector: props.customerDetails?.detail?.employment_sector ?? null,
  trade_license: props.customerDetails?.detail?.trade_license_no ?? null,
  company_position: props.customerDetails?.detail?.position_in_company ?? null,
  pep: props.customerDetails?.detail?.pep ?? props.amlQuoteStatus,
  financial_sanctions:
    props.customerDetails?.detail?.financial_sanctions ?? props.amlQuoteStatus,
  dual_nationality:
    props.customerDetails?.detail?.dual_nationality ?? props.amlQuoteStatus,
  transaction_pattern:
    props.customerDetails?.detail?.transaction_pattern ?? 'no_changes',
  premium_tenure:
    props.customerDetails?.detail?.premium_tenure ?? 'single_premium',
  in_sanction_list:
    props.customerDetails?.detail?.in_sanction_list ?? props.amlQuoteStatus, // kycLogsValue.value
  deal_sanction_list:
    props.customerDetails?.detail?.deal_sanction_list ?? props.amlQuoteStatus, // kycLogsValue.value
  is_operation_high_risk:
    props.customerDetails?.detail?.is_operation_high_risk ??
    props.amlQuoteStatus, // kycLogsValue.value
  is_partner: props.customerDetails?.detail?.is_partner ?? incomeSource.value,
});

const incomeSourceFields = reactive({
  employed: false,
  business: false,
});

function changeIncomeSource(val) {
  if (val === 'employed') {
    incomeSourceFields.employed = true;
    incomeSourceFields.business = false;
    kycForm.is_partner = 2;
  } else if (val === 'business') {
    incomeSourceFields.employed = false;
    incomeSourceFields.business = true;
    kycForm.is_partner = 1;
  }
}

const isNationalityEmpty = ref(false);
const isPlaceOfBirthEmpty = ref(false);

const onKycSubmit = isValid => {
  if (!kycForm.nationality_id) isNationalityEmpty.value = true;
  else isNationalityEmpty.value = false;

  if (!kycForm.place_of_birth) isPlaceOfBirthEmpty.value = true;
  else isPlaceOfBirthEmpty.value = false;

  if (!isValid) return;

  if (confirm('Are you sure you want to create and save the document?')) {
    isLoading.value = true;
    axios
      .post(`/${props.modelType}/upload-individual-kycdoc`, kycForm)
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
            title: 'Document not uploaded.',
            position: 'top',
          });
        }
      })
      .catch(error => {
        if (error.response.status === 422) {
          console.error(error.response.data.errors);
        }
      })
      .finally(() => (isLoading.value = false));
  }
};

const countryList = computed(() => {
  return props.countryList?.map(nat => ({
    value: nat.id,
    label: nat.country_name,
  }));
});

const nationalityOptions = computed(() => {
  return props.nationalities?.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const residentialStatusOptions = computed(() => {
  return props.residentialStatus?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const documentIdTypeOptions = computed(() => {
  return props.idDocumentType?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const modeOfContactOptions = computed(() => {
  return props.modeOfContact?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const modeOfDeliveryOptions = computed(() => {
  return props.modeOfDelivery?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const professionalTitleOptions = computed(() => {
  return props.professionalTitle?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const employmentSectorsOptions = computed(() => {
  return props.employmentSectors?.map(nat => ({
    value: nat.code,
    label: nat.text,
  }));
});

const companyPositionOptions = computed(() => {
  return props.companyPosition?.map(nat => ({
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
const premiumTenureOptions = [
  {
    value: 'single_premium',
    label: 'Single Premium',
  },
  {
    value: 'quarter_premium',
    label: 'Quarterly/Semi-Annual',
  },
  {
    value: 'monthly',
    label: 'Monthly',
  },
];
const activeField = ref(true);
function activePatternField() {
  if (hasRole(rolesEnum.COMPLIANCE) || hasRole(rolesEnum.ComplianceSuperUser)) {
    activeField.value = false;
  }
}

onMounted(() => {
  complianceDisable.isDisable = !(
    can(permissionsEnum.AMLDecisionUpdate) ||
    can(permissionsEnum.AMLDecisionUpdateTrueMatch)
  );
  activePatternField();
  changeIncomeSource(props.customerDetails?.detail?.source_of_income ?? null);
});
</script>

<template>
  <x-form @submit="onKycSubmit" :auto-focus="false">
    <div class="grid md:grid-cols-4 gap-4">
      <x-input
        v-model="kycForm.customer_id"
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
        :rules="[isRequired, rules.nameCheck]"
      />

      <x-input
        v-model="kycForm.last_name"
        label="Last Name"
        placeholder="Last Name"
        class="w-full"
        :rules="[isRequired, rules.nameCheck]"
      />

      <DatePicker
        v-model="kycForm.dob"
        label="DOB"
        type="date"
        :rules="[isRequired]"
      />

      <ComboBox
        v-model="kycForm.nationality_id"
        label="Nationality"
        :options="nationalityOptions"
        placeholder="Nationality"
        :single="true"
        :hasError="isNationalityEmpty"
      />

      <ComboBox
        v-model="kycForm.country_of_residence"
        label="Country of residence"
        :options="countryList"
        placeholder="Country of residence"
        :single="true"
        :rules="[isRequired]"
      />

      <ComboBox
        v-model="kycForm.place_of_birth"
        label="Place of birth"
        :options="countryList"
        placeholder="Place of birth"
        :single="true"
        :hasError="isPlaceOfBirthEmpty"
      />

      <x-select
        v-model="kycForm.resident_status"
        label="Resident Status"
        :options="residentialStatusOptions"
        placeholder="Resident Status"
        :rules="[isRequired]"
      />
    </div>
    <div class="grid md:grid-cols-2">
      <x-input
        v-model="kycForm.residential_address"
        label="RESIDENT ADDRESS"
        placeholder="RESIDENT ADDRESS"
        :rules="[isRequired]"
      />
    </div>
    <div class="grid md:grid-cols-4 gap-4">
      <x-input
        v-model="kycForm.mobile_number"
        label="Mobile number"
        placeholder="Mobile number"
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

      <x-input
        v-model="kycForm.customer_tenure"
        label="Customer tenure"
        placeholder="Customer tenure"
        type="number"
        :rules="[isRequired]"
      />
    </div>
    <div class="grid md:grid-cols-4 gap-4">
      <x-select
        v-model="kycForm.id_type"
        label="ID type"
        :options="documentIdTypeOptions"
        placeholder="ID type"
        :rules="[isRequired]"
      />

      <x-input
        v-model="kycForm.id_number"
        label="ID number"
        :placeholder="
          kycForm.id_type === 'emiratesId' ? 'xxx-xxxx-xxxxxxx-x' : 'ID number'
        "
        :rules="[isRequired, rules.isEmiratesId]"
      />

      <DatePicker
        v-model="kycForm.id_issue_date"
        label="ID issue date"
        :rules="[isRequired]"
      />

      <DatePicker
        v-model="kycForm.id_expiry_date"
        label="ID expiry date"
        :rules="[isRequired]"
      />
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <x-select
        v-model="kycForm.mode_of_contact"
        label="Mode of contact"
        :options="modeOfContactOptions"
        placeholder="Mode of contact"
        :rules="[isRequired]"
      />

      <x-select
        v-model="kycForm.mode_of_delivery"
        label="Mode of delivery"
        :options="modeOfDeliveryOptions"
        placeholder="Mode of delivery"
        :rules="[isRequired]"
      />
      <div>
        <label
          class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
        >
          Premium Tenure
        </label>
        <x-select
          v-model="kycForm.premium_tenure"
          :options="premiumTenureOptions"
          placeholder="Premium Tenure"
          class="w-full"
          :single="true"
          :rules="[isRequired]"
        />
      </div>
    </div>

    <div class="grid md:grid-cols-1 gap-4">
      <h3 class="font-bold text-black-800 text-center">Source of income</h3>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-2">
      <x-radio
        v-model="kycForm.income_source"
        value="employed"
        label="Employed"
        :rules="[isRequired]"
        @change="changeIncomeSource('employed')"
      />
    </div>
    <div
      class="grid md:grid-cols-3 my-5 gap-4"
      v-if="incomeSourceFields.employed"
    >
      <x-input
        v-model="kycForm.company_name"
        label="Employer / Company name"
        placeholder="Employer / Company name"
        :rules="[rules.isRequired]"
      />

      <ComboBox
        v-model="kycForm.professional_title"
        label="Professional job title"
        :options="professionalTitleOptions"
        placeholder="Professional job title"
        :single="true"
        :rules="[rules.isRequired]"
      />

      <x-select
        v-model="kycForm.employment_sector"
        label="Employment sector"
        :options="employmentSectorsOptions"
        placeholder="Employment sector"
        :rules="[rules.isRequired]"
      />
    </div>
    <div class="grid md:grid-cols-3 gap-4">
      <x-radio
        v-model="kycForm.income_source"
        value="business"
        label="Business"
        :rules="[isRequired]"
        @change="changeIncomeSource('business')"
      />
    </div>
    <div
      class="grid md:grid-cols-3 my-5 gap-4"
      v-if="incomeSourceFields.business"
    >
      <x-input
        v-model="kycForm.company_name"
        label="Company name"
        placeholder="Company name"
        :rules="[rules.isRequired]"
      />

      <x-input
        v-model="kycForm.trade_license"
        label="Trade License#"
        placeholder="Trade License#"
        :rules="[rules.isRequired]"
      />

      <x-select
        v-model="kycForm.company_position"
        label="Position in Company"
        :options="companyPositionOptions"
        placeholder="Position in Company"
        :rules="[rules.isRequired]"
      />
    </div>

    <div class="grid md:grid-cols-1 gap-4 my-5">
      <h3 class="font-bold text-black-800 text-center">
        For compliance use only
      </h3>
    </div>

    <div class="flex justify-between gap-4 mb-1">
      <x-label> Is the customer a PEP? </x-label>
      <div class="grid md:grid-cols-2">
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
      <x-label>
        Is the customer or business subjected to financial sanctions / or
        connected with prescribed terrorist organizations?
      </x-label>

      <div class="grid md:grid-cols-2 mt-3">
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
      <x-label> Does the customer have dual nationality?</x-label>
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
            :value="2"
            label="No"
            :disabled="complianceDisable.isDisable"
          />
        </x-form-group>
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Is the Natural Person listed in any Sanction/OOL/SIP list?</x-label
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
        >Is the Natural Person an Owner/Shareholder/Partner in any
        Organization?</x-label
      >
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.is_partner"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.is_partner"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <x-label
        >Does the Natural Person intend to provide professional services in any
        sanctions-listed country/ies?</x-label
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
        >Is the Natural Person controlling/involved in any business listed in
        High-Risk Countries?</x-label
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
    <div class="grid md:grid-cols-2 gap-4 mt-4">
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
          :disabled="activeField"
          :rules="[isRequired]"
        />
      </div>
    </div>

    <template #secondary-action>
      <x-button
        ghost
        tabindex="-1"
        size="sm"
        @click.prevent="status(false)"
        class="px-6"
      >
        Cancel
      </x-button>
    </template>
    <template #primary-action>
      <x-button
        :loading="isLoading"
        size="sm"
        color="orange"
        type="submit"
        class="px-6"
      >
        Save
      </x-button>
    </template>
  </x-form>
</template>
