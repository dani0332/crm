<script setup>
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
  is_ebp_renewal: null,
  is_ecommerce: null,
  has_dental: null,
  has_worldwide_cover: null,
  has_home: null,
});

const { isRequired, isEmail } = useRules();

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
        <x-field label="FIRST NAME" required>
          <x-input
            v-model="quoteForm.first_name"
            :rules="[isRequired]"
            class="w-full"
          />
        </x-field>

        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            :rules="[isRequired]"
            class="w-full"
          />
        </x-field>

        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired, isEmail]"
            class="w-full"
          />
        </x-field>

        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>

        <x-field label="DATE OF BIRTH" required>
          <DatePicker
            v-model="quoteForm.dob"
            :rules="[isRequired]"
            class="w-full"
          />
        </x-field>

        <x-field label="PREMIUM">
          <x-input v-model="quoteForm.premium" class="w-full" />
        </x-field>

        <x-field label="POLICY NUMBER">
          <x-input v-model="quoteForm.policy_number" class="w-full" />
        </x-field>

        <x-field label="WHO WOULD YOU LIKE COVER FOR?" required>
          <x-select
            v-model="quoteForm.cover_for_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.cover_for_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="NATIONALITY" required>
          <ComboBox
            v-model="quoteForm.nationality_id"
            :single="true"
            :options="
              dropdownSource.nationality_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :hasError="isEmptyField"
          />
        </x-field>

        <x-field label="EMIRATE OF YOUR VISA" required>
          <x-select
            v-model="quoteForm.emirate_of_your_visa_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.emirate_of_your_visa_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="PREFERENCE">
          <x-input v-model="quoteForm.preference" class="w-full" />
        </x-field>

        <x-field label="DETAILS">
          <x-input v-model="quoteForm.details" class="w-full" />
        </x-field>

        <x-field label="LEAD TYPE">
          <x-select
            v-model="quoteForm.lead_type_id"
            :options="
              dropdownSource.lead_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CURRENTLY INSURED WITH">
          <x-select
            v-model="quoteForm.currently_insured_with_id"
            :options="
              dropdownSource.currently_insured_with_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="MARITAL STATUS">
          <x-select
            v-model="quoteForm.marital_status_id"
            :options="
              dropdownSource.marital_status_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="MEMBER CATEGORY">
          <x-select
            v-model="quoteForm.member_category_id"
            :options="
              dropdownSource.member_category_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="SALARY BAND">
          <x-select
            v-model="quoteForm.salary_band_id"
            :options="
              dropdownSource.salary_band_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="GENDER">
          <x-select
            v-model="quoteForm.gender"
            :options="genderSelect"
            class="w-full"
          />
        </x-field>

        <x-field label="POLICY START DATE">
          <x-input v-model="quoteForm.policy_start_date" class="w-full" />
        </x-field>

        <x-field>
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
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
