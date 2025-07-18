<script setup>
import { ref, computed, onMounted } from 'vue'
const { isRequired } = useRules();

const page = usePage();
const notification = useToast();
const lookups = page.props.lookups;

// Computed options for dropdowns
const rtaTransactionTypeOptions = computed(() => {
  return useGenerateOptions(lookups?.rta_transaction_type ?? [], 'code', 'text');
});

const rtaPlateCategoryOptions = computed(() => {
  return useGenerateOptions(lookups?.rta_plate_category ?? [], 'code', 'text');
});

const vehicleColorOptions = computed(() => {
  return useGenerateOptions(lookups?.vehicle_color ?? [], 'code', 'text');
});

const plateColorOptions = computed(() => {
  return useGenerateOptions(lookups?.vehicle_color ?? [], 'code', 'text');
});

const bankNameOptions = computed(() => {
  return useGenerateOptions(lookups?.bank_name ?? [], 'code', 'text');
});

const annualMileageEstimateOptions = computed(() => {
  return useGenerateOptions(lookups?.annual_mileage_estimate ?? [], 'code', 'text');
});

const rules = {
  chassisNumberCheck: v => {
    const regex = /^[A-Za-z0-9]+$/;
    const lengthValid = v?.length >= 8 && v?.length <= 17;
    const isAlphanumeric = regex.test(v);
    return (
      (lengthValid && isAlphanumeric) ||
      'The entered value does not meet the required length of 8 to 17 characters'
    );
  },
};

const additionalVehicleTransactionDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  additional_vehicle_transaction_details: true,
  rta_transaction_type: page.props.quoteRequest?.car_quote_request_detail?.rta_transaction_type?.toString() ?? '',
  plate_code: page.props.quoteRequest?.car_quote_request_detail?.plate_code ?? '',
  plate_number: page.props.quoteRequest?.car_quote_request_detail?.plate_number ?? '',
  traffic_code_number: page.props.quoteRequest?.car_quote_request_detail?.traffic_code_number ?? '',
  chassis_number: page.props.quoteRequest?.car_quote_request_detail?.chassis_number ?? '',
  engine_number: page.props.quoteRequest?.car_quote_request_detail?.engine_number ?? '',
  rta_plate_category: page.props.quoteRequest?.car_quote_request_detail?.rta_plate_category ?? '',
  vehicle_color: page.props.quoteRequest?.car_quote_request_detail?.vehicle_color ?? '',
  plate_color: page.props.quoteRequest?.car_quote_request_detail?.plate_color ?? '',
  bank_loan: page.props.quoteRequest?.car_quote_request_detail?.bank_loan ?? '',
  bank_name: page.props.quoteRequest?.car_quote_request_detail?.bank_name ?? '',
  first_registration_date: page.props.quoteRequest?.car_quote_request_detail?.first_registration_date ?? '',
  policy_effective_date: page.props.quoteRequest?.car_quote_request_detail?.policy_effective_date ?? '',
  policy_expiry_date: page.props.quoteRequest?.car_quote_request_detail?.policy_expiry_date ?? '',
  certificate_start_date: page.props.quoteRequest?.car_quote_request_detail?.certificate_start_date ?? '',
  certificate_end_date: page.props.quoteRequest?.car_quote_request_detail?.certificate_end_date ?? '',
  annual_mileage_estimate: page.props.quoteRequest?.car_quote_request_detail?.annual_mileage_estimate?.toString() ?? '',
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
      additionalVehicleTransactionDetailsForm.chassis_number?.length >= 8 &&
      additionalVehicleTransactionDetailsForm.chassis_number?.length <= 17;
    const isAlphanumeric = regex.test(additionalVehicleTransactionDetailsForm.chassis_number);
    if (
      additionalVehicleTransactionDetailsForm.chassis_number &&
      (!lengthValid || !isAlphanumeric)
    ) {
      additionalVehicleTransactionDetailsForm.errors.chassis_number =
        'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.';
      event.preventDefault();
      return true;
    } else {
      additionalVehicleTransactionDetailsForm.clearErrors('chassis_number');
      return false;
    }
  }
};

const submitAdditionalVehicleTransactionDetailsForm = (isValid) => {
  if (isValid) {
    additionalVehicleTransactionDetailsForm.processing = true;
    axios.post('/kyc/update-additional-vehicle-driver-details', additionalVehicleTransactionDetailsForm).then(response => {
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
    }).finally(() => {
      additionalVehicleTransactionDetailsForm.processing = false;
    });
  } 
}

