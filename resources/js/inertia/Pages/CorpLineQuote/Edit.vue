<script setup>
const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  model: String,
});

const quoteForm = useForm({
  modelType: '"Business"',
  gender: props.quote.gender,
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  premium: props.quote.premium,
  company_name: props.quote.company_name,
  number_of_employees: props.quote.number_of_employees,
  business_type_of_insurance_id: props.quote.business_type_of_insurance_id,
  group_medical_type_id: props.selectedGmType,
  brief_details: props.quote.brief_details,
});

const { isRequired, isNumber, isDecimal, isEmail } = useRules();

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
  if (isValid) {
    quoteForm.put(route('business.update', props.quote.uuid), {
      onSuccess: () => {
        router.get(`/quotes/business/${props.quote.uuid}`);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Edit Business Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Business Quote Lead</h2>
      <div class="space-x-4">
        <Link :href="`/quotes/business/${props.quote.uuid}`">
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
          />
        </x-field>
        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>

        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired, isEmail]"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>

        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired]"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>

        <x-field label="COMPANY NAME" required>
          <x-input
            v-model="quoteForm.company_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.company_name"
          />
        </x-field>

        <!-- <label>
          <p>POLICY NUMBER</p>

          <x-input
            v-model="quoteForm.policy_number"
            type="text"
            class="w-full"
            :error="quoteForm.errors.company_name"
          />
        </label> -->
        <x-field label="PRICE" required>
          <x-input
            v-model="quoteForm.premium"
            type="text"
            :rules="[isRequired, isDecimal]"
            class="w-full"
            :error="quoteForm.errors.premium"
          />
        </x-field>

        <x-field label="NUMBER OF EMPLOYEES" required>
          <x-input
            v-model="quoteForm.number_of_employees"
            type="text"
            :rules="[isRequired, isNumber]"
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

        <x-field label="Brief Details" required>
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
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
