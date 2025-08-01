<script setup>
import { ref, computed, onMounted, watch } from 'vue'
const { isRequired } = useRules();

const props = defineProps({
  insurerPortalSyncData: {
    type: Object,
    default: null
  },
  // RTA Configuration props (passed from backend)
  rta_transaction_types: {
    type: Object,
    default: () => ({})
  },
  rta_field_configurations: {
    type: Object,
    default: () => ({})
  },
  rta_validation_summaries: {
    type: Object,
    default: () => ({})
  }
});

const page = usePage();
const notification = useToast();
const lookups = page.props.lookups;
const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

// RTA Transaction Type Constants
const RTA_CONSTANTS = {
  NEW_VEHICLE_REGISTRATION: 'RTT01',
  CHANGE_VEHICLE_OWNERSHIP: 'RTT03',
  VEHICLE_RENEWAL: 'RTT04',
  UPDATE_REGISTRATION: 'RTT07',
  VEHICLE_RENEWAL_WITH_CHANGE_NUMBER: 'RTT10',
  POLICY_DURATION_MONTHS: 13,
  POLICY_EFFECTIVE_DATE_MAX_DAYS: 30,
  GIG_PROVIDER_CODE: 'AXA'
};

// Field configuration state
const fieldConfig = ref({});
const calculatedDates = ref({});

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
  bankLoanRequired: v => {
    return (v === '0' || v === '1') || 'Please select Yes or No for Bank Loan';
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
  bank_loan: page.props.quoteRequest?.car_quote_request_detail?.bank_loan?.toString() ?? '',
  bank_name: page.props.quoteRequest?.car_quote_request_detail?.bank_name ?? '',
  first_registration_date: page.props.quoteRequest?.car_quote_request_detail?.first_registration_date ?? '',
  policy_effective_date: page.props.quoteRequest?.car_quote_request_detail?.policy_effective_date ?? '',
  policy_expiry_date: page.props.quoteRequest?.car_quote_request_detail?.policy_expiry_date ?? '',
  certificate_start_date: page.props.quoteRequest?.car_quote_request_detail?.certificate_start_date ?? '',
  certificate_end_date: page.props.quoteRequest?.car_quote_request_detail?.certificate_end_date ?? '',
  annual_mileage_estimate: page.props.quoteRequest?.car_quote_request_detail?.annual_mileage_estimate?.toString() ?? '',
  previous_policy_provider: '', // For GIG renewal detection
});

// Computed properties for insurance provider checks
const isGIG = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.AXA;
});

const isLIVA = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.RSA;
});

const isSUKOON = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.OIC;
});

// RTA Transaction Type specific computed properties
const isGigRenewal = computed(() => {
  return additionalVehicleTransactionDetailsForm.rta_transaction_type === RTA_CONSTANTS.VEHICLE_RENEWAL &&
         additionalVehicleTransactionDetailsForm.previous_policy_provider === RTA_CONSTANTS.GIG_PROVIDER_CODE;
});

// Field configuration computed properties
const isFieldDisabled = (fieldName) => {
  return fieldConfig.value[fieldName]?.disabled || fieldConfig.value[fieldName]?.readonly || fieldConfig.value[fieldName]?.hidden || false;
};

const isFieldRequired = (fieldName) => {
  // Hidden fields are never required
  if (fieldConfig.value[fieldName]?.hidden) {
    return false;
  }

  // Check RTA-specific requirements first, then fall back to insurance provider requirements
  if (fieldConfig.value[fieldName]?.required !== undefined) {
    return fieldConfig.value[fieldName].required;
  }

  // Legacy insurance provider-based requirements
  switch (fieldName) {
    case 'plate_code':
    case 'plate_number':
      return !isGIG.value;
    case 'engine_number':
      return !isSUKOON.value;
    case 'rta_plate_category':
    case 'plate_color':
      return isGIG.value;
    case 'bank_name':
      return !isGIG.value;
    case 'first_registration_date':
    case 'certificate_start_date':
      return !isSUKOON.value;
    case 'policy_expiry_date':
    case 'certificate_end_date':
    case 'annual_mileage_estimate':
      return isLIVA.value;
    default:
      return false;
  }
};

