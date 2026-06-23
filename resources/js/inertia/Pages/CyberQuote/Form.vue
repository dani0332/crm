<script setup>
const notification = useNotifications('toast');
const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';

const page = usePage();

const props = defineProps({
  quote: { type: Object, default: null },
  lookUpData: { type: Object, required: true },
});

// Format API data for dropdowns and selects
const nationalities = computed(() => {
  return props.lookUpData.nationality.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const emiratesOfRegistration = computed(() => {
  return props.lookUpData.emiratesOfRegistration.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const quoteForm = useForm({
  modelType: 'Cyber',
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob ? dateFormat(props.quote?.dob) : '',
  nationality_id: props.quote?.nationality_id || '',
  emirate_of_registration_id:
    props.quote?.cyber_quote?.emirate_of_registration_id || '',
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

const isCourierStatusNotPending = computed(() => {
  return quoteForm.courierQuoteStatus !== 'Pending';
});

const { isRequired, isEmail, isMobileNo, isValidName } = useRules();

const editMode = computed(() => {
  return props.quote && props.quote.uuid ? true : false;
});

const isEmptyField = ref(false);

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('cyber-quotes-update', props.quote.uuid)
      : route('cyber-quotes-store');

    if (
      !editMode.value &&
      new URLSearchParams(window.location.search).get('ea_model') === 'collaborate'
    ) {
      url += '?ea_model=collaborate';
    }

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
    <Head title="Cyber Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Cyber Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('cyber-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Cyber Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
        {{ quoteForm?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Personal Details -->
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          label="First Name"
          required
          :rules="[isRequired, isValidName]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="Last Name"
          required
          maxLength="50"
          :rules="[isRequired, isValidName]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          label="Email"
          required
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="Phone Number"
          required
          :disabled="editMode"
          :rules="[isRequired, ...(editMode ? [] : [isMobileNo])]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <DatePicker
          v-model="quoteForm.dob"
          class="w-full"
          :rules="[isRequired]"
          :max-date="new Date()"
          label="Date of Birth"
          format="dd-MM-yyyy"
          required
        />

        <x-select
          v-model="quoteForm.nationality_id"
          :options="nationalities"
          class="w-full"
          :error="quoteForm.errors.nationality_id"
          :rules="[isRequired]"
          label="Nationality"
          filterable
          placeholder="Search by Nationality"
          required
        />

        <x-select
          v-model="quoteForm.emirate_of_registration_id"
          :options="emiratesOfRegistration"
          class="w-full"
          :error="quoteForm.errors.emirate_of_registration_id"
          :rules="[isRequired]"
          label="Emirate of Residence"
          filterable
          placeholder="Search by Emirate of Residence"
          required
        />

        <div class="col-span-2"></div>

        <x-select
          v-model="quoteForm.addressObj.address_type"
          placeholder="Select address type"
          :options="addressTypes"
          :disabled="isCourierStatusNotPending"
          filterable
          filterPlaceholder="Filter Address Type...."
          label="Address Type"
        />
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
                :disabled="isCourierStatusNotPending"
                :error="
                  quoteForm.errors['addressObj.villa_apartment_office_no']
                "
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.floor_no"
                :placeholder="floorLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusNotPending"
                :error="quoteForm.errors['addressObj.floor_no']"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.villa_building_name"
                :placeholder="villaBuildingLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusNotPending"
                :error="quoteForm.errors['addressObj.villa_building_name']"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.street_name"
                placeholder="Street (Optional)"
                class="w-full"
                :disabled="isCourierStatusNotPending"
                :error="quoteForm.errors['addressObj.street_name']"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.area"
                placeholder="Area"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusNotPending"
                :error="quoteForm.errors['addressObj.area']"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.city"
                placeholder="City"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusNotPending"
                :error="quoteForm.errors['addressObj.city']"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.landmark"
                placeholder="Landmark (Optional)"
                class="w-full"
                :disabled="isCourierStatusNotPending"
                :error="quoteForm.errors['addressObj.landmark']"
              />
            </div>
          </div>
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

<style scoped>
/* Ensure tooltip appears on hover */
.group:hover .group-hover\:block {
  display: block;
}

/* Custom tooltip styling to match design */
.tooltip-box {
  border: 1px solid #1d83bc;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  color: #33333399;
  line-height: 1.5;
  padding: 12px;
  font-size: 14px;
  max-width: 280px;
  background-color: #f8fafc;
}

.radio-wrapper {
  cursor: pointer;
}
</style>
