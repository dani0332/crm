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
  pet_type_id: props.quote?.pet_quote?.pet_type_id || '',
  breed_of_pet1: props.quote?.pet_quote?.breed_of_pet1 || '',
  pet_age_id: props.quote?.pet_quote?.pet_age_id || '',
  is_neutered: props.quote ? props.quote?.pet_quote?.is_neutered : null,
  is_microchipped: props.quote ? props.quote?.pet_quote?.is_microchipped : null,
  microchip_no: props.quote?.pet_quote?.microchip_no || '',
  is_mixed_breed: props.quote ? props.quote?.pet_quote?.is_mixed_breed : null,
  has_injury: props.quote ? props.quote?.pet_quote?.has_injury : null,
  gender: props.quote?.pet_quote?.gender || '',
});

const { isRequired, isEmail, isMobileNo } = useRules();
const editMode = computed(() => {
  return props.quote ? true : false;
});
function onSubmit(isValid) {
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('pet-quotes-update', props.quote.uuid)
      : route('pet-quotes-store');

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
    <Head :title="editMode ? 'Update Pet Quote' : 'Pet Quote Create'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        <span>{{ editMode ? 'Update Pet' : 'Create Pet' }} </span>
      </h2>
      <div>
        <Link :href="route('pet-quotes-list')">
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
            :disabled="editMode"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="TYPE OF PET" required>
          <x-select
            v-model="quoteForm.pet_type_id"
            type="text"
            maxlength="3"
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
        </x-field>
        <x-field label="BREED OF PET" required>
          <x-input
            v-model="quoteForm.breed_of_pet1"
            type="tel"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.breed_of_pet1"
          />
        </x-field>
        <x-field label="AGE OF PET" required>
          <x-select
            v-model="quoteForm.pet_age_id"
            type="number"
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
        </x-field>
        <x-field label="IS NEUTERED">
          <x-select
            v-model="quoteForm.is_neutered"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.is_neutered"
          />
        </x-field>
        <x-field label="IS MICROCHIPPED">
          <x-select
            v-model="quoteForm.is_microchipped"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.is_microchipped"
          />
        </x-field>
        <x-field label="MICROCHIP NO">
          <x-input
            v-model="quoteForm.microchip_no"
            type="text"
            class="w-full"
            :error="quoteForm.errors.microchip_no"
          />
        </x-field>
        <x-field label="IS MIXED BREED">
          <x-select
            v-model="quoteForm.is_mixed_breed"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.is_mixed_breed"
          />
        </x-field>
        <x-field label="HAS INJURY">
          <x-select
            v-model="quoteForm.has_injury"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.has_injury"
          />
        </x-field>
        <x-field label="GENDER" required>
          <x-select
            v-model="quoteForm.gender"
            :rules="[isRequired]"
            :options="[
              { value: 'Male', label: 'Male' },
              { value: 'Female', label: 'Female' },
            ]"
            class="w-full"
            :error="quoteForm.errors.gender"
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