// Format date to YYYY-MM-DD
const formatDate = (date) => {
  return date.toISOString().split('T')[0];
};

// Calculate dates for New Vehicle Registration and Change Vehicle Ownership
const calculateDatesForNewVehicleOrOwnershipChange = () => {
  if (additionalVehicleTransactionDetailsForm.policy_effective_date) {
    const policyEffectiveDate = new Date(additionalVehicleTransactionDetailsForm.policy_effective_date);

    // Policy Expiry Date = Policy Effective Date + 13 months
    const policyExpiryDate = new Date(policyEffectiveDate);
    policyExpiryDate.setMonth(policyExpiryDate.getMonth() + RTA_CONSTANTS.POLICY_DURATION_MONTHS);

    // Certificate Start Date = Policy Effective Date
    // Certificate End Date = Policy Expiry Date
    additionalVehicleTransactionDetailsForm.policy_expiry_date = formatDate(policyExpiryDate);
    additionalVehicleTransactionDetailsForm.certificate_start_date = additionalVehicleTransactionDetailsForm.policy_effective_date;
    additionalVehicleTransactionDetailsForm.certificate_end_date = formatDate(policyExpiryDate);
  }
};

// Calculate dates for Non-GIG Vehicle Renewal
const calculateDatesForNonGigRenewal = () => {
  if (additionalVehicleTransactionDetailsForm.policy_effective_date) {
    const policyEffectiveDate = new Date(additionalVehicleTransactionDetailsForm.policy_effective_date);

    // Certificate Start Date = Policy Effective Date
    // Certificate End Date = Policy Effective Date + 13 months
    // Policy Expiry Date = Certificate End Date
    const certificateEndDate = new Date(policyEffectiveDate);
    certificateEndDate.setMonth(certificateEndDate.getMonth() + RTA_CONSTANTS.POLICY_DURATION_MONTHS);

    additionalVehicleTransactionDetailsForm.certificate_start_date = additionalVehicleTransactionDetailsForm.policy_effective_date;
    additionalVehicleTransactionDetailsForm.certificate_end_date = formatDate(certificateEndDate);
    additionalVehicleTransactionDetailsForm.policy_expiry_date = formatDate(certificateEndDate);
  }
};

// Calculate dates for GIG Vehicle Renewal
const calculateDatesForGigRenewal = () => {
  if (additionalVehicleTransactionDetailsForm.certificate_start_date) {
    const certificateStartDate = new Date(additionalVehicleTransactionDetailsForm.certificate_start_date);

    // Certificate End Date = Certificate Start Date + 13 months
    const certificateEndDate = new Date(certificateStartDate);
    certificateEndDate.setMonth(certificateEndDate.getMonth() + RTA_CONSTANTS.POLICY_DURATION_MONTHS);

    additionalVehicleTransactionDetailsForm.certificate_end_date = formatDate(certificateEndDate);
  }
};

// Apply auto-calculations based on current form data
const applyAutoCalculations = () => {
  const rtaType = additionalVehicleTransactionDetailsForm.rta_transaction_type;

  if (!rtaType) return;

  switch (rtaType) {
    case RTA_CONSTANTS.NEW_VEHICLE_REGISTRATION:
    case RTA_CONSTANTS.CHANGE_VEHICLE_OWNERSHIP:
      calculateDatesForNewVehicleOrOwnershipChange();
      break;

    case RTA_CONSTANTS.VEHICLE_RENEWAL:
      if (isGigRenewal.value) {
        calculateDatesForGigRenewal();
      } else {
        calculateDatesForNonGigRenewal();
      }
      break;
  }
};

