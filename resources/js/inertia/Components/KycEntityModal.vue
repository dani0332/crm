<script setup>
const page = usePage();
const notification = useToast();
const hasRole = role => useHasRole(role);
const {isRequired, isEmail, isNumber, isMobileNo} = useRules();

const props = defineProps({
  roles: Array,
  quote: Object,
  status: Function,
  countryList: Array,
  amlQuoteStatus: String,
  nationalities: Array,
  modelType: String,
  entities: Array,
})

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
  company_name: props.quote.company_name,
  legal_structure: null,
  industry_type: null,
  country_of_corporation: 56, //Default UAE
  registered_address: null,
  communication_address: null,
  mobile_number: props.quote.mobile_number,
  email: props.quote.email,
  website: null,
  id_document_type: null,
  id_number: null,
  id_issue_date: null,
  id_expiry_date: null,
  place_of_issue: null,
  issuing_authority: null,
  manager_name: null,
  manager_nationality: null,
  manager_dob: null,
  manager_position: null,
  pep: props.amlQuoteStatus,
  financial_sanctions: props.amlQuoteStatus,
  dual_nationality: props.amlQuoteStatus,
});

const onKycSubmit = () => {
  if (confirm('Are you sure you want to create and save the document?')) {
    axios.post(`/${props.modelType}/upload-entity-kycdoc`, kycForm).then(response => {
      if (response.data.success) {
        notification.success({
          title: 'KYC Document uploaded.',
          position: 'top',
        });
        props.status(false)
      } else {
        notification.error({
          title: 'Document not uploaded.',
          position: 'top',
        });
      }
    }).catch(error => {
      if (error.response.status === 422) {
        console.error(error.response.data.errors);
      }
    });
  }
};

const countryList = computed(() => {
  return props.countryList.map(nat => ({
    value: nat.id,
    label: nat.country_name,
  }))
});

