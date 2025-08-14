<script setup>
const props = defineProps({
  claim: Object,
  dropdowns: Object,
  additionalContacts: Object,
  documents: Object,
});

const modelClass = 'App\\Models\\ClaimRequest';
const page = usePage();
const claimsEnum = page.props.claimsEnum;
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();
const { isRequired, maxCharacters, isNumber, minValue, maxValue, isValidName } = useRules();

const quoteTypeIds = page.props.quoteTypeIds;

const sectionExpanded = ref(true);


const claimForm = useForm({

  quote_type_id: props.claim?.quote_type_id,

  // Car-specific fields (visible when editing)
  plat_number: props.claim?.claim_request_details?.plat_number || '',
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

const claimStatusForm = useForm({
  claim_status_id: props.claim?.claim_status_id || '',
  claim_sub_status_id: props.claim?.claim_sub_status_id || '',
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

const getCarModel = reset => {
  let carMakeCode = props.dropdowns?.carMake.find(item => item.text === claimForm.car_make)?.id;
  console.log('carMakeCode', carMakeCode , ', reset' , reset);

  axios.get(`/car-model-by-id?id=${carMakeCode}`).then(({ data }) => {
    props.dropdowns.carModel = data;
    if (claimForm.car_model !== null && reset) {
      claimForm.car_model = null;
    }
  });
};



const statusOptions = computed(() => {
  return (
    props.dropdowns.claimStatuses?.map(status => ({
      value: status.id,
      label: status.text,
    })) || []
  );
});

const subStatusOptions = computed(() => {
  return (
    props.dropdowns.claimSubStatuses?.filter(subStatus => subStatus.quote_type_id === page.props.claim.quote_type_id)?.map(subStatus => ({
      value: subStatus.id,
      label: subStatus.text,
    })) || []
  );
});

const complaintStatusOptions = computed(() => {
  return (
    props.dropdowns.complaintStatuses?.map(status => ({
      value: status.value,
      label: status.label,
    })) || []
  );
});

// Check if the claim request type is pending approval
const isPendingClaimRequestType = ref(page.props.claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE === page.props.claim?.claim_request_type?.code);

// Check if the selected line of business is Car
const isCarLOB = computed(() => {
  return page.props.quoteTypeIds?.Car === page.props.claim.quote_type_id;
});

// Check if the selected line of business is Health
const isHealthLOB = computed(() => {
  return page.props.quoteTypeIds?.Health === page.props.claim.quote_type_id;
});

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function formatDateTime(date) {
  console.log('formatDateTime -> date -> ', date);
  if (!date) return '-';
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

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
    if (!/^[A-Z0-9\-\s]+$/.test(v)) return 'Plate number can only contain letters, numbers, hyphens, and spaces.';
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
    if (year > currentYear + 1) return 'Vehicle year cannot be more than one year in the future.';
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
    if (v.length > 2000) return 'Claim decline reason cannot exceed 2000 characters.';
    return true;
  },

  // Incident date validation
  incidentDate: v => {
    if (!v) return 'Incident date is required.';
    const date = new Date(v);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (isNaN(date.getTime())) return 'Incident date must be a valid date.';
    if (date >= today) return 'Incident date must be before today.';
    return true;
  }
};

// Validation functions that mirror the PHP validation logic
const validateCarFields = () => {
  const errors = {};

  if (isCarLOB.value) {
    // For car LOB, all car fields are required
    if (!claimForm.plat_number?.trim()) {
      errors.plat_number = 'Vehicle plate number is required.';
    } else {
      const platValidation = validationRules.platNumber(claimForm.plat_number);
      if (platValidation !== true) errors.plat_number = platValidation;
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
        item => item.id === claimForm.claim_request_type_id
      );

      const isPendingType = claimRequestType?.code === claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE;

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
  const incidentValidation = validationRules.incidentDate(claimForm.incident_date);
  if (incidentValidation !== true) {
    errors.incident_date = incidentValidation;
  }

  // Validate claim number if provided
  if (claimForm.claim_number) {
    const claimNumberValidation = validationRules.claimNumber(claimForm.claim_number);
    if (claimNumberValidation !== true) {
      errors.claim_number = claimNumberValidation;
    }
  }

  // Validate claim decline reason if provided
  if (claimForm.claim_decline_reason) {
    const declineReasonValidation = validationRules.claimDeclineReason(claimForm.claim_decline_reason);
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
const prepareFormData = (data) => {
  return {
    ...data,
    // Trim and normalize string fields
    plat_number: data.plat_number ? data.plat_number.trim().toUpperCase() : null,
    car_make: data.car_make ? data.car_make.trim() : null,
    car_model: data.car_model ? data.car_model.trim() : null,
    claim_number: data.claim_number ? data.claim_number.trim() : null,
    claim_decline_reason: data.claim_decline_reason ? data.claim_decline_reason.trim() : null,
    // Format incident date
    incident_date: data.incident_date ? new Date(data.incident_date).toISOString().split('T')[0] : null,
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

  claimForm.transform(data => prepareFormData(data))
    .post(route('claims.update.details', props.claim?.uuid), {
      preserveScroll: true,
      onSuccess: response => {
        console.log('response', response);
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

const updateClaimStatus = isValid => {
  console.log('updateClaimStatus');
  claimStatusForm.post(route('claims.update.status', props.claim?.uuid), {
      preserveScroll: true,
      onSuccess: response => {
        console.log('response', response);
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


const disableClaimStatusUpdate = computed(() => {
  return !page.props.requiredFieldsFilled ||  !canAny([permissionsEnum.CLAIMS_STATUS_UPDATE, permissionsEnum.CLAIMS_SUB_STATUS_UPDATE]) ; 
});

watch(() => claimForm.claim_request_type_id, (newClaimRequestId) => {
  console.log('newClaimRequestId', newClaimRequestId);
  let requestTypeCode = props.dropdowns?.claimRequestTypes.find(item => item.id === newClaimRequestId)?.code;
  if (requestTypeCode !== claimsEnum?.CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE) {
    claimForm.service_type_id = null;
    isPendingClaimRequestType.value = false;
  }else{
    isPendingClaimRequestType.value = true;
  }
  console.log('requestTypeCode', requestTypeCode, 'isPendingClaimRequestType', isPendingClaimRequestType.value);
});

</script>

<template>
  <div>
    <Head :title="`Claim ${claim.ref_id}`" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">
          Claim Details - {{ claim.uuid }}
        </h2>

      </template>
      <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
        <div class="flex gap-2">
          <Link
            v-if="can(permissionsEnum.CLAIM_LIST)"
            href="/claim"
            preserve-scroll
          >
            <x-button size="sm" color="primary" tag="div">Claims List</x-button>
          </Link>
          <Link
            v-if="can(permissionsEnum.CLAIM_EDIT)"
            :href="`/claim/${claim.uuid}/edit`"
          >
            <x-button size="sm" color="emerald" tag="div">Edit</x-button>
          </Link>
        </div>
      </div>
    </StickyHeader>

    <!-- Claim Details -->
    <div class="p-4 rounded shadow my-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Claim Details
            </h3>
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
                      <dt class="font-medium">VEHICLE PLATE NUMBER <span class="text-red-500">*</span></dt>
                      <dd>
                        <x-input
                            v-model="claimForm.plat_number"
                            type="text"
                            placeholder="Enter Plate Number"
                            class="w-full"
                            :error="claimForm.errors.plat_number"
                            :rules="[validationRules.platNumber]"
                          />
                        </dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                      <dt class="font-medium">VEHICLE MAKE <span class="text-red-500">*</span></dt>
                      <dd>
                        <template v-if="claim.claim_request_details.car_make">
                          {{ claim.claim_request_details.car_make   }}
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
                      <dt class="font-medium">VEHICLE MODEL <span class="text-red-500">*</span></dt>
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
                      <dt class="font-medium">VEHICLE YEAR <span class="text-red-500">*</span></dt>
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
                      <dd>{{ formatCurrency(claim.approved_total_loss_amount) }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                      <dt class="font-medium">APPROVED CASH LOSS AMOUNT</dt>
                      <dd>{{ formatCurrency(claim.approved_cash_loss_amount) }}</dd>
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
                      <dt class="font-medium">HEALTH CLAIM REQUEST TYPE <span class="text-red-500">*</span></dt>
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
                    <div v-if="isPendingClaimRequestType" class="grid sm:grid-cols-2">
                      <dt class="font-medium">HEALTH CLAIM SERVICE TYPE <span class="text-red-500">*</span></dt>
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
                  <dt class="font-medium">CLAIM TYPE <span class="text-red-500">*</span></dt>
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
                  <dt class="font-medium">INCIDENT DATE <span class="text-red-500">*</span></dt>
                  <dd>
                    <DatePicker
                      v-model="claimForm.incident_date"
                      placeholder="Select Incident Date"
                      :error="claimForm.errors.incident_date"
                      :clearable="false"
                      :format="`dd/MM/yyyy`"
                      :rules="[validationRules.incidentDate]"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">CREATED DATE</dt>
                  <dd>{{ formatDateTime(claim.created_at) }}</dd>
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

    <!-- Customer Details -->
    <div class="p-4 rounded shadow my-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Customer Details
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words py-8">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">FIRST NAME</dt>
                <dd>{{ claim.first_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST NAME</dt>
                <dd>{{ claim.last_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">EMAIL</dt>
                <dd>{{ claim.email }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">MOBILE NUMBER</dt>
                <dd>{{ claim.mobile_no }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <div v-if="canAny([permissionsEnum.CLAIMS_STATUS_UPDATE, permissionsEnum.CLAIMS_SUB_STATUS_UPDATE])" class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">Claim Status</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <x-form :form="claimStatusForm" @submit="updateClaimStatus">
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div  v-if="can(permissionsEnum.CLAIMS_STATUS_UPDATE)" class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-select 
                  v-model="claimStatusForm.claim_status_id"
                  label="Claim Status"
                  :error="claimStatusForm.errors.claim_status_id"
                  :options="statusOptions"
                  :disabled="disableClaimStatusUpdate"
                  placeholder="Claim Status"
                  class="w-full uppercase"
                  filterable
                />

              </div>
            </div>
            <div class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-select
                  v-model="claimStatusForm.claim_sub_status_id"
                  label="Claim Sub Status"
                  :error="claimStatusForm.errors.claim_sub_status_id"
                  :options="subStatusOptions"
                  :disabled="disableClaimStatusUpdate"
                  placeholder="Claim Sub Status"
                  class="w-full uppercase"
                  filterable
                />

              </div>
            </div>
          </div>

          <x-divider class="mt-4" />
          <div class="flex justify-end">
              <x-button
                :disabled="disableClaimStatusUpdate"
                class="mt-4"
                color="emerald"
                size="sm"
                :loading="claimStatusForm.processing"
                type="submit"
              >
                Update
              </x-button>
            </div>
          </x-form>

        </template>
      </Collapsible>
    </div>

    <!-- Audit Logs -->
    <AuditLogs
      :type="modelClass"
      :id="$page.props.claim.id"
      :quoteCode="$page.props.claim.code"
      :expanded="sectionExpanded"
    />
  </div>
</template>
