<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  company_name: props.quote?.company_name || null,
  company_address: props.quote?.company_address || null,
  boat_details: props.quote?.yacht_quote?.boat_details || '',
  engine_details: props.quote?.yacht_quote?.engine_details || '',
  claim_experience: props.quote?.yacht_quote?.claim_experience || '',
  asset_value: props.quote?.asset_value || null,
  use: props.quote?.yacht_quote?.use || '',
  operator_experience: props.quote?.yacht_quote?.operator_experience || '',
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
      ? route('yacht-quotes-update', props.quote.uuid)
      : route('yacht-quotes-store');

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
    <Head title="Yacht Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Yacht Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('yacht-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Yacht Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">{{
        quoteForm?.errors?.error
      }}</x-alert>

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
            maxLength="50"
          />
        </x-field>
        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="editMode"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>
        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired, isMobileNo]"
            :disabled="editMode"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="COMPANY NAME">
          <x-input
            v-model="quoteForm.company_name"
            type="text"
            class="w-full"
            :error="quoteForm?.errors?.company_name"
          />
        </x-field>
        <x-field label="COMPANY ADDRESS">
          <x-input
            v-model="quoteForm.company_address"
            type="text"
            class="w-full"
            :error="quoteForm?.errors?.company_address"
          />
        </x-field>
        <x-field label="BOAT DETAILS" required>
          <x-input
            v-model="quoteForm.boat_details"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.boat_details"
          />
        </x-field>
        <x-field label="ENGINE DETAILS" required>
          <x-input
            v-model="quoteForm.engine_details"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.engine_details"
          />
        </x-field>
        <x-field label="CLAIM EXPERIENCE" required>
          <x-input
            v-model="quoteForm.claim_experience"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.claim_experience"
          />
        </x-field>
        <x-field label="SUM INSURED" required>
          <x-input
            v-model="quoteForm.asset_value"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.asset_value"
          />
        </x-field>
        <x-field label="USE" required>
          <x-input
            v-model="quoteForm.use"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.use"
          />
        </x-field>
        <x-field label="OPERATOR EXPERIENCE" required>
          <x-input
            v-model="quoteForm.operator_experience"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.operator_experience"
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
