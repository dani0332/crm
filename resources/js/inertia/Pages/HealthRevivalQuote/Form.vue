<script setup>
const props = defineProps({
  formOptions: Object,
  quote: {
    type: Object,
    default: {},
  },
});

const notification = useToast();

const { isRequired, isEmail, isMobileNo } = useRules();
const isEmptyField = ref(false);

const convertDate = date => useConvertDate(date);

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const coverForOptions = computed(() => {
  return props.formOptions.coverFor.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
const nationalitiesOptions = computed(() => {
  return props.formOptions.nationalities.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
const emirateOptions = computed(() => {
  return props.formOptions.emirateOfVisa.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
const healthLeadTypeOptions = computed(() => {
  return props.formOptions.healthLeadType.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
const memberCategoriesOptions = computed(() => {
  return props.formOptions.memberCategories.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
const salaryBandOptions = computed(() => {
  return props.formOptions.salaryBand.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
const genderOptions = computed(() => {
  return Object.keys(props.formOptions.gender).map(item => ({
    value: item,
    label: props.formOptions.gender[item],
  }));
});
const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: convertDate(props.quote.dob) || '',
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

function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) return;

  quoteForm.clearErrors();

  const method = 'put';
  const url = route('health-revival-quotes-update', props.quote.uuid);
  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
    onSuccess: () => {
      notification.success({
        title: 'Quote Updated Successfully',
        position: 'top',
      });
    },
  };

  quoteForm.submit(method, url, options);
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Health Revival' : 'Create Health Revival'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Health Revival
      </h2>
      <div>
        <Link :href="route('health-revival-quotes-list')">
          <x-button size="sm" color="#1d83bc" tag="div">
            Health Revival List
          </x-button>
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
            :options="coverForOptions"
            class="w-full"
          />
        </x-field>

        <x-field label="NATIONALITY" required>
          <ComboBox
            v-model="quoteForm.nationality_id"
            :single="true"
            :options="nationalitiesOptions"
            :hasError="isEmptyField"
          />
        </x-field>

        <x-field label="EMIRATE OF YOUR VISA" required>
          <x-select
            v-model="quoteForm.emirate_of_your_visa_id"
            :rules="[isRequired]"
            :options="emirateOptions"
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
            :options="healthLeadTypeOptions"
            class="w-full"
          />
        </x-field>

        <x-field v-if="!isEdit" label="CURRENTLY INSURED WITH">
          <x-select
            v-model="quoteForm.currently_insured_with_id"
            :options="[]"
            class="w-full"
          />
        </x-field>

        <x-field v-if="!isEdit" label="MARITAL STATUS">
          <x-select
            v-model="quoteForm.marital_status_id"
            :options="[]"
            class="w-full"
          />
        </x-field>

        <x-field label="MEMBER CATEGORY" required>
          <x-select
            v-model="quoteForm.member_category_id"
            :rules="[isRequired]"
            :options="memberCategoriesOptions"
            class="w-full"
          />
        </x-field>

        <x-field label="SALARY BAND">
          <x-select
            v-model="quoteForm.salary_band_id"
            :options="salaryBandOptions"
            class="w-full"
          />
        </x-field>

        <x-field label="GENDER" required>
          <x-select
            v-model="quoteForm.gender"
            :rules="[isRequired]"
            :options="genderOptions"
            class="w-full"
          />
        </x-field>

        <x-field v-if="!isEdit" label="POLICY START DATE">
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
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
