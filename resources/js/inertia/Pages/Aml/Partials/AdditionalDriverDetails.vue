<script setup>
import { ref, computed } from 'vue'
const { isRequired } = useRules();

const props = defineProps({
  insurerPortalSyncData: {
    type: Object,
    default: null
  }
});

const page = usePage();
const notification = useToast();
const lookups = page.props.lookups;
const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const isSyncFromInsurer = ref(false);

// Computed options for dropdowns
const driverGenderOptions = computed(() => [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
])

const licenseIssuePlaceOptions = computed(() => {
  return useGenerateOptions(lookups?.issuance_place ?? [], 'code', 'text');
});

const nationalitiesOptions = computed(() => {
  return useGenerateOptions(page.props.nationalities ?? [], 'code', 'text');
});

const drivingExperienceOptions = computed(() => {
  const options = [{ value: '0', label: 'No Experience' }]

  for (let i = 1; i <= 50; i++) {
    options.push({
      value: i.toString(),
      label: i === 1 ? '1 Year' : `${i} Years`
    })
  }

  return options
});

const carDetail = computed(() => {
  return page.props.quoteRequest?.car_quote_request_detail;
});

const additionalDriverDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  is_insured_and_driver_same: carDetail.value?.is_insured_and_driver_same?.toString() ?? '',
  driver_first_name: carDetail.value?.driver_first_name ?? '',
  driver_last_name: carDetail.value?.driver_last_name ?? '',
  driver_dob: carDetail.value?.driver_dob ?? '',
  driver_gender: carDetail.value?.driver_gender ?? '',
  driver_license_number: carDetail.value?.driver_license_number ?? '',
  license_issue_place: carDetail.value?.driver_license_issue_place?.toString() ?? '',
  license_issue_date: carDetail.value?.driver_license_issue_date ?? '',
  license_expiry_date: carDetail.value?.driver_license_expiry_date ?? '',
  uae_driving_experience: carDetail.value?.driver_uae_driving_experience?.toString() ?? '',
  home_country_license_issuance: carDetail.value?.home_country_license_issuance ?? '',
  home_country_driving_experience: carDetail.value?.home_country_driving_experience?.toString() ?? '',
});

const hasNotEditPermission = computed(() => {
  return !hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS);
});

const submitAdditionalDriverDetailsForm = async (isValid) => {
  if (isValid) {
    // Clear any previous errors
    additionalDriverDetailsForm.clearErrors();

    additionalDriverDetailsForm.processing = true;
    try {
      const response = await axios.post('/kyc/update-additional-vehicle-driver-details', additionalDriverDetailsForm);
      if (response.data.success) {
        notification.success({
          title: response.data.message,
          position: 'top',
        });
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
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.AXA;
});

const isLIVA = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.RSA;
});

const isSUKOON = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.OIC;
});

// Computed property to check if driver name fields should be disabled
const isDriverNameFieldsDisabled = computed(() => {
  return additionalDriverDetailsForm.is_insured_and_driver_same === 1 || additionalDriverDetailsForm.is_insured_and_driver_same === '1';
});

// Computed property to check if driver name fields should be required
const isDriverNameFieldsRequired = computed(() => {
  return (additionalDriverDetailsForm.is_insured_and_driver_same === 0 || additionalDriverDetailsForm.is_insured_and_driver_same === '0');
});

// Watch for changes in is_insured_and_driver_same to clear driver names when they become disabled
watch(() => additionalDriverDetailsForm.is_insured_and_driver_same, (newValue) => {
  // If insured and driver are the same (1 or '1'), clear the driver name fields
  if ((newValue === 1 || newValue === '1') && !isSyncFromInsurer.value) {
    additionalDriverDetailsForm.driver_first_name = '';
    additionalDriverDetailsForm.driver_last_name = '';
  }
});

