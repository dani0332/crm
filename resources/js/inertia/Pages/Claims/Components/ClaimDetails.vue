<script setup>
const props = defineProps({
  claim: Object,
  dropdowns: Object,
  expanded: {
    type: Boolean,
    default: false,
  },
});

import {
  formattedDateDmyWithTime,
  formattedDateYmd,
} from '@/inertia/Composables/utilities.js';

const page = usePage();
const claimsEnum = page.props.claimsEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();
const { isRequired, maxCharacters, isNumber, minValue, maxValue, isValidName } =
  useRules();

const quoteTypeIds = page.props.quoteTypeIds;

const claimForm = useForm({
  quote_type_id: props.claim?.quote_type_id,

  // Car-specific fields (visible when editing)
  plate_number: props.claim?.claim_request_details?.plate_number || '',
  car_make: props.claim?.claim_request_details?.car_make || '',
  car_model: props.claim?.claim_request_details?.car_model || '',
  model_year: props.claim?.claim_request_details?.model_year || '',

  /* Health-specific fields */
  claim_request_type_id: props.claim?.claim_request_type_id || '',
  service_type_id: props.claim?.claim_request_details?.service_type_id || '',

  // Additional Fields
  claim_type_id: props.claim?.claim_type_id || '',
  claim_number: props.claim?.claim_number || '',

  // Claim denial reason (visible when editing)
  claim_decline_reason: props.claim?.claim_decline_reason || '',
  incident_date: props.claim?.incident_date || '',
});

