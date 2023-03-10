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

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  isNumber: v => /^\d+$/.test(v) || 'This field must be a number',
  isDecimal: v => /^\d+(\.\d{1,2})?$/.test(v) || 'Must be a decimal',
};

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.post('/medical/amt', {
      onSuccess: () => {
        router.get('/medical/amt');
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
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          label="FIRST NAME"
          :rules="[rules.isRequired]"
          class="w-full"
          :errors="quoteForm.errors.first_name"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME"
          :rules="[rules.isRequired]"
          class="w-full"
          :errors="quoteForm.errors.last_name"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL"
          :rules="[rules.isRequired, rules.isEmail]"
          class="w-full"
          :errors="quoteForm.errors.email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          :rules="[rules.isRequired]"
          class="w-full"
          :errors="quoteForm.errors.mobile_no"
        />

        <x-input
          v-model="quoteForm.company_name"
          type="text"
          label="Company Name"
          :rules="[rules.isRequired]"
          class="w-full"
          :errors="quoteForm.errors.company_name"
        />

        <x-input
          v-model="quoteForm.number_of_employees"
          type="text"
          label="Number Of Employees"
          :rules="[rules.isRequired, rules.isNumber]"
          class="w-full"
          :errors="quoteForm.errors.number_of_employees"
        />

        <x-select
          v-model="quoteForm.business_type_of_insurance_id"
          label="Business Insurance Type"
          :options="
            businessInsuranceType.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :rules="[rules.isRequired]"
          class="w-full"
          :errors="quoteForm.errors.business_type_of_insurance_id"
        />

        <x-input
          v-model="quoteForm.premium"
          type="text"
          label="PREMIUM"
          :rules="[rules.isRequired, rules.isDecimal]"
          class="w-full"
          :errors="quoteForm.errors.premium"
        />

        <x-textarea
          v-model="quoteForm.brief_details"
          label="Brief Details"
          :rules="[rules.isRequired]"
          class="w-full"
          :errors="quoteForm.errors.brief_details"
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
