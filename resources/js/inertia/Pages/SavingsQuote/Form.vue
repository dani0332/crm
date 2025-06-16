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
  tenure_id: props.quote?.savings_quote?.tenure_id || '',
  purpose_id: props.quote?.savings_quote?.purpose_id || '',
  currency_id: props.quote?.savings_quote?.currency_id || '',
  investment_amount: props.quote?.savings_quote?.investment_amount || '',
  investment_frequency:
    props.quote?.savings_quote?.investment_criteria_id || '',
  additional_notes: props.quote?.savings_quote?.additional_notes || '',
});

console.log('quoteForm', quoteForm.data());
console.log('props.quote', props.quote);

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
          label="First Name"
          required
          :rules="[isRequired]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="Last Name"
          required
          maxLength="50"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          label="Email"
          required
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Phone Number"
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
          label="Date of Birth"
          format="dd-MM-yyyy"
          required
        />

        <x-select
          v-model="quoteForm.nationality_id"
          :options="nationalities"
          class="w-full"
          :error="quoteForm.errors.nationality_id"
          :rules="[isRequired]"
          label="Nationality"
          filterable
          placeholder="Search by Nationality"
          required
        />

        <x-select
          v-model="quoteForm.gender"
          :options="props.genders"
          class="w-full"
          label="Gender"
          placeholder="Select Gender"
          required
          :rules="[isRequired]"
          :error="quoteForm.errors.gender"
        />

        <x-select
          v-model="quoteForm.marital_status_id"
          :options="maritalStatuses"
          class="w-full"
          label="Marital Status"
          placeholder="Select Marital Status"
          required
          :error="quoteForm.errors.marital_status_id"
          :rules="[isRequired]"
        />

        <!-- Savings Details -->
        <x-select
          v-model="quoteForm.tenure_id"
          :options="tenures"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.tenure_id"
          label="Tenure of savings"
          placeholder="Select Tenure of Savings"
          required
        />

        <x-select
          v-model="quoteForm.purpose_id"
          :options="purposes"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.purpose_id"
          label="Purpose of savings"
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
            label="Currency"
            placeholder="Select Investment Currency"
            required
          />
          <x-input
            v-model="quoteForm.investment_amount"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.investment_amount"
            label="Investment amount"
            required
          />
        </div>

        <div class="px-2 w-full">
          <div class="mb-2">
            <x-field label="Investment frequency" required>
              <div class="flex gap-12 mt-2">
                <x-form-group
                  v-model="quoteForm.investment_frequency"
                  :rules="[isRequired]"
                >
                  <x-radio
                    v-for="item in investmentFrequencies"
                    :value="item.value"
                    :label="item.label"
                  />
                </x-form-group>
              </div>
            </x-field>
          </div>
        </div>
        <x-field label="Additional Notes" required>
          <x-input
            v-model="quoteForm.additional_notes"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.additional_notes"
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
