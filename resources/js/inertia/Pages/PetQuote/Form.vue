<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import ComboBox from '@/inertia/Components/ComboBox.vue';
import { useNotifications } from '@indielayer/ui';

const notification = useNotifications('toast');

const props = defineProps({
  quote: { type: Object, default: null },
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  premium: props.quote?.pet_quote?.premium || '',
  policy_number: props.quote?.pet_quote?.policy_number || '',
  type_of_pet1: props.quote?.pet_quote?.type_of_pet1 || '',
  breed_of_pet1: props.quote?.pet_quote?.breed_of_pet1 || '',
  age_of_pet1: props.quote?.pet_quote?.age_of_pet1 || '',
  is_neutered: props.quote?.pet_quote?.is_neutered ? '1' : '0' || '',
  is_microchipped: props.quote?.pet_quote?.is_microchipped ? '1' : '0' || '',
  microchip_no: props.quote?.pet_quote?.microchip_no || '',
  is_mixed_breed: props.quote?.pet_quote?.is_mixed_breed ? '1' : '0' || '',
  has_injury: props.quote?.pet_quote?.has_injury ? '1' : '0' || '',
  gender: props.quote?.pet_quote?.gender || '',
  ilivein_accommodation_type_id:
    props.quote?.pet_quote?.ilivein_accommodation_type_id || '',
  iam_possesion_type_id: props.quote?.pet_quote?.iam_possesion_type_id || '',
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
};

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/personal-quotes/pet/`;
    if (props.quote) {
      method = 'put';
      url = url + props.quote.uuid;
    }

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
      onSuccess: () => {
        notification.success({
          title: 'Quote saved successfully',
          position: 'top',
        });

        setTimeout(function () {
          router.get(`/personal-quotes/pet`);
        }, 500);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Cycle Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Pet Quote <span v-if="quote">{{ quote?.uuid }}</span>
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
          label="First Name*"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="Last Name*"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="Email*"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Phone Number*"
          :rules="[rules.isRequired]"
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
        <x-input
          v-model="quoteForm.type_of_pet1"
          type="text"
          label="TYPE OF PET*"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.type_of_pet1"
        />
        <x-input
          v-model="quoteForm.breed_of_pet1"
          type="tel"
          label="BREED OF PET*"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.breed_of_pet1"
        />

        <x-input
          v-model="quoteForm.age_of_pet1"
          type="number"
          label="AGE OF PET*"
          :rules="[rules.isRequired]"
          class="w-full"
          :error="quoteForm.errors.age_of_pet1"
        />
        <x-select
          v-model="quoteForm.is_neutered"
          label="IS NEUTERED"
          :options="[
            { value: '1', label: 'Yes' },
            { value: '0', label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_neutered"
        />
        <x-select
          v-model="quoteForm.is_microchipped"
          label="IS MICROCHIPPED"
          :options="[
            { value: '1', label: 'Yes' },
            { value: '0', label: 'No' },
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
            { value: '1', label: 'Yes' },
            { value: '0', label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_mixed_breed"
        />
        <x-select
          v-model="quoteForm.has_injury"
          label="HAS INJURY"
          :options="[
            { value: '1', label: 'Yes' },
            { value: '0', label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.has_injury"
        />
        <x-select
          v-model="quoteForm.gender"
          label="GENDER"
          :options="[
            { value: 'Male', label: 'Male' },
            { value: 'Female', label: 'Female' },
          ]"
          class="w-full"
          :error="quoteForm.errors.gender"
        />
        <x-select
          v-model="quoteForm.ilivein_accommodation_type_id"
          label="ACCOMMODATION TYPE*"
          :rules="[rules.isRequired]"
          :options="[
            { value: '', label: 'Please confirm Accommodation Type' },
            { value: 1, label: 'An apartment' },
            { value: 2, label: 'A villa' },
            { value: 3, label: 'Shared accommodation' },
          ]"
          class="w-full"
          :error="quoteForm.errors.ilivein_accommodation_type_id"
        />
        <x-select
          v-model="quoteForm.iam_possesion_type_id"
          label="POSSESION TYPE *"
          :rules="[rules.isRequired]"
          :options="[
            { value: '', label: 'Please confirm Possesion Type' },
            { value: 1, label: 'A landlord' },
            { value: 2, label: 'A tenant' },
          ]"
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
