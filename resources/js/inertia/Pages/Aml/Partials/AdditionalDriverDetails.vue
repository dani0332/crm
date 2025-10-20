<script setup>
import { ref, computed, watch } from 'vue';
const { isRequired } = useRules();

const props = defineProps({
  insurerPortalSyncData: {
    type: Object,
    default: null,
  },
  quote_type_id: {
    type: Number,
    default: null
  },
});

const page = usePage();
const notification = useToast();
const lookups = page.props.lookups;
const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const isSyncFromInsurer = ref(false);
const quote = page.props?.quoteRequest ?? page.props?.record;
const insuranceProviderCode = quote?.plan?.insurance_provider?.code ?? quote?.plan_provider_code;

// Computed options for dropdowns
const driverGenderOptions = computed(() => [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
]);

const vehicleDriverDetail = computed(() => {
  return quote?.vehicle_driver_detail;
});

const formatDate = date => {
  if (!date) return '';

  const dateObj = new Date(date);
  // Use local timezone to avoid date shifting
  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
};

/**
 * Normalize gender value
 * If starts with 'M' or 'm', returns 'male', otherwise returns 'female'
 */
const normalizeGender = gender => {
  if (!gender) return '';
  const genderStr = String(gender).trim();
  return genderStr.toLowerCase().startsWith('m') ? 'male' : 'female';
};

const additionalDriverDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id ?? props.quote_type_id,
  quote_uuid: quote?.uuid,
  insurance_provider_code: insuranceProviderCode ?? '',
  is_insured_and_driver_same:
    vehicleDriverDetail.value?.is_insured_and_driver_same?.toString() ?? '',
  driver_first_name: vehicleDriverDetail.value?.driver_first_name ?? '',
  driver_last_name: vehicleDriverDetail.value?.driver_last_name ?? '',
  driver_dob: formatDate(vehicleDriverDetail.value?.driver_dob) ?? '',
  driver_gender: vehicleDriverDetail.value?.driver_gender ?? '',
  driver_license_number: vehicleDriverDetail.value?.driver_license_number ?? '',
  license_issue_place:
    vehicleDriverDetail.value?.driver_license_issue_place?.toString() ?? '',
  license_issue_date:
    vehicleDriverDetail.value?.driver_license_issue_date ?? '',
  license_expiry_date:
    vehicleDriverDetail.value?.driver_license_expiry_date ?? '',
  uae_driving_experience:
    vehicleDriverDetail.value?.driver_uae_driving_experience?.toString() ?? '',
  home_country_license_issuance:
    vehicleDriverDetail.value?.driver_home_country_license_issuance ?? '',
  home_country_driving_experience:
    vehicleDriverDetail.value?.driver_home_country_driving_experience?.toString() ??
    '',
});

const hasNotEditPermission = computed(() => {
  return !hasPermission(
    permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS,
  );
});

const submitAdditionalDriverDetailsForm = async isValid => {
  if (isValid) {
    // Clear any previous errors
    additionalDriverDetailsForm.clearErrors();

    additionalDriverDetailsForm.processing = true;
    try {
      const response = await axios.post(
        '/kyc/update-additional-vehicle-driver-details',
        additionalDriverDetailsForm,
      );
      if (response.data.success) {
        notification.success({
          title: response.data.message,
          position: 'top',
        });
        if (
          response.data.hasOwnProperty('is_insured_driver_same') &&
          response.data.is_insured_driver_same == '0'
        ) {
          notification.success({
            title: `Please Update Additional Drivers on ${insuranceProviderCode} Portal`,
            position: 'top',
            timeout: 5000,
          });
        }
        router.reload({
          replace: true,
          preserveScroll: true,
          preserveState: true,
        });
      } else {
        notification.error({
          title: response.data.message,
          position: 'top',
        });
      }
    } catch (error) {
      // Handle validation errors (422 status)
      if (error.response && error.response.status === 422) {
        const validationErrors = error.response.data.errors;
        if (validationErrors) {
          // Set each validation error on the form
          Object.entries(validationErrors).forEach(([field, messages]) => {
            notification.error({
              title: messages[0],
              position: 'top',
            });
            additionalDriverDetailsForm.setError(field, messages[0]);
          });
        }
      } else {
        notification.error({
          title: 'Error saving driver details',
          position: 'top',
        });
      }
    } finally {
      additionalDriverDetailsForm.processing = false;
    }
  }
};

const isGIG = computed(() => {
  return (
    quote?.plan?.insurance_provider.code ===
    page.props.insuranceProviderCodeEnum.AXA
  );
});

const isLIVA = computed(() => {
  return (
    quote?.plan?.insurance_provider.code ===
    page.props.insuranceProviderCodeEnum.RSA
  );
});

const isSUKOON = computed(() => {
  return (
    quote?.plan?.insurance_provider.code ===
    page.props.insuranceProviderCodeEnum.OIC
  );
});

