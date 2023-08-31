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
  dob: props.quote?.dob || null,
  nationality_id: props.quote?.nationality_id || null,
  uae_license_held_for_id:
    props.quote?.bike_quote?.uae_license_held_for_id || null,
  bike_company_to_insure:
    props.quote?.bike_quote?.bike_company_to_insure || null,
  asset_value: props.quote?.asset_value || null,
  currently_insured_with_id: props.quote?.currently_insured_with_id || null,
  year_of_manufacture: props.quote?.bike_quote?.year_of_manufacture || null,
});

const { isRequired, isEmail } = useRules();

const formFieldReq = reactive({
  nationality: false,
  dob: false,
});

const editMode = computed(() => {
    return props.quote ? true : false;
});

const isEmptyField = ref(false);
function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    formFieldReq.nationality_id = true;
  } else {
    formFieldReq.nationality_id = false;
  }
  if (quoteForm.dob == null || quoteForm.dob == '') {
    formFieldReq.dob = true;
  } else {
    formFieldReq.dob = false;
  }

  if (isValid) {
    let method = 'post';
    let url = `/personal-quotes/bike/`;
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
    <Head title="Bike Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Bike Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link href="/personal-quotes/bike">
          <x-button size="sm" color="#ff5e00"> Bike Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="$page.props?.errors">
        {{ $page.props?.errors }}
      </x-alert>

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
          :rules="[isRequired, isEmail]"
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

        <DatePicker
          v-model="quoteForm.dob"
          name="created_at_start"
          label="Date of Birth*"
          :rules="[isRequired]"
          :hasError="quoteForm.errors.dob || formFieldReq.dob"
        />

        <ComboBox
          v-model="quoteForm.nationality_id"
          label="Nationality*"
          :single="true"
          :options="
            nationalities.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :hasError="isEmptyField || formFieldReq.nationality_id"
          :error="quoteForm.errors.nationality_id"
        />

        <x-select
          v-model="quoteForm.uae_license_held_for_id"
          label="UAE licence held for*"
          :rules="[isRequired]"
          :options="
            uaeLicenses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.uae_license_held_for_id"
        />

        <x-input
          v-model="quoteForm.bike_company_to_insure"
          type="text"
          label="Bike(s) to insure*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.bike_company_to_insure"
        />

        <x-input
          v-model="quoteForm.asset_value"
          type="number"
          label="Bike value(AED)*"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.asset_value"
        />

        <x-select
          v-model="quoteForm.year_of_manufacture"
          label="Year of manufacture*"
          :rules="[isRequired]"
          :options="
            yearOfManufacture.map(item => ({
              value: item.text,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.year_of_manufacture"
        />

        <x-select
          v-model="quoteForm.currently_insured_with_id"
          label="Currently with:*"
          :rules="[isRequired]"
          :options="
            insuranceProviders.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.currently_insured_with_id"
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
