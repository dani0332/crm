<script setup>
const page = usePage();
const notification = useToast();
const { isRequired, isEmail } = useRules();
const comp_for_car = 'car';
const dateFormat = date => (date ? useDateFormat(date, 'Y-m-d').value : '-');

const props = defineProps({
  dynamic_route: {
    type: String,
    default: '',
    required: true,
  },
});

const nationalities = computed(() => {
  return page.props.form_options.nationalities.map(nationality => ({
    value: nationality.id,
    label: nationality.text,
  }));
});

const vehicleTypeOptions = computed(() => {
  return page.props.form_options.vehicle_types.map(vehicle_type => ({
    value: vehicle_type.id,
    label: vehicle_type.text,
  }));
});

const typeOfInsuranceOptions = computed(() => {
  return page.props.form_options.types_of_insurance.map(type_of_insurance => ({
    value: type_of_insurance.id,
    label: type_of_insurance.text,
  }));
});

const currentlyInsuredWith = computed(() => {
  return page.props.form_options.currently_insured_with_options.map(
    currently_insured_with => ({
      value: currently_insured_with.id,
      label: currently_insured_with.text,
    }),
  );
});

const uaeLicenseHeldFor = computed(() => {
  return page.props.form_options.uae_license_help_for.map(
    uae_license_help_for => ({
      value: uae_license_help_for.id,
      label: uae_license_help_for.text,
    }),
  );
});

const emiratesOfRegistration = computed(() => {
  return page.props.form_options.emirate_of_visa.map(emirate_of_visa => ({
    value: emirate_of_visa.id,
    label: emirate_of_visa.text,
  }));
});

const carMakes = computed(() => {
  return page.props.form_options.car_make.map(car_make => ({
    value: car_make.id,
    label: car_make.text,
  }));
});

const yearOfManufacture = computed(() => {
  return page.props.form_options.year_of_manufacture.map(
    year_of_manufacture => ({
      value: year_of_manufacture.text,
      label: year_of_manufacture.text,
    }),
  );
});

const claimHistory = computed(() => {
  return page.props.form_options.claim_history.map(claim_history => ({
    value: claim_history.id,
    label: claim_history.text,
  }));
});

const conditionallyTitle = computed(() => {
  return props.dynamic_route == comp_for_car ? 'Car' : 'Car Revival';
});

