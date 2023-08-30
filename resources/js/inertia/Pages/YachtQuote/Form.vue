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
  boat_details: props.quote?.yacht_quote?.boat_details || '',
  engine_details: props.quote?.yacht_quote?.engine_details || '',
  claim_experience: props.quote?.yacht_quote?.claim_experience || '',
  asset_value: props.quote?.asset_value || null,
  use: props.quote?.yacht_quote?.use || '',
  operator_experience: props.quote?.yacht_quote?.operator_experience || '',
});

const { isRequired, isEmail } = useRules();
const editMode = computed(() => {
    return !!props.quote;
});
const isEmptyField = ref(false);
function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = 'post';
    let url = `/personal-quotes/yacht/`;
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
    <Head title="Yacht Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Yacht Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link href="/personal-quotes/yacht">
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
          :disabled="editMode"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Phone Number*"
          :disabled="editMode"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <x-input
          v-model="quoteForm.boat_details"
          type="text"
          label="Boat Details*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.boat_details"
        />

        <x-input
          v-model="quoteForm.engine_details"
          type="text"
          label="Engine Details*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.engine_details"
        />

        <x-input
          v-model="quoteForm.claim_experience"
          type="text"
          label="Claim Experience*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.claim_experience"
        />

        <x-input
          v-model="quoteForm.asset_value"
          type="number"
          label="Sum Insured*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.asset_value"
        />

        <x-input
          v-model="quoteForm.use"
          type="text"
          label="Use*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.use"
        />

        <x-input
          v-model="quoteForm.operator_experience"
          type="text"
          label="Operator Experience*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.operator_experience"
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
