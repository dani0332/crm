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

// Reactive state
const isCollapsed = ref(false)

// Computed options for dropdowns
const driverGenderOptions = computed(() => [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
])

const licenseIssuePlaceOptions = computed(() => [
  { value: 'abu_dhabi', label: 'Abu Dhabi' },
  { value: 'dubai', label: 'Dubai' },
  { value: 'sharjah', label: 'Sharjah' },
  { value: 'ajman', label: 'Ajman' },
  { value: 'fujairah', label: 'Fujairah' },
  { value: 'ras_al_khaimah', label: 'Ras Al Khaimah' },
  { value: 'umm_al_quwain', label: 'Umm Al Quwain' },
])

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

// Methods
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Driver Details
      </h3>
      <button @click="toggleCollapse" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5 transform transition-transform" :class="{ 'rotate-180': isCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </button>
    </div>
    
    <div v-show="!isCollapsed">
      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
        <!-- Is Insured and Driver Same -->
        <x-field label="Is the Insured and Driver the same?" required>
          <x-form-group v-model="props.formData.isInsuredAndDriverSame" :rules="[isRequired]">
            <x-radio value="yes" label="Yes" />
            <x-radio value="no" label="No" />
          </x-form-group>
        </x-field>
        
        <!-- Driver Name -->
        <x-field label="Driver First Name" required>
          <x-input 
            v-model="props.formData.driverFirstName" 
            :rules="[isRequired]"
            placeholder="Driver First Name"
            type="text"
          />
        </x-field>
        
        <x-field label="Driver Last Name" required>
          <x-input 
            v-model="props.formData.driverLastName" 
            :rules="[isRequired]"
            placeholder="Driver Last Name"
            type="text"
          />
        </x-field>
        
        <!-- Driver DOB -->
        <x-field label="Driver DOB" required>
          <DatePicker
            v-model="props.formData.driverDob"
            :rules="[isRequired]"
            placeholder="Driver DOB"
          />
        </x-field>
        
        <!-- Driver Gender -->
        <x-field label="Driver Gender" required>
          <x-select 
            v-model="props.formData.driverGender" 
            :rules="[isRequired]"
            :options="driverGenderOptions"
            placeholder="Select Driver Gender"
          />
        </x-field>
        
        <!-- Driver License Number -->
        <x-field label="Driver License Number" required>
          <x-input 
            v-model="props.formData.driverLicenseNumber" 
            :rules="[isRequired]"
            placeholder="Driver License Number"
            type="text"
          />
        </x-field>
        
        <!-- License Issue Place -->
        <x-field label="License Issue Place">
          <x-select 
            v-model="props.formData.licenseIssuePlace" 
            :options="licenseIssuePlaceOptions"
            placeholder="Select License Issue Place"
          />
        </x-field>
        
        <!-- License Issue Date -->
        <x-field label="License Issue Date">
          <DatePicker
            v-model="props.formData.licenseIssueDate"
            placeholder="License Issue Date"
          />
        </x-field>
        
        <!-- License Expiry Date -->
        <x-field label="License Expiry Date">
          <DatePicker
            v-model="props.formData.licenseExpiryDate"
            placeholder="License Expiry Date"
          />
        </x-field>
        
        <!-- UAE Driving Experience -->
        <!-- TODO: Required only if 'Driver same as Client?' is NO -->
        <x-field label="UAE Driving Experience" required>
          <x-select 
            v-model="props.formData.uaeDrivingExperience" 
            :rules="[isRequired]"
            :options="drivingExperienceOptions"
            placeholder="Select UAE License Years"
          />
        </x-field>
        
        <!-- Home Country License Issuance -->
        <x-field label="Home Country License Issuance" required>
          <x-input 
            v-model="props.formData.homeCountryLicenseIssuance" 
            :rules="[isRequired]"
            placeholder="License Home Country"
            type="text"
          />
        </x-field>
        
        <!-- Home Country Driving Experience -->
        <x-field label="Home Country Driving Experience" required>
          <x-select 
            v-model="props.formData.homeCountryDrivingExperience" 
            :rules="[isRequired]"
            :options="drivingExperienceOptions"
            placeholder="Select Driver Years Home Country"
          />
        </x-field>
      </dl>
    </div>
  </div>
</template> 