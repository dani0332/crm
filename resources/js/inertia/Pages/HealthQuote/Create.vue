<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import ComboBox from '@/inertia/Components/ComboBox.vue';

const props = defineProps({
  dropdownSource: Object,
  model: String,
  genderOptions: Object,
});

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const quoteForm = useForm({
  modelType: '"Health"',
  model: props.model,
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  dob: '',
  premium: null,
  policy_number: null,
  preference: '',
  details: '',
  marital_status_id: null,
  cover_for_id: null,
  nationality_id: null,
  lead_type_id: null,
  emirate_of_your_visa_id: null,
  salary_band_id: null,
  member_category_id: null,
  gender: null,
  currently_insured_with_id: null,
  policy_start_date: null,
  is_ebp_renewal: null,
  is_ecommerce: null,
  has_dental: null,
  has_worldwide_cover: null,
  has_home: null,
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
};

const isEmptyField = ref(false);
function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }
  if (isValid) {
    quoteForm.post(`/quotes/save`, {
      onError: errors => {
        console.log(errors);
      },
      onSuccess: () => {
        router.get(`/quotes/health/`);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Create Health" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Create Health</h2>
      <div>
        <Link href="/quotes/health">
          <x-button size="sm" color="#ff5e00" tag="div"> Health List </x-button>
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
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME"
          :rules="[rules.isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL"
          :rules="[rules.isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <x-input
          v-model="quoteForm.dob"
          type="date"
          label="DATE OF BIRTH"
          :rules="[rules.isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.premium"
          type="text"
          label="PREMIUM"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.policy_number"
          type="text"
          label="POLICY NUMBER"
          class="w-full"
        />

        <x-select
          v-model="quoteForm.cover_for_id"
          label="WHO WOULD YOU LIKE COVER FOR?"
          :rules="[rules.isRequired]"
          :options="
            dropdownSource.cover_for_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-input
          v-model="quoteForm.preference"
          type="text"
          label="PREFERENCE"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.details"
          type="text"
          label="DETAILS"
          class="w-full"
        />

        <x-select
          v-model="quoteForm.lead_type_id"
          label="LEAD TYPE"
          :options="
            dropdownSource.lead_type_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-select
          v-model="quoteForm.currently_insured_with_id"
          label="CURRENTLY INSURED WITH"
          :options="
            dropdownSource.currently_insured_with_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-select
          v-model="quoteForm.marital_status_id"
          label="MARITAL STATUS"
          :options="
            dropdownSource.marital_status_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <ComboBox
          v-model="quoteForm.nationality_id"
          label="NATIONALITY"
          :single="true"
          :options="
            dropdownSource.nationality_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :hasError="isEmptyField"
        />

        <x-select
          v-model="quoteForm.emirate_of_your_visa_id"
          label="EMIRATE OF YOUR VISA"
          :rules="[rules.isRequired]"
          :options="
            dropdownSource.emirate_of_your_visa_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-select
          v-model="quoteForm.member_category_id"
          label="MEMBER CATEGORY"
          :options="
            dropdownSource.member_category_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-select
          v-model="quoteForm.salary_band_id"
          label="SALARY BAND"
          :options="
            dropdownSource.salary_band_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-select
          v-model="quoteForm.gender"
          label="GENDER"
          :options="genderSelect"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.policy_start_date"
          type="text"
          label="POLICY START DATE"
          class="w-full"
        />

        <div class="grid grid-cols-2 gap-2">
          <x-checkbox
            v-model="quoteForm.is_ebp_renewal"
            label="IS EBP RENEWAL"
            color="primary"
            class="w-full"
          />

          <x-checkbox
            v-model="quoteForm.has_dental"
            label="DENTAL"
            color="primary"
          />

          <x-checkbox
            v-model="quoteForm.has_worldwide_cover"
            label="WORLDWIDE COVER"
            color="primary"
          />

          <x-checkbox
            v-model="quoteForm.has_home"
            label="HOME COUNTRY COVER"
            color="primary"
          />
        </div>
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
