<script setup>
const notification = useNotifications('toast');
const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';

const props = defineProps({
  quote: { type: Object, default: null },
  genders: { type: Object, required: true },
  lookUpData: { type: Object, required: true },
});

// Format API data for dropdowns and selects
const nationalities = computed(() => {
  return props.lookUpData.nationality.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const maritalStatuses = computed(() => {
  return props.lookUpData.maritalStatus.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const purposes = computed(() => {
  return props.lookUpData.savingsPurpose.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const currencies = computed(() => {
  return props.lookUpData.currencyType.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const tenures = computed(() => {
  return props.lookUpData.savingsTenure.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const investmentFrequencies = computed(() => {
  return props.lookUpData.savingsInvestmentType.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob ? dateFormat(props.quote?.dob) : '',
  nationality_id: props.quote?.nationality_id || '',
  gender: props.quote?.gender || '',
  marital_status_id: props.quote?.savings_quote?.marital_status_id || '',
  tenure_of_savings: props.quote?.savings_quote?.tenure_of_savings || '',
  has_nicotine: (props.quote?.savings_quote?.has_nicotine | 0).toString(),
  purpose_of_savings: props.quote?.savings_quote?.purpose_of_savings || '',
  currency_id: props.quote?.savings_quote?.currency_id || '',
  amount: props.quote?.savings_quote?.amount || '',
  investment_frequency: props.quote?.savings_quote?.investment_frequency || '',
  additional_notes: props.quote?.savings_quote?.additional_notes || '',
});

const { isRequired, isEmail, isMobileNo } = useRules();

const editMode = computed(() => {
  return props.quote && props.quote.uuid ? true : false;
});

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = editMode.value ? 'put' : 'post';
    const url = editMode.value
      ? route('savings-quotes-update', props.quote.uuid)
      : route('savings-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Savings Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Savings Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('savings-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Savings Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
        {{ quoteForm?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Personal Details -->
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          label="FIRST NAME"
          required
          :rules="[isRequired]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME"
          required
          maxLength="50"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL"
          required
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          required
          :disabled="editMode"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <DatePicker
          v-model="quoteForm.dob"
          class="w-full"
          :rules="[isRequired]"
          :max-date="new Date()"
          label="DATE OF BIRTH"
          required
        />

        <x-select
          v-model="quoteForm.nationality_id"
          :options="nationalities"
          class="w-full"
          :error="quoteForm.errors.nationality_id"
          :rules="[isRequired]"
          label="NATIONALITY"
          filterable
          placeholder="Search by Nationality"
          required
        />

        <x-select
          v-model="quoteForm.gender"
          :options="props.genders"
          class="w-full"
          label="GENDER"
          placeholder="Select Gender"
          required
          :rules="[isRequired]"
          :error="quoteForm.errors.gender"
        />

        <x-select
          v-model="quoteForm.marital_status_id"
          :options="maritalStatuses"
          class="w-full"
          label="MARITAL STATUS"
          placeholder="Select Marital Status"
          required
          :error="quoteForm.errors.marital_status_id"
          :rules="[isRequired]"
        />

        <!-- Savings Details -->
        <x-select
          v-model="quoteForm.tenure_of_savings"
          :options="tenures"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.tenure_of_savings"
          label="TENURE OF SAVINGS"
          placeholder="Select Tenure of Savings"
          required
        />

        <div class="w-full">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            HAVE YOU USED ANY NICOTINE-CONTAINING PRODUCTS WITHIN THE PAST 12
            MONTHS? <span class="text-red-500">*</span>
          </label>
          <div class="flex gap-12 mt-2">
            <x-form-group
              v-model="quoteForm.has_nicotine"
              :rules="[isRequired]"
            >
              <x-radio value="1" label="Yes" />
              <x-radio value="0" label="No" />
            </x-form-group>
          </div>
        </div>

        <x-select
          v-model="quoteForm.purpose_of_savings"
          :options="purposes"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.purpose_of_savings"
          label="PURPOSE OF SAVINGS"
          placeholder="Select Purpose of Savings"
          required
        />

        <div class="grid sm:grid-cols-2 gap-4">
          <x-select
            v-model="quoteForm.currency_id"
            :options="currencies"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.currency_id"
            label="INVESTMENT CURRENCY"
            placeholder="Select Investment Currency"
            required
          />
          <x-input
            v-model="quoteForm.amount"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.amount"
            label="AMOUNT"
            required
          />
        </div>

        <div class="w-full">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            INVESTMENT FREQUENCY <span class="text-red-500">*</span>
          </label>
          <div class="flex gap-12 mt-2">
            <x-form-group
              v-model="quoteForm.investment_frequency"
              :rules="[isRequired]"
            >
              <x-radio
                v-for="item in investmentFrequencies"
                :key="item.value"
                :value="item.value"
                :label="item.label"
              />
            </x-form-group>
          </div>
        </div>

        <x-input
          v-model="quoteForm.additional_notes"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.additional_notes"
          label="ADDITIONAL NOTES"
          required
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
          {{ editMode ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