// Get field configuration from props (no API call needed)
const loadFieldConfigurationFromProps = () => {
  if (!additionalVehicleTransactionDetailsForm.rta_transaction_type) {
    fieldConfig.value = {};
    return;
  }

  const rtaType = additionalVehicleTransactionDetailsForm.rta_transaction_type;
  const isGigRenewal = additionalVehicleTransactionDetailsForm.previous_policy_provider === RTA_CONSTANTS.GIG_PROVIDER_CODE;
  const configKey = rtaType + (isGigRenewal ? '_GIG' : '');

  // Load configuration from props
  if (props.rta_field_configurations[configKey]) {
    fieldConfig.value = props.rta_field_configurations[configKey];
  } else {
    fieldConfig.value = {};
  }

  // Apply auto-calculations after configuration is loaded
  applyAutoCalculations();
};

// Watch for RTA transaction type changes
watch(() => additionalVehicleTransactionDetailsForm.rta_transaction_type, (newRtaType) => {
  if (newRtaType) {
    loadFieldConfigurationFromProps();
  }
}, { immediate: true });

// Watch for previous policy provider changes (for GIG renewal detection)
watch(() => additionalVehicleTransactionDetailsForm.previous_policy_provider, () => {
  if (additionalVehicleTransactionDetailsForm.rta_transaction_type) {
    loadFieldConfigurationFromProps();
  }
});

// Watch for policy effective date changes to trigger auto-calculations
watch(() => additionalVehicleTransactionDetailsForm.policy_effective_date, () => {
  applyAutoCalculations();
});

// Watch for certificate start date changes (for GIG renewals)
watch(() => additionalVehicleTransactionDetailsForm.certificate_start_date, () => {
  if (isGigRenewal.value) {
    applyAutoCalculations();
  }
});



// Get validation rules for a field
const getFieldRules = (fieldName) => {
  // Hidden fields have no validation rules
  if (fieldConfig.value[fieldName]?.hidden) {
    return [];
  }

  const fieldRules = [];

  // Add field-specific rules
  if (fieldName === 'chassis_number') {
    fieldRules.push(rules.chassisNumberCheck);
  } else if (fieldName === 'bank_loan') {
    fieldRules.push(rules.bankLoanRequired);
  } else if (isFieldRequired(fieldName)) {
    fieldRules.push(isRequired);
  }

  return fieldRules;
};

// Enhanced submit function with auto-calculated data
const submitAdditionalVehicleTransactionDetailsForm = async (isValid) => {
  if (isValid) {
    // Clear any previous errors
    additionalVehicleTransactionDetailsForm.clearErrors();

    // Apply final auto-calculations before submission
    applyAutoCalculations();
    additionalVehicleTransactionDetailsForm.processing = true;
    try {
      const response = await axios.post('/kyc/update-additional-vehicle-driver-details', additionalVehicleTransactionDetailsForm);
      if (response.data.success) {
        notification.success({
          title: response.data.message,
          position: 'top',
        });

        // Update form with any calculated dates from backend
        if (response.data.calculated_dates) {
          Object.entries(response.data.calculated_dates).forEach(([field, value]) => {
            if (value) {
              additionalVehicleTransactionDetailsForm[field] = value;
            }
          });
        }

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
            additionalVehicleTransactionDetailsForm.setError(field, messages[0]);
          });
        }
      } else {
        notification.error({
          title: 'Error saving vehicle details',
          position: 'top',
        });
      }
    } finally {
      additionalVehicleTransactionDetailsForm.processing = false;
    }
  }
};

