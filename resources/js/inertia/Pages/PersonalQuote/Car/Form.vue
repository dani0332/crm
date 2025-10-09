<script setup>
import { watch } from 'vue';

const notification = useNotifications('toast');

const props = defineProps({
  dropdownSource: Object,
  model: String,
  quote: {
    type: Object,
    default: {},
  },
  quoteStatusEnums: Array,
  subSources: {
    type: Array,
    default: () => [],
  },
  leadSourceParams: {
    type: Object,
    default: () => ({}),
  },
});

const { isRequired, isEmail, maxValue, maxCharacters } = useRules();
const isEmptyField = ref(false);
const isCommercialCar = ref(false);
const isError = ref(false);
const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;
const permissionEnum = page.props.permissionsEnum;
const carRegistrationTypeEnum = page.props.carRegistrationType;
const carVehicleUseEnum = page.props.carVehicleUse;
const amlStatusEnum = page.props.amlStatusEnum;
const kycStatusEnum = page.props.kycEnums;

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

const bussinessActivityOptions = computed(() => {
  return props.dropdownSource.business_activities.map(item => ({
    value: item.id,
    label: item.name,
  }));
});

const quoteForm = useForm({
  modelType: '"Car"',
  model: props.model,
  renewal_batch: props.quote?.renewal_batch || '',
  driver_name: `${props.quote?.driver_name || ''}`.trim(),
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
  additional_notes: (() => {
    let notes = props.quote?.additional_notes || '';
    const partnerName = props.leadSourceParams?.partnerName;

    if (partnerName) {
      if (notes) {
        notes += `, ${partnerName}`;
      } else {
        notes = partnerName;
      }
    }

    return notes;
  })(),
  car_model_id: props.quote?.car_model_id || null,
  chassis_number: props.quote?.chassis_number || null,
  year_of_manufacture: props.quote?.year_of_manufacture || null,
  emirate_of_registration_id: props.quote?.emirate_of_registration_id || null,
  car_type_insurance_id: props.quote?.car_type_insurance_id || null,
  claim_history_id: props.quote?.claim_history_id || null,
  seat_capacity: props.quote?.seat_capacity || '',
  has_ncd_supporting_documents: props.quote?.has_ncd_supporting_documents,
  car_value_tier: props.quote?.car_value_tier || '',
  car_value: props.quote?.car_value || '',

  // Lead source fields from CreateLeadModal
  sub_source_id:
    parseInt(
      props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0,
    ) || null,
  sub_source_options_id:
    parseInt(
      props.quote?.sub_source_options_id ||
        props.leadSourceParams?.subSourceOption ||
        0,
    ) || null,
  primary_ref_id:
    props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',

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
  registration_type:
    props.quote?.registration_type || carRegistrationTypeEnum.PERSONAL,
  vehicle_use: props.quote?.vehicle_use || '',
  company_contact_name:
    `${props.quote.first_name || ''} ${props.quote.last_name || ''}`.trim(),
  business_activity_id: props.quote?.business_activity_id || '',
});

const isCompanyCar = computed(() => {
  return quoteForm.registration_type === carRegistrationTypeEnum.COMPANY;
});

const isPersonalCar = computed(() => {
  return quoteForm.registration_type === carRegistrationTypeEnum.PERSONAL;
});

const isPrivateCar = computed(() => {
  return (
    isCompanyCar.value && quoteForm.vehicle_use == carVehicleUseEnum.PRIVATE
  );
});

const isDisbaled =
  !hasAnyRole([rolesEnum.CarManager, rolesEnum.Admin, rolesEnum.LeadPool]) ||
  (!can(permissionEnum.RenewalBatchUpdate) && !!quoteForm.renewal_batch);

const trimOptions = ref([]);

const validateCompanyCar = () => {
  const filteredCarModel = props.dropdownSource.car_model_id.filter(
    item => item.id === quoteForm.car_model_id,
  );
  if (filteredCarModel && filteredCarModel[0]?.is_commercial == 1) {
    quoteForm.vehicle_use = carVehicleUseEnum.COMMERCIAL;
    isCommercialCar.value = true;
  } else {
    isCommercialCar.value = false;
  }
};

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
  validateCompanyCar();
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

  clearFormValues();
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

