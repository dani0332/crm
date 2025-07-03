<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  formData: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['save'])

// Reactive state
const isCollapsed = ref(false)

// Computed options for dropdowns
const rtaTransactionTypeOptions = computed(() => [
  { value: 'new_registration', label: 'New Registration' },
  { value: 'renewal', label: 'Renewal' },
  { value: 'transfer', label: 'Transfer' },
])

const rtaPlateCategoryOptions = computed(() => [
  { value: 'private', label: 'Private' },
  { value: 'commercial', label: 'Commercial' },
  { value: 'government', label: 'Government' },
])

const vehicleColorOptions = computed(() => [
  { value: 'green', label: 'Green' },
  { value: 'white', label: 'White' },
  { value: 'black', label: 'Black' },
  { value: 'blue', label: 'Blue' },
  { value: 'red', label: 'Red' },
  { value: 'silver', label: 'Silver' },
  { value: 'gray', label: 'Gray' },
])

const plateColorOptions = computed(() => [
  { value: 'alloy', label: 'Alloy' },
  { value: 'white', label: 'White' },
  { value: 'yellow', label: 'Yellow' },
  { value: 'green', label: 'Green' },
])

const bankNameOptions = computed(() => [
  { value: 'emirates_nbd', label: 'Emirates NBD' },
  { value: 'adcb', label: 'ADCB' },
  { value: 'fab', label: 'FAB' },
  { value: 'rakbank', label: 'RAKBANK' },
  { value: 'mashreq', label: 'Mashreq Bank' },
  { value: 'cbd', label: 'CBD' },
])

// Methods
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

const handleSave = () => {
  emit('save', props.formData)
}
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Vehicle and Transaction Details
      </h3>
      <button @click="toggleCollapse" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5 transform transition-transform" :class="{ 'rotate-180': isCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </button>
    </div>
    
    <div v-show="!isCollapsed">
      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
        <!-- Row 1 -->
        <x-field label="RTA Transaction Type">
          <x-select 
            v-model="props.formData.rtaTransactionType" 
            :options="rtaTransactionTypeOptions"
            placeholder="Select RTA Transaction Type"
          />
        </x-field>
        
        <x-field label="Plate Code">
          <x-input 
            v-model="props.formData.plateCode" 
            placeholder="Reg Txt"
            type="text"
          />
        </x-field>
        
        <x-field label="Plate Number">
          <x-input 
            v-model="props.formData.plateNumber" 
            placeholder="Reg Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Traffic Code Number">
          <x-input 
            v-model="props.formData.trafficCodeNumber" 
            placeholder="TCF Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Chassis Number">
          <x-input 
            v-model="props.formData.chassisNumber" 
            placeholder="Chassis Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Engine Number">
          <x-input 
            v-model="props.formData.engineNumber" 
            placeholder="Engine Number"
            type="text"
          />
        </x-field>
        
        <!-- Row 2 -->
        <x-field label="RTA Plate Category">
          <x-select 
            v-model="props.formData.rtaPlateCategory" 
            :options="rtaPlateCategoryOptions"
            placeholder="Select RTA Plate Category"
          />
        </x-field>
        
        <x-field label="Vehicle Color">
          <x-select 
            v-model="props.formData.vehicleColor" 
            :options="vehicleColorOptions"
            placeholder="Select Vehicle Color"
          />
        </x-field>
        
        <x-field label="Plate Color">
          <x-select 
            v-model="props.formData.plateColor" 
            :options="plateColorOptions"
            placeholder="Select Plate Color"
          />
        </x-field>
        
        <x-field label="Bank Loan?">
          <x-form-group v-model="props.formData.bankLoan">
            <x-radio value="yes" label="Yes" />
            <x-radio value="no" label="No" />
          </x-form-group>
        </x-field>
        
        <x-field label="Bank Name">
          <x-select 
            v-model="props.formData.bankName" 
            :options="bankNameOptions"
            :disabled="props.formData.bankLoan !== 'yes'"
            placeholder="Select Bank Name"
          />
        </x-field>
        
        <!-- Row 3 - Dates -->
        <x-field label="First Registration Date">
          <DatePicker
            v-model="props.formData.firstRegistrationDate"
            placeholder="First Registration Date"
          />
        </x-field>
        
        <x-field label="Policy Effective Date">
          <DatePicker
            v-model="props.formData.policyEffectiveDate"
            placeholder="Policy Effective Date"
          />
        </x-field>
        
        <x-field label="Policy Expiry Date">
          <DatePicker
            v-model="props.formData.policyExpiryDate"
            placeholder="Policy Expiry Date"
          />
        </x-field>
        
        <x-field label="Certificate Start Date">
          <DatePicker
            v-model="props.formData.certificateStartDate"
            placeholder="Certificate Start Date"
          />
        </x-field>
        
        <x-field label="Certificate End Date">
          <DatePicker
            v-model="props.formData.certificateEndDate"
            placeholder="Certificate End Date"
          />
        </x-field>
        
        <!-- Row 4 - Mileage -->
        <x-field label="Annual Mileage Estimate">
          <x-input 
            v-model="props.formData.annualMileageEstimate" 
            placeholder="Mileage"
            type="number"
          />
        </x-field>
      </dl>
      
      <!-- Save Button -->
      <div class="flex justify-end mt-6">
        <x-button 
          @click="handleSave"
          color="primary"
          size="sm"
        >
          Save
        </x-button>
      </div>
    </div>
  </div>
</template>