<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  genderOptions: Object,
  nationalities: Object,
  uaeLicenses: Object,
  insuranceProviders: Object,
  yearOfManufacture: Object,
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
  cycle_make: props.quote?.cycle_quote?.cycle_make || '',
  cycle_model: props.quote?.cycle_quote?.cycle_model || '',
  accessories: props.quote?.cycle_quote?.accessories || '',
  asset_value: props.quote?.asset_value || null,
  year_of_manufacture_id:
    props.quote?.cycle_quote?.year_of_manufacture_id || null,
  has_accident: String(props.quote?.cycle_quote?.has_accident) || null,
  has_good_condition:
    String(props.quote?.cycle_quote?.has_good_condition) || null,
});

const { isRequired, isEmail } = useRules();

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/personal-quotes/cycle/`;
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
          router.get(`/personal-quotes/cycle`);
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
        Cycle Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link href="/personal-quotes/cycle">
          <x-button size="sm" color="#ff5e00"> Cycle Quotes List </x-button>
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
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Phone Number*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <x-input
          v-model="quoteForm.cycle_make"
          type="text"
          label="Cycle Make*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.cycle_make"
        />

        <x-input
          v-model="quoteForm.cycle_model"
          type="text"
          label="Cycle Model*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.cycle_model"
        />

        <x-select
          v-model="quoteForm.year_of_manufacture_id"
          label="Year of manufacture*"
          :rules="[isRequired]"
          :options="
            yearOfManufacture.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.year_of_manufacture_id"
        />

        <x-input
          v-model="quoteForm.asset_value"
          type="number"
          label="Purchased value(AED)*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.asset_value"
        />

        <x-input
          v-model="quoteForm.accessories"
          type="text"
          label="Accessories*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.accessories"
        />

        <div class="px-2 w-full">
          <div class="mb-2">
            <label
              for="entry"
              class="block text-gray-700 text-sm font-semibold mb-3"
            >
              Have you had any accidents or injuries whilst cycling in the past
              3 years in the UAE*
            </label>
            <div class="w-full">
              <div class="grid grid-cols-4 gap-1">
                <x-radio
                  v-model="quoteForm.has_accident"
                  value="1"
                  label="Yes"
                  :rules="[isRequired]"
                />
                <x-radio
                  v-model="quoteForm.has_accident"
                  value="0"
                  label="No"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="px-2 w-full">
          <div class="mb-2">
            <label
              for="entry"
              class="block text-gray-700 text-sm font-semibold mb-3"
            >
              Confirm that your bicycle is currently in good condition and there
              is no existing damage*
            </label>
            <div class="w-full">
              <div class="grid grid-cols-4 gap-1">
                <x-radio
                  v-model="quoteForm.has_good_condition"
                  value="1"
                  label="Yes"
                  :rules="[isRequired]"
                />
                <x-radio
                  v-model="quoteForm.has_good_condition"
                  value="0"
                  label="No"
                />
              </div>
            </div>
          </div>
        </div>
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
