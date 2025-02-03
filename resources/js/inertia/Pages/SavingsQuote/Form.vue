<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
  nationalities: Object,
  genders: Object,
  maritalStatuses: Object,
  purposes: Object,
  currencies: Object,
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  company_name: props.quote?.company_name || null,
  company_address: props.quote?.company_address || null,
  boat_details: props.quote?.savings_quote?.boat_details || '',
  engine_details: props.quote?.savings_quote?.engine_details || '',
  claim_experience: props.quote?.savings_quote?.claim_experience || '',
  asset_value: props.quote?.asset_value || null,
  use: props.quote?.savings_quote?.use || '',
  operator_experience: props.quote?.savings_quote?.operator_experience || '',
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
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">{{
        quoteForm?.errors?.error
      }}</x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
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
            :rules="[isRequired]"
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
          />
        </x-field>
        <x-field label="Nationality" required>
          <x-select
            v-model="quoteForm.nationality_id"
            :options="nationalities"
            class="w-full"
            :error="quoteForm.errors.nationality_id"
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
          />
        </x-field>
        <x-field label="Tenure of savings" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired, isMobileNo]"
            :disabled="editMode"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
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
                  v-model="quoteForm.has_accident"
                  :rules="[isRequired]"
                >
                  <x-radio value="1" label="Yes" />
                  <x-radio value="0" label="No" />
                </x-form-group>
              </div>
            </x-field>
          </div>
        </div>
        <x-field label="Purpose os savings" required>
          <x-select
            v-model="quoteForm.marital_status_id"
            :options="purposes"
            class="w-full"
            :error="quoteForm.errors.marital_status_id"
          />
        </x-field>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-field label="Investment Amount (Currency)" required>
            <x-select
              v-model="quoteForm.marital_status_id"
              :options="currencies"
              class="w-full"
              :error="quoteForm.errors.marital_status_id"
            />
          </x-field>
          <x-field label="Amount" required>
            <x-input
              v-model="quoteForm.mobile_no"
              type="tel"
              :rules="[isRequired, isMobileNo]"
              :disabled="editMode"
              class="w-full"
              :error="quoteForm.errors.mobile_no"
            />
          </x-field>
        </div>

        <div class="px-2 w-full">
          <div class="mb-2">
            <x-field label="Investment Frequency" required>
              <div class="flex gap-12 mt-2">
                <x-form-group
                  v-model="quoteForm.has_accident"
                  :rules="[isRequired]"
                >
                  <x-radio value="regular" label="Regular" />
                  <x-radio value="lumpsum" label="Lumpsum" />
                </x-form-group>
              </div>
            </x-field>
          </div>
        </div>
        <x-field label="Additional Notes" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired, isMobileNo]"
            :disabled="editMode"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
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
