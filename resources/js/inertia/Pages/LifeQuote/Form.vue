<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
  nationalities: Object,
  currency: Object,
  purposeOfInsurance: Object,
  maritalStatus: Object,
  typeOfInsurance: Object,
  numberOfYears: Object,
  flash: Object,
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob || '',
  sum_insured_value: props.quote?.sum_insured_value || '',
  nationality_id: props.quote?.nationality_id || '',
  sum_insured_currency_id: props.quote?.sum_insured_currency_id || '',
  marital_status_id: props.quote?.marital_status_id || '',
  purpose_of_insurance_id: props.quote?.purpose_of_insurance_id || '',
  tenure_of_insurance_id: props.quote?.tenure_of_insurance_id || '',
  number_of_years_id: props.quote?.number_of_years_id || '',
  is_smoker: props.quote?.is_smoker || 0,
  gender: props.quote?.gender || '',
  others_info: props.quote?.others_info || '',
  height: props.quote?.height || '',
  weight: props.quote?.weight || '',
  bmi: props.quote?.bmi || '',
  age: props.quote?.age || '',
});

const editMode = computed(() => {
  return props.quote ? true : false;
});
const { isRequired, isEmail, isMobileNo } = useRules();

function onSubmit(isValid) {
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('life-quotes-update', props.quote.uuid)
      : route('life-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}
const getBMI = () => {
  if(!quoteForm.height || !quoteForm.weight) {
    quoteForm.bmi = '';
    return;
  }

  quoteForm.bmi = calculateBMI(quoteForm.height, quoteForm.weight);
}
const dobChanged = () => {
  if(!quoteForm.dob) {
    quoteForm.age = '';
    return;
  }
  const date = quoteForm.dob.split('T')[0];
  quoteForm.age = calculateAge(date);
}
watch(
  () => quoteForm.dob,
  (newValue, oldValue) => {
    if (newValue) {
      dobChanged(newValue);
    }
  },
  { deep: true }
);
</script>

<template>
  <div>
    <Head title="Life Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Life Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('life-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Life Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">{{
        quoteForm?.errors?.error
      }}</x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
         <x-field label="Type of Insurance" required>
          <x-select
            v-model="quoteForm.tenure_of_insurance_id"
            :options="
              typeOfInsurance.map(item => ({
                value: item.id,
                label: item.text,
                disabled: item.code === 'SAVINGS'
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.tenure_of_insurance_id"
          />
        </x-field>
        <x-field label="Purpose of Insurance" required>
          <x-select
            v-model="quoteForm.purpose_of_insurance_id"
            :options="
              purposeOfInsurance.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.purpose_of_insurance_id"
          />
        </x-field>
        <x-field label="First Name" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.first_name"
            maxLength="20"
          />
        </x-field>
        <x-field label="Last Name" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
            maxLength="50"
          />
        </x-field>
        <x-field label="Email" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired]"
            :disabled="editMode"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>
        <x-field label="Phone Number" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :disabled="editMode"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="Date of Birth" required>
          <DatePicker v-model="quoteForm.dob" :rules="[isRequired]" input-classes="w-full" />
        </x-field>
        <x-field label="Age">
          <x-input
            v-model="quoteForm.age"
            type="text"
            class="w-full"
            :disabled="true"
            :error="quoteForm.errors.age"
          />
        </x-field>
        <x-field label="Nationality" required>
          <x-select
            v-model="quoteForm.nationality_id"
            :options="
              nationalities.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.nationality_id"
          />
        </x-field>
         <x-field label="Marital Status" required>
          <x-select
            v-model="quoteForm.marital_status_id"
            :options="
              maritalStatus.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.marital_status_id"
          />
        </x-field>
        <x-field label="Gender" required>
          <x-select
            v-model="quoteForm.gender"
            :options="[
              { value: 'Male', label: 'Male' },
              { value: 'Female', label: 'Female' },
            ]"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.gender"
          />
        </x-field>
        <div class="w-full">
          <div class="grid sm:grid-cols-3 gap-4">
            <x-field label="Height" required>
              <div class="relative w-full">
                <x-input
                  v-model="quoteForm.height"
                  @change="getBMI"
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[isRequired]"
                  class="w-full" 
                  :error="quoteForm.errors.height"
                  maxLength="20"
                />
                <span class="absolute inset-y-0 right-3 flex items-center text-gray-500">Cms</span>
              </div>
            </x-field>
            <x-field label="Weight" required>
              <div class="relative w-full">
                <x-input
                  v-model="quoteForm.weight"
                  @change="getBMI"
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[isRequired]"
                  class="w-full" 
                  :error="quoteForm.errors.weight"
                  maxLength="20"
                />
                <span class="absolute inset-y-0 right-3 flex items-center text-gray-500">Kgs</span>
              </div>
            </x-field>
            <x-field label="BMI">
              <div class="relative w-full">
                <x-input
                  v-model="quoteForm.bmi"
                  type="text"
                  class="w-full" 
                  :error="quoteForm.errors.bmi"
                  :disabled="true"
                />
              </div>
            </x-field>
          </div>
        </div>
        <x-field label="Tenure of Cover" required>
          <x-select
            v-model="quoteForm.number_of_years_id"
            :options="
              numberOfYears.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.number_of_years_id"
          />
        </x-field>
        <div class="w-full">
          <div class="grid sm:grid-cols-12 gap-4">
            <div class="sm:col-span-4">
            <x-field label="Currency" required>
              <x-select
                v-model="quoteForm.sum_insured_currency_id"
                :options="
                  currency.map(item => ({
                    value: item.id,
                    label: item.text,
                  }))
                "
                :rules="[isRequired]"
                class="w-full"
                :error="quoteForm.errors.sum_insured_currency_id"
              />
            </x-field>
            </div>
            <div class="sm:col-span-8">
            <x-field label="Sum Assured" required>
              <x-input
                v-model="quoteForm.sum_insured_value"
                type="number"
                class="w-full"
                :rules="[isRequired]"
                :error="quoteForm.errors.sum_insured_value"
              />
            </x-field>
            </div>
          </div>
        </div>

        <x-field label="Have you smoked tobacco/nicotine in the last 12 months?" required>
          <x-select
            v-model="quoteForm.is_smoker"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.is_smoker"
          />
        </x-field>
        <x-field label="Additional Notes">
          <x-textarea
            v-model="quoteForm.others_info"
            type="text"
            class="w-full"
            :error="quoteForm.errors.others_info"
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
          {{ editMode ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
