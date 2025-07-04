<script setup>
import { ref, computed } from 'vue'
const { isRequired } = useRules();

// Props
const props = defineProps({
  formData: {
    type: Object,
    required: true
  }
})

const page = usePage();
const lookups = page.props.lookups;

// Computed options for dropdowns
const driverGenderOptions = computed(() => [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
])

const licenseIssuePlaceOptions = computed(() => {
  return useGenerateOptions(lookups.issuance_place, 'code', 'text');
});

const nationalitiesOptions = computed(() => {
  return useGenerateOptions(page.props.nationalities, 'code', 'text');
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
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Driver Details
      </h3>
    </div>
    
    <div>
      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
        <!-- Is Insured and Driver Same -->
        <x-field label="Is the Insured and Driver the same?" required>
          <x-select 
            v-model="props.formData.is_insured_and_driver_same" 
            :rules="[isRequired]"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' }
            ]"
            placeholder="Select Is Insured and Driver Same"
          />
        </x-field>
        
        <!-- Driver Name -->
        <x-field label="Driver First Name" required>
          <x-input 
            v-model="props.formData.driver_first_name" 
            :rules="[isRequired]"
            placeholder="Driver First Name"
            type="text"
          />
        </x-field>
        
        <x-field label="Driver Last Name" required>
          <x-input 
            v-model="props.formData.driver_last_name" 
            :rules="[isRequired]"
            placeholder="Driver Last Name"
            type="text"
          />
        </x-field>
        
        <!-- Driver DOB -->
        <x-field label="Driver DOB" required>
          <DatePicker
            v-model="props.formData.driver_dob"
            :rules="[isRequired]"
            placeholder="Driver DOB"
          />
        </x-field>
        
        <!-- Driver Gender -->
        <x-field label="Driver Gender" required>
          <x-select 
            v-model="props.formData.driver_gender" 
            :rules="[isRequired]"
            :options="driverGenderOptions"
            placeholder="Select Driver Gender"
          />
        </x-field>
        
        <!-- Driver License Number -->
        <x-field label="Driver License Number" required>
          <x-input 
            v-model="props.formData.driver_license_number" 
            :rules="[isRequired]"
            placeholder="Driver License Number"
            type="text"
          />
        </x-field>
        
        <!-- License Issue Place -->
        <x-field label="License Issue Place">
          <x-select 
            v-model="props.formData.license_issue_place" 
            :options="licenseIssuePlaceOptions"
            placeholder="Select License Issue Place"
          />
        </x-field>
        
        <!-- License Issue Date -->
        <x-field label="License Issue Date">
          <DatePicker
            v-model="props.formData.license_issue_date"
            placeholder="License Issue Date"
          />
        </x-field>
        
        <!-- License Expiry Date -->
        <x-field label="License Expiry Date">
          <DatePicker
            v-model="props.formData.license_expiry_date"
            placeholder="License Expiry Date"
          />
        </x-field>
        
        <!-- UAE Driving Experience -->
        <!-- TODO: Required only if 'Driver same as Client?' is NO -->
        <x-field label="UAE Driving Experience" required>
          <x-select 
            v-model="props.formData.uae_driving_experience" 
            :rules="[isRequired]"
            :options="drivingExperienceOptions"
            placeholder="Select UAE License Years"
          />
        </x-field>
        
        <!-- Home Country License Issuance -->
        <x-field label="Home Country License Issuance" required>
          <ComboBox
            :single="true"
            v-model="props.formData.home_country_license_issuance"
            :rules="[isRequired]"
            placeholder="Select License Home Country"
            :options="nationalitiesOptions"
            class="w-full"
          />
        </x-field>
        
        <!-- Home Country Driving Experience -->
        <x-field label="Home Country Driving Experience" required>
          <x-select 
            v-model="props.formData.home_country_driving_experience" 
            :rules="[isRequired]"
            :options="drivingExperienceOptions"
            placeholder="Select Driver Years Home Country"
          />
        </x-field>
      </dl>
    </div>
  </div>
</template> 