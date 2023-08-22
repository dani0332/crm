<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
  nationalities: Object,
  currency: Object,
  purposeOfInsurance: Object,
  maritalStatus: Object,
  childern: Object,
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
  children_id: props.quote?.children_id || '',
  premium: props.quote?.premium || '',
  tenure_of_insurance_id: props.quote?.tenure_of_insurance_id || '',
  number_of_years_id: props.quote?.number_of_years_id || '',
  is_smoker: props.quote?.is_smoker || 0,
  gender: props.quote?.gender || '',
  others_info: props.quote?.others_info || '',
});

const editMode = computed(() => {
  return props.quote ? true : false;
});
const { isRequired, isEmail } = useRules();

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/quotes/life/`;
    let title = 'Quote saved successfully';
    if (props.quote) {
      method = 'put';
      url = url + props.quote.uuid;
      title = 'Quote updated successfully';
    }

     quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Life Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Life Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link href="/quotes/life">
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
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          label="First Name*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="Last Name*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="Email*"
          :rules="[isRequired]"
          :disabled="editMode"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Mobile Number*"
          :rules="[isRequired]"
          class="w-full"
          :disabled="editMode"
          :error="quoteForm.errors.mobile_no"
        />
        <DatePicker
          v-model="quoteForm.dob"
          label="Date of Birth"
          input-classes="w-full"
        />
        <x-select
          v-model="quoteForm.nationality_id"
          label="Nationality"
          :options="
            nationalities.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.nationality_id"
        />
        <x-input
          v-model="quoteForm.sum_insured_value"
          type="number"
          label="Sum Insured Value"
          class="w-full"
          :error="quoteForm.errors.sum_insured_value"
        />
        <x-input
          v-model="quoteForm.premium"
          type="number"
          label="PRICE"
          class="w-full"
          :error="quoteForm.errors.premium"
        />
        <x-select
          v-model="quoteForm.sum_insured_currency_id"
          label="Currency"
          :options="
            currency.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.sum_insured_currency_id"
        />
        <x-select
          v-model="quoteForm.purpose_of_insurance_id"
          label="Purpose of Insurance"
          :options="
            purposeOfInsurance.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.purpose_of_insurance_id"
        />
        <x-select
          v-model="quoteForm.marital_status_id"
          label="Marital Status"
          :options="
            maritalStatus.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.marital_status_id"
        />
        <x-select
          v-model="quoteForm.children_id"
          label="Children"
          :options="
            childern.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.children_id"
        />
        <x-select
          v-model="quoteForm.tenure_of_insurance_id"
          label="Type of Insurance"
          :options="
            typeOfInsurance.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.tenure_of_insurance_id"
        />
        <x-select
          v-model="quoteForm.number_of_years_id"
          label="Tenure of Cover"
          :options="
            numberOfYears.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.number_of_years_id"
        />
        <x-select
          v-model="quoteForm.gender"
          label="Gender"
          :options="[
            { value: 'Male', label: 'Male' },
            { value: 'Female', label: 'Female' },
          ]"
          class="w-full"
          :error="quoteForm.errors.gender"
        />
        <x-select
          v-model="quoteForm.is_smoker"
          label="Smoker"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_smoker"
        />
        <x-input
          v-model="quoteForm.others_info"
          type="text"
          label="Others Info"
          class="w-full"
          :error="quoteForm.errors.others_info"
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
          Save
        </x-button>
      </div>
    </x-form>
  </div>
</template>
