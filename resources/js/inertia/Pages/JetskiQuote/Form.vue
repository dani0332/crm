<script setup>
import { ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import ComboBox from '@/inertia/Components/ComboBox.vue';
import { useNotifications } from '@indielayer/ui';

const notification = useNotifications('toast');

const props = defineProps({
  genderOptions: Object,
  nationalities: Object,
  uaeLicenses: Object,
  insuranceProviders: Object,
  yearOfManufacture: Object,
    jetski_uses: Object,
    jetski_materials: Object,
  dropdownSource: Object,
  model: String,
  quote: { type: Object, default: null },
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
    jetski_make: props.quote?.jetski_quote?.jetski_make,
    jetski_model: props.quote?.jetski_quote?.jetski_model,
    year_of_manufacture_id: props.quote?.jetski_quote?.year_of_manufacture_id,
    max_speed: props.quote?.jetski_quote?.max_speed,
    seat_capacity: props.quote?.jetski_quote?.seat_capacity,
    engine_power: props.quote?.jetski_quote?.engine_power,
    jetski_material_id: props.quote?.jetski_quote?.jetski_material_id,
    jetski_use_id: props.quote?.jetski_quote?.jetski_use_id,
    claim_history: props.quote?.jetski_quote?.claim_history,
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
};

const isEmptyField = ref(false);
function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (isValid) {
    let method = 'post';
    let url = `/personal-quotes/jetski/`;

    if (props.quote) {
      method = 'put';
      url = url + props.quote.uuid;
    }

    quoteForm.clearErrors();
    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
      onSuccess: () => {
        notification.success({
          title: 'Quote is saved successfully',
          position: 'top',
        });

        setTimeout(function () {
          router.get(`/personal-quotes/jetski`);
        }, 500);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Jetski Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
          Jetski Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link href="/personal-quotes/jetski">
          <x-button size="sm" color="#ff5e00"> Jetski Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">

      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
        {{ quoteForm?.errors?.error }}
      </x-alert>

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
              v-model="quoteForm.jetski_make"
              type="text"
              label="JetSki Make*"
              :rules="[rules.isRequired]"
              class="w-full"
              :error="quoteForm.errors.jetski_make"
          />

          <x-input
              v-model="quoteForm.jetski_model"
              type="text"
              label="JetSki Model*"
              :rules="[rules.isRequired]"
              class="w-full"
              :error="quoteForm.errors.jetski_model"
          />

          <x-input
              v-model="quoteForm.max_speed"
              type="number"
              label="Max Speed*"
              :rules="[rules.isRequired]"
              class="w-full"
              :error="quoteForm.errors.max_speed"
          />

          <x-input
              v-model="quoteForm.seat_capacity"
              type="text"
              label="Seating Capacity*"
              :rules="[rules.isRequired]"
              class="w-full"
              :error="quoteForm.errors.seat_capacity"
          />

          <x-input
              v-model="quoteForm.engine_power"
              type="text"
              label="Engine Power (hp)*"
              :rules="[rules.isRequired]"
              class="w-full"
              :error="quoteForm.errors.engine_power"
          />

        <x-select
          v-model="quoteForm.year_of_manufacture_id"
          label="Year of manufacture*"
          :rules="[rules.isRequired]"
          :options="
            yearOfManufacture.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.year_of_manufacture_id"
        />

          <x-select
              v-model="quoteForm.jetski_material_id"
              label="Material of Construction*"
              :rules="[rules.isRequired]"
              :options="
            jetski_materials.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
              class="w-full"
              :error="quoteForm.errors.jetski_material_id"
          />

          <x-select
              v-model="quoteForm.jetski_use_id"
              label="Jet SKI Use*"
              :rules="[rules.isRequired]"
              :options="
            jetski_uses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
              class="w-full"
              :error="quoteForm.errors.jetski_use_id"
          />

          <x-input
              v-model="quoteForm.claim_history"
              type="text"
              label="Claims Experience for past 5 years*"
              :rules="[rules.isRequired]"
              class="w-full"
              :error="quoteForm.errors.claim_history"
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