const clearFormValues = () => {
  if (quoteForm.registration_type === carRegistrationTypeEnum.PERSONAL) {
    delete quoteForm.vehicle_use;
    delete quoteForm.company_name;
    delete quoteForm.company_contact_name;
    delete quoteForm.business_activity_id;
    delete quoteForm.driver_name;
  } else {
    delete quoteForm.first_name;
    delete quoteForm.last_name;

    if (quoteForm.vehicle_use === carVehicleUseEnum.COMMERCIAL) {
      delete quoteForm.driver_name;
      delete quoteForm.dob;
      delete quoteForm.nationality_id;
      delete quoteForm.uae_license_held_for_id;
      delete quoteForm.back_home_license_held_for_id;
    }
  }
};

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

const registrationTypeOptions = Object.values(carRegistrationTypeEnum).map(
  item => ({
    value: item,
    label: item.charAt(0).toUpperCase() + item.slice(1),
  }),
);

const vehicleUseOptions = Object.values(carVehicleUseEnum).map(item => ({
  value: item,
  label: item.charAt(0).toUpperCase() + item.slice(1),
}));

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

const chassisNumberDisabled = computed(() => {
  let disallowedStatus = [
    props.quoteStatusEnums.PolicySentToCustomer,
    props.quoteStatusEnums.PolicyBooked,
  ];
  return disallowedStatus.includes(props?.quote?.quote_status_id);
});

const chassisNumberValidate = eventType => {
  const regex = /^[a-zA-Z0-9]*$/; // Allow only alphanumeric characters

  if (eventType == 'keypress') {
    const event = window.event || event;
    const key = event.key;
    if (
      !regex.test(key) &&
      key !== 'Backspace' &&
      key !== 'Delete' &&
      key !== 'ArrowLeft' &&
      key !== 'ArrowRight'
    ) {
      event.preventDefault();
    }
  }

  if (eventType == 'blur') {
    const lengthValid =
      quoteForm.chassis_number.length >= 8 &&
      quoteForm.chassis_number.length <= 17;
    const isAlphanumeric = regex.test(quoteForm.chassis_number);
    if (quoteForm.chassis_number && (!lengthValid || !isAlphanumeric)) {
      quoteForm.errors.chassis_number =
        'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.';
    } else {
      quoteForm.clearErrors('chassis_number');
    }
  }
};

const chassisNumberRule = v => {
  const regex = /^[a-zA-Z0-9]*$/;
  const lengthValid = v.length >= 8 && v.length <= 17;
  const isAlphanumeric = regex.test(v);
  if (v && (!lengthValid || !isAlphanumeric)) {
    return 'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.';
  }
  return true;
};

const gender = computed(() => {
  return [
    { value: 'M', label: 'Male' },
    { value: 'F', label: 'Female' },
  ];
});

// Sub-source dropdown options
const subSourceOptions = computed(() => {
  if (!props.subSources || props.subSources.length === 0) {
    return [];
  }

  const options = props.subSources.map(item => ({
    value: item.id, // Keep as integer to match form data type
    label: item.text,
    suffix:
      item.description || item.tooltip || `Information about ${item.text}`, // Use suffix for tooltip data
  }));

  return options;
});

// Sub-source option dropdown (childs of selected sub-source)
const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];

  const selectedSubSource = props.subSources.find(
    item => item.id == quoteForm.sub_source_id,
  );
  if (!selectedSubSource || !selectedSubSource.childs) return [];

  return selectedSubSource.childs.map(child => ({
    value: child.id, // Keep as integer to match form data type
    label: child.text,
    suffix:
      child.description || child.tooltip || `Information about ${child.text}`, // Add tooltip support
  }));
});

// Check if this is a referral type lead
const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' ||
    props.quote?.source === 'IMCRM'
  );
});

// Check if ECOM lead extension is selected
const isEcomLeadExtension = computed(() => {
  // From CreateLeadModal
  if (props.leadSourceParams?.type === 'ecom_lead_extension') return true;

  // From existing quote: sub_source_id and sub_source_options_id are null but primary_ref_id has value
  if (
    !quoteForm.sub_source_id &&
    !quoteForm.sub_source_options_id &&
    quoteForm.primary_ref_id
  ) {
    return true;
  }

  return false;
});

