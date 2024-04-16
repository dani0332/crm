<script setup>
const props = defineProps({
  businessInsuranceType: Object,
  quote: Object,
  gmTypes: Object,
  selectedGmType: Object,
});

const notification = useToast();
const isEdit = computed(() => (props.quote.uuid ? true : false));
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

const maxValidation = maxValue => {
  return value => {
    const isValid = value <= maxValue;
    return isValid || `Value must be less than or equal to ${maxValue}.`;
  };
};

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
  group_medical_type_id: props.selectedGmType ?? '',
  brief_details: props.quote.brief_details,
});

const { isRequired, emptyOrDecimal, isNumber, isEmail, isMobileNo } =
  useRules();

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (!isValid) return;
  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('amt.update', props.quote.uuid)
    : route('amt.store');

  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
    onStart: () => {
      quoteForm.clearErrors();
    },
  };
  quoteForm.submit(method, url, options);
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Group Medical' : 'Create Group Medical'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Update' : 'Create' }} Group Medical Lead
      </h2>
      <div class="space-x-4">
        <Link v-if="isEdit" :href="route('amt.show', props.quote.uuid)">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link :href="route('amt.index')">
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
            maxLength="20"
          />
        </x-field>

        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
            maxLength="20"
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

        <x-field label="COMPANY NAME" required>
          <x-input
            v-model="quoteForm.company_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.company_name"
          />
        </x-field>

        <x-field label="NUMBER OF EMPLOYEES" required>
          <x-input
            v-model="quoteForm.number_of_employees"
            type="number"
            :rules="[isRequired, isNumber, maxValidation(2147483645)]"
            class="w-full"
            :error="quoteForm.errors.number_of_employees"
          />
        </x-field>

        <x-field label="Business Insurance Type" required>
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
        </x-field>

        <x-field label="BRIEF DETAILS" required>
          <x-textarea
            v-model="quoteForm.brief_details"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.brief_details"
          />
        </x-field>

        <x-field label="PRICE">
          <x-input
            v-model="quoteForm.premium"
            type="number"
            class="w-full"
            :rules="[emptyOrDecimal]"
            :error="quoteForm.errors.premium"
          />
        </x-field>

        <x-field label="Group Medical Type" required v-if="isEdit">
          <x-select
            v-model="quoteForm.group_medical_type_id"
            :options="
              gmTypes.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.group_medical_type_id"
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