// Watch for insurer portal sync data changes
watch(() => props.insurerPortalSyncData, (vehicleTransactionDetails) => {
  if (vehicleTransactionDetails) {
    const fieldMappings = {
      vehicleTransactionDetails: {
        rtaTransactionType: 'rta_transaction_type',
        plateCode: 'plate_code',
        plateNumber: 'plate_number',
        trafficCodeNumber: 'traffic_code_number',
        chassisNumber: 'chassis_number',
        engineNumber: 'engine_number',
        rtaPlateCategory: 'rta_plate_category',
        vehicleColor: 'vehicle_color',
        plateColor: 'plate_color',
        bankLoan: 'bank_loan',
        bankName: 'bank_name',
        firstRegistrationDate: 'first_registration_date',
        policyEffectiveDate: 'policy_effective_date',
        policyExpiryDate: 'policy_expiry_date',
        certificateStartDate: 'certificate_start_date',
        certificateEndDate: 'certificate_end_date',
        annualMileageEstimate: 'annual_mileage_estimate',
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

// Initialize field configuration on component mount
onMounted(() => {
  if (additionalVehicleTransactionDetailsForm.rta_transaction_type) {
    loadFieldConfigurationFromProps();
  }
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
      <x-form @submit="submitAdditionalVehicleTransactionDetailsForm" :auto-focus="false">
        <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">

          <!-- RTA Transaction Type -->
          <x-field label="RTA Transaction Type" required>
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.rta_transaction_type"
              :rules="[isRequired]"
              :options="rtaTransactionTypeOptions"
              placeholder="Select RTA Transaction Type"
              :disabled="isFieldDisabled('rta_transaction_type')"
            />
          </x-field>

          <!-- Plate Code -->
          <x-field
            label="Plate Code"
            :required="isFieldRequired('plate_code')"
          >
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.plate_code"
              :rules="getFieldRules('plate_code')"
              placeholder="Plate Code"
              type="text"
              :disabled="isFieldDisabled('plate_code')"
              :readonly="fieldConfig.plate_code?.readonly"
            />
          </x-field>

          <!-- Plate Number -->
          <x-field
            label="Plate Number"
            :required="isFieldRequired('plate_number')"
          >
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.plate_number"
              :rules="getFieldRules('plate_number')"
              placeholder="Plate Number"
              type="text"
              :disabled="isFieldDisabled('plate_number')"
              :readonly="fieldConfig.plate_number?.readonly"
            />
          </x-field>

          <!-- Traffic Code Number -->
          <x-field label="Traffic Code Number" required>
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.traffic_code_number"
              :rules="[isRequired]"
              placeholder="Traffic Code Number"
              type="text"
              :disabled="isFieldDisabled('traffic_code_number')"
            />
          </x-field>

          <!-- Chassis Number -->
          <x-field label="Chassis Number" required>
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.chassis_number"
              :rules="[isRequired, rules.chassisNumberCheck]"
              @keypress="chassisNumberValidate('keypress')"
              @blur="chassisNumberValidate('blur')"
              placeholder="Chassis Number"
              type="text"
              :error="additionalVehicleTransactionDetailsForm.errors.chassis_number"
              :disabled="isFieldDisabled('chassis_number')"
            />
          </x-field>

          <!-- Engine Number -->
          <x-field
            label="Engine Number"
            :required="isFieldRequired('engine_number')"
          >
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.engine_number"
              :rules="getFieldRules('engine_number')"
              placeholder="Engine Number"
              type="text"
              :disabled="isFieldDisabled('engine_number')"
            />
          </x-field>

          <!-- RTA Plate Category -->
          <x-field
            label="RTA Plate Category"
            :required="isFieldRequired('rta_plate_category')"
          >
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.rta_plate_category"
              :rules="getFieldRules('rta_plate_category')"
              :options="rtaPlateCategoryOptions"
              placeholder="Select RTA Plate Category"
              :disabled="isFieldDisabled('rta_plate_category')"
            />
          </x-field>

          <!-- Vehicle Color -->
          <x-field label="Vehicle Color" required>
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.vehicle_color"
              :options="vehicleColorOptions"
              :rules="[isRequired]"
              placeholder="Select Vehicle Color"
              :disabled="isFieldDisabled('vehicle_color')"
            />
          </x-field>

          <!-- Plate Color -->
          <x-field
            label="Plate Color"
            :required="isFieldRequired('plate_color')"
          >
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.plate_color"
              :options="plateColorOptions"
              :rules="getFieldRules('plate_color')"
              placeholder="Select Plate Color"
              :disabled="isFieldDisabled('plate_color')"
            />
          </x-field>

          <!-- Bank Loan -->
          <x-field label="Bank Loan?" required>
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.bank_loan"
              :rules="getFieldRules('bank_loan')"
              :options="[
                { value: '1', label: 'Yes' },
                { value: '0', label: 'No' }
              ]"
              placeholder="Select Bank Loan"
              :disabled="isFieldDisabled('bank_loan')"
            />
          </x-field>

          <!-- Bank Name -->
          <x-field
            label="Bank Name"
            :required="isFieldRequired('bank_name')"
          >
            <ComboBox
              :single="true"
              v-model="additionalVehicleTransactionDetailsForm.bank_name"
              placeholder="Select Bank Name"
              :options="bankNameOptions"
              :disabled="additionalVehicleTransactionDetailsForm.bank_loan !== '1' || isFieldDisabled('bank_name')"
              :rules="getFieldRules('bank_name')"
              class="w-full"
            />
          </x-field>

          <!-- First Registration Date -->
          <x-field
            label="First Registration Date"
            :required="isFieldRequired('first_registration_date')"
          >
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.first_registration_date"
              :rules="getFieldRules('first_registration_date')"
              placeholder="First Registration Date"
              :disabled="isFieldDisabled('first_registration_date')"
            />
          </x-field>

          <!-- Policy Effective Date -->
          <x-field
            label="Policy Effective Date"
            :required="isFieldRequired('policy_effective_date')"
          >
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_effective_date"
              :rules="getFieldRules('policy_effective_date')"
              placeholder="Policy Effective Date"
              :disabled="isFieldDisabled('policy_effective_date')"
              :readonly="fieldConfig.policy_effective_date?.readonly"
            />
          </x-field>

          <!-- Policy Expiry Date -->
          <x-field
            label="Policy Expiry Date"
            :required="isFieldRequired('policy_expiry_date')"
          >
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_expiry_date"
              :rules="getFieldRules('policy_expiry_date')"
              placeholder="Policy Expiry Date"
              :disabled="isFieldDisabled('policy_expiry_date')"
              :readonly="fieldConfig.policy_expiry_date?.readonly"
            />
          </x-field>

          <!-- Certificate Start Date -->
          <x-field
            label="Certificate Start Date"
            :required="isFieldRequired('certificate_start_date')"
          >
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_start_date"
              :rules="getFieldRules('certificate_start_date')"
              placeholder="Certificate Start Date"
              :disabled="isFieldDisabled('certificate_start_date')"
              :readonly="fieldConfig.certificate_start_date?.readonly"
            />
          </x-field>

          <!-- Certificate End Date -->
          <x-field
            label="Certificate End Date"
            :required="isFieldRequired('certificate_end_date')"
          >
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_end_date"
              :rules="getFieldRules('certificate_end_date')"
              placeholder="Certificate End Date"
              :disabled="isFieldDisabled('certificate_end_date')"
              :readonly="fieldConfig.certificate_end_date?.readonly"
            />
          </x-field>

          <!-- Annual Mileage Estimate -->
          <x-field
            label="Annual Mileage Estimate"
            :required="isFieldRequired('annual_mileage_estimate')"
          >
            <x-input
              v-if="isGIG"
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
              :rules="getFieldRules('annual_mileage_estimate')"
              placeholder="Select Annual Mileage Estimate"
              type="text"
              :disabled="isFieldDisabled('annual_mileage_estimate')"
            />
            <x-select
              v-else
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
              :rules="getFieldRules('annual_mileage_estimate')"
              :options="annualMileageEstimateOptions"
              placeholder="Select Annual Mileage Estimate"
              :disabled="isFieldDisabled('annual_mileage_estimate')"
            />
          </x-field>
        </dl>

        <div class="flex justify-end my-5 gap-x-2">
          <x-button
            v-if="hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)"
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
