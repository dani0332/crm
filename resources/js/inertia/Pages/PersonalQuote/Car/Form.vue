<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  dropdownSource: Object,
  model: String,
  quote: {
    type: Object,
    default: {},
  },
});

const { isRequired, isEmail, maxValue } = useRules();
const isEmptyField = ref(false);
const isError = ref(false);
const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;
const permissionEnum = page.props.permissionsEnum;

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const carMakeOptions = computed(() => {
  return props.dropdownSource.car_make_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const carModelOptions = computed(() => {
  return props.dropdownSource.car_model_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const quoteForm = useForm({
  modelType: '"Car"',
  model: props.model,
  renewal_batch: props.quote?.renewal_batch || '',
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  company_name: props.quote?.car_company_name || null,
  company_address: props.quote?.car_company_address || null,
  dob: props.quote?.dob ? props.quote?.dob.split('-').reverse().join('-') : '',
  cylinder: props.quote?.cylinder || null,
  uae_license_held_for_id: props.quote?.uae_license_held_for_id || null,
  nationality_id: props.quote?.nationality_id || null,
  back_home_license_held_for_id:
    props.quote?.back_home_license_held_for_id || null,
  gender: props.quote?.gender || null,
  currently_insured_with: props.quote?.currently_insured_with || null,
  is_ecommerce: props.quote?.is_ecommerce || null,
  car_make_id: props.quote?.car_make_id || null,
  vehicle_type_id: props.quote?.vehicle_type_id || null,
  trim: props.quote?.trim || null,
  additional_notes: props.quote?.additional_notes || '',
  car_model_id: props.quote?.car_model_id || null,
  year_of_manufacture: props.quote?.year_of_manufacture || null,
  emirate_of_registration_id: props.quote?.emirate_of_registration_id || null,
  car_type_insurance_id: props.quote?.car_type_insurance_id || null,
  claim_history_id: props.quote?.claim_history_id || null,
  seat_capacity: props.quote?.seat_capacity || '',
  has_ncd_supporting_documents:
    props.quote?.has_ncd_supporting_documents || null,
  car_value_tier: props.quote?.car_value_tier || '',
  car_value: props.quote?.car_value || '',
  addressObj: {
    address_type: page.props.customerAddressData?.type || null,
    villa_apartment_office_no:
      page.props.customerAddressData?.office_number || null,
    floor_no: page.props.customerAddressData?.floor_number || null,
    villa_building_name: page.props.customerAddressData?.building_name || null,
    street_name: page.props.customerAddressData?.street || null,
    area: page.props.customerAddressData?.area || null,
    city: page.props.customerAddressData?.city || null,
    landmark: page.props.customerAddressData?.landmark || null,
  },
  courierQuoteStatus: page.props.courierQuoteStatus || 'Pending',
});

const isDisbaled =
  !hasAnyRole([rolesEnum.CarManager, rolesEnum.Admin, rolesEnum.LeadPool]) ||
  (!can(permissionEnum.RenewalBatchUpdate) && !!quoteForm.renewal_batch);

const trimOptions = ref([]);

const getCarModel = reset => {
  if (reset) {
    quoteForm.cylinder = null;
    quoteForm.seat_capacity = null;
    quoteForm.vehicle_type_id = null;
    quoteForm.trim = null;
  }
  axios.get(`/car-model-by-id?id=${quoteForm.car_make_id}`).then(({ data }) => {
    props.dropdownSource.car_model_id = data;
    if (quoteForm.car_model_id !== null) {
      quoteForm.car_model_id = null;
    }
  });
};

const getModelDetails = onchange => {
  axios
    .get(`/getCarModelDetails?car_model_id=${quoteForm.car_model_id}`)
    .then(({ data }) => {
      const item = data.length > 0 ? data[0] : null;
      let trimDropdown = [];
      data.forEach(item => {
        trimDropdown.push({
          value: item.id,
          label: item.text,
        });
      });
      trimOptions.value = trimDropdown;

      if (isError.value) {
        isError.value = false;
        return;
      }

      if (item) {
        notification.success({
          title: 'Vehicle Assumptions Data Found',
          position: 'top',
        });
        quoteForm.cylinder = item.cylinder;
        quoteForm.seat_capacity = item.seat_capacity;
        quoteForm.vehicle_type_id = item.vehicle_type_id;
        quoteForm.trim = item.id;
      } else {
        notification.error({
          title: 'No Vehicle Assumptions Data Found',
          position: 'top',
        });
      }
    });
};

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

function onSubmit(isValid) {
  if (
    quoteForm.nationality_id == null ||
    quoteForm.currently_insured_with == null
  ) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) return;

  quoteForm.clearErrors();

  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('car.update', props.quote.uuid)
    : route('car.store');

  const options = {
    onError: errors => {
      isError.value = true;
      setCarMakeAndModalValues();
      quoteForm.setError(errors);
    },
  };

  quoteForm
    .transform(data => ({ ...data, isDisbaled }))
    .submit(method, url, options);
}

onMounted(() => {
  setCarMakeAndModalValues();
});

const setCarMakeAndModalValues = () => {
  if (quoteForm.car_make_id !== null) {
    setCarMake(quoteForm.car_make_id);
    axios
      .get(`/car-model-by-id?id=${quoteForm.car_make_id}`)
      .then(({ data }) => {
        props.dropdownSource.car_model_id = data;
        getModelDetails(false);
      });
  }
};

const setCarMake = id => {
  axios.get(`/car-make?id=${id}`).then(({ data }) => {
    props.dropdownSource.car_make_id = data;
  });
};

const cylinderValidation = event => {
  if (quoteForm.cylinder && quoteForm.cylinder.length >= 5) {
    event.preventDefault();
  }
};

const addressTypes = [
  { value: '', label: 'No Address' }, // option for leaving it blank
  { value: 'Home', label: 'Home' },
  { value: 'Office', label: 'Office' },
];

const villaApartmentOfficeLabel = computed(() => {
  let label;

  if (quoteForm.addressObj.address_type === 'Home') {
    label = 'Villa / Apartment Number';
  } else if (quoteForm.addressObj.address_type === 'Office') {
    label = 'Office Name';
  } else {
    label = 'Villa / Apartment / Office No.';
  }

  return label;
});

const villaBuildingLabel = computed(() => {
  let label;

  if (quoteForm.addressObj.address_type === 'Home') {
    label = 'Community / Building Name';
  } else if (quoteForm.addressObj.address_type === 'Office') {
    label = 'Building Name';
  } else {
    label = 'Villa / Building Name';
  }

  return label;
});

const floorLabel = computed(() => {
  let label;

  if (quoteForm.addressObj.address_type === 'Home') {
    label = 'Floor / Block';
  } else if (quoteForm.addressObj.address_type === 'Office') {
    label = 'Floor';
  } else {
    label = 'Floor No.';
  }

  return label;
});

const isCourierStatusPending = computed(() => {
  return quoteForm.courierQuoteStatus !== 'Pending';
});
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Car' : 'Create Car'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Car
      </h2>
      <!-- <div class="alert" v-if="isEdit && hasRole(rolesEnum.CarManager)">
				Only Renewal Batch # field will be updated
			</div> -->
      <div>
        <Link :href="route('car.index')">
          <x-button size="sm" color="#1d83bc" tag="div"> Car List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- <x-field label="RENEWAL BATCH" v-if="isEdit" :required="isDisbaled ? false : hasRole(rolesEnum.CarManager)">
					<x-input
						v-model="quoteForm.renewal_batch"
						:rules="isDisbaled ? [] : (hasRole(rolesEnum.CarManager) ? [isRequired] : [])"
						class="w-full"
						:error="quoteForm.errors.renewal_batch"
						:disabled="isDisbaled"
						/>
				</x-field> -->

        <x-field label="FIRST NAME" required>
          <x-input
            maxLength="20"
            v-model="quoteForm.first_name"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.first_name"
          />
        </x-field>

        <x-field label="LAST NAME" required>
          <x-input
            maxLength="50"
            v-model="quoteForm.last_name"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>

        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="isEdit"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>

        <x-field label="PHONE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired]"
            class="w-full"
            :disabled="isEdit"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>

        <x-field label="COMPANY NAME">
          <x-input
            v-model="quoteForm.company_name"
            type="text"
            class="w-full"
            :error="quoteForm?.errors?.company_name"
          />
        </x-field>

        <x-field label="COMPANY ADDRESS">
          <x-input
            v-model="quoteForm.company_address"
            type="text"
            class="w-full"
            :error="quoteForm?.errors?.company_address"
          />
        </x-field>

        <x-field label="Address Type">
          <ComboBox
            v-model="quoteForm.addressObj.address_type"
            placeholder="Select address type"
            :options="addressTypes"
            :single="true"
            :disabled="isCourierStatusPending"
          />
        </x-field>
        <x-field
          label="ADDRESS"
          required
          v-if="
            quoteForm.addressObj.address_type === 'Home' ||
            quoteForm.addressObj.address_type === 'Office'
          "
        >
          <div class="flex flex-wrap -mx-2">
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.villa_apartment_office_no"
                :placeholder="villaApartmentOfficeLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.floor_no"
                :placeholder="floorLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.villa_building_name"
                :placeholder="villaBuildingLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.street_name"
                placeholder="Street (Optional)"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.area"
                placeholder="Area"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.city"
                placeholder="City"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.landmark"
                placeholder="Landmark (Optional)"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
          </div>
        </x-field>

        <x-field label="DATE OF BIRTH" required>
          <DatePicker
            v-model="quoteForm.dob"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.dob"
          />
        </x-field>

        <x-field label="NATIONALITY" required>
          <ComboBox
            v-model="quoteForm.nationality_id"
            :single="true"
            :options="
              dropdownSource.nationality_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :hasError="quoteForm.errors.nationality_id"
            :error="quoteForm.errors.nationality_id"
            :rules="[isRequired]"
          />
        </x-field>

        <x-field label="UAE LICENCE HELD FOR" required>
          <ComboBox
            v-model="quoteForm.uae_license_held_for_id"
            :single="true"
            :options="
              dropdownSource.uae_license_held_for_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :rules="[isRequired]"
            :error="quoteForm.errors.uae_license_held_for_id"
            :hasError="quoteForm.errors.uae_license_held_for_id"
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

        <x-field label="CAR MAKE" required>
          <ComboBox
            v-model="quoteForm.car_make_id"
            :single="true"
            :options="carMakeOptions"
            @update:modelValue="getCarModel(true)"
            class="w-full"
            :rules="[isRequired]"
            :hasError="quoteForm.errors.car_make_id"
            :error="quoteForm.errors.car_make_id"
          />
        </x-field>

        <x-field label="CAR MODEL" required>
          <ComboBox
            v-model="quoteForm.car_model_id"
            :single="true"
            :options="carModelOptions"
            @update:modelValue="getModelDetails(true)"
            class="w-full"
            :rules="[isRequired]"
            :error="quoteForm.errors.car_model_id"
            :hasError="quoteForm.errors.car_model_id"
          />
        </x-field>

        <x-field label="CYLINDER" required>
          <x-input
            v-model="quoteForm.cylinder"
            class="w-full"
            type="number"
            :rules="[isRequired]"
            :error="quoteForm.errors.cylinder"
            @keypress="cylinderValidation"
          />
        </x-field>

        <x-field label="TRIM">
          <ComboBox
            v-model="quoteForm.trim"
            :single="true"
            :options="trimOptions"
            class="w-full"
          />
        </x-field>

        <x-field label="CAR MODEL YEAR" required>
          <ComboBox
            v-model="quoteForm.year_of_manufacture"
            :single="true"
            :options="
              dropdownSource.year_of_manufacture.map(item => ({
                value: item.text,
                label: item.text,
              }))
            "
            class="w-full"
            :error="quoteForm.errors.year_of_manufacture"
            :rules="[isRequired]"
            :hasError="quoteForm.errors.year_of_manufacture"
          />
        </x-field>

        <x-field label="CAR VALUE" required v-if="isEdit">
          <x-input
            v-model="quoteForm.car_value"
            class="w-full"
            type="text"
            :rules="[isRequired]"
            :error="quoteForm.errors.car_value"
            @keydown="validateDecimal"
          />
        </x-field>

        <x-field label="CAR VALUE (AT ENQUIRY)" required>
          <x-input
            v-model="quoteForm.car_value_tier"
            class="w-full"
            type="text"
            :rules="[isRequired]"
            :error="quoteForm.errors.car_value_tier"
            @keydown="validateDecimal"
          />
        </x-field>

        <x-field label="VEHICLE TYPE" required>
          <ComboBox
            v-model="quoteForm.vehicle_type_id"
            :single="true"
            :options="
              dropdownSource.vehicle_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :rules="[isRequired]"
            :error="quoteForm.errors.vehicle_type_id"
            :hasError="quoteForm.errors.vehicle_type_id"
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

        <x-field label="EMIRATE OF REGISTRATION" required>
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

        <x-field label="TYPE OF CAR INSURANCE" required>
          <x-select
            v-model="quoteForm.car_type_insurance_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.car_type_insurance_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :error="quoteForm.errors.car_type_insurance_id"
          />
        </x-field>

        <x-field label="CURRENTLY INSURED WITH" required>
          <ComboBox
            v-model="quoteForm.currently_insured_with"
            :single="true"
            :options="
              dropdownSource.currently_insured_with.map(item => ({
                value: item.text,
                label: item.text,
              }))
            "
            class="w-full"
            :rules="[isRequired]"
            :error="quoteForm.errors.currently_insured_with"
            :hasError="quoteForm.errors.currently_insured_with"
          />
        </x-field>

        <x-field label="CLAIM HISTORY" required>
          <x-select
            v-model="quoteForm.claim_history_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.claim_history_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :error="quoteForm.errors.claim_history_id"
          />
        </x-field>

        <x-field
          label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?"
        >
          <x-select
            v-model="quoteForm.has_ncd_supporting_documents"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
          />
        </x-field>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
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
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
