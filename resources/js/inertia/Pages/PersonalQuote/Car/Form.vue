<script setup>
const props = defineProps({
  dropdownSource: Object,
  model: String,
  genderOptions: Object,
  quote: {
    type: Object,
    default: {},
  },
});

const { isRequired, isEmail } = useRules();
const isEmptyField = ref(false);

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const quoteForm = useForm({
  modelType: '"Car"',
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob
    ? props.quote?.dob.split('-').reverse().join('-')
    : null,
  policy_number: props.quote?.policy_number || null,
  preference: props.quote?.preference || '',
  details: props.quote?.details || '',
  marital_status_id: props.quote?.marital_status_id || null,
  uae_license_held_for_id: props.quote?.uae_license_held_for_id || null,
  nationality_id: props.quote?.nationality_id || null,
  lead_type_id: props.quote?.lead_type_id || null,
  back_home_license_held_for_id: props.quote?.back_home_license_held_for_id || null,
  salary_band_id: props.quote?.salary_band_id || null,
  member_category_id: props.quote?.member_category_id || null,
  gender: props.quote?.gender || null,
  currently_insured_with_id: props.quote?.currently_insured_with_id || null,
  policy_start_date: props.quote?.policy_start_date || null,
  is_ebp_renewal: props.quote?.is_ebp_renewal || null,
  is_ecommerce: props.quote?.is_ecommerce || null,
  has_dental: props.quote?.has_dental || null,
  has_worldwide_cover: props.quote?.has_worldwide_cover || null,
  has_home: props.quote?.has_home || null,
});

function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) return;

  quoteForm.clearErrors();

  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('car.update', props.quote.uuid)
    : route('car.store');

  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
  };

  quoteForm.submit(method, url, options);
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Car' : 'Create Car'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Car
      </h2>
      <div>
        <Link :href="route('car.index')">
          <x-button size="sm" color="#1d83bc" tag="div"> Car List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-alert
          v-if="quoteForm.errors.length > 0"
          color="error"
          class="sm:col-span-2"
        >
          <ul class="list-disc list-inside">
            <li v-for="error in quoteForm.errors" :key="error">
              {{ error }}
            </li>
          </ul>
        </x-alert>
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

        <x-field label="PHONE NUMBER" required>
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

        <x-field label="UAE LICENCE HELD FOR" required>
          <x-select
            v-model="quoteForm.uae_license_held_for_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.uae_license_held_for_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="HOME COUNTRY DRIVING LICENSE HELD FOR" required>
          <x-select
            v-model="quoteForm.back_home_license_held_for_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.back_home_license_held_for_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CAR MAKE" required>
          <x-select
            v-model="quoteForm.car_make_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.car_make_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CAR MODEL" required>
          <x-select
            v-model="quoteForm.car_model_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.car_model_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CYLINDER" required>
          <x-input v-model="quoteForm.preference" class="w-full" type="number" :rules="[isRequired]" />
        </x-field>

        <x-field label="TRIM" required>
          <x-select
            v-model="quoteForm.trim"
            :rules="[isRequired]"
            :options="
              dropdownSource.trim.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CAR MODEL YEAR" required>
          <x-select
            v-model="quoteForm.year_of_manufacture"
            :rules="[isRequired]"
            :options="
              dropdownSource.year_of_manufacture.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CAR VALUE (AT ENQUIRY)" required>
          <x-input v-model="quoteForm.car_value_tier" class="w-full" type="number" :rules="[isRequired]" />
        </x-field>

        <x-field label="VEHICLE TYPE" required>
          <x-select
            v-model="quoteForm.vehicle_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.vehicle_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="SEAT CAPACITY" required>
          <x-input v-model="quoteForm.seat_capacity" class="w-full" type="number" :rules="[isRequired]" />
        </x-field>

        <x-field label="EMIRATE OF REGISTRATION" required>
          <x-select
            v-model="quoteForm.emirate_of_registration_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.emirate_of_registration_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="TYPE OF CAR INSURANCE" required>
          <x-select
            v-model="quoteForm.car_type_insurance_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.car_type_insurance_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CURRENTLY INSURED WITH" required>
          <x-select
            v-model="quoteForm.currently_insured_with"
            :rules="[isRequired]"
            :options="
              dropdownSource.currently_insured_with.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CLAIM HISTORY" required>
          <x-select
            v-model="quoteForm.claim_history_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.claim_history_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?">
          <x-select
            v-model="quoteForm.has_ncd_supporting_documents"
            :options="[{ value: 'Yes', label: 'Yes' }, { value: 'No', label: 'No' }]"
            class="w-full"
          />
        </x-field>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="ADDITIONAL NOTES">
          <x-textarea
              v-model="quoteForm.additional_notes"
              type="textarea"
              rows="5"
              class="w-full"
              :adjust-to-text="false"
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