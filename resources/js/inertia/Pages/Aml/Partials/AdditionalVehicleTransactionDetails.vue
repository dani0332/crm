<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
const { isRequired } = useRules();

const props = defineProps({
  insurerPortalSyncData: {
    type: Object,
    default: null
  },
});

const page = usePage();
const notification = useToast();
const lookups = page.props.lookups;
const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

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

const plateCodeOptions = computed(() => {
  return useGenerateOptions(lookups?.plate_code ?? [], 'code', 'text');
});

const carQuoteRequestDetail = computed(() => {
  return page.props.quoteRequest?.car_quote_request_detail;
});

const additionalVehicleTransactionDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  additional_vehicle_transaction_details: true,
  rta_transaction_type: carQuoteRequestDetail.value?.rta_transaction_type?.toString() ?? '',
  plate_code: carQuoteRequestDetail.value?.plate_code ?? '',
  plate_number: carQuoteRequestDetail.value?.plate_number ?? '',
  traffic_code_number: carQuoteRequestDetail.value?.traffic_code_number ?? '',
  chassis_number: carQuoteRequestDetail.value?.chassis_number ?? '',
  engine_number: carQuoteRequestDetail.value?.engine_number ?? '',
  rta_plate_category: carQuoteRequestDetail.value?.rta_plate_category ?? '',
  vehicle_color: carQuoteRequestDetail.value?.vehicle_color ?? '',
  plate_color: carQuoteRequestDetail.value?.plate_color ?? '',
  bank_loan: carQuoteRequestDetail.value?.bank_loan?.toString() ?? '',
  bank_name: carQuoteRequestDetail.value?.bank_name ?? '',
  first_registration_date: carQuoteRequestDetail.value?.first_registration_date ?? '',
  policy_effective_date: carQuoteRequestDetail.value?.policy_effective_date ?? '',
  policy_expiry_date: carQuoteRequestDetail.value?.policy_expiry_date ?? '',
  certificate_start_date: carQuoteRequestDetail.value?.certificate_start_date ?? '',
  certificate_end_date: carQuoteRequestDetail.value?.certificate_end_date ?? '',
  annual_mileage_estimate: carQuoteRequestDetail.value?.annual_mileage_estimate?.toString() ?? '',
});

const hasNotEditPermission = computed(() => {
  return !hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)
});

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
    }).catch(error => {
      const flash_messages = error.response.data.errors;
      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    }).finally(() => {
      additionalVehicleTransactionDetailsForm.processing = false;
    });
  }
}

watch(() => props.insurerPortalSyncData, (vehicleTransactionDetails) => {
  if (vehicleTransactionDetails) {
    const fieldMappings = {
      vehicleTransactionDetails: {
        rta_transaction_type: 'rta_transaction_type',
        plate_code: 'plate_code',
        plate_number: 'plate_number',
        traffic_code_number: 'traffic_code_number',
        chassis_number: 'chassis_number',
        engine_number: 'engine_number',
        rta_plate_category: 'rta_plate_category',
        vehicle_color: 'vehicle_color',
        plate_color: 'plate_color',
        bank_loan: 'bank_loan',
        bank_name: 'bank_name',
        first_registration_date: 'first_registration_date',
        policy_effective_date: 'policy_effective_date',
        policy_expiry_date: 'policy_expiry_date',
        certificate_start_date: 'certificate_start_date',
        certificate_end_date: 'certificate_end_date',
        annual_mileage_estimate: 'annual_mileage_estimate',
      },
    };

    Object.entries(fieldMappings.vehicleTransactionDetails).forEach(([sourceKey, targetKey]) => {
      if (vehicleTransactionDetails?.[sourceKey]) {
        additionalVehicleTransactionDetailsForm[targetKey] = vehicleTransactionDetails[sourceKey];
      }
    });
  }
}, { deep: true });

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

const isGIG = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.AXA;
});

const isLIVA = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.RSA;
});

const isSUKOON = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.OIC;
});

// for new business only.
watch(
  () => additionalVehicleTransactionDetailsForm.policy_effective_date,
  (newVal) => {
  if (isLIVA.value && newVal) {
    // Add 13 months to policy_effective_date for policy_expiry_date
    const effectiveDate = new Date(newVal);
    const expiryDate = new Date(effectiveDate);
    expiryDate.setMonth(expiryDate.getMonth() + 13);

    // Format date as YYYY-MM-DD for the form
    const formattedExpiryDate = expiryDate.toISOString().split('T')[0];
    additionalVehicleTransactionDetailsForm.policy_expiry_date = formattedExpiryDate;
    additionalVehicleTransactionDetailsForm.certificate_end_date = formattedExpiryDate;
    additionalVehicleTransactionDetailsForm.certificate_start_date = newVal;
  }
});