const quoteForm = useForm({
  renewal_batch: page.props.quote?.renewal_batch || '',
  first_name: page.props.quote?.first_name || '',
  last_name: page.props.quote?.last_name || '',
  dob: page.props.quote?.dob || null,
  email: page.props.quote?.email || '',
  mobile_no: page.props.quote?.mobile_no || '',
  nationality_id: page.props.quote?.nationality_id || null,
  uae_license_held_for_id: page.props.quote?.uae_license_held_for_id || null,
  back_home_license_held_for_id:
    page.props.quote?.back_home_license_held_for_id || null,
  car_make_id: page.props.quote?.car_make_id || null,
  car_model_id: page.props.quote?.car_model_id || null,
  cylinder: page.props.quote?.cylinder || '',
  car_model_detail_id: page.props.quote?.car_model_detail_id || null,
  year_of_manufacture: page.props.quote?.year_of_manufacture || '',
  car_value: page.props.quote?.car_value || '',
  vehicle_type_id: page.props.quote?.vehicle_type_id || null,
  seat_capacity: page.props.quote?.seat_capacity || '',
  emirate_of_registration_id:
    page.props.quote?.emirate_of_registration_id || null,
  car_type_insurance_id: page.props.quote?.car_type_insurance_id || null,
  currently_insured_with: page.props.quote?.currently_insured_with || null,
  claim_history_id: page.props.quote?.claim_history_id || null,
  previous_quote_policy_number:
    page.props.quote?.previous_quote_policy_number || '',
  previous_policy_expiry_date:
    page.props.quote?.previous_policy_expiry_date || '',
  additional_notes: page.props.quote?.additional_notes || '',
});

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/quotes/${props.dynamic_route}/`;
    let title = 'Quote saved successfully';
    let redirectUrl = `/quotes/${props.dynamic_route}`;
    if (page.props.quote) {
      method = 'put';
      url = url + page.props.quote.uuid;
      title = 'Quote updated successfully';
      redirectUrl = `/quotes/${props.dynamic_route}/${page.props.quote?.uuid}`;
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
          router.get(redirectUrl);
        }, 500);
      },
    });
  }
}

const carModel = reactive({
  loading: false,
  options: [],
});

const carTrim = reactive({
  loading: false,
  options: [],
});
const fetchCarModel = async (notFirst = true) => {
  carModel.loading = true;

  if (notFirst) {
    quoteForm.car_model_id = null;
  }

  await axios
    .get(`/car-model-by-id?id=${quoteForm.car_make_id}`)
    .then(response => {
      carModel.options = response.data?.map(item => {
        return {
          value: item.id,
          label: item.text,
        };
      });
      carModel.loading = false;
    });
};
const fetchCarTrim = async (notFirst = true) => {
  carTrim.loading = true;

  if (notFirst) {
    quoteForm.car_model_detail_id = null;
  }

  await axios
    .get(`/getCarModelDetails?car_model_id=${quoteForm.car_model_id}`)
    .then(response => {
      carTrim.options = response.data?.map(item => {
        return {
          value: item.id,
          label: item.text,
        };
      });
      carTrim.loading = false;
    });
};

onMounted(() => {
  fetchCarModel(false);
  fetchCarTrim(false);
});
</script>

<template>
  <div>
    <Head :title="conditionallyTitle" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ conditionallyTitle }} Quote
        <span v-if="page.props.quote">{{ page.props.quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="`/quotes/${props.dynamic_route}`">
          <x-button size="sm" color="#ff5e00">
            {{ conditionallyTitle }} Quotes List
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
        {{ quoteForm?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="RENEWAL BATCH #">
          <x-input
            v-model="quoteForm.renewal_batch"
            type="text"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.renewal_batch"
          />
        </x-field>

        <x-field label="First Name" required>
          <x-input
            v-model="quoteForm.first_name"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.first_name"
          />
        </x-field>

        <x-field label="Last Name" required>
          <x-input
            v-model="quoteForm.last_name"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>

        <x-field label="Date of Birth" required>
          <DatePicker
            v-model="quoteForm.dob"
            name="created_at_start"
            :rules="[isRequired]"
            :hasError="quoteForm.errors.dob"
          />
        </x-field>

        <x-field label="Email" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="true"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>

        <x-field label="Phone Number" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="true"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>

        <x-field label="Nationality" required>
          <ComboBox
            v-model="quoteForm.nationality_id"
            :single="true"
            :rules="[isRequired]"
            :options="nationalities"
            :error="quoteForm.errors.nationality_id"
          />
        </x-field>

        <x-field label="UAE licence held for" required>
          <x-select
            v-model="quoteForm.uae_license_held_for_id"
            :rules="[isRequired]"
            :options="uaeLicenseHeldFor"
            class="w-full"
            :error="quoteForm.errors.uae_license_held_for_id"
          />
        </x-field>

        <x-field label="HOME COUNTRY DRIVING LICENSE HELD FOR">
          <x-select
            v-model="quoteForm.back_home_license_held_for_id"
            :options="uaeLicenseHeldFor"
            class="w-full"
            :error="quoteForm.errors.back_home_license_held_for_id"
          />
        </x-field>

        <x-field label="CAR MAKE" required>
          <x-select
            v-model="quoteForm.car_make_id"
            :rules="[isRequired]"
            :options="carMakes"
            class="w-full"
            :error="quoteForm.errors.car_make_id"
            @update:modelValue="fetchCarModel"
          />
        </x-field>

        <x-field label="CAR MODEL" required>
          <x-select
            v-model="quoteForm.car_model_id"
            :rules="[isRequired]"
            :options="carModel.options"
            :loading="carModel.loading"
            class="w-full"
            :error="quoteForm.errors.car_model_id"
          />
        </x-field>

        <x-field label="CYLINDER" required>
          <x-input
            v-model="quoteForm.cylinder"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.cylinder"
          />
        </x-field>

        <x-field label="TRIM">
          <x-select
            v-model="quoteForm.car_model_detail_id"
            :options="carTrim.options"
            :loading="carModel.loading"
            class="w-full"
            :error="quoteForm.errors.car_model_detail_id"
          />
        </x-field>

        <x-field label="CAR MODEL YEAR" required>
          <x-select
            v-model="quoteForm.year_of_manufacture"
            :rules="[isRequired]"
            :options="yearOfManufacture"
            class="w-full"
            :error="quoteForm.errors.year_of_manufacture"
          />
        </x-field>

        <x-field label="CAR VALUE">
          <x-input
            v-model="quoteForm.car_value"
            class="w-full"
            :error="quoteForm.errors.car_value"
          />
        </x-field>

        <x-field label="CAR VALUE (AT ENQUIRY)" required>
          <x-input
            v-model="quoteForm.car_value"
            :disabled="true"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.car_value"
          />
        </x-field>

        <x-field label="VEHICLE TYPE" required>
          <x-select
            v-model="quoteForm.vehicle_type_id"
            :rules="[isRequired]"
            :options="vehicleTypeOptions"
            class="w-full"
            :error="quoteForm.errors.vehicle_type_id"
          />
        </x-field>

        <x-field label="SEAT CAPACITY" required>
          <x-input
            v-model="quoteForm.seat_capacity"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.seat_capacity"
          />
        </x-field>

        <x-field label="EMIRATE OF REGISTRATION" required>
          <x-select
            v-model="quoteForm.emirate_of_registration_id"
            :rules="[isRequired]"
            :options="emiratesOfRegistration"
            class="w-full"
            :error="quoteForm.errors.emirate_of_registration_id"
          />
        </x-field>

        <x-field label="TYPE OF CAR INSURANCE" required>
          <x-select
            v-model="quoteForm.car_type_insurance_id"
            :rules="[isRequired]"
            :options="typeOfInsuranceOptions"
            class="w-full"
            :error="quoteForm.errors.car_type_insurance_id"
          />
        </x-field>

        <x-field label="CURRENTLY INSURED WITH" required>
          <x-select
            v-model="quoteForm.currently_insured_with"
            :rules="[isRequired]"
            :options="currentlyInsuredWith"
            class="w-full"
            :error="quoteForm.errors.currently_insured_with"
          />
        </x-field>

        <x-field label="CLAIM HISTORY" required>
          <x-select
            v-model="quoteForm.claim_history_id"
            :rules="[isRequired]"
            :options="claimHistory"
            class="w-full"
            :error="quoteForm.errors.claim_history_id"
          />
        </x-field>

        <x-field
          label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?"
        >
          <x-select
            v-model="quoteForm.uae_license_held_for_id"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.uae_license_held_for_id"
          />
        </x-field>

        <x-field label="PREVIOUS POLICY NUMBER">
          <x-input
            v-model="quoteForm.previous_quote_policy_number"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.previous_quote_policy_number"
          />
        </x-field>

        <x-field label="PREVIOUS POLICY EXPIRY DATE">
          <x-input
            v-model="quoteForm.previous_policy_expiry_date"
            :disabled="true"
            class="w-full"
            :error="quoteForm.errors.previous_policy_expiry_date"
          />
        </x-field>

        <x-field label="ADDITIONAL NOTES">
          <x-textarea
            v-model="quoteForm.additional_notes"
            type="textarea"
            class="w-full"
            :error="quoteForm.errors.additional_notes"
          />
        </x-field>

        <x-divider class="my-4" />
        <div class="flex justify-end gap-3 mb-4">
          <x-button
            size="md"
            color="emerald"
            type="submit"
            :loading="quoteForm.processing"
          >
            Update
          </x-button>
        </div>
      </div>
    </x-form>
  </div>
</template>
