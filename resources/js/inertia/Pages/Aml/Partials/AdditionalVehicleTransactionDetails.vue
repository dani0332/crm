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
  PREVIOUS_GIG_PROVIDER: 'Gulf Insurance Group (Gulf) B.S.C. (C)',
  RENEWALS_UPLOADS: 'renewals_uploads'
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
  policyEffectiveDateValidation: v => {
    // Get current RTA type each time validation is called
    const rtaType = additionalVehicleTransactionDetailsForm.rta_transaction_type;

    // Only validate for New Vehicle or Change Vehicle Ownership
    if (rtaType !== RTA_CONSTANTS.NEW_VEHICLE_REGISTRATION && rtaType !== RTA_CONSTANTS.CHANGE_VEHICLE_OWNERSHIP && (rtaType == RTA_CONSTANTS.VEHICLE_RENEWAL && isGigRenewal.value)) {
      return true;
    }

    if (!v) {
      return true; // Let required validation handle empty values
    }

    const selectedDate = new Date(v);
    const currentDate = new Date();
    const maxDate = new Date();
    maxDate.setDate(currentDate.getDate() + RTA_CONSTANTS.POLICY_EFFECTIVE_DATE_MAX_DAYS);

    if (selectedDate > maxDate) {
      // Use validation message from rta_validation_summaries prop
      const validationKey = 'policy_effective_date_max_days_validation';
      const errorMessage = props.rta_validation_summaries[validationKey] ||
             `Policy effective date should not be more than ${RTA_CONSTANTS.POLICY_EFFECTIVE_DATE_MAX_DAYS} days from current date`;
      console.log('Policy effective date validation failed:', errorMessage);
      return errorMessage;
    }

    return true;
  },
  certificateStartDateValidation: v => {
    // Only validate for GIG renewals
    if (!isGigRenewal.value) {
      return true;
    }

    if (!v) {
      return true; // Let required validation handle empty values
    }

    const selectedDate = new Date(v);
    const currentDate = new Date();
    // Set currentDate to start of day for fair comparison
    currentDate.setHours(0, 0, 0, 0);

    if (selectedDate < currentDate) {
      // Use validation message from rta_validation_summaries prop
      const validationKey = 'certificate_start_date_back_date_validation';
      const errorMessage = props.rta_validation_summaries[validationKey] ||
             'Certificate start date cannot be back dated';
      return errorMessage;
    }

    return true;
  },
};

const plateCodeOptions = computed(() => {
  return useGenerateOptions(lookups?.plate_code ?? [], 'code', 'text');
});

const carDetail = computed(() => {
  return page.props.quoteRequest?.car_quote_request_detail;
});

const vehicleDriverDetail = computed(() => {
  return page.props.quoteRequest?.vehicle_driver_detail;
});

const additionalVehicleTransactionDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  additional_vehicle_transaction_details: true,
  rta_transaction_type: vehicleDriverDetail.value?.rta_transaction_type?.toString() ?? '',
  plate_code: vehicleDriverDetail.value?.vehicle_plate_code ?? '',
  plate_number: vehicleDriverDetail.value?.vehicle_plate_number ?? '',
  traffic_code_number: vehicleDriverDetail.value?.traffic_code_number ?? '',
  chassis_number: carDetail.value?.chassis_number ?? '',
  engine_number: vehicleDriverDetail.value?.vehicle_engine_number ?? '',
  rta_plate_category: vehicleDriverDetail.value?.rta_plate_category ?? '',
  vehicle_color: vehicleDriverDetail.value?.vehicle_color ?? '',
  plate_color: vehicleDriverDetail.value?.vehicle_plate_color ?? '',
  bank_loan: vehicleDriverDetail.value?.bank_loan?.toString() ?? '',
  bank_name: vehicleDriverDetail.value?.bank_name ?? '',
  first_registration_date: vehicleDriverDetail.value?.first_registration_date ?? '',
  policy_effective_date: page.props.quoteRequest.value?.policy_start_date ?? '',
  policy_expiry_date: page.props.quoteRequest.value?.policy_expiry_date ?? '',
  certificate_start_date: page.props.quoteRequest.value?.certificate_start_date ?? '',
  certificate_end_date: page.props.quoteRequest.value?.certificate_end_date ?? '',
  annual_mileage_estimate: vehicleDriverDetail.value?.annual_mileage_estimate?.toString() ?? '',
  previous_policy_provider: page.props.quoteRequest?.currently_insured_with?.toString() ?? '',
  lead_source: page.props.quoteRequest?.source?.toString() ?? '',
});

