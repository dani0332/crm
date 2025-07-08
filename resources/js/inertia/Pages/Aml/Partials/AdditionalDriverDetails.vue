<script setup>
import { ref, computed } from 'vue'
const { isRequired } = useRules();

const page = usePage();
const notification = useToast();

const lookups = page.props.lookups;

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

const additionalDriverDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  is_insured_and_driver_same: page.props.quoteRequest?.car_quote_request_detail?.is_insured_and_driver_same ?? '',
  driver_first_name: page.props.quoteRequest?.car_quote_request_detail?.driver_first_name ?? '',
  driver_last_name: page.props.quoteRequest?.car_quote_request_detail?.driver_last_name ?? '',
  driver_dob: page.props.quoteRequest?.car_quote_request_detail?.driver_dob ?? '',
  driver_gender: page.props.quoteRequest?.car_quote_request_detail?.driver_gender ?? '',
  driver_license_number: page.props.quoteRequest?.car_quote_request_detail?.driver_license_number ?? '',
  license_issue_place: page.props.quoteRequest?.car_quote_request_detail?.license_issue_place ?? '',
  license_issue_date: page.props.quoteRequest?.car_quote_request_detail?.license_issue_date ?? '',
  license_expiry_date: page.props.quoteRequest?.car_quote_request_detail?.license_expiry_date ?? '',
  uae_driving_experience: page.props.quoteRequest?.car_quote_request_detail?.uae_driving_experience ?? '',
  home_country_license_issuance: page.props.quoteRequest?.car_quote_request_detail?.home_country_license_issuance ?? '',
  home_country_driving_experience: page.props.quoteRequest?.car_quote_request_detail?.home_country_driving_experience ?? '',
});

const submitAdditionalDriverDetailsForm = (isValid) => {
  if (isValid) {
    additionalDriverDetailsForm.processing = true;
    axios.post('/kyc/update-additional-vehicle-driver-details', additionalDriverDetailsForm).then(response => {
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
    }).catch(error => {
      notification.error({
        title: error.response.data.message,
        position: 'top',
      });
    }).finally(() => {
      additionalDriverDetailsForm.processing = false;
    });
  } 
};
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
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' }
            ]"
            placeholder="Select Is Insured and Driver Same"
          />
        </x-field>
        
        <!-- Driver Name -->
        <x-field label="Driver First Name" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
          <x-input 
            v-model="additionalDriverDetailsForm.driver_first_name" 
            :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
            placeholder="Driver First Name"
            type="text"
          />
        </x-field>
        
        <x-field label="Driver Last Name" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
          <x-input 
            v-model="additionalDriverDetailsForm.driver_last_name" 
            :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
            placeholder="Driver Last Name"
            type="text"
          />
        </x-field>
        
        <x-field label="Driver DOB" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
          <DatePicker
            v-model="additionalDriverDetailsForm.driver_dob"
            :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
            placeholder="Driver DOB"
          />
        </x-field>
        
        <x-field label="Driver Gender" required>
          <x-select 
            v-model="additionalDriverDetailsForm.driver_gender" 
            :rules="[isRequired]"
            :options="driverGenderOptions"
            placeholder="Select Driver Gender"
          />
        </x-field>
        
        <x-field label="Driver License Number" required>
          <x-input 
            v-model="additionalDriverDetailsForm.driver_license_number" 
            :rules="[isRequired]"
            placeholder="Driver License Number"
            type="text"
          />
        </x-field>
        
        <x-field label="License Issue Place">
          <x-select 
            v-model="additionalDriverDetailsForm.license_issue_place" 
            :options="licenseIssuePlaceOptions"
            placeholder="Select License Issue Place"
          />
        </x-field>
        
        <x-field label="License Issue Date">
          <DatePicker
            v-model="additionalDriverDetailsForm.license_issue_date"
            placeholder="License Issue Date"
          />
        </x-field>
        
        <x-field label="License Expiry Date" :required="page.props.insuranceProviderCodeEnum.AXA === page.props.insuranceProviderCodeEnum.AXA">
          <DatePicker
            v-model="additionalDriverDetailsForm.license_expiry_date"
            placeholder="License Expiry Date"
            :rules="page.props.insuranceProviderCodeEnum.AXA === page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
          />
        </x-field>
        
        <!-- TODO: Required only if 'Driver same as Client?' is NO -->
        <x-field label="UAE Driving Experience" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
          <x-select 
            v-model="additionalDriverDetailsForm.uae_driving_experience" 
            :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
            :options="drivingExperienceOptions"
            placeholder="Select UAE License Years"
          />
        </x-field>
        
        <x-field label="Home Country License Issuance" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
          <ComboBox
            :single="true"
            v-model="additionalDriverDetailsForm.home_country_license_issuance"
            :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
            placeholder="Select License Home Country"
            :options="nationalitiesOptions"
            class="w-full"
          />
        </x-field>
        
        <x-field label="Home Country Driving Experience" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
          <x-select 
            v-model="additionalDriverDetailsForm.home_country_driving_experience" 
            :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
            :options="drivingExperienceOptions"
            placeholder="Select Driver Years Home Country"
          />
        </x-field>
      </dl>
      <div class="flex justify-end my-5 gap-x-2">
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
    </x-form>
  </div>
</template> 