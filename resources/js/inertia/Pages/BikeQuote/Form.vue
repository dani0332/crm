<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  genderOptions: Object,
  nationalities: Object,
  uaeLicenses: Object,
  yearOfManufacture: Object,
  dropdownSource: Object,
  model: String,
  quote: { type: Object, default: null },
  bikeQuoteDetail: { type: Object, default: null },
});

const bikeClaimHistoryOptions = computed(() => {
  return props.dropdownSource.claim_history_id
    .filter(item => {
      return item.quote_type_id === null || item.quote_type_id === 6;
    })
    .map(item => ({
      value: item.id,
      label: item.text,
    }));
});

const bikeMakeOptions = computed(() => {
  return props.dropdownSource.bike_make_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const bikeModelOptions = computed(() => {
  return props.dropdownSource.bike_model_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const getBikeModel = (initial = false) => {
  isBikeModelDisabled.value = true;
  axios.get(`/bike-model-by-id?id=${quoteForm.make_id}`).then(({ data }) => {
    props.dropdownSource.bike_model_id = data;
    isBikeModelDisabled.value = false;
    if (quoteForm.model_id !== null && !initial) {
      quoteForm.model_id = null;
    }
  });
};

const currentlyInsuredWithOptions = computed(() => {
  return props.dropdownSource.currently_insured_with_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
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
  bike_value: props.bikeQuoteDetail?.bike_value || null,
  year_of_manufacture: props.quote?.bike_quote?.year_of_manufacture || null,
  back_home_license_held_for_id:
    props.bikeQuoteDetail?.back_home_license_held_for_id || null,
  additional_notes: props.bikeQuoteDetail?.additional_notes || '',
  has_ncd_supporting_documents: null,
  has_ncd_supporting_documents_dropdown: null,
  claim_history_id: props.bikeQuoteDetail?.claim_history_id || null,
  insurance_type_id: props.bikeQuoteDetail?.insurance_type_id || null,
  emirate_of_registration_id:
    props.bikeQuoteDetail?.emirate_of_registration_id || null,
  seat_capacity: props.bikeQuoteDetail?.seat_capacity || '',
  make_id: props.bikeQuoteDetail?.make_id || null,
  model_id: props.bikeQuoteDetail?.model_id || null,
  bike_value_tier: props.bikeQuoteDetail?.bike_value_tier || null,
  currently_insured_with: props.quote?.currently_insured_with_id || null,
  cubic_capacity: props.bikeQuoteDetail?.cubic_capacity || null,
});

const { isRequired, isEmail, isMobileNo } = useRules();

const formFieldReq = reactive({
  nationality: false,
  dob: false,
  make_id: false,
  model_id: false,
  currently_insured_with: false,
});

const editMode = computed(() => {
  return props.quote ? true : false;
});

const isEmptyField = ref(false);
const isError = ref(false);
const isBikeModelDisabled = ref(false);
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
  if (quoteForm.make_id == null || quoteForm.make_id == '') {
    formFieldReq.make_id = true;
  } else {
    formFieldReq.make_id = false;
  }
  if (
    quoteForm.currently_insured_with == null ||
    quoteForm.currently_insured_with == ''
  ) {
    formFieldReq.currently_insured_with = true;
  } else {
    formFieldReq.currently_insured_with = false;
  }
  if (quoteForm.model_id == null || quoteForm.model_id == '') {
    formFieldReq.model_id = true;
  } else {
    formFieldReq.model_id = false;
  }
  quoteForm.has_ncd_supporting_documents =
    quoteForm.has_ncd_supporting_documents_dropdown == 'y' ? 1 : 0;
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('bike-quotes-update', props.quote.uuid)
      : route('bike-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}

onMounted(() => {
  getBikeModel(true);
  quoteForm.has_ncd_supporting_documents_dropdown =
    props.bikeQuoteDetail?.has_ncd_supporting_documents == 1
      ? 'y'
      : 'n' || null;
});

const ccValidation = event => {
  if (quoteForm.cubic_capacity && quoteForm.cubic_capacity.length >= 5) {
    event.preventDefault();
  }
};

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const validateDecimal = event => {
  if (
    event.key === '.' ||
    event.key === 'Backspace' ||
    event.key === 'Delete'
  ) {
    return;
  }
  const regex = /^\d+(\.\d{0,2})?$/;
  if (!regex.test(event.key)) {
    event.preventDefault();
  }
};

const getModelDetails = onchange => {
  axios
    .get(`/getBikeModelDetails?bike_model_id=${quoteForm.model_id}`)
    .then(({ data }) => {
      const item = data.length > 0 ? data[0] : null;
      if (isError.value) {
        isError.value = false;
        return;
      }
      if (item) {
        notification.success({
          title: 'Vehicle Assumptions Data Found',
          position: 'top',
        });
        quoteForm.seat_capacity = item.seat_capacity;
        quoteForm.cubic_capacity = item.cubic_capacity;
      } else {
        notification.error({
          title: 'No Vehicle Assumptions Data Found',
          position: 'top',
        });
      }
    });
};
</script>

<template>
  <div>
    <Head title="Bike Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Bike Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('bike-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Bike Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
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
            :rules="[isRequired, isEmail]"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>
        <x-field label="PHONE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="editMode"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="DATE OF BIRTH" required>
          <DatePicker
            v-model="quoteForm.dob"
            name="created_at_start"
            :rules="[isRequired]"
            :hasError="quoteForm.errors.dob || formFieldReq.dob"
          />
        </x-field>
        <x-field label="NATIONALITY" required>
          <ComboBox
            v-model="quoteForm.nationality_id"
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
        </x-field>
        <x-field label="UAE LICENCE HELD FOR" required>
          <x-select
            v-model="quoteForm.uae_license_held_for_id"
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
        </x-field>

        <x-field label="HOME COUNTRY DRIVING LICENSE HELD FOR">
          <x-select
            v-model="quoteForm.back_home_license_held_for_id"
            :options="
              dropdownSource.back_home_license_held_for_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>

        <x-field label="BIKE MAKE" required>
          <ComboBox
            v-model="quoteForm.make_id"
            :rules="[isRequired]"
            :single="true"
            :options="bikeMakeOptions"
            @update:modelValue="getBikeModel()"
            class="w-full"
            :hasError="isEmptyField || formFieldReq.make_id"
            :error="quoteForm.errors.make_id"
          />
        </x-field>

        <x-field label="BIKE MODEL" required>
          <ComboBox
            v-model="quoteForm.model_id"
            :rules="[isRequired]"
            :single="true"
            :options="bikeModelOptions"
            @update:modelValue="getModelDetails(true)"
            class="w-full"
            :hasError="isEmptyField || formFieldReq.model_id"
            :disabled="isBikeModelDisabled || !bikeModelOptions.length"
            :error="quoteForm.errors.model_id"
          />
        </x-field>

        <x-field label="BIKE MODEL YEAR" required>
          <x-select
            v-model="quoteForm.year_of_manufacture"
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
        </x-field>

        <x-field label="CC" required>
          <x-input
            v-model="quoteForm.cubic_capacity"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.cubic_capacity"
            @keypress="ccValidation"
          />
        </x-field>

        <x-field label="BIKE VALUE" required v-if="isEdit">
          <x-input
            v-model="quoteForm.bike_value"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.bike_value"
            @keydown="validateDecimal"
          />
        </x-field>

        <x-field label="BIKE VALUE(AT ENQUIRY)" required>
          <x-input
            v-model="quoteForm.bike_value_tier"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.bike_value_tier"
            @keydown="validateDecimal"
          />
        </x-field>

        <x-field label="SEAT CAPACITY" required>
          <x-input
            v-model="quoteForm.seat_capacity"
            class="w-full"
            type="number"
            :rules="[isRequired]"
            :error="quoteForm.errors.seat_capacity"
          />
        </x-field>

        <x-field label="EMIRATES OF REGISTRATION" required>
          <x-select
            v-model="quoteForm.emirate_of_registration_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.emirate_of_registration_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :error="quoteForm.errors.emirate_of_registration_id"
          />
        </x-field>

        <x-field label="TYPE OF BIKE INSURANCE" required>
          <x-select
            v-model="quoteForm.insurance_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.car_type_insurance_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :error="quoteForm.errors.insurance_type_id"
          />
        </x-field>

        <x-field label="CURRENTLY INSURED WITH" required>
          <ComboBox
            v-model="quoteForm.currently_insured_with"
            :rules="[isRequired]"
            :single="true"
            :options="currentlyInsuredWithOptions"
            class="w-full"
            :hasError="isEmptyField || formFieldReq.currently_insured_with"
            :error="quoteForm.errors.currently_insured_with"
          />
        </x-field>

        <x-field label="CLAIM HISTORY" required>
          <x-select
            v-model="quoteForm.claim_history_id"
            :rules="[isRequired]"
            :options="bikeClaimHistoryOptions"
            class="w-full"
            :error="quoteForm.errors.claim_history_id"
          />
        </x-field>

        <x-field
          label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?"
        >
          <x-select
            v-model="quoteForm.has_ncd_supporting_documents_dropdown"
            :options="[
              { value: 'y', label: 'Yes' },
              { value: 'n', label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>

        <x-field label="ADDITIONAL NOTES">
          <x-textarea
            v-model="quoteForm.additional_notes"
            type="textarea"
            rows="5"
            class="w-full"
            :adjust-to-text="false"
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
