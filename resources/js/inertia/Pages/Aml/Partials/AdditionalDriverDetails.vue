<script setup>
import { ref, computed } from 'vue'

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

const uaeDrivingExperienceOptions = computed(() => [
  { value: '0', label: 'No Experience' },
  { value: '1', label: '1 Year' },
  { value: '2', label: '2 Years' },
  { value: '3', label: '3 Years' },
  { value: '4', label: '4 Years' },
  { value: '5', label: '5 Years' },
  { value: '6', label: '6 Years' },
  { value: '7', label: '7 Years' },
  { value: '8', label: '8 Years' },
  { value: '9', label: '9 Years' },
  { value: '10+', label: '10+ Years' },
])

const homeCountryDrivingExperienceOptions = computed(() => [
  { value: '0', label: 'No Experience' },
  { value: '1', label: '1 Year' },
  { value: '2', label: '2 Years' },
  { value: '3', label: '3 Years' },
  { value: '4', label: '4 Years' },
  { value: '5', label: '5 Years' },
  { value: '6', label: '6 Years' },
  { value: '7', label: '7 Years' },
  { value: '8', label: '8 Years' },
  { value: '9', label: '9 Years' },
  { value: '10+', label: '10+ Years' },
])

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
        <x-field label="Is the Insured and Driver the same?">
          <x-form-group v-model="props.formData.isInsuredAndDriverSame">
            <x-radio value="yes" label="Yes" />
            <x-radio value="no" label="No" />
          </x-form-group>
        </x-field>
        
        <!-- Driver Name -->
        <x-field label="Driver First Name">
          <x-input 
            v-model="props.formData.driverFirstName" 
            placeholder="Driver First Name"
            type="text"
          />
        </x-field>
        
        <x-field label="Driver Last Name">
          <x-input 
            v-model="props.formData.driverLastName" 
            placeholder="Driver Last Name"
            type="text"
          />
        </x-field>
        
        <!-- Driver DOB -->
        <x-field label="Driver DOB">
          <DatePicker
            v-model="props.formData.driverDob"
            placeholder="Driver DOB"
          />
        </x-field>
        
        <!-- Driver Gender -->
        <x-field label="Driver Gender">
          <x-select 
            v-model="props.formData.driverGender" 
            :options="driverGenderOptions"
            placeholder="Select Driver Gender"
          />
        </x-field>
        
        <!-- Driver License Number -->
        <x-field label="Driver License Number">
          <x-input 
            v-model="props.formData.driverLicenseNumber" 
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
        <x-field label="UAE Driving Experience">
          <x-select 
            v-model="props.formData.uaeDrivingExperience" 
            :options="uaeDrivingExperienceOptions"
            placeholder="Select UAE License Years"
          />
        </x-field>
        
        <!-- Home Country License Issuance -->
        <x-field label="Home Country License Issuance">
          <x-input 
            v-model="props.formData.homeCountryLicenseIssuance" 
            placeholder="License Home Country"
            type="text"
          />
        </x-field>
        
        <!-- Home Country Driving Experience -->
        <x-field label="Home Country Driving Experience">
          <x-select 
            v-model="props.formData.homeCountryDrivingExperience" 
            :options="homeCountryDrivingExperienceOptions"
            placeholder="Select Driver Years Home Country"
          />
        </x-field>
      </dl>
    </div>
  </div>
</template> 