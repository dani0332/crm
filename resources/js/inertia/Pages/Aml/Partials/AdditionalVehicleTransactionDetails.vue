<script setup>
import { ref, computed, onMounted } from 'vue'
const { isRequired } = useRules();

const props = defineProps({
  formData: {
    type: Object,
    required: true
  }
});

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
            v-model="props.formData.rta_transaction_type" 
            :rules="[isRequired]"
            :options="rtaTransactionTypeOptions"
            placeholder="Select RTA Transaction Type"
            :error="props.formData.errors.rta_transaction_type"
          />
        </x-field>
        
        <x-field label="Plate Code" required>
          <x-input 
            v-model="props.formData.plate_code" 
            :rules="[isRequired]"
            placeholder="Plate Code"
              type="text" 
          />
        </x-field>
        
        <x-field label="Plate Number" required>
          <x-input 
            v-model="props.formData.plate_number" 
            :rules="[isRequired]"
            placeholder="Plate Number"
              type="text" 
            />
        </x-field>
        
        <x-field label="Traffic Code Number" required>
          <x-input 
            v-model="props.formData.traffic_code_number" 
            :rules="[isRequired]"
            placeholder="Traffic Code Number"
            type="text" 
          />
        </x-field>
        
        <x-field label="Chassis Number" required>
          <x-input 
            v-model="props.formData.chassis_number" 
            :rules="[isRequired]"
            placeholder="Chassis Number"
            type="text"
          />
        </x-field>
        
        <x-field label="Engine Number" required>
          <x-input 
            v-model="props.formData.engine_number" 
            :rules="[isRequired]"
            placeholder="Engine Number"
            type="text"
          />
        </x-field>
      
      <!-- Row 2 -->
        <x-field label="RTA Plate Category">
          <x-select 
            v-model="props.formData.rta_plate_category" 
            :options="rtaPlateCategoryOptions"
            placeholder="Select RTA Plate Category"
          />
        </x-field>
        
        <x-field label="Vehicle Color" required>
          <x-select 
            v-model="props.formData.vehicle_color" 
            :rules="[isRequired]"
            :options="vehicleColorOptions"
            placeholder="Select Vehicle Color"
          />
        </x-field>
        
        <x-field label="Plate Color">
          <x-select 
            v-model="props.formData.plate_color" 
            :options="plateColorOptions"
            placeholder="Select Plate Color"
          />
        </x-field>
        
        <x-field label="Bank Loan?" required>
          <x-select
            v-model="props.formData.bank_loan"
            :rules="[isRequired]"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' }
            ]"
            placeholder="Select Bank Loan"
          />
        </x-field>
        
        <x-field label="Bank Name">
          <ComboBox
            :single="true"
            v-model="props.formData.bank_name"
            placeholder="Select Bank Name"
            :options="bankNameOptions"
            :disabled="props.formData.bank_loan !== 1"
            class="w-full"
            :hasError="validateNationality"
          />
        </x-field>
      
      <!-- Row 3 - Dates -->
        <x-field label="First Registration Date" required>
          <DatePicker
            v-model="props.formData.first_registration_date"
            :rules="[isRequired]"
            placeholder="First Registration Date"
          />
        </x-field>
        
        <x-field label="Policy Effective Date" required>
          <DatePicker
            v-model="props.formData.policy_effective_date"
            :rules="[isRequired]"
            placeholder="Policy Effective Date"
          />
        </x-field>
        
        <x-field label="Policy Expiry Date" required>
          <DatePicker
            v-model="props.formData.policy_expiry_date"
            :rules="[isRequired]"
            placeholder="Policy Expiry Date"
          />
        </x-field>
        
        <x-field label="Certificate Start Date" required>
          <DatePicker
            v-model="props.formData.certificate_start_date"
            :rules="[isRequired]"
            placeholder="Certificate Start Date"
          />
        </x-field>
        
        <x-field label="Certificate End Date" required>
          <DatePicker
            v-model="props.formData.certificate_end_date"
            :rules="[isRequired]"
            placeholder="Certificate End Date"
          />
        </x-field>

      <!-- Row 4 - Mileage -->
        <x-field label="Annual Mileage Estimate" required>
          <x-select 
            v-model="props.formData.annual_mileage_estimate" 
            :rules="[isRequired]"
            :options="annualMileageEstimateOptions"
            placeholder="Select Annual Mileage Estimate"
          />
        </x-field>
      </dl>
    </div>
  </div>
</template>