watch(() => props.insurerPortalSyncData, (driverDetails) => {
  if (driverDetails) {
    const fieldMappings = {
      driverDetails: {
        is_insured_and_driver_same: 'is_insured_and_driver_same',
        driver_first_name: 'driver_first_name',
        driver_last_name: 'driver_last_name',
        driver_dob: 'driver_dob',
        driver_gender: 'driver_gender',
        driver_license_number: 'driver_license_number',
        driver_license_expiry_date: 'license_expiry_date',
        driver_uae_driving_experience: 'uae_driving_experience',
      },
    };

    Object.entries(fieldMappings.driverDetails).forEach(([sourceKey, targetKey]) => {
      if (driverDetails?.[sourceKey]) {
        additionalDriverDetailsForm[targetKey] = driverDetails[sourceKey];
      }
    });
    if (driverDetails?.is_insured_and_driver_same === '1') {
      isSyncFromInsurer.value = true;
    }
  }
}, { deep: true });
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
        <x-field label="Is the Insured and Driver the same?" required>
          <x-select
            v-model="additionalDriverDetailsForm.is_insured_and_driver_same"
            :rules="[isRequired]"
            :options="[
              { value: '1', label: 'Yes' },
              { value: '0', label: 'No' }
            ]"
            placeholder="Select Is Insured and Driver Same"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <!-- Driver Name - disabled when insured and driver are the same -->
        <x-field
          label="Driver First Name"
          :required="isDriverNameFieldsRequired"
        >
          <x-input
            v-model="additionalDriverDetailsForm.driver_first_name"
            :rules="isDriverNameFieldsRequired ? [isRequired] : []"
            placeholder="Driver First Name"
            type="text"
            :disabled="isDriverNameFieldsDisabled || hasNotEditPermission"
          />
        </x-field>

        <x-field
          label="Driver Last Name"
          :required="isDriverNameFieldsRequired"
        >
          <x-input
            v-model="additionalDriverDetailsForm.driver_last_name"
            :rules="isDriverNameFieldsRequired ? [isRequired] : []"
            placeholder="Driver Last Name"
            type="text"
            :disabled="isDriverNameFieldsDisabled || hasNotEditPermission"
          />
        </x-field>

        <x-field label="Driver DOB" :required="isSUKOON">
          <DatePicker
            v-model="additionalDriverDetailsForm.driver_dob"
            :rules="isSUKOON ? [isRequired] : []"
            placeholder="Driver DOB"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="Driver Gender" :required="! isSUKOON">
          <x-select
            v-model="additionalDriverDetailsForm.driver_gender"
            :rules="(! isSUKOON) ? [isRequired] : []"
            :options="driverGenderOptions"
            placeholder="Select Driver Gender"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="Driver License Number" required>
          <x-input
            v-model="additionalDriverDetailsForm.driver_license_number"
            :rules="[isRequired]"
            placeholder="Driver License Number"
            type="text"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="License Issue Place" :required="isSUKOON">
          <x-select
            v-model="additionalDriverDetailsForm.license_issue_place"
            :rules="isSUKOON ? [isRequired] : []"
            :options="licenseIssuePlaceOptions"
            placeholder="Select License Issue Place"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="License Issue Date" :required="isSUKOON">
          <DatePicker
            v-model="additionalDriverDetailsForm.license_issue_date"
            :rules="isSUKOON ? [isRequired] : []"
            placeholder="License Issue Date"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="License Expiry Date" :required="! isLIVA">
          <DatePicker
            v-model="additionalDriverDetailsForm.license_expiry_date"
            placeholder="License Expiry Date"
            :rules="(! isLIVA) ? [isRequired] : []"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <!-- TODO: Required only if 'Driver same as Client?' is NO -->
        <x-field label="UAE Driving Experience" :required="isLIVA">
          <x-select
            filterable
            v-model="additionalDriverDetailsForm.uae_driving_experience"
            :rules="isLIVA ? [isRequired] : []"
            :options="drivingExperienceOptions"
            placeholder="Select UAE License Years"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="Home Country License Issuance" :required="! isGIG">
          <x-select
            filterable
            v-model="additionalDriverDetailsForm.home_country_license_issuance"
            :rules="(! isGIG) ? [isRequired] : []"
            placeholder="Select License Home Country"
            :options="nationalitiesOptions"
            class="w-full"
            :disabled="hasNotEditPermission"
          />
        </x-field>

        <x-field label="Home Country Driving Experience" :required="isLIVA">
          <x-select
            filterable
            v-model="additionalDriverDetailsForm.home_country_driving_experience"
            :rules="isLIVA ? [isRequired] : []"
            :options="drivingExperienceOptions"
            placeholder="Select Driver Years Home Country"
            :disabled="hasNotEditPermission"
          />
        </x-field>
      </dl>
      <div
        class="flex justify-end my-5 gap-x-2"
        v-if="hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)"
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
      <div
        class="flex justify-end my-5 gap-x-2"
        v-else
      >
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
