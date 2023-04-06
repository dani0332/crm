<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import ComboBox from '@/inertia/Components/ComboBox.vue';

const props = defineProps({
  businessInsuranceType: Object,
  quote: Object,
});

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const formFields = computed(() => {
  return Object.keys(props.fields).map(field => ({
    value: field,
    label: props.fields[field].label,
  }));
});

const quoteForm = useForm({
  modelType: '"Business"',
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  premium: props.quote.premium,
  company_name: props.quote.company_name,
  number_of_employees: props.quote.number_of_employees,
  business_type_of_insurance_id: props.quote.business_type_of_insurance_id,
  brief_details: props.quote.brief_details,
});

const { isRequired, emptyOrDecimal, isNumber, isEmail  } = useRules();

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.post('/medical/amt', {
      onSuccess: () => {
        router.get('/medical/amt');
        },
        onFinish: () => {
            console.log(quoteForm);
          isEmptyField.value = true;
        },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Edit Group Medical" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Group Medical Lead</h2>
      <div class="space-x-4">
        <Link :href="`/medical/amt/${props.quote.uuid}`">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link href="/medical/amt">
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
            Business Insurance Type
            <sup class="text-red-500">*</sup>
          </p>

          <x-select
            v-model="quoteForm.business_type_of_insurance_id"
            :options="
              businessInsuranceType.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.business_type_of_insurance_id"
          />
        </label>

        <label>
          <p>
            BRIEF DETAILS
            <sup class="text-red-500">*</sup>
          </p>

          <x-textarea
            v-model="quoteForm.brief_details"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.brief_details"
          />
        </label>

        <x-input
          v-model="quoteForm.premium"
          type="text"
          label="PREMIUM"
          class="w-full"
          :rules="[emptyOrDecimal]"
          :error="quoteForm.errors.premium"
        />
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
