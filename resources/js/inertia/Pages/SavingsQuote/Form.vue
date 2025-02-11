<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
  nationalities: Object,
  genders: Object,
  maritalStatuses: Object,
  purposes: Object,
  currencies: Object,
  investmentFrequencies: Object,
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob || '',
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
            :disabled="editMode"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>
        <x-field label="Mobile Number" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired, isMobileNo]"
            :disabled="editMode"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="Date of Birth" required>
          <DatePicker
            v-model="quoteForm.dob"
            input-classes="w-full"
            :rules="[isRequired]"
            :max-date="new Date()"
          />
        </x-field>
        <x-field label="Nationality" required>
          <x-select
            v-model="quoteForm.nationality_id"
            :options="nationalities"
            class="w-full"
            :error="quoteForm.errors.nationality_id"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Gender" required>
          <x-select
            v-model="quoteForm.gender"
            :rules="[isRequired]"
            :options="genders"
            class="w-full"
          />
        </x-field>
        <x-field label="Marital Status" required>
          <x-select
            v-model="quoteForm.marital_status_id"
            :options="maritalStatuses"
            class="w-full"
            :error="quoteForm.errors.marital_status_id"
            :rules="[isRequired]"
          />
        </x-field>
        <!-- Savings Details -->
        <x-field label="Tenure of Savings" required>
          <x-input
            v-model="quoteForm.tenure_of_savings"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.tenure_of_savings"
          />
        </x-field>

        <div class="px-2 w-full">
          <div class="mb-2">
            <x-field
              label="Have you used any nicotine-containing products within the past 12 months?"
              required
            >
              <div class="flex gap-12 mt-2">
                <x-form-group
                  v-model="quoteForm.has_nicotine"
                  :rules="[isRequired]"
                >
                  <x-radio value="1" label="Yes" />
                  <x-radio value="0" label="No" />
                </x-form-group>
              </div>
            </x-field>
          </div>
        </div>

        <x-field label="Purpose of Savings" required>
          <x-select
            v-model="quoteForm.purpose_of_savings"
            :options="purposes"
            class="w-full"
            :rules="[isRequired]"
            :error="quoteForm.errors.purpose_of_savings"
          />
        </x-field>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-field label="Investment Amount (Currency)" required>
            <x-select
              v-model="quoteForm.currency_id"
              :options="currencies"
              :rules="[isRequired]"
              class="w-full"
              :error="quoteForm.errors.currency_id"
            />
          </x-field>
          <x-field label="Amount" required>
            <x-input
              v-model="quoteForm.amount"
              type="number"
              :rules="[isRequired]"
              :disabled="editMode"
              class="w-full"
              :error="quoteForm.errors.amount"
            />
          </x-field>
        </div>

        <div class="px-2 w-full">
          <div class="mb-2">
            <x-field label="Investment Frequency" required>
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