const claimRequestTypeOptions = computed(() => {
  return (
    props.dropdowns?.claimRequestTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

const claimServiceTypeOptions = computed(() => {
  return (
    props.dropdowns?.claimServiceTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

const claimTypeOptions = computed(() => {
  return (
    props.dropdowns?.claimTypes?.map(ct => ({
      value: ct.id,
      label: ct.text,
    })) || []
  );
});

const carMakeOptions = computed(() => {
  return (
    props.dropdowns?.carMake?.map(item => ({
      value: item.text,
      label: item.text,
    })) || []
  );
});

const carModelOptions = computed(() => {
  return (
    props.dropdowns?.carModel?.map(item => ({
      value: item.text,
      label: item.text,
    })) || []
  );
});

const carModelYearOptions = computed(() => {
  return (
    props.dropdowns?.carModelYear?.map(item => ({
      value: item.text,
      label: item.text,
    })) || []
  );
});

const getCarModel = reset => {
  let carMakeCode = props.dropdowns?.carMake.find(
    item => item.text === claimForm.car_make,
  )?.id;
  console.log('carMakeCode', carMakeCode, ', reset', reset);

  axios.get(`/car-model-by-id?id=${carMakeCode}`).then(({ data }) => {
    props.dropdowns.carModel = data;
    if (claimForm.car_model !== null && reset) {
      claimForm.car_model = null;
    }
  });
};

// Check if the claim request type is pending approval
const isPendingClaimRequestType = ref(
  page.props.claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE ===
    page.props.claim?.claim_request_type?.code,
);

// Check if the selected line of business is Car
const isCarLOB = computed(() => {
  return page.props.quoteTypeIds?.Car === page.props.claim.quote_type_id;
});

// Check if the selected line of business is Health
const isHealthLOB = computed(() => {
  return page.props.quoteTypeIds?.Health === page.props.claim.quote_type_id;
});

function formatCurrency(amount) {
  if (!amount) return '-';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'AED',
  }).format(amount);
}

// Custom validation rules that mirror ClaimDetailsUpdateRequest
const validationRules = {
  // Plate number validation
  platNumber: v => {
    if (!v) return true;
    if (v.length > 20) return 'Plate number cannot exceed 20 characters.';
    if (!/^[A-Za-z0-9\-\s]+$/.test(v))
      return 'Plate number can only contain letters, numbers, hyphens, and spaces.';
    return true;
  },

  // Car make/model validation
  carMake: v => {
    if (!v) return true;
    if (v.length > 100) return 'Vehicle make cannot exceed 100 characters.';
    return true;
  },

  carModel: v => {
    if (!v) return true;
    if (v.length > 100) return 'Vehicle model cannot exceed 100 characters.';
    return true;
  },

  // Model year validation
  modelYear: v => {
    if (!v) return true;
    const year = parseInt(v);
    const currentYear = new Date().getFullYear();
    if (isNaN(year)) return 'Vehicle year must be a valid number.';
    if (year < 1900) return 'Vehicle year must be after 1900.';
    if (year > currentYear + 1)
      return 'Vehicle year cannot be more than one year in the future.';
    return true;
  },

  // Claim number validation
  claimNumber: v => {
    if (!v) return 'Claim number is required.';
    if (v.length > 100) return 'Claim number cannot exceed 100 characters.';
    return true;
  },

  // Claim decline reason validation
  claimDeclineReason: v => {
    if (!v) return true;
    if (v.length > 2000)
      return 'Claim decline reason cannot exceed 2000 characters.';
    return true;
  },

  // Incident date validation
  incidentDate: v => {
    if (!v) return 'Incident date is required.';

    let date;
    // Handle different date formats
    if (v instanceof Date) {
      date = new Date(v);
    } else if (typeof v === 'string') {
      // Try parsing the string - handle various formats
      date = new Date(v);

      // If invalid, try parsing as ISO date format
      if (isNaN(date.getTime())) {
        const isoDate = v.includes('T') ? v : v + 'T00:00:00';
        date = new Date(isoDate);
      }

      // If still invalid, try parsing DD/MM/YYYY format
      if (isNaN(date.getTime()) && v.includes('/')) {
        const parts = v.split('/');
        if (parts.length === 3) {
          // Assuming DD/MM/YYYY format
          const day = parseInt(parts[0]);
          const month = parseInt(parts[1]) - 1; // Month is 0-indexed
          const year = parseInt(parts[2]);
          date = new Date(year, month, day);
        }
      }
    } else {
      date = new Date(v);
    }

    const today = new Date();
    today.setHours(23, 59, 59, 999); // Set to end of today to allow today's date

    console.log('Original value:', v, 'Parsed date:', date, 'Today:', today);

    if (isNaN(date.getTime())) {
      return 'Incident date must be a valid date.';
    }

    if (date > today) {
      return 'Incident date cannot be in the future.';
    }

    return true;
  },
};

// Validation functions that mirror the PHP validation logic
const validateCarFields = () => {
  const errors = {};

  if (isCarLOB.value) {
    // For car LOB, all car fields are required
    if (!claimForm.plate_number?.trim()) {
      errors.plate_number = 'Vehicle plate number is required.';
    } else {
      const platValidation = validationRules.platNumber(claimForm.plate_number);
      if (platValidation !== true) errors.plate_number = platValidation;
    }

    if (!claimForm.car_make?.trim()) {
      errors.car_make = 'Vehicle make is required.';
    } else {
      const makeValidation = validationRules.carMake(claimForm.car_make);
      if (makeValidation !== true) errors.car_make = makeValidation;
    }

    if (!claimForm.car_model?.trim()) {
      errors.car_model = 'Vehicle model is required.';
    } else {
      const modelValidation = validationRules.carModel(claimForm.car_model);
      if (modelValidation !== true) errors.car_model = modelValidation;
    }

    if (!claimForm.model_year) {
      errors.model_year = 'Vehicle year is required.';
    } else {
      const yearValidation = validationRules.modelYear(claimForm.model_year);
      if (yearValidation !== true) errors.model_year = yearValidation;
    }
  }

  return errors;
};

const validateHealthFields = () => {
  const errors = {};

  if (isHealthLOB.value) {
    // Claim request type is required for health
    if (!claimForm.claim_request_type_id) {
      errors.claim_request_type_id = 'Claim request type is required.';
    } else {
      // Check if it's pending approval type and service type is required
      const claimRequestType = props.dropdowns?.claimRequestTypes.find(
        item => item.id === claimForm.claim_request_type_id,
      );

      const isPendingType =
        claimRequestType?.code ===
        claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE;

      if (isPendingType && !claimForm.service_type_id) {
        errors.service_type_id = 'Service type is required.';
      }
    }
  }

  return errors;
};

const validateCommonFields = () => {
  const errors = {};

  // Claim type is always required
  if (!claimForm.claim_type_id) {
    errors.claim_type_id = 'Claim type is required.';
  }

  // Incident date is always required
  const incidentValidation = validationRules.incidentDate(
    claimForm.incident_date,
  );
  if (incidentValidation !== true) {
    errors.incident_date = incidentValidation;
  }

  // Validate claim number if provided
  if (claimForm.claim_number) {
    const claimNumberValidation = validationRules.claimNumber(
      claimForm.claim_number,
    );
    if (claimNumberValidation !== true) {
      errors.claim_number = claimNumberValidation;
    }
  }

  // Validate claim decline reason if provided
  if (claimForm.claim_decline_reason) {
    const declineReasonValidation = validationRules.claimDeclineReason(
      claimForm.claim_decline_reason,
    );
    if (declineReasonValidation !== true) {
      errors.claim_decline_reason = declineReasonValidation;
    }
  }

  return errors;
};

// Main validation function
const validateForm = () => {
  // Clear existing errors
  Object.keys(claimForm.errors).forEach(key => {
    claimForm.errors[key] = '';
  });

  let allErrors = {};

  // Validate common fields
  allErrors = { ...allErrors, ...validateCommonFields() };

  // Validate LOB-specific fields
  if (isCarLOB.value) {
    allErrors = { ...allErrors, ...validateCarFields() };
  }

  if (isHealthLOB.value) {
    allErrors = { ...allErrors, ...validateHealthFields() };
  }

  // Set errors on the form
  Object.keys(allErrors).forEach(key => {
    claimForm.errors[key] = allErrors[key];
  });

  return Object.keys(allErrors).length === 0;
};

// Data preparation function that mirrors PHP prepareForValidation
const prepareFormData = data => {
  return {
    ...data,
    // Trim and normalize string fields
    plate_number: data.plate_number
      ? data.plate_number.trim().toUpperCase()
      : null,
    car_make: data.car_make ? data.car_make.trim() : null,
    car_model: data.car_model ? data.car_model.trim() : null,
    claim_number: data.claim_number ? data.claim_number.trim() : null,
    claim_decline_reason: data.claim_decline_reason
      ? data.claim_decline_reason.trim()
      : null,
    // Format incident date
    incident_date: data.incident_date
      ? formattedDateYmd(data.incident_date)
      : null,
  };
};

const updateClaim = isValid => {
  // Perform custom validation
  const isFormValid = validateForm();

  if (!isValid || !isFormValid) {
    // Show validation errors
    Object.keys(claimForm.errors).forEach(key => {
      if (claimForm.errors[key]) {
        notification.error({
          title: claimForm.errors[key],
          position: 'top',
        });
      }
    });
    return;
  }

  claimForm
    .transform(data => prepareFormData(data))
    .post(route('claims.update.details', props.claim?.uuid), {
      preserveScroll: true,
      onSuccess: response => {
        console.log('response', response);
        router.visit(route('claims.show', props.claim?.uuid), {
          preserveScroll: true,
        });
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    });
};

watch(
  () => claimForm.claim_request_type_id,
  newClaimRequestId => {
    console.log('newClaimRequestId', newClaimRequestId);
    let requestTypeCode = props.dropdowns?.claimRequestTypes.find(
      item => item.id === newClaimRequestId,
    )?.code;
    if (
      requestTypeCode !== claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE
    ) {
      claimForm.service_type_id = null;
      isPendingClaimRequestType.value = false;
    } else {
      isPendingClaimRequestType.value = true;
    }
    console.log(
      'requestTypeCode',
      requestTypeCode,
      'isPendingClaimRequestType',
      isPendingClaimRequestType.value,
    );
  },
);
</script>

<template>
  <div class="p-4 rounded shadow my-6 bg-white">
    <Collapsible :expanded="true">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Claim Details</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form :form="claimForm" @submit="updateClaim">
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CODE</dt>
                <dd class="font-mono">{{ claim.code }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ claim.source || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Line Of Business</dt>
                <dd>{{ claim.quote_type?.text }}</dd>
              </div>
              <template v-if="claim.claim_request_details">
                <template v-if="isCarLOB">
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">
                      VEHICLE PLATE NUMBER <span class="text-red-500">*</span>
                    </dt>
                    <dd>
                      <x-input
                        v-model="claimForm.plate_number"
                        type="text"
                        placeholder="Enter Plate Number"
                        class="w-full"
                        :error="claimForm.errors.plate_number"
                        :rules="[validationRules.platNumber]"
                      />
                    </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">
                      VEHICLE MAKE <span class="text-red-500">*</span>
                    </dt>
                    <dd>
                      <template v-if="claim.claim_request_details.car_make">
                        {{ claim.claim_request_details.car_make }}
                      </template>
                      <template v-else>
                        <x-select
                          v-model="claimForm.car_make"
                          @update:modelValue="getCarModel(true)"
                          :options="carMakeOptions"
                          placeholder="Select Vehicle Make"
                          class="w-full"
                          :error="claimForm.errors.car_make"
                          :rules="[isRequired, validationRules.carMake]"
                        />
                      </template>
                    </dd>
                  </div>

                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">
                      VEHICLE MODEL <span class="text-red-500">*</span>
                    </dt>
                    <dd>
                      <template v-if="claim.claim_request_details.car_model">
                        {{ claim.claim_request_details.car_model }}
                      </template>
                      <template v-else>
                        <x-select
                          v-model="claimForm.car_model"
                          placeholder="Select Vehicle Model"
                          :options="carModelOptions"
                          filterable
                          filterPlaceholder="Filter Car Model...."
                          :error="claimForm.errors.car_model"
                          :rules="[isRequired, validationRules.carModel]"
                        />
                      </template>
                    </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">
                      VEHICLE YEAR <span class="text-red-500">*</span>
                    </dt>
                    <dd>
                      <template v-if="claim.claim_request_details.model_year">
                        {{ claim.claim_request_details.model_year }}
                      </template>
                      <template v-else>
                        <x-select
                          v-model="claimForm.model_year"
                          placeholder="Select Car Model Year"
                          :options="carModelYearOptions"
                          filterable
                          filterPlaceholder="Filter Car Model Year...."
                          :error="claimForm.errors.model_year"
                          :rules="[isRequired, validationRules.modelYear]"
                        />
                      </template>
                    </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">APPROVED REPAIR AMOUNT</dt>
                    <dd>{{ formatCurrency(claim.approved_repair_amount) }}</dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">APPROVED TOTAL LOSS AMOUNT</dt>
                    <dd>
                      {{ formatCurrency(claim.approved_total_loss_amount) }}
                    </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">APPROVED CASH LOSS AMOUNT</dt>
                    <dd>
                      {{ formatCurrency(claim.approved_cash_loss_amount) }}
                    </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">CLAIM DENIAL REASON</dt>
                    <dd>
                      <x-textarea
                        v-model="claimForm.claim_decline_reason"
                        placeholder="Claim Denial Reason..."
                        rows="4"
                        class="w-full"
                        :error="claimForm.errors.claim_decline_reason"
                        :rules="[validationRules.claimDeclineReason]"
                      />
                    </dd>
                  </div>
                </template>
                <template v-if="isHealthLOB">
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">
                      HEALTH CLAIM REQUEST TYPE
                      <span class="text-red-500">*</span>
                    </dt>
                    <dd>
                      <x-select
                        v-model="claimForm.claim_request_type_id"
                        placeholder="Select Claim Request Type"
                        :options="claimRequestTypeOptions"
                        :rules="[isRequired]"
                        filterable
                        filterPlaceholder="Filter Claim Request Type...."
                        :error="claimForm.errors.claim_request_type_id"
                      />
                    </dd>
                  </div>
                  <div
                    v-if="isPendingClaimRequestType"
                    class="grid sm:grid-cols-2"
                  >
                    <dt class="font-medium">
                      HEALTH CLAIM SERVICE TYPE
                      <span class="text-red-500">*</span>
                    </dt>
                    <dd>
                      <x-select
                        v-model="claimForm.service_type_id"
                        :options="claimServiceTypeOptions"
                        placeholder="Select Service Type"
                        class="w-full"
                        :error="claimForm.errors.service_type_id"
                        :rules="isPendingClaimRequestType ? [isRequired] : []"
                      />
                    </dd>
                  </div>
                </template>
              </template>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Insurer Claim Number</dt>
                <dd>
                  <x-input
                    v-model="claimForm.claim_number"
                    type="text"
                    placeholder="Enter Insurer Claim Number"
                    class="w-full"
                    :error="claimForm.errors.claim_number"
                    :rules="[validationRules.claimNumber]"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY NUMBER</dt>
                <dd>{{ claim.policy_number || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY ADVISOR</dt>
                <dd>{{ claim.personal_quote?.advisor?.name || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIMS MANAGER</dt>
                <dd>{{ claim.manager?.name || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  CLAIM TYPE <span class="text-red-500">*</span>
                </dt>
                <dd>
                  <x-select
                    v-model="claimForm.claim_type_id"
                    placeholder="Select Claim Type"
                    :options="claimTypeOptions"
                    :rules="[isRequired]"
                    filterable
                    filterPlaceholder="Filter Claim Type...."
                    :error="claimForm.errors.claim_type_id"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  INCIDENT DATE <span class="text-red-500">*</span>
                </dt>
                <dd>
                  <DatePicker
                    v-model="claimForm.incident_date"
                    placeholder="Select Incident Date"
                    :error="claimForm.errors.incident_date"
                    :clearable="false"
                    type="date"
                    max-date="today"
                    :utc="true"
                    :rules="[validationRules.incidentDate]"
                  />
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Complaint Status</dt>
                <dd>{{ claim.complaint_status?.text || 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Next Follow Up Date</dt>
                <dd>
                  {{
                    claim.next_followup_datetime
                      ? formattedDateDmyWithTime(claim.next_followup_datetime)
                      : 'N/A'
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ formattedDateDmyWithTime(claim.created_at) }}</dd>
              </div>
            </dl>
            <x-divider class="mt-4" />
            <div class="flex justify-end">
              <x-button
                v-if="can(permissionsEnum.CLAIM_EDIT)"
                class="mt-4"
                color="emerald"
                size="sm"
                :loading="claimForm.processing"
                type="submit"
              >
                Update
              </x-button>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
