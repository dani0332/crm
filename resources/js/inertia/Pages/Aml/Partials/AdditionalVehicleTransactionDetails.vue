<script setup>
import { ref, computed } from 'vue'
const { isRequired } = useRules();

const props = defineProps({
  formData: {
    type: Object,
    required: true
  }
})

const page = usePage();
const lookups = page.props.lookups;

// Computed options for dropdowns
const rtaTransactionTypeOptions = computed(() => {
  return useGenerateOptions(lookups.rta_transaction_type, 'code', 'text');
});


const rtaPlateCategoryOptions = computed(() => {
  return useGenerateOptions(lookups.rta_plate_category, 'code', 'text');
});

const vehicleColorOptions = computed(() => {
  return useGenerateOptions(lookups.vehicle_color, 'code', 'text');
});

const plateColorOptions = computed(() => {
  return useGenerateOptions(lookups.vehicle_color, 'code', 'text');
});

const bankNameOptions = computed(() => {
  return useGenerateOptions(lookups.bank_name, 'code', 'text');
});

const annualMileageEstimateOptions = computed(() => {
  return useGenerateOptions(lookups.annual_mileage_estimate, 'code', 'text');
});
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Vehicle and Transaction Details
      </h3>
    </div>
    
    <div>
      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
      <!-- Row 1 -->
        <x-field label="RTA Transaction Type" required>
          <x-select 
            v-model="props.formData.rtaTransactionType" 
            :rules="[isRequired]"
            :options="rtaTransactionTypeOptions"
            placeholder="Select RTA Transaction Type"
            :error="props.formData.errors.rtaTransactionType"
          />
        </x-field>
        
        <x-field label="Plate Code" required>
          <x-input 
            v-model="props.formData.plateCode" 
            :rules="[isRequired]"
            placeholder="Plate Code"
            type="text"
          />
        </x-field>
        
        <x-field label="Plate Number" required>
          <x-input 
            v-model="props.formData.plateNumber" 
            :rules="[isRequired]"
            placeholder="Plate Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Traffic Code Number" required>
          <x-input 
            v-model="props.formData.trafficCodeNumber" 
            :rules="[isRequired]"
            placeholder="Traffic Code Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Chassis Number" required>
          <x-input 
            v-model="props.formData.chassisNumber" 
            :rules="[isRequired]"
            placeholder="Chassis Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Engine Number" required>
          <x-input 
            v-model="props.formData.engineNumber" 
            :rules="[isRequired]"
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
        
        <x-field label="Vehicle Color" required>
          <x-select 
            v-model="props.formData.vehicleColor" 
            :rules="[isRequired]"
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
        
        <x-field label="Bank Loan?" required>
          <x-form-group v-model="props.formData.bankLoan" :rules="[isRequired]">
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
        <x-field label="First Registration Date" required>
          <DatePicker
            v-model="props.formData.firstRegistrationDate"
            :rules="[isRequired]"
            placeholder="First Registration Date"
          />
        </x-field>
        
        <x-field label="Policy Effective Date" required>
          <DatePicker
            v-model="props.formData.policyEffectiveDate"
            :rules="[isRequired]"
            placeholder="Policy Effective Date"
          />
        </x-field>
        
        <x-field label="Policy Expiry Date" required>
          <DatePicker
            v-model="props.formData.policyExpiryDate"
            :rules="[isRequired]"
            placeholder="Policy Expiry Date"
          />
        </x-field>
        
        <x-field label="Certificate Start Date" required>
          <DatePicker
            v-model="props.formData.certificateStartDate"
            :rules="[isRequired]"
            placeholder="Certificate Start Date"
          />
        </x-field>
        
        <x-field label="Certificate End Date" required>
          <DatePicker
            v-model="props.formData.certificateEndDate"
            :rules="[isRequired]"
            placeholder="Certificate End Date"
          />
        </x-field>
      
      <!-- Row 4 - Mileage -->
        <x-field label="Annual Mileage Estimate" required>
          <x-select 
            v-model="props.formData.annualMileageEstimate" 
            :rules="[isRequired]"
            :options="annualMileageEstimateOptions"
            placeholder="Select Annual Mileage Estimate"
          />
        </x-field>
      </dl>
    </div>
  </div>
</template>