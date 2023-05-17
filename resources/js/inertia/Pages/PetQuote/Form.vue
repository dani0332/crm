<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
  pet_ages: Object,
  pet_types: Object,
  accomodation_types: Object,
  possession_types: Object,
  flash: Object,
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  premium: props.quote?.pet_quote?.premium || '',
  policy_number: props.quote?.pet_quote?.policy_number || '',
  pet_type_id: props.quote?.pet_quote?.pet_type_id || '',
  breed_of_pet1: props.quote?.pet_quote?.breed_of_pet1 || '',
  pet_age_id: props.quote?.pet_quote?.pet_age_id || '',
  is_neutered: props.quote ? props.quote?.pet_quote?.is_neutered : null,
  is_microchipped: props.quote ? props.quote?.pet_quote?.is_microchipped : null,
  microchip_no: props.quote?.pet_quote?.microchip_no || '',
  is_mixed_breed: props.quote ? props.quote?.pet_quote?.is_mixed_breed : null,
  has_injury: props.quote ? props.quote?.pet_quote?.has_injury : null,
  gender: props.quote?.pet_quote?.gender || '',
  ilivein_accommodation_type_id:
    props.quote?.pet_quote?.ilivein_accommodation_type_id || '',
  iam_possesion_type_id: props.quote?.pet_quote?.iam_possesion_type_id || '',
});

const { isRequired, isEmail } = useRules();

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/personal-quotes/pet/`;
    let title = 'Quote saved successfully';
    let redirectUrl = '/personal-quotes/pet';
    if (props.quote) {
      method = 'put';
      url = url + props.quote.uuid;
      title = 'Quote updated successfully';
      redirectUrl = `/personal-quotes/pet/${props.quote?.uuid}`;
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

        setTimeout(function () {
          const uuid = props.flash?.uid;
          if (uuid) {
            redirectUrl = `/personal-quotes/pet/${uuid}`;
          }
          router.get(redirectUrl);
        }, 500);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Pet Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        <span v-if="quote">Edit Pet</span>
        <span v-else>Create Pet</span>
      </h2>
      <div>
        <Link href="/personal-quotes/pet">
          <x-button size="sm" color="#ff5e00"> Pet Quotes List </x-button>
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
          label="FIRST NAME *"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME *"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL  *"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER *"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <x-input
          v-model="quoteForm.premium"
          type="number"
          label="PREMIUM"
          class="w-full"
          :error="quoteForm.errors.premium"
        />

        <x-input
          v-model="quoteForm.policy_number"
          type="text"
          label="POLICY NUMBER"
          class="w-full"
          :error="quoteForm.errors.policy_number"
        />
        <x-select
          v-model="quoteForm.pet_type_id"
          type="text"
          maxlength="3"
          label="TYPE OF PET *"
          :rules="[isRequired]"
          :options="
            pet_types.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.pet_type_id"
        />
        <x-input
          v-model="quoteForm.breed_of_pet1"
          type="tel"
          label="BREED OF PET *"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.breed_of_pet1"
        />

        <x-select
          v-model="quoteForm.pet_age_id"
          type="number"
          label="AGE OF PET *"
          :rules="[isRequired]"
          :options="
            pet_ages.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.pet_age_id"
        />
        <x-select
          v-model="quoteForm.is_neutered"
          label="IS NEUTERED"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_neutered"
        />
        <x-select
          v-model="quoteForm.is_microchipped"
          label="IS MICROCHIPPED"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_microchipped"
        />
        <x-input
          v-model="quoteForm.microchip_no"
          type="text"
          label="MICROCHIP NO"
          class="w-full"
          :error="quoteForm.errors.microchip_no"
        />
        <x-select
          v-model="quoteForm.is_mixed_breed"
          label="IS MIXED BREED"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_mixed_breed"
        />
        <x-select
          v-model="quoteForm.has_injury"
          label="HAS INJURY"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.has_injury"
        />
        <x-select
          v-model="quoteForm.gender"
          label="GENDER *"
          :rules="[isRequired]"
          :options="[
            { value: 'Male', label: 'Male' },
            { value: 'Female', label: 'Female' },
          ]"
          class="w-full"
          :error="quoteForm.errors.gender"
        />
        <x-select
          v-model="quoteForm.ilivein_accommodation_type_id"
          label="ACCOMMODATION TYPE *"
          :rules="[isRequired]"
          :options="
            accomodation_types.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.ilivein_accommodation_type_id"
        />
        <x-select
          v-model="quoteForm.iam_possesion_type_id"
          label="POSSESION TYPE  *"
          :rules="[isRequired]"
          :options="
            possession_types.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.iam_possesion_type_id"
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