const licenseIssuePlaceOptions = computed(() => {
  if (isLIVA.value) {
    return useGenerateOptions(
      page.props.nationalities ?? [],
      'rsa_country_code',
      'text',
    );
  } else {
    return useGenerateOptions(lookups?.issuance_place ?? [], 'code', 'text');
  }
});

const nationalitiesOptions = computed(() => {
  let code = isLIVA.value ? 'rsa_country_code' : 'code';

  return useGenerateOptions(page.props.nationalities ?? [], code, 'text');
});

const drivingExperienceOptions = computed(() => {
  if (isLIVA.value) {
    return useGenerateOptions(
      lookups?.driving_experience ?? [],
      'rsa_driving_experience',
      'text',
    );
  }
  const options = [{ value: '0', label: 'No Experience' }];

  for (let i = 1; i <= 50; i++) {
    options.push({
      value: i.toString(),
      label: i === 1 ? '1 Year' : `${i} Years`,
    });
  }

  return options;
});

// Computed property to check if driver name fields should be disabled
const isDriverNameFieldsDisabled = computed(() => {
  return (
    additionalDriverDetailsForm.is_insured_and_driver_same === 1 ||
    additionalDriverDetailsForm.is_insured_and_driver_same === '1'
  );
});

// Computed property to check if driver name fields should be required
const isDriverNameFieldsRequired = computed(() => {
  return (
    additionalDriverDetailsForm.is_insured_and_driver_same === 0 ||
    additionalDriverDetailsForm.is_insured_and_driver_same === '0'
  );
});

// Watch for changes in is_insured_and_driver_same to clear driver names when they become disabled
watch(
  () => additionalDriverDetailsForm.is_insured_and_driver_same,
  newValue => {
    // If insured and driver are the same (1 or '1'), clear the driver name fields
    if ((newValue === 1 || newValue === '1') && !isSyncFromInsurer.value) {
      additionalDriverDetailsForm.driver_first_name = quote?.first_name;
      additionalDriverDetailsForm.driver_last_name = quote?.last_name;
      additionalDriverDetailsForm.driver_dob = quote?.dob;
      additionalDriverDetailsForm.driver_gender = normalizeGender(
        quote?.gender,
      );
      additionalDriverDetailsForm.uae_driving_experience =
        quote?.uae_license_held_for?.rsa_driving_experience;
    }
  },
);

watch(
  () => props.insurerPortalSyncData,
  driverDetails => {
    if (driverDetails) {
      const fieldMappings = {
        driverDetails: {
          is_insured_and_driver_same: 'is_insured_and_driver_same',
          driver_first_name: 'driver_first_name',
          driver_last_name: 'driver_last_name',
          driver_dob: 'driver_dob',
          driver_gender: 'driver_gender',
          driver_license_number: 'driver_license_number',
          driver_license_issue_place: 'license_issue_place',
          // driver_license_issue_date: 'license_issue_date',
          // driver_license_expiry_date: 'license_expiry_date',
          driver_uae_driving_experience: 'uae_driving_experience',
          driver_home_country_license_issuance: 'home_country_license_issuance',
          driver_home_country_driving_experience:
            'home_country_driving_experience',
        },
      };

      Object.entries(fieldMappings.driverDetails).forEach(
        ([sourceKey, targetKey]) => {
          if (driverDetails?.[sourceKey]) {
            additionalDriverDetailsForm[targetKey] = driverDetails[sourceKey];
          }
        },
      );
      if (driverDetails?.is_insured_and_driver_same === '1') {
        isSyncFromInsurer.value = true;
      }
    }
  },
  { deep: true },
);

