<script setup>
const page = usePage();
const notification = useToast();
const hasRole = role => useHasRole(role);
const { isRequired, isEmail, isNumber, isMobileNo } = useRules();

const props = defineProps({
  roles: Array,
  quote: Object,
  status: Function,
  countryList: Array,
  amlQuoteStatus: String,
  nationalities: Array,
  modelType: String
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
  dob: props.quote.dob,
  nationality_id: props.quote.nationality_id,
  country_of_residence: null,
  place_of_birth: null,
  resident_status: null,
  residential_address: null,
  mobile_number: props.quote.mobile_number,
  email: props.quote.email,
  customer_tenure: null,
  id_type: null,
  id_number: null,
  id_issue_date: null,
  id_expiry_date: null,
  mode_of_contact: null,
  mode_of_delivery: null,
  income_source: null,
  company_name: null,
  professional_title: null,
  employment_sector: null,
  trade_license: null,
  company_position: null,
  pep: props.amlQuoteStatus,
  financial_sanctions: props.amlQuoteStatus,
  dual_nationality: props.amlQuoteStatus,
});

const incomeSourceFields = reactive({
  employed: false,
  business: false,
});

function changeIncomeSource(val) {
  if(val === 'employed') {
    incomeSourceFields.employed = true;
    incomeSourceFields.business = false;
  } else if(val === 'business') {
    incomeSourceFields.employed = false;
    incomeSourceFields.business = true;
  }
}

const onKycSubmit = () => {
  if (confirm('Are you sure you want to create and save the document?')) {
    axios.post(`/${props.modelType}/upload-individual-kycdoc`, kycForm).then(response => {
      if (response.data.success) {
        notification.success({
          title: 'KYC Document uploaded.',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Document not uploaded.',
          position: 'top',
        });
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

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

</script>

<template>
  <x-form @submit="onKycSubmit" :auto-focus="false">
      <div class="grid md:grid-cols-4 gap-4">
        <x-input
            v-model="quote.customer_id"
            label="Customer ID"
            placeholder="Customer ID"
            class="w-full disabled"
            :disabled="true"
        />

        <x-input
            v-model="quote.first_name"
            label="First Name"
            placeholder="First Name"
            class="w-full"
            :disabled="true"
        />

        <x-input
            v-model="quote.last_name"
            label="Last Name"
            placeholder="Last Name"
            class="w-full"
            :disabled="true"
        />

        <DatePicker
            v-model="quote.dob"
            label="DOB"
            :disabled="true"
        />

        <ComboBox
            v-model="quote.nationality_id"
            label="Nationality"
            :options="nationalityOptions"
            placeholder="Select Nationality"
            :single="true"
            :disabled="true"
        />

        <ComboBox
            v-model="kycForm.country_of_residence"
            label="Country of residence"
            :options="countryList"
            placeholder="Country of residence"
            :single="true"
        />

        <ComboBox
            v-model="kycForm.place_of_birth"
            label="Place of birth"
            :options="countryList"
            placeholder="Place of birth"
            :single="true"
        />

        <x-select
            v-model="kycForm.resident_status"
            label="Resident Status"
            :options="[
                  { value: 'UAE resident', label: 'UAE resident' },
                  { value: 'Non UAE resident', label: 'Non UAE resident' },
                ]"
            placeholder="Resident Status"
            :rules="[isRequired]"
        />
      </div>
      <div class="grid md:grid-cols-2">
        <x-input
            v-model="kycForm.residential_address"
            label="Resident Address"
            placeholder="Resident Address"
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

        <x-input
            v-model="kycForm.customer_tenure"
            label="Customer tenure"
            placeholder="Customer tenure"
            :rules="[isRequired]"
        />
      </div>
      <div class="grid md:grid-cols-4 gap-4">
        <x-select
            v-model="kycForm.id_type"
            label="ID type"
            :options="[
                  { value: 'emirated_id', label: 'Emirates ID' },
                  { value: 'passport', label: 'Passport' },
                  { value: 'home_country_id', label: 'Home Country ID' },
                  { value: 'driving_license', label: 'Driving License' },
                  { value: 'visa', label: 'Visa' },
                ]"
            placeholder="ID type"
            :rules="[isRequired]"
        />

        <x-input
            v-model="kycForm.id_number"
            label="ID number"
            placeholder="ID number"
            type="number"
            :rules="[isRequired]"
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

        <x-select
            v-model="kycForm.mode_of_contact"
            label="Mode of contact"
            :options="[
                  { value: 'phone', label: 'Phone' },
                  { value: 'email', label: 'Email' },
                  { value: 'phone_and_email', label: 'Phone and Email' },
                  { value: 'walk_in', label: 'Walk-in' },
                ]"
            placeholder="Mode of contact"
            :rules="[isRequired]"
        />

        <x-select
            v-model="kycForm.mode_of_delivery"
            label="Mode of delivery"
            :options="[
                  { value: 'phone', label: 'Phone' },
                  { value: 'email', label: 'Email' },
                  { value: 'phone_and_email', label: 'Phone and Email' },
                  { value: 'walk_in', label: 'Walk-in' },
                ]"
            placeholder="Mode of delivery"
            :rules="[isRequired]"
        />
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
        <div class="grid md:grid-cols-1" v-if="incomeSourceFields.employed">
          <x-input
              v-model="kycForm.company_name"
              label="Employer / Company name"
              placeholder="Employer / Company name"
              :rules="[rules.isRequired]"
          />

          <x-input
              v-model="kycForm.professional_title"
              label="Professional job title"
              placeholder="Professional job title"
              :rules="[rules.isRequired]"
          />

          <x-select
              v-model="kycForm.employment_sector"
              label="Employment sector"
              :options="[
                    { value: 'Government Sector', label: 'Government Sector' },
                    { value: 'Semi Government Sector', label: 'Semi Government Sector' },
                    { value: 'Private Sector', label: 'Private Sector' },
                    { value: 'Freezone sector', label: 'Freezone sector' },
                  ]"
              placeholder="Employment sector"
              :rules="[rules.isRequired]"
          />
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <x-radio
            v-model="kycForm.income_source"
            value="business"
            label="Business"
            :rules="[isRequired]"
            @change="changeIncomeSource('business')"
        />
        <div class="grid md:grid-cols-1" v-if="incomeSourceFields.business">
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
              :options="[
                    { value: 'Owner', label: 'Owner' },
                    { value: 'Partner', label: 'Partner' },
                    { value: 'Shareholder', label: 'Shareholder' },
                    { value: 'Manager', label: 'Manager' },
                  ]"
              placeholder="Position in Company"
              :rules="[rules.isRequired]"
          />
        </div>
      </div>

      <div class="grid md:grid-cols-1 gap-4">
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
              :rules="[isRequired]"
              :disabled="!hasRole(props.roles.COMPLIANCE)"
          />
          <x-radio
              v-model="kycForm.pep"
              value="No"
              label="No"
              :rules="[isRequired]"
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
              :rules="[isRequired]"
              :disabled="!hasRole(props.roles.COMPLIANCE)"
          />
          <x-radio
              v-model="kycForm.financial_sanctions"
              value="No"
              label="No"
              :rules="[isRequired]"
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
              :rules="[isRequired]"
              :disabled="!hasRole(props.roles.COMPLIANCE)"
          />
          <x-radio
              v-model="kycForm.dual_nationality"
              value="No"
              label="No"
              :rules="[isRequired]"
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
