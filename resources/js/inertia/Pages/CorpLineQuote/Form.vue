<script setup>
const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  model: String,
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
  premium: props.quote.premium,
  company_name: props.quote.business_company_name,
  company_address: props.quote.business_company_address,
  number_of_employees: props.quote.number_of_employees,
  business_type_of_insurance_id: props.quote.business_type_of_insurance_id,
  group_medical_type_id: props.selectedGmType,
  brief_details: props.quote.brief_details,
});

const { isRequired, emptyOrDecimal, isNumber, isEmail, isMobileNo } =
  useRules();

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
        <x-field label="FIRST NAME" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.first_name"
            maxLength="20"
          />
        </x-field>

        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
            maxLength="50"
          />
        </x-field>

        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="isEdit"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>

        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="isEdit"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>

        <x-field label="COMPANY NAME">
          <x-input
            v-model="quoteForm.company_name"
            type="text"
            class="w-full"
            :error="quoteForm.errors.company_name"
          />
        </x-field>

        <x-field label="COMPANY ADDRESS">
          <x-input
            v-model="quoteForm.company_address"
            type="text"
            class="w-full"
            :error="quoteForm.errors.company_address"
          />
        </x-field>

        <x-field label="NUMBER OF EMPLOYEES" required>
          <x-input
            v-model="quoteForm.number_of_employees"
            type="number"
            :rules="[isRequired, isNumber, maxValidation(2147483645)]"
            class="w-full"
            :error="quoteForm.errors.number_of_employees"
          />
        </x-field>

        <x-field label="BUSINESS INSURANCE TYPE" required>
          <x-select
            v-model="quoteForm.business_type_of_insurance_id"
            :options="businessInsuranceTypeOptions"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.business_type_of_insurance_id"
          />
        </x-field>

        <x-field label="GENDER" required>
          <x-select
            v-model="quoteForm.gender"
            :options="genderOptions"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.gender"
          />
        </x-field>

        <x-field label="PRICE" required>
          <x-input
            v-model="quoteForm.premium"
            type="number"
            :rules="[emptyOrDecimal]"
            class="w-full"
            :error="quoteForm.errors.premium"
          />
        </x-field>

        <x-field label="BRIEF DETAILS" required>
          <x-textarea
            v-model="quoteForm.brief_details"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.brief_details"
          />
        </x-field>
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