watch(
  () => additionalDriverDetailsForm.driver_dob,
  newValue => {
    if (newValue) {
      additionalDriverDetailsForm.driver_dob = formatDate(newValue);
    }
  },
);
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Driver Details
      </h3>
    </div>

    <x-form @submit="submitAdditionalDriverDetailsForm">
      <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
        <!-- Is Insured and Driver Same -->
        <x-select
          v-model="additionalDriverDetailsForm.is_insured_and_driver_same"
          :rules="[isRequired]"
          :options="[
            { value: '1', label: 'Yes' },
            { value: '0', label: 'No' },
          ]"
          placeholder="Select Is Insured and Driver Same"
          :disabled="hasNotEditPermission"
          label="Is the Insured and Driver the same?"
          :tooltip="'Indicates if the insured person is also the main driver of the vehicle'"
          required
        />

        <!-- Driver Name - disabled when insured and driver are the same -->
        <x-input
          v-model="additionalDriverDetailsForm.driver_first_name"
          :rules="isDriverNameFieldsRequired ? [isRequired] : []"
          :required="isDriverNameFieldsRequired"
          placeholder="Driver First Name"
          type="text"
          :disabled="isDriverNameFieldsDisabled || hasNotEditPermission"
          label="Driver First Name"
          :tooltip="'First name of the driver operating the insured vehicle'"
        />

        <x-input
          v-model="additionalDriverDetailsForm.driver_last_name"
          :rules="isDriverNameFieldsRequired ? [isRequired] : []"
          :required="isDriverNameFieldsRequired"
          placeholder="Driver Last Name"
          type="text"
          :disabled="isDriverNameFieldsDisabled || hasNotEditPermission"
          label="Driver Last Name"
          :tooltip="'Last name of the driver operating the insured vehicle'"
        />

        <DatePicker
          v-model="additionalDriverDetailsForm.driver_dob"
          :rules="isSUKOON ? [isRequired] : []"
          :required="isSUKOON || isLIVA"
          placeholder="Driver DOB"
          :disabled="hasNotEditPermission"
          label="Driver DOB"
          :tooltip="'Date of birth of the driver'"
        />

        <x-select
          v-model="additionalDriverDetailsForm.driver_gender"
          :rules="!isSUKOON ? [isRequired] : []"
          :required="!isSUKOON"
          :options="driverGenderOptions"
          placeholder="Select Driver Gender"
          :disabled="hasNotEditPermission"
          label="Driver Gender"
          :tooltip="'Gender of the driver'"
        />

        <x-input
          v-model="additionalDriverDetailsForm.driver_license_number"
          :rules="[isRequired]"
          required
          placeholder="Driver License Number"
          type="text"
          :disabled="hasNotEditPermission"
          label="Driver License Number"
          :tooltip="`Driver's license number issued by the local authority`"
        />

        <x-select
          filterable
          v-model="additionalDriverDetailsForm.license_issue_place"
          :rules="isSUKOON ? [isRequired] : []"
          :required="isSUKOON"
          :options="licenseIssuePlaceOptions"
          placeholder="Select License Issue Place"
          :disabled="hasNotEditPermission"
          label="License Issue Place"
          :tooltip="`Emirate where the driver's license was issued`"
        />

        <DatePicker
          v-model="additionalDriverDetailsForm.license_issue_date"
          :rules="isSUKOON ? [isRequired] : []"
          :required="isSUKOON"
          placeholder="License Issue Date"
          :disabled="hasNotEditPermission"
          label="License Issue Date"
          :tooltip="`Date of issuance of the current driver's license`"
        />

        <DatePicker
          v-model="additionalDriverDetailsForm.license_expiry_date"
          placeholder="License Expiry Date"
          :rules="!isLIVA ? [isRequired] : []"
          :required="!isLIVA"
          :disabled="hasNotEditPermission"
          label="License Expiry Date"
          :tooltip="`Expiry date of the current driver's license`"
        />

        <!-- TODO: Required only if 'Driver same as Client?' is NO -->
        <x-select
          filterable
          v-model="additionalDriverDetailsForm.uae_driving_experience"
          :rules="isLIVA ? [isRequired] : []"
          :required="isLIVA"
          :options="drivingExperienceOptions"
          placeholder="Select UAE License Years"
          :disabled="hasNotEditPermission"
          label="UAE Driving Experience"
          :tooltip="`Number of years the driver has held a valid license in the UAE`"
        />

        <x-select
          filterable
          v-model="additionalDriverDetailsForm.home_country_license_issuance"
          :rules="!isGIG ? [isRequired] : []"
          :required="!isGIG"
          placeholder="Select License Home Country"
          :options="nationalitiesOptions"
          class="w-full"
          :disabled="hasNotEditPermission"
          label="Home Country License Issuance"
          :tooltip="`Select the home country where the driver's license was issued`"
        />

        <x-select
          filterable
          v-model="additionalDriverDetailsForm.home_country_driving_experience"
          :rules="isLIVA ? [isRequired] : []"
          :required="isLIVA"
          :options="drivingExperienceOptions"
          placeholder="Select Driver Years Home Country"
          :disabled="hasNotEditPermission"
          label="Home Country Driving Experience"
          :tooltip="`Number of years the driver has held a valid license in their home country`"
        />
      </dl>
      <div
        class="flex justify-end my-5 gap-x-2"
        v-if="
          hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)
        "
      >
        <x-button
          size="sm"
          color="orange"
          type="submit"
          class="px-6"
          :loading="additionalDriverDetailsForm.processing"
        >
          Save
        </x-button>
      </div>
      <div class="flex justify-end my-5 gap-x-2" v-else>
        <x-tooltip>
          <x-button
            size="sm"
            color="orange"
            type="submit"
            class="px-6"
            disabled
          >
            Save
          </x-button>
          <template #tooltip>
            You don't have permission to edit this section.
          </template>
        </x-tooltip>
      </div>
    </x-form>
  </div>
</template>
