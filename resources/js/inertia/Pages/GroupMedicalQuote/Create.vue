<script setup>
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

const { isRequired, emptyOrDecimal, isNumber, isEmail } = useRules();

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    // '/medical/amt'
    quoteForm.post(route('amt.store'), {
      onFinish: () => {
        isEmptyField.value = true;
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Create Group Medical" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Create Group Medical Lead</h2>
      <div class="space-x-4">
        <Link
          v-if="props.quote.uuid"
          :href="route('amt.show', props.quote.uuid)"
        >
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link :href="route('amt.index')">
          <!-- href="/medical/amt" -->
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
          />
        </x-field>

        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>

        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :error="quoteForm.errors.email"
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
            type="text"
            :rules="[isRequired, isNumber]"
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
            type="text"
            class="w-full"
            :rules="[emptyOrDecimal]"
            :error="quoteForm.errors.premium"
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
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