const minDate = computed(() => {
  const today = new Date();
  const tomorrow = new Date(today);

  return tomorrow.setDate(today.getDate() + 1);
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const entitiesOptions = computed(() => {
  return props.entities.map(nat => ({
    value: nat.id,
    label: nat.industry_type_code,
  }));
});

const legalStructureOptions = [
  {value: 'Establishment', label: 'Establishment'},
  {value: 'Sole proprietorship', label: 'Sole proprietorship'},
  {value: 'Private joint stock company', label: 'Private joint stock company'},
  {value: 'Limited liability company', label: 'Limited liability company'},
  {value: 'Public joint stock company', label: 'Public joint stock company'},
  {value: 'Branch of a foreign company', label: 'Branch of a foreign company'},
];

const placeOfIssuanceOptions = [
  {value: 'Dubai', label: 'Dubai'},
  {value: 'Abu Dhabi', label: 'Abu Dhabi'},
  {value: 'Sharjah', label: 'Sharjah'},
  {value: 'Umm Al Quwain', label: 'Umm Al Quwain'},
  {value: 'Ras Al Khaima', label: 'Ras Al Khaima'},
  {value: 'Ajman', label: 'Ajman'},
  {value: 'Fujairah', label: 'Fujairah'},
];

const issuingAuthorityOptions = [
  {value: 'Department of Economic Development (DED)', label: 'Department of Economic Development (DED)'},
  {value: 'Free Zone Authorities', label: 'Free Zone Authorities'},
  {value: 'Dubai Creative Clusters Authority (DCCA)', label: 'Dubai Creative Clusters Authority (DCCA)'},
  {value: 'Ministry of Economy', label: 'Ministry of Economy'},
  {value: 'Department of Tourism and Commerce Marketing (DTCM)', label: 'Department of Tourism and Commerce Marketing (DTCM)'},
  {value: 'Department of Health and Prevention (DoHP)', label: 'Department of Health and Prevention (DoHP)'},
  {value: 'Ministry of Human Resources and Emiratisation (MOHRE)', label: 'Ministry of Human Resources and Emiratisation (MOHRE)'},
  {value: 'Ministry of Interior', label: 'Ministry of Interior'},
  {value: 'Department of Energy (DoE)', label: 'Department of Energy (DoE)'},
  {value: 'Central Bank of the UAE', label: 'Central Bank of the UAE'},
  {value: 'Telecommunications Regulatory Authority (TRA)', label: 'Telecommunications Regulatory Authority (TRA)'},
  {value: 'Dubai Multi Commodities Centre (DMCC)', label: 'Dubai Multi Commodities Centre (DMCC)'},
];

const complianceRules = computed(() => {
  return hasRole(props.roles.COMPLIANCE) ? [rules.isRequired] : [];
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
          v-model="quote.first_name"
          label="First Name"
          placeholder="First Name"
          class="w-full"
          :disabled="true"
          :rules="[isRequired]"
      />

      <x-input
          v-model="quote.last_name"
          label="Last Name"
          placeholder="Last Name"
          class="w-full"
          :disabled="true"
          :rules="[isRequired]"
      />

      <x-input
          v-model="kycForm.company_name"
          label="Employer / Company name"
          placeholder="Employer / Company name"
          :rules="[rules.isRequired]"
      />

      <div>
        <x-tooltip position="right">
          <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            Legal structure
          </label>
          <template #tooltip> Please select the legal structure of the company</template>
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
          :options="entitiesOptions"
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
          <template #tooltip> Please enter the official website of the entity here </template>
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
          <template #tooltip> Please specify the type of ID received from the customer </template>
        </x-tooltip>
        <x-select
            v-model="kycForm.id_document_type"
            :options="[
                {value: 'Trade License', label: 'Trade License'},
                {value: 'MOA', label: 'MOA'},
                {value: 'Others', label: 'Others'},
            ]"
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
          type="number"
      />

      <div>
        <x-tooltip position="bottom">
          <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
          >
            ID / Document Issue Date
          </label>
          <template #tooltip> Please specify the issuance date of the ID collected </template>
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
          <template #tooltip> Please specify the Expiry date of the ID collected </template>
        </x-tooltip>
        <DatePicker
            v-model="kycForm.id_expiry_date"
            class="w-full"
            :min-date="minDate"
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
          <template #tooltip> Please select the Emirates of Registration as per Trade License </template>
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
          <template #tooltip> Please select the license issuing authority as per trade license </template>
        </x-tooltip>
        <ComboBox
            v-model="kycForm.issuing_authority"
            :options="issuingAuthorityOptions"
            placeholder="ID issuing authority"
            :single="true"
            :rules="[isRequired]"
        />
      </div>
    </div>

    <div class="grid md:grid-cols-1 gap-4 mb-5">
      <h3 class="font-bold text-black-800 text-center">UBO and Manager details</h3>
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
          :rules="[isRequired]"
      />

      <DatePicker
          v-model="kycForm.manager_dob"
          label="Date of birth"
          :rules="[isRequired]"
      />

      <x-input
          v-model="kycForm.manager_position"
          label="Position"
          placeholder="Position"
          :rules="[isRequired]"
      />
    </div>

    <div class="grid md:grid-cols-1 gap-4 mb-5">
      <h3 class="font-bold text-black-800 text-center">For compliance use only</h3>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-1">
      <x-label>
        Is the customer a PEP?
      </x-label>
      <div class="grid md:grid-cols-2">
        <x-radio
            v-model="kycForm.pep"
            value="Yes"
            label="Yes"
            :rules="complianceRules"
            :disabled="!hasRole(props.roles.COMPLIANCE)"
        />
        <x-radio
            v-model="kycForm.pep"
            value="No"
            label="No"
            :rules="complianceRules"
            :disabled="!hasRole(props.roles.COMPLIANCE)"
        />
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-1">
      <x-label>
        Is the customer or business subjected to financial sanctions / or connected with prescribed terrorist organizations?
      </x-label>
      <div class="grid md:grid-cols-2 mt-3">
        <x-radio
            v-model="kycForm.financial_sanctions"
            value="Yes"
            label="Yes"
            :rules="complianceRules"
            :disabled="!hasRole(props.roles.COMPLIANCE)"
        />
        <x-radio
            v-model="kycForm.financial_sanctions"
            value="No"
            label="No"
            :rules="complianceRules"
            :disabled="!hasRole(props.roles.COMPLIANCE)"
        />
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <x-label>
        Does the customer have dual nationality
      </x-label>
      <div class="grid md:grid-cols-2">
        <x-radio
            v-model="kycForm.dual_nationality"
            value="Yes"
            label="Yes"
            :rules="complianceRules"
            :disabled="!hasRole(props.roles.COMPLIANCE)"
        />
        <x-radio
            v-model="kycForm.dual_nationality"
            value="No"
            label="No"
            :rules="complianceRules"
            :disabled="!hasRole(props.roles.COMPLIANCE)"
        />
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <x-button size="sm" @click.prevent="status(false)">
        Cancel
      </x-button>

      <x-button
          size="sm"
          color="emerald"
          type="submit"
          class="px-6"
      >
        Save
      </x-button>
    </div>
  </x-form>
</template>
