<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import ComboBox from '@/inertia/Components/ComboBox.vue';

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

const { isRequired, isNumber, isDecimal, isEmail  } = useRules();

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
    quoteForm.put(`/quotes/business/${props.quote.uuid}`, {
      onSuccess: () => {
        router.get(`/quotes/business/${props.quote.uuid}`);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Edit Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Business Quote Lead</h2>
      <div class="space-x-4">
        <Link :href="`/quotes/business/${props.quote.uuid}`">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link href="/quotes/business">
          <x-button size="sm" color="#ff5e00" tag="div"> Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <label>
          <p>
            FIRST NAME
            <sup class="text-red-500">*</sup>
          </p>

          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.first_name"
          />
        </label>

        <label>
          <p>
            LAST NAME
            <sup class="text-red-500">*</sup>
          </p>

          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </label>

        <label>
          <p>
            EMAIL
            <sup class="text-red-500">*</sup>
          </p>

          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired, isEmail]"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </label>

        <label>
          <p>
            MOBILE NUMBER
            <sup class="text-red-500">*</sup>
          </p>

          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired]"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </label>

        <label>
          <p>
            COMPANY NAME
            <sup class="text-red-500">*</sup>
          </p>

          <x-input
            v-model="quoteForm.company_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.company_name"
          />
        </label>

        <!-- <label>
          <p>POLICY NUMBER</p>

          <x-input
            v-model="quoteForm.policy_number"
            type="text"
            class="w-full"
            :error="quoteForm.errors.company_name"
          />
        </label> -->

        <x-input
          v-model="quoteForm.premium"
          type="text"
          label="PREMIUM"
          :rules="[isRequired, isDecimal]"
          class="w-full"
          :error="quoteForm.errors.premium"
        />

        <label>
          <p>
            NUMBER OF EMPLOYEES
            <sup class="text-red-500">*</sup>
          </p>

          <x-input
            v-model="quoteForm.number_of_employees"
            type="text"
            :rules="[isRequired, isNumber]"
            class="w-full"
            :error="quoteForm.errors.number_of_employees"
          />
        </label>

        <label>
          <p>
            BUSINESS INSURANCE TYPE
            <sup class="text-red-500">*</sup>
          </p>

          <x-select
            v-model="quoteForm.business_type_of_insurance_id"
            :options="businessInsuranceTypeOptions"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.business_type_of_insurance_id"
          />
        </label>

        <label>
          <p>
            GENDER
            <sup class="text-red-500">*</sup>
          </p>
          <x-select
            v-model="quoteForm.gender"
            :options="genderOptions"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.gender"
          />
        </label>

        <label>
          <p>
            Brief Details
            <sup class="text-red-500">*</sup>
          </p>

          <x-textarea
            v-model="quoteForm.brief_details"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.brief_details"
          />
        </label>
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
