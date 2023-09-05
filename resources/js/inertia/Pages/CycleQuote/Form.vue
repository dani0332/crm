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
const editMode = computed(() => {
  return props.quote ? true : false;
});
function onSubmit(isValid) {
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('cycle-quotes-update', props.quote.uuid)
      : route('cycle-quotes-store');
    let title = editMode.value ? '' : 'Quote saved successfully';

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },

      onSuccess: () => {
        if (title != '')
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
    <Head title="Cycle Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Cycle Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('cycle-quotes-list')">
          <!-- href="/personal-quotes/cycle" -->
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
        <x-field label="First Name" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.first_name"
          />
        </x-field>
        <x-field label="Last Name" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
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
        <x-field label="Phone Number" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="editMode"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="Cycle Make" required>
          <x-input
            v-model="quoteForm.cycle_make"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.cycle_make"
          />
        </x-field>
        <x-field label="Cycle Model" required>
          <x-input
            v-model="quoteForm.cycle_model"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.cycle_model"
          />
        </x-field>
        <x-field label="Year of manufacture" required>
          <x-select
            v-model="quoteForm.year_of_manufacture_id"
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
        </x-field>
        <x-field label="Purchased value(AED)" required>
          <x-input
            v-model="quoteForm.asset_value"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.asset_value"
          />
        </x-field>
        <x-field label="Accessories" required>
          <x-input
            v-model="quoteForm.accessories"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.accessories"
          />
        </x-field>

        <div class="px-2 w-full">
          <div class="mb-2">
            <x-field
              label="Have you had any accidents or injuries whilst cycling in the past
              3 years in the UAE"
              required
            ></x-field>
            <!-- <label
              for="entry"
              class="block text-gray-700 text-sm font-semibold mb-3"
            >
              Have you had any accidents or injuries whilst cycling in the past
              3 years in the UAE*
            </label> -->
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
            <x-field
              label="Confirm that your bicycle is currently in good condition and there
              is no existing damage"
              required
            ></x-field>
            <!-- <label
              for="entry"
              class="block text-gray-700 text-sm font-semibold mb-3"
            >
              Confirm that your bicycle is currently in good condition and there
              is no existing damage*
            </label> -->
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
          {{ editMode ? 'Update' : 'Save' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