</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Vehicle and Transaction Details
      </h3>
    </div>
    <div>
    <x-form @submit="submitAdditionalVehicleTransactionDetailsForm" :auto-focus="false">
      <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
          <x-field label="RTA Transaction Type" required>
            <x-select 
              v-model="additionalVehicleTransactionDetailsForm.rta_transaction_type" 
              :rules="[isRequired]"
              :options="rtaTransactionTypeOptions"
              placeholder="Select RTA Transaction Type"
            />
          </x-field>
          
          <x-field label="Plate Code" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
            <x-input 
              v-model="additionalVehicleTransactionDetailsForm.plate_code" 
              :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              placeholder="Plate Code"
              type="text"
            />
          </x-field>
          
          <x-field label="Plate Number" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
            <x-input 
              v-model="additionalVehicleTransactionDetailsForm.plate_number" 
              :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              placeholder="Plate Number"
              type="text"
            />
          </x-field>
          
          <x-field label="Traffic Code Number" required>
            <x-input 
              v-model="additionalVehicleTransactionDetailsForm.traffic_code_number" 
              :rules="[isRequired]"
              placeholder="Traffic Code Number"
              type="text"
            />
          </x-field>
          
          <x-field label="Chassis Number" required>
            <x-input 
              v-model="additionalVehicleTransactionDetailsForm.chassis_number" 
              :rules="[isRequired, rules.chassisNumberCheck]"
              @keypress="chassisNumberValidate('keypress')"
              @blur="chassisNumberValidate('blur')"
              placeholder="Chassis Number"
              type="text"
              :error="additionalVehicleTransactionDetailsForm.errors.chassis_number"
            />
          </x-field>
          
          <x-field label="Engine Number" required>
            <x-input 
              v-model="additionalVehicleTransactionDetailsForm.engine_number" 
              :rules="[isRequired]"
              placeholder="Engine Number"
              type="text"
            />
          </x-field>
        
          <x-field label="RTA Plate Category">
            <x-select 
              v-model="additionalVehicleTransactionDetailsForm.rta_plate_category" 
              :options="rtaPlateCategoryOptions"
              placeholder="Select RTA Plate Category"
            />
          </x-field>
          
          <x-field label="Vehicle Color" required>
            <x-select 
              v-model="additionalVehicleTransactionDetailsForm.vehicle_color" 
              :options="vehicleColorOptions"
              :rules="[isRequired]"
              placeholder="Select Vehicle Color"
            />
          </x-field>
          
          <x-field label="Plate Color" :required="page.props.insuranceProviderCodeEnum.AXA == page.props.insuranceProviderCodeEnum.AXA">
            <x-select 
              v-model="additionalVehicleTransactionDetailsForm.plate_color" 
              :options="plateColorOptions"
              :rules="page.props.insuranceProviderCodeEnum.AXA == page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              placeholder="Select Plate Color"
            />
          </x-field>
          
          <x-field label="Bank Loan?" required>
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.bank_loan"
              :rules="[isRequired]"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' }
              ]"
              placeholder="Select Bank Loan"
            />
          </x-field>
          
          <x-field label="Bank Name" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
            <ComboBox
              :single="true"
              v-model="additionalVehicleTransactionDetailsForm.bank_name"
              placeholder="Select Bank Name"
              :options="bankNameOptions"
              :disabled="additionalVehicleTransactionDetailsForm.bank_loan !== 1"
              :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              class="w-full"
            />
          </x-field>
        
          <x-field label="First Registration Date" required>
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.first_registration_date"
              :rules="[isRequired]"
              placeholder="First Registration Date"
            />
          </x-field>
          
          <x-field label="Policy Effective Date" required>
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_effective_date"
              :rules="[isRequired]"
              placeholder="Policy Effective Date"
            />
          </x-field>
          
          <x-field label="Policy Expiry Date" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_expiry_date"
              :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              placeholder="Policy Expiry Date"
            />
          </x-field>
          
          <x-field label="Certificate Start Date" required>
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_start_date"
              :rules="[isRequired]"
              placeholder="Certificate Start Date"
            />
          </x-field>
          
          <x-field label="Certificate End Date" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_end_date"
              :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              placeholder="Certificate End Date"
            />
          </x-field>

          <x-field label="Annual Mileage Estimate" :required="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA">
            <x-input 
              v-if="page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.AXA"
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate" 
              :rules="page.props.insuranceProviderCodeEnum.AXA !== page.props.insuranceProviderCodeEnum.AXA ? [isRequired] : []"
              placeholder="Select Annual Mileage Estimate"
              type="text"
            />
            <x-select 
              v-else
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate" 
              :rules="[isRequired]"
              :options="annualMileageEstimateOptions"
              placeholder="Select Annual Mileage Estimate"
            />
          </x-field>
        </dl>
        <div class="flex justify-end my-5 gap-x-2">
          <x-button
            size="sm"
            color="orange"
            type="submit"
            class="px-6"
            :loading="additionalVehicleTransactionDetailsForm.processing"
          >
            Save
          </x-button>
        </div>
      </x-form>
    </div>
  </div>
</template>