const hasNotEditPermission = computed(() => {
  return !hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)
});

watch(() => props.insurerPortalSyncData, (vehicleTransactionDetails) => {
  if (vehicleTransactionDetails) {
    const fieldMappings = {
      vehicleTransactionDetails: {
        rta_transaction_type: 'rta_transaction_type',
        vehicle_plate_code: 'plate_code',
        vehicle_plate_number: 'plate_number',
        traffic_code_number: 'traffic_code_number',
        chassis_number: 'chassis_number',
        vehicle_engine_number: 'engine_number',
        rta_plate_category: 'rta_plate_category',
        vehicle_color: 'vehicle_color',
        vehicle_plate_color: 'plate_color',
        bank_loan: 'bank_loan',
        bank_name: 'bank_name',
        first_registration_date: 'first_registration_date',
        policy_start_date: 'policy_effective_date',
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
        (additionalVehicleTransactionDetailsForm.lead_source == RTA_CONSTANTS.RENEWALS_UPLOADS && additionalVehicleTransactionDetailsForm.previous_policy_provider === RTA_CONSTANTS.PREVIOUS_GIG_PROVIDER);
});

console.log(isGigRenewal.value);

// Check if current quote source is NOT renewals_uploads
const isNonRenewalsUploadSource = computed(() => {
  return page.props.quoteRequest?.source !== RTA_CONSTANTS.RENEWALS_UPLOADS;
});

// Check if this is Vehicle Renewal with non-renewals_uploads source
const isVehicleRenewalNonUpload = computed(() => {
  return additionalVehicleTransactionDetailsForm.rta_transaction_type === RTA_CONSTANTS.VEHICLE_RENEWAL &&
         isNonRenewalsUploadSource.value;
});

// Field configuration computed properties
const isFieldDisabled = (fieldName) => {
  // Special handling for Vehicle Renewal with non-renewals_uploads source
  if (isVehicleRenewalNonUpload.value &&
      ['policy_expiry_date', 'certificate_start_date'].includes(fieldName)) {
    return false;
  }

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
  console.log('calculateDatesForNewVehicleOrOwnershipChange', additionalVehicleTransactionDetailsForm.policy_effective_date);
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

// Calculate dates for Vehicle Renewal with non-renewals_uploads source
const calculateDatesForVehicleRenewalNonUpload = () => {
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
      if (isVehicleRenewalNonUpload.value) {
        calculateDatesForVehicleRenewalNonUpload();
      } else if (isGigRenewal.value) {
        console.log('isGigRenewal');
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
  const isGigRenewal = (additionalVehicleTransactionDetailsForm.lead_source == RTA_CONSTANTS.RENEWALS_UPLOADS && additionalVehicleTransactionDetailsForm.previous_policy_provider === RTA_CONSTANTS.PREVIOUS_GIG_PROVIDER);
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

    // Re-validate policy effective date when RTA type changes
    if (additionalVehicleTransactionDetailsForm.policy_effective_date) {
      const validationResult = rules.policyEffectiveDateValidation(additionalVehicleTransactionDetailsForm.policy_effective_date);
      if (validationResult !== true) {
        additionalVehicleTransactionDetailsForm.setError('policy_effective_date', validationResult);
      } else {
        additionalVehicleTransactionDetailsForm.clearErrors('policy_effective_date');
      }
    }

    // Re-validate certificate start date when RTA type changes (for GIG renewals)
    if (additionalVehicleTransactionDetailsForm.certificate_start_date) {
      const validationResult = rules.certificateStartDateValidation(additionalVehicleTransactionDetailsForm.certificate_start_date);
      if (validationResult !== true && isGigRenewal.value) {
        additionalVehicleTransactionDetailsForm.setError('certificate_start_date', validationResult);
      } else {
        additionalVehicleTransactionDetailsForm.clearErrors('certificate_start_date');
      }
    }
  }
}, { immediate: true });

// Watch for previous policy provider changes (for GIG renewal detection)
watch(() => additionalVehicleTransactionDetailsForm.previous_policy_provider, () => {
  if (additionalVehicleTransactionDetailsForm.rta_transaction_type) {
    loadFieldConfigurationFromProps();

    // Re-validate certificate start date when previous policy provider changes (affects GIG renewal status)
    if (additionalVehicleTransactionDetailsForm.certificate_start_date) {
      const validationResult = rules.certificateStartDateValidation(additionalVehicleTransactionDetailsForm.certificate_start_date);
      if (validationResult !== true && isGigRenewal.value) {
        additionalVehicleTransactionDetailsForm.setError('certificate_start_date', validationResult);
      } else {
        additionalVehicleTransactionDetailsForm.clearErrors('certificate_start_date');
      }
    }
  }
});

// Watch for policy effective date changes to trigger auto-calculations and validation
watch(() => additionalVehicleTransactionDetailsForm.policy_effective_date, (newValue) => {
  console.log('policy_effective_date watcher triggered', newValue);
  applyAutoCalculations();

  // Manually trigger validation for policy effective date
  if (newValue) {
    const validationResult = rules.policyEffectiveDateValidation(newValue);
    if (validationResult !== true) {
      additionalVehicleTransactionDetailsForm.setError('policy_effective_date', validationResult);
    } else {
      additionalVehicleTransactionDetailsForm.clearErrors('policy_effective_date');
    }
  }
});

// Watch for certificate start date changes (for GIG renewals and Vehicle Renewal with non-renewals_uploads source)
watch(() => additionalVehicleTransactionDetailsForm.certificate_start_date, (newValue) => {
  if (isGigRenewal.value || isVehicleRenewalNonUpload.value) {
    applyAutoCalculations();
  }

  // Manually trigger validation for certificate start date in GIG renewals
  if (isGigRenewal.value && newValue) {
    const validationResult = rules.certificateStartDateValidation(newValue);
    if (validationResult !== true) {
      additionalVehicleTransactionDetailsForm.setError('certificate_start_date', validationResult);
    } else {
      additionalVehicleTransactionDetailsForm.clearErrors('certificate_start_date');
    }
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
  } else if (fieldName === 'policy_effective_date') {
    fieldRules.push(rules.policyEffectiveDateValidation);
    if (isFieldRequired(fieldName)) {
      fieldRules.push(isRequired);
    }
  } else if (fieldName === 'certificate_start_date') {
    fieldRules.push(rules.certificateStartDateValidation);
    if (isFieldRequired(fieldName)) {
      fieldRules.push(isRequired);
    }
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

          <!-- RTA Transaction Type -->
          <x-select
            filterable
            v-model="additionalVehicleTransactionDetailsForm.rta_transaction_type"
            :rules="[isRequired]"
            :options="rtaTransactionTypeOptions"
            placeholder="Select RTA Transaction Type"
            label="RTA Transaction Type"
            :disabled="isFieldDisabled('rta_transaction_type') || hasNotEditPermission"
            :tooltip="`Type of transaction with the traffic department for your vehicle`"
            required
          />

          <!-- Plate Code -->
          <x-input
            v-if="isGIG"
            v-model="additionalVehicleTransactionDetailsForm.plate_code"
            :rules="getFieldRules('plate_code')"
            :required="isFieldRequired('plate_code')"
            placeholder="Plate Code"
            type="text"
            :disabled="isFieldDisabled('plate_code') || hasNotEditPermission"
            :readonly="fieldConfig.plate_code?.readonly"
            label="Plate Code"
            :tooltip="`Official plate code and registration number`"
          />
          <x-select
            v-else
            filterable
            v-model="additionalVehicleTransactionDetailsForm.plate_code"
            :rules="(registrationNoValidation) ? [isRequired] : []"
            :required="registrationNoValidation"
            placeholder="Select Plate Code"
            :options="plateCodeOptions"
            class="w-full"
            :disabled="hasNotEditPermission"
            label="Plate Code"
            :tooltip="`Official plate code and registration number`"
          />

          <!-- Plate Number -->
          <x-input
            v-if="isGIG"
            v-model="additionalVehicleTransactionDetailsForm.plate_number"
            :rules="getFieldRules('plate_number')"
            :required="isFieldRequired('plate_number')"
            placeholder="Plate Number"
            type="text"
            :disabled="isFieldDisabled('plate_number') || hasNotEditPermission"
            :readonly="fieldConfig.plate_number?.readonly"
            label="Plate Number"
            :tooltip="`Official plate code and registration number`"
          />
          <x-input
            v-else
            v-model="additionalVehicleTransactionDetailsForm.plate_number"
            :rules="(registrationNoValidation) ? [isRequired] : []"
            :required="registrationNoValidation"
            placeholder="Plate Number"
            type="text"
            :disabled="isFieldDisabled('plate_number') || hasNotEditPermission"
            :readonly="fieldConfig.plate_number?.readonly"
            label="Plate Number"
            :tooltip="`Official plate code and registration number`"
          />

          <!-- Traffic Code Number -->
          <x-input
            v-model="additionalVehicleTransactionDetailsForm.traffic_code_number"
            :rules="[isRequired]"
            placeholder="Traffic Code Number"
            type="text"
            :disabled="isFieldDisabled('traffic_code_number') || hasNotEditPermission"
            required
            label="Traffic Code Number"
            :tooltip="`Unique traffic file number assigned by the traffic department`"
          />

          <!-- Chassis Number -->
          <x-input
            v-model="additionalVehicleTransactionDetailsForm.chassis_number"
            :rules="[isRequired, rules.chassisNumberCheck]"
            @keypress="chassisNumberValidate('keypress')"
            @blur="chassisNumberValidate('blur')"
            placeholder="Chassis Number"
            type="text"
            :error="additionalVehicleTransactionDetailsForm.errors.chassis_number"
            :disabled="isFieldDisabled('chassis_number') || hasNotEditPermission"
            required
            label="Chassis Number"
            :tooltip="`Vehicle chassis number`"
          />

          <!-- Engine Number -->
          <x-input
            v-model="additionalVehicleTransactionDetailsForm.engine_number"
            :rules="getFieldRules('engine_number')"
            :required="isFieldRequired('engine_number')"
            placeholder="Engine Number"
            type="text"
            :disabled="isFieldDisabled('engine_number') || hasNotEditPermission"
            label="Engine Number"
            :tooltip="`Vehicle engine number`"
          />

          <!-- RTA Plate Category -->
          <x-select
            filterable
            v-model="additionalVehicleTransactionDetailsForm.rta_plate_category"
            :rules="getFieldRules('rta_plate_category')"
            :required="isFieldRequired('rta_plate_category')"
            :options="rtaPlateCategoryOptions"
            placeholder="Select RTA Plate Category"
            :disabled="isFieldDisabled('rta_plate_category') || hasNotEditPermission"
            label="RTA Plate Category"
            :tooltip="`Vehicle plate type as defined by the traffic department (e.g., private, commercial)`"
          />

          <!-- Vehicle Color -->
          <x-select
            filterable
            v-model="additionalVehicleTransactionDetailsForm.vehicle_color"
            :options="vehicleColorOptions"
            :rules="[isRequired]"
            required
            placeholder="Select Vehicle Color"
            :disabled="isFieldDisabled('vehicle_color') || hasNotEditPermission"
            class="w-full"
            label="Vehicle Color"
            :tooltip="`Vehicle color as per the official documentation`"
          />

          <x-select
            filterable
            v-model="additionalVehicleTransactionDetailsForm.plate_color"
            :options="plateColorOptions"
            :rules="getFieldRules('plate_color')"
            :required="isFieldRequired('plate_color')"
            placeholder="Select Plate Color"
            :disabled="isFieldDisabled('plate_color') || hasNotEditPermission"
            class="w-full"
            label="Plate Color"
            :tooltip="`Plate color as per the official documentation`"
          />

          <!-- Bank Loan -->
          <x-select
            v-model="additionalVehicleTransactionDetailsForm.bank_loan"
            :rules="getFieldRules('bank_loan')"
            required
            :options="[{ value: '1', label: 'Yes' }, { value: '0', label: 'No' }]"
            placeholder="Select Bank Loan"
            :disabled="isFieldDisabled('bank_loan') || hasNotEditPermission"
            label="Bank Loan"
            :tooltip="`Indicates vehicle is under bank finance or loan agreement`"
          />

          <!-- Bank Name -->
          <x-select
            v-if="isGIG"
            filterable
            v-model="additionalVehicleTransactionDetailsForm.bank_name"
            placeholder="Select Bank Name"
            :options="bankNameOptions"
            :disabled="additionalVehicleTransactionDetailsForm.bank_loan !== '1' || isFieldDisabled('bank_name') || hasNotEditPermission"
            :rules="getFieldRules('bank_name')"
            :required="isFieldRequired('bank_name')"
            class="w-full"
            label="Bank Name"
            :tooltip="`Name of the bank that issued the vehicle loan`"
          />
          <x-select
            v-else
            filterable
            v-model="additionalVehicleTransactionDetailsForm.bank_name"
            placeholder="Select Bank Name"
            :options="bankNameOptions"
            :disabled="additionalVehicleTransactionDetailsForm.bank_loan !== '1' || hasNotEditPermission"
            :rules="(additionalVehicleTransactionDetailsForm.bank_loan === '1') ? [isRequired] : []"
            :required="additionalVehicleTransactionDetailsForm.bank_loan === '1'"
            class="w-full"
            label="Bank Name"
            :tooltip="`Name of the bank that issued the vehicle loan`"
          />

          <!-- First Registration Date -->
          <DatePicker
            v-model="additionalVehicleTransactionDetailsForm.first_registration_date"
            :rules="getFieldRules('first_registration_date')"
            :required="isFieldRequired('first_registration_date')"
            placeholder="First Registration Date"
            :disabled="isFieldDisabled('first_registration_date') || hasNotEditPermission"
            label="First Registration Date"
            :tooltip="`Date the vehicle was first registered with the traffic department`"
          />

          <!-- Policy Effective Date -->
          <DatePicker
            v-model="additionalVehicleTransactionDetailsForm.policy_effective_date"
            :rules="getFieldRules('policy_effective_date')"
            :required="isFieldRequired('policy_effective_date')"
            :error="additionalVehicleTransactionDetailsForm.errors.policy_effective_date"
            placeholder="Policy Effective Date"
            :disabled="isFieldDisabled('policy_effective_date') || hasNotEditPermission"
            :readonly="fieldConfig.policy_effective_date?.readonly"
            label="Policy Effective Date"
            :tooltip="`Start date of the insurance policy coverage`"
          />

          <!-- Policy Expiry Date -->
          <DatePicker
            v-model="additionalVehicleTransactionDetailsForm.policy_expiry_date"
            :rules="getFieldRules('policy_expiry_date')"
            :required="isFieldRequired('policy_expiry_date')"
            placeholder="Policy Expiry Date"
            :disabled="isFieldDisabled('policy_expiry_date') || hasNotEditPermission"
            :readonly="fieldConfig.policy_expiry_date?.readonly"
            label="Policy Expiry Date"
            :tooltip="`Expiry date of the insurance policy coverage`"
          />

          <!-- Certificate Start Date -->
          <DatePicker
            v-model="additionalVehicleTransactionDetailsForm.certificate_start_date"
            :rules="getFieldRules('certificate_start_date')"
            :required="isFieldRequired('certificate_start_date')"
            :error="additionalVehicleTransactionDetailsForm.errors.certificate_start_date"
            placeholder="Certificate Start Date"
            :disabled="isFieldDisabled('certificate_start_date') || hasNotEditPermission"
            :readonly="fieldConfig.certificate_start_date?.readonly"
            label="Certificate Start Date"
            :tooltip="`Start date for the insurance certificate validity period`"
          />

          <!-- Certificate End Date -->
          <DatePicker
            v-model="additionalVehicleTransactionDetailsForm.certificate_end_date"
            :rules="getFieldRules('certificate_end_date')"
            :required="isFieldRequired('certificate_end_date')"
            placeholder="Certificate End Date"
            :disabled="isFieldDisabled('certificate_end_date') || hasNotEditPermission"
            :readonly="fieldConfig.certificate_end_date?.readonly"
            label="Certificate End Date"
            :tooltip="`End date for the insurance certificate validity period`"
          />

          <!-- Annual Mileage Estimate -->
          <x-input
            v-if="isGIG"
            v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
            :rules="getFieldRules('annual_mileage_estimate')"
            :required="isFieldRequired('annual_mileage_estimate')"
            placeholder="Select Annual Mileage Estimate"
            type="text"
            :disabled="isFieldDisabled('annual_mileage_estimate') || hasNotEditPermission"
            label="Annual Mileage Estimate"
            :tooltip="`Estimated annual mileage driven by the vehicle`"
          />
          <x-select
            v-else
            filterable
            v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
            :rules="getFieldRules('annual_mileage_estimate')"
            :required="isFieldRequired('annual_mileage_estimate')"
            :options="annualMileageEstimateOptions"
            placeholder="Select Annual Mileage Estimate"
            :disabled="isFieldDisabled('annual_mileage_estimate') || hasNotEditPermission"
            label="Annual Mileage Estimate"
            :tooltip="`Estimated annual mileage of the vehicle`"
          />
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
