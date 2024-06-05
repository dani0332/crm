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
};



const kycForm = reactive({
  quote_uuid: props.quote.uuid,
  customer_id: props.quote.customer_id,
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  company_name: props.entityDetails?.entity?.company_name ?? null, // props.quote.company_name
  legal_structure: props.entityDetails?.entity?.legal_structure ?? null,
  industry_type: props.entityDetails?.entity?.industry_type_code ?? null,
  country_of_corporation: props.entityDetails?.entity?.country_of_corporation ?? 56, //Default UAE
  registered_address: props.entityDetails?.entity?.registered_address ?? null,
  communication_address: props.entityDetails?.entity?.communication_address ?? null,
  mobile_number: props.entityDetails?.entity?.mobile_no ?? props.quote.mobile_no,
  email: props.entityDetails?.entity?.email ?? props.quote.email,
  website: props.entityDetails?.entity?.website ?? null,
  id_document_type: props.entityDetails?.entity?.id_type ?? null,
  id_number: props.entityDetails?.entity?.id_number ?? null,
  id_issue_date: convertDate(props.entityDetails?.entity?.id_issuance_date),
  id_expiry_date: convertDate(props.entityDetails?.entity?.id_expiry_date),
  place_of_issue: props.entityDetails?.entity?.issuance_place ?? null,
  issuing_authority: props.entityDetails?.entity?.id_issuance_authority ?? null,
  manager_name: props.entityDetails?.entity?.quote_member?.first_name ?? null,
  manager_nationality: props.entityDetails?.entity?.quote_member?.nationality_id ?? null,
  manager_dob: props.entityDetails?.entity?.quote_member?.dob ?? null,
  manager_position: props.entityDetails?.entity?.quote_member?.relation_code ?? null,
  pep: props.entityDetails?.entity?.pep ?? props.amlQuoteStatus,
  financial_sanctions: props.entityDetails?.entity?.financial_sanctions ?? props.amlQuoteStatus,
  dual_nationality: props.entityDetails?.entity?.dual_nationality ?? props.amlQuoteStatus,
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

  if(!isValid) return;

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
        <x-tooltip position="right">
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
        label="Resident Address"
        placeholder="Resident Address"
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
      />

      <x-input
        v-model="kycForm.email"
        label="Email"
        placeholder="Email"
        type="email"
        :rules="[isRequired]"
      />

      <div>
        <x-tooltip position="bottom">
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
        <x-tooltip position="bottom">
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
        placeholder="Id number"
        :rules="[isRequired]"
      />

      <div>
        <x-tooltip position="bottom">
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
        <x-tooltip position="bottom">
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
        <x-tooltip position="bottom">
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
        <x-tooltip position="bottom">
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

    <div class="grid md:grid-cols-2 gap-4 mb-1">
      <x-label> Is the customer a PEP? </x-label>
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.pep"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.pep"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-1">
      <x-label>
        Is the customer or business subjected to financial sanctions / or
        connected with prescribed terrorist organizations?
      </x-label>
      <div class="grid md:grid-cols-2 mt-3">
        <x-radio
          v-model="kycForm.financial_sanctions"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.financial_sanctions"
          :value="2"
          label="No"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <x-label> Does the customer have dual nationality </x-label>
      <div class="grid md:grid-cols-2">
        <x-radio
          v-model="kycForm.dual_nationality"
          :value="1"
          label="Yes"
          :rules="complianceRules"
          :disabled="complianceDisable.isDisable"
        />
        <x-radio
          v-model="kycForm.dual_nationality"
          :value="2"
          label="No"
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