// Role-based permissions for sub-source fields
const canEditSubSourceFields = computed(() => {
  return hasAnyRole([
    rolesEnum.CarManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

// Show partner name field when "other-clubs-or-campaigns" is selected
const showPartnerNameField = computed(() => {
  if (!quoteForm.sub_source_options_id) return false;
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  if (!selectedSubSource?.childs) return false;
  const selectedSubSourceOption = selectedSubSource.childs.find(
    child => child.id == quoteForm.sub_source_options_id,
  );
  return selectedSubSourceOption?.code === 'other-clubs-or-campaigns';
});

// Watch for sub-source changes to reset sub-source option
watch(
  () => quoteForm.sub_source_id,
  newValue => {
    quoteForm.sub_source_options_id = null;
    quoteForm.partner_name = '';
  },
);

// Watch for sub-source option changes to reset partner name
watch(
  () => quoteForm.sub_source_options_id,
  newValue => {
    quoteForm.partner_name = '';
  },
);

// Watch for partner name changes to update additional_notes
watch(
  () => quoteForm.partner_name,
  (newValue, oldValue) => {
    if (!showPartnerNameField.value) return;

    // Remove old partner name from additional_notes if it exists
    if (oldValue) {
      const oldPattern = new RegExp(
        `(, ${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}|${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}, |${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`,
        'g',
      );
      quoteForm.additional_notes = quoteForm.additional_notes
        .replace(oldPattern, '')
        .trim();
      // Clean up any double commas or leading/trailing commas
      quoteForm.additional_notes = quoteForm.additional_notes
        .replace(/,\s*,/g, ',')
        .replace(/^,\s*|,\s*$/g, '');
    }

    // Add new partner name to additional_notes
    if (newValue) {
      if (quoteForm.additional_notes) {
        quoteForm.additional_notes = `${quoteForm.additional_notes}, ${newValue}`;
      } else {
        quoteForm.additional_notes = newValue;
      }
    }
  },
);
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

        <!-- Lead Source Fields - Only show when type is referral -->
        <x-select
          v-if="isReferralType && !isEcomLeadExtension"
          label="IMCRM SUB-SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select IMCRM SUB-SOURCE"
          filterable
          filterPlaceholder="Filter IMCRM SUB-SOURCE...."
          :disabled="!canEditSubSourceFields"
          :rules="[isRequired]"
          :required="subSourceOptions.length > 0"
          :error="quoteForm.errors.sub_source_id"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>

        <x-select
          v-if="
            isReferralType && quoteForm.sub_source_id && !isEcomLeadExtension
          "
          label="SUB SOURCE OPTION"
          v-model="quoteForm.sub_source_options_id"
          :options="subSourceOptionOptions"
          class="w-full"
          placeholder="Select Sub Source Option"
          filterable
          filterPlaceholder="Filter Sub Source Option...."
          :disabled="!canEditSubSourceFields"
          :rules="subSourceOptionOptions.length > 0 ? [isRequired] : []"
          :required="subSourceOptionOptions.length > 0"
          :error="quoteForm.errors.sub_source_options_id"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>

        <x-input
          v-if="isEcomLeadExtension"
          label="PRIMARY REF ID"
          required
          v-model="quoteForm.primary_ref_id"
          class="w-full"
          type="text"
          placeholder="Enter Primary Ref ID"
          :rules="[isRequired]"
          :tooltip="'ID of the original ECOM lead'"
          :error="quoteForm.errors.primary_ref_id"
          :disabled="!canEditSubSourceFields"
        />

        <x-input
          v-if="showPartnerNameField"
          label="PARTNER NAME"
          :required="canEditSubSourceFields"
          v-model="quoteForm.partner_name"
          class="w-full"
          type="text"
          placeholder="Enter Partner Name"
          :tooltip="'Name of campaign, event or club'"
          maxLength="50"
          :rules="canEditSubSourceFields ? [isRequired, maxCharacters(50)] : []"
          :error="quoteForm.errors.partner_name"
          :disabled="!canEditSubSourceFields"
        />

        <x-select
          label="REGISTRATION TYPE"
          required
          v-model="quoteForm.registration_type"
          :options="registrationTypeOptions"
          class="w-full"
          :rules="[isRequired]"
          :disabled="isAmlOrKycUpdated"
        />

        <x-select
          v-if="isCompanyCar"
          label="VEHICLE USE"
          required
          v-model="quoteForm.vehicle_use"
          :options="vehicleUseOptions"
          placeholder="Please select vehicle use"
          :rules="[isRequired]"
          class="w-full"
          :disabled="isCommercialCar"
        />

        <x-input
          v-if="isCompanyCar"
          label="YOUR COMPANY NAME"
          required
          maxLength="200"
          v-model="quoteForm.company_name"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.company_name"
        />

        <x-input
          v-if="isCompanyCar"
          label="POINT OF CONTACT NAME"
          required
          maxLength="20"
          v-model="quoteForm.company_contact_name"
          :rules="[
            isRequired,
            v => v.trim().split(' ').length >= 2 || 'Please enter full name',
          ]"
          class="w-full"
          :error="quoteForm.errors.company_contact_name"
        />

        <x-input
          v-if="isCompanyCar"
          label="POINT OF CONTACT EMAIL"
          required
          v-model="quoteForm.email"
          type="email"
          :disabled="isEdit"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          v-if="isCompanyCar"
          label="POINT OF CONTACT PHONE NUMBER"
          required
          v-model="quoteForm.mobile_no"
          type="tel"
          :rules="[isRequired]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.mobile_no"
        />

        <x-select
          v-if="isCompanyCar"
          label="BUSINESS ACTIVITY"
          required
          v-model="quoteForm.business_activity_id"
          placeholder="Select bussiness activity"
          :options="bussinessActivityOptions"
          :rules="[isRequired]"
          :error="quoteForm.errors.business_activity_id"
          :hasError="quoteForm.errors.business_activity_id"
          filterable
          filterPlaceholder="Filter Business Activity...."
        />

        <x-input
          v-if="isPersonalCar"
          label="FIRST NAME"
          required
          maxLength="20"
          v-model="quoteForm.first_name"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
        />

        <x-input
          v-if="isPersonalCar"
          label="LAST NAME"
          required
          maxLength="50"
          v-model="quoteForm.last_name"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />

        <x-input
          v-if="isPrivateCar"
          label="DRIVER'S NAME"
          required
          maxLength="20"
          v-model="quoteForm.driver_name"
          :rules="[
            v => v.trim().split(' ').length >= 2 || 'Please enter full name',
          ]"
          class="w-full"
          :error="quoteForm.errors.driver_name"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          :disabled="isEdit"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
          v-if="isPersonalCar"
          label="EMAIL"
          required
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :rules="[isRequired]"
          class="w-full"
          :disabled="isEdit"
          :error="quoteForm.errors.mobile_no"
          v-if="isPersonalCar"
          label="PHONE NUMBER"
          required
        />

        <x-select
          v-model="quoteForm.addressObj.address_type"
          placeholder="Select address type"
          :options="addressTypes"
          :disabled="isCourierStatusPending"
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

        <DatePicker
          v-if="isPrivateCar || isPersonalCar"
          :label="isPrivateCar ? 'DRIVER\'S DATE OF BIRTH' : 'DATE OF BIRTH'"
          required
          v-model="quoteForm.dob"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.dob"
        />

        <x-select
          v-if="isPrivateCar || isPersonalCar"
          :label="isPrivateCar ? 'DRIVER\'S NATIONALITY' : 'NATIONALITY'"
          required
          v-model="quoteForm.nationality_id"
          :options="
            dropdownSource.nationality_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          :hasError="quoteForm.errors.nationality_id"
          :error="quoteForm.errors.nationality_id"
          :rules="[isRequired]"
          filterable
          filterPlaceholder="Filter Nationality...."
          placeholder="Select Nationality"
        />

        <x-select
          label="GENDER"
          v-model="quoteForm.gender"
          :options="gender"
          placeholder="Gender"
        />

        <x-select
          v-if="isPrivateCar || isPersonalCar"
          label="UAE LICENCE HELD FOR"
          required
          v-model="quoteForm.uae_license_held_for_id"
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
          filterable
          filterPlaceholder="Filter UAE License Held For...."
          placeholder="Select UAE License Held For"
        />

        <x-select
          v-model="quoteForm.back_home_license_held_for_id"
          :options="
            dropdownSource.back_home_license_held_for_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          v-if="isPrivateCar || isPersonalCar"
          label="HOME COUNTRY DRIVING LICENSE HELD FOR"
          class="w-full"
        />

        <x-select
          v-model="quoteForm.car_make_id"
          :options="carMakeOptions"
          @update:modelValue="getCarModel(true)"
          class="w-full"
          :rules="[isRequired]"
          :hasError="quoteForm.errors.car_make_id"
          :error="quoteForm.errors.car_make_id"
          filterable
          filterPlaceholder="Filter Car Make...."
          placeholder="Select Car Make"
          label="CAR MAKE"
          required
        />

        <x-select
          v-model="quoteForm.car_model_id"
          :options="carModelOptions"
          @update:modelValue="getModelDetails(true)"
          class="w-full"
          :rules="[isRequired]"
          filterable
          filterPlaceholder="Filter Car Model...."
          placeholder="Select Car Model"
          :error="quoteForm.errors.car_model_id"
          :hasError="quoteForm.errors.car_model_id"
          label="CAR MODEL"
          required
        />

        <x-input
          v-model="quoteForm.cylinder"
          class="w-full"
          type="number"
          :rules="[isRequired]"
          :error="quoteForm.errors.cylinder"
          @keypress="cylinderValidation"
          label="CYLINDER"
          required
        />

        <div>
          <template v-if="chassisNumberDisabled">
            <x-tooltip placement="bottom">
              <label
                class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
              >
                CHASSIS NUMBER
              </label>
              <template #tooltip>
                This lead is now locked as the policy has been booked. If
                changes are needed, go to 'Send Update', select 'Add Update',
                and choose 'Cancellation from Inception and Reissuance'
              </template>
            </x-tooltip>
            <x-input
              :disabled="chassisNumberDisabled"
              v-model="quoteForm.chassis_number"
              class="w-full"
              type="text"
              placeholder="Enter Chassis Number"
            />
          </template>
          <template v-else>
            <x-input
              v-model="quoteForm.chassis_number"
              class="w-full"
              type="text"
              label="CHASSIS NUMBER"
              placeholder="Enter Chassis Number"
              :rules="quoteForm.chassis_number ? [chassisNumberRule] : []"
              @keypress="chassisNumberValidate('keypress')"
              @blur="chassisNumberValidate('blur')"
              :error="quoteForm.errors.chassis_number"
            />
          </template>
        </div>

        <x-select
          v-model="quoteForm.trim"
          :options="trimOptions"
          class="w-full"
          label="TRIM"
          filterable
          filterPlaceholder="Filter Trim...."
          placeholder="Select Trim"
        />

        <x-select
          label="CAR MODEL YEAR"
          required
          v-model="quoteForm.year_of_manufacture"
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
          filterable
          filterPlaceholder="Filter Car Model Year...."
          placeholder="Select Car Model Year"
        />

        <x-input
          label="CAR VALUE"
          required
          v-if="isEdit"
          v-model="quoteForm.car_value"
          class="w-full"
          type="text"
          :rules="[isRequired]"
          :error="quoteForm.errors.car_value"
          @keydown="validateDecimal"
        />

        <x-input
          label="CAR VALUE (AT ENQUIRY)"
          required
          v-model="quoteForm.car_value_tier"
          class="w-full"
          type="text"
          :rules="[isRequired]"
          :error="quoteForm.errors.car_value_tier"
          @keydown="validateDecimal"
        />

        <x-select
          label="VEHICLE TYPE"
          required
          v-model="quoteForm.vehicle_type_id"
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
          filterable
          filterPlaceholder="Filter Vehicle Type...."
          placeholder="Select Vehicle Type"
        />

        <x-input
          label="SEAT CAPACITY"
          required
          v-model="quoteForm.seat_capacity"
          class="w-full"
          type="number"
          :rules="[isRequired]"
          :error="quoteForm.errors.seat_capacity"
        />

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
          label="EMIRATE OF REGISTRATION"
          required
        />

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
          label="TYPE OF CAR INSURANCE"
          required
          :error="quoteForm.errors.car_type_insurance_id"
        />

        <x-select
          label="CURRENTLY INSURED WITH"
          required
          v-model="quoteForm.currently_insured_with"
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
          filterable
          filterPlaceholder="Filter Currently Insured With...."
          placeholder="Select Currently Insured With"
        />

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
          label="CLAIM HISTORY"
          required
        />

        <x-select
          label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?"
          v-model="quoteForm.has_ncd_supporting_documents"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
        />
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <x-textarea
          v-model="quoteForm.additional_notes"
          type="textarea"
          rows="5"
          class="w-full"
          :adjust-to-text="false"
          label="ADDITIONAL NOTES"
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
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
