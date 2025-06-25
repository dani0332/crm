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
    description: item.description,
    code: item.code,
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

const { isRequired, isEmail, isMobileNo, isValidName } = useRules();

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
          :rules="[isRequired, isValidName]"
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
          :rules="[isRequired, isValidName]"
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
          :rules="[isRequired, ...(editMode ? [] : [isMobileNo])]"
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
          v-model="quoteForm.purpose_id"
          :options="purposes"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.purpose_id"
          label="Purpose of savings"
          placeholder="Select Purpose of Savings"
          required
        />

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

        <div class="px-2 w-full">
          <div class="mb-2">
            <x-field label="Investment frequency" required>
              <div class="flex gap-12 mt-2">
                <x-form-group
                  v-model="quoteForm.investment_frequency"
                  :rules="[isRequired]"
                >
                  <div
                    v-for="item in investmentFrequencies"
                    :key="item.value"
                    class="relative group mb-6"
                  >
                    <div class="radio-wrapper">
                      <x-radio :value="item.value" :label="item.label" />
                    </div>
                    <div
                      class="hidden group-hover:block absolute left-0 top-full mt-1 w-64 p-3 bg-white border border-blue-400 rounded-md shadow-lg z-10 text-sm tooltip-box"
                    >
                      {{ item.description }}
                    </div>
                  </div>
                </x-form-group>
              </div>
            </x-field>
          </div>
        </div>

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

<style scoped>
/* Ensure tooltip appears on hover */
.group:hover .group-hover\:block {
  display: block;
}

/* Custom tooltip styling to match design */
.tooltip-box {
  border: 1px solid #1d83bc;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  color: #33333399;
  line-height: 1.5;
  padding: 12px;
  font-size: 14px;
  max-width: 280px;
  background-color: #f8fafc;
}

.radio-wrapper {
  cursor: pointer;
}
</style>