const registrationNoValidation = ref(false);

watch(
  () => additionalVehicleTransactionDetailsForm.rta_transaction_type,
  (newVal) => {
    if (isLIVA.value && newVal) {
      if (newVal == '10') {
        registrationNoValidation.value = false
      } else {
        registrationNoValidation.value = true;
      }
    }
  }
);
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
              filterable
              v-model="additionalVehicleTransactionDetailsForm.rta_transaction_type"
              :rules="[isRequired]"
              :options="rtaTransactionTypeOptions"
              placeholder="Select RTA Transaction Type"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Plate Code" :required="! isGIG && registrationNoValidation">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.plate_code"
              :rules="(! isGIG && registrationNoValidation) ? [isRequired] : []"
              placeholder="Select Plate Code"
              :options="plateCodeOptions"
              class="w-full"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Plate Number" :required="! isGIG && registrationNoValidation">
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.plate_number"
              :rules="(! isGIG && registrationNoValidation) ? [isRequired] : []"
              placeholder="Plate Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Traffic Code Number" required>
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.traffic_code_number"
              :rules="[isRequired]"
              placeholder="Traffic Code Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Chassis Number" required>
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.chassis_number"
              :rules="[isRequired]"
              @keypress="chassisNumberValidate('keypress')"
              @blur="chassisNumberValidate('blur')"
              placeholder="Chassis Number"
              type="text"
              :error="additionalVehicleTransactionDetailsForm.errors.chassis_number"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Engine Number" :required="! isSUKOON">
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.engine_number"
              :rules="(! isSUKOON) ? [isRequired] : []"
              placeholder="Engine Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="RTA Plate Category" :required="isGIG">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.rta_plate_category"
              :rules="isGIG ? [isRequired] : []"
              :options="rtaPlateCategoryOptions"
              placeholder="Select RTA Plate Category"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Vehicle Color" required>
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.vehicle_color"
              :options="vehicleColorOptions"
              :rules="[isRequired]"
              placeholder="Select Vehicle Color"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Plate Color" :required="isGIG">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.plate_color"
              :options="plateColorOptions"
              :rules="isGIG ? [isRequired] : []"
              placeholder="Select Plate Color"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Bank Loan?" required>
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.bank_loan"
              :rules="[isRequired]"
              :options="[
                { value: '1', label: 'Yes' },
                { value: '0', label: 'No' }
              ]"
              placeholder="Select Bank Loan"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Bank Name" :required="! isGIG && additionalVehicleTransactionDetailsForm.bank_loan === '1'">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.bank_name"
              placeholder="Select Bank Name"
              :options="bankNameOptions"
              :disabled="additionalVehicleTransactionDetailsForm.bank_loan !== '1' || hasNotEditPermission"
              :rules="(! isGIG && additionalVehicleTransactionDetailsForm.bank_loan === '1') ? [isRequired] : []"
              class="w-full"
            />
          </x-field>

          <x-field label="First Registration Date" :required="! isSUKOON">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.first_registration_date"
              :rules="(! isSUKOON) ? [isRequired] : []"
              placeholder="First Registration Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Policy Effective Date" required>
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_effective_date"
              :rules="[isRequired]"
              placeholder="Policy Effective Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Policy Expiry Date" :required="isLIVA">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_expiry_date"
              :rules="isLIVA ? [isRequired] : []"
              placeholder="Policy Expiry Date"
              :disabled="hasNotEditPermission || isLIVA"
            />
          </x-field>

          <x-field label="Certificate Start Date" :required="! isSUKOON">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_start_date"
              :rules="(! isSUKOON) ? [isRequired] : []"
              placeholder="Certificate Start Date"
              :disabled="hasNotEditPermission || isLIVA"
            />
          </x-field>

          <x-field label="Certificate End Date" :required="isLIVA">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_end_date"
              :rules="isLIVA ? [isRequired] : []"
              placeholder="Certificate End Date"
              :disabled="hasNotEditPermission || isLIVA"
            />
          </x-field>

          <x-field label="Annual Mileage Estimate" :required="isLIVA">
            <x-input
              v-if="isGIG"
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
              :rules="(! isGIG) ? [isRequired] : []"
              placeholder="Select Annual Mileage Estimate"
              type="text"
              :disabled="hasNotEditPermission"
            />
            <x-select
              v-else
              filterable
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
              :rules="isLIVA ? [isRequired] : []"
              :options="annualMileageEstimateOptions"
              placeholder="Select Annual Mileage Estimate"
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
            :loading="additionalVehicleTransactionDetailsForm.processing"
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
  </div>
</template>
