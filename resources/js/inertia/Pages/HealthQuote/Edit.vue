<script setup>
const props = defineProps({
  quote: Object,
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
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  dob: props.quote.dob ? props.quote.dob.split('-').reverse().join('-') : null,
  premium: props.quote.premium,
  policy_number: props.quote.policy_number,
  preference: props.quote.preference,
  details: props.quote.details,
  marital_status_id: props.quote.marital_status_id,
  cover_for_id: props.quote.cover_for_id,
  nationality_id: props.quote.nationality_id,
  lead_type_id: props.quote.lead_type_id,
  emirate_of_your_visa_id: props.quote.emirate_of_your_visa_id,
  salary_band_id: props.quote.salary_band_id,
  member_category_id: props.quote.member_category_id,
  gender: props.quote.gender,
  currently_insured_with_id: props.quote.currently_insured_with_id,
  policy_start_date: props.quote.policy_start_date,
  is_ebp_renewal: props.quote.is_ebp_renewal,
  is_ecommerce: props.quote.is_ecommerce,
  has_dental: props.quote.has_dental,
  has_worldwide_cover: props.quote.has_worldwide_cover,
  has_home: props.quote.has_home,
});

const { isRequired } = useRules();

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }
  if (isValid) {
    quoteForm.put(route('health.update', props.quote.uuid));
  }
}
</script>

<template>
  <div>
    <Head title="Edit Health" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Health</h2>
      <div class="space-x-2">
        <Link :href="route('health.show', props.quote.uuid)">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link :href="route('health.index')" preserve-scroll>
          <x-button size="sm" color="#1d83bc" tag="div"> Health List </x-button>
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
          :rules="[isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME"
          :rules="[isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL"
          :disabled="true"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          :disabled="true"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.dob"
          type="date"
          label="DATE OF BIRTH"
          :rules="[isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.premium"
          type="text"
          label="PREMIUM"
          class="w-full"
        />

        <!-- <x-input
          v-model="quoteForm.policy_number"
          type="text"
          label="POLICY NUMBER"
          class="w-full"
        /> -->

        <x-select
          v-model="quoteForm.cover_for_id"
          label="WHO WOULD YOU LIKE COVER FOR?"
          :rules="[isRequired]"
          :options="
            dropdownSource.cover_for_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <!-- <x-input
          v-model="quoteForm.preference"
          type="text"
          label="PREFERENCE"
          class="w-full"
        /> -->

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

        <!-- <x-select
          v-model="quoteForm.currently_insured_with_id"
          label="CURRENTLY INSURED WITH"
          :options="
            dropdownSource.currently_insured_with_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        /> -->

        <!-- <x-select
          v-model="quoteForm.marital_status_id"
          label="MARITAL STATUS"
          :options="
            dropdownSource.marital_status_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        /> -->

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
          :rules="[isRequired]"
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

        <!-- <x-input
          v-model="quoteForm.policy_start_date"
          type="text"
          label="POLICY START DATE"
          class="w-full"
        /> -->

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
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
