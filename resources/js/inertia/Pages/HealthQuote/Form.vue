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

const { isRequired, isEmail, isMobileNo } = useRules();
const isEmptyField = ref(false);

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const initialEditCategoryId = computed(() => {
  if (route().current().includes('edit')) {
    return props.quote.member_category_id;
  }
});

let previouslySelectedCategoryId = ref(initialEditCategoryId.value);

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const quoteForm = useForm({
  modelType: '"Health"',
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
  cover_for_id: props.quote?.cover_for_id || null,
  nationality_id: props.quote?.nationality_id || null,
  lead_type_id: props.quote?.lead_type_id || null,
  emirate_of_your_visa_id: props.quote?.emirate_of_your_visa_id || null,
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
  plan_type_id: props.quote?.health_plan_type_id || null,
});

const memberCategorySalaryMapping = {
  'Investor or Partner': 2,
  'Golden visa': 2,
  'Self-employed or Freelancer': 2,
  'Domestic worker': 1,
  'Dependent spouse': 2,
  'Dependent child': 2,
  'Dependent parent': 2,
  'Dependent sibling or Other relatives': 2,
  'Employee with salary AED 4000 and below': 1,
  'Employee with salary above AED 4000': 2,
};

const salaryBrandMapping = {
  1: 'AED 4000 and below',
  2: 'More than AED 4000',
};

const selectedSalaryBand = computed(() => {
  return route().current().includes('edit');
});

watch(
  () => quoteForm.member_category_id,
  (newValue, oldValue) => {
    if (newValue) {
      if (
        !isEdit.value ||
        (isEdit.value &&
          (newValue !== initialEditCategoryId.value ||
            (newValue === initialEditCategoryId.value &&
              newValue !== previouslySelectedCategoryId.value)))
      ) {
        //fetch category text
        const selectedCategory = props.dropdownSource.member_category_id.find(
          option => option.id === newValue,
        );

        // fetch salary band id based on category text
        const salaryBandId = memberCategorySalaryMapping[selectedCategory.text];

        // if quote status is Transaction Approved do not auto-popualte salary band automatically
        if (props.quote.quote_status_id != 15) {
          quoteForm.salary_band_id = salaryBandId;
        }
        previouslySelectedCategoryId.value = newValue;
      }
    }
  },
  { immediate: true },
);

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
    ? route('health.update', props.quote.uuid)
    : route('health.store');

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
    <Head :title="isEdit ? 'Edit Health' : 'Create Health'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Health
      </h2>
      <div>
        <Link :href="route('health.index')">
          <x-button size="sm" color="#1d83bc" tag="div"> Health List </x-button>
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
            maxLength="20"
            :error="quoteForm.errors.first_name"
          />
        </x-field>

        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            :rules="[isRequired]"
            class="w-full"
            maxLength="50"
            :error="quoteForm.errors.last_name"
          />
        </x-field>

        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :disabled="isEdit"
            :error="quoteForm.errors.email"
          />
        </x-field>

        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :disabled="isEdit"
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

        <x-field v-if="!isEdit" label="CURRENTLY INSURED WITH">
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

        <x-field v-if="!isEdit" label="MARITAL STATUS">
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

        <x-field label="MEMBER CATEGORY" required>
          <x-select
            v-model="quoteForm.member_category_id"
            :rules="[isRequired]"
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

        <x-field label="GENDER" required>
          <x-select
            v-model="quoteForm.gender"
            :rules="[isRequired]"
            :options="genderSelect"
            class="w-full"
          />
        </x-field>

        <x-field v-if="!isEdit" label="POLICY START DATE">
          <x-input v-model="quoteForm.policy_start_date" class="w-full" />
        </x-field>
        <x-field label="TYPE OF PLAN">
          <x-select
            v-model="quoteForm.plan_type_id"
            :options="
              dropdownSource.plan_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            placeholder="Select plan type"
            :rules="[isRequired]"
          />
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
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
