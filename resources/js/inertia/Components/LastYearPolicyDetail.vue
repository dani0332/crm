<script setup>
const page = usePage();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  canAddBatchNumber: Boolean,
  modelType: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  inslyId: String,
  previousQuote: {
    type: Object,
    default: () => ({}),
  },
});

const can = permission => useCan(permission);
const advisors = page.props.advisors;
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const permissionsEnum = page.props.permissionsEnum;
const leadSource = page.props.leadSource;


const dateFormat = date => {
  try {
    console.log('date', date);
    if (!date || date == '' || date == null) {
      return '-';
    }

    if (date.includes(':') && !date.includes(' ')) {
      return date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
    }

    const formattedDate = parseDate(date);
    return formattedDate;
  } catch (error) {
    console.error(`Error parsing date "${date}": ${error.message}`);
  }
};

// Check if user can edit last year details
const canEditLastYearDetails = computed(() => {
  return can(permissionsEnum.EDIT_LAST_YEAR_DETAILS);
});

// Legacy edit check for renewal batch only
const allowEdit = computed(() => {
  if (
    (props.quote.renewal_batch === '' || props.quote.renewal_batch == null) &&
    props.canAddBatchNumber == true &&
    props.modelType == 'Car'
  )
    return true;

  return false;
});

// Memoized advisor options to prevent recalculation - THIS IS THE KEY FIX!
const advisorOptions = ref([]);

// Function to build advisor options - only runs when needed
const buildAdvisorOptions = () => {
  if (!advisors || advisors.length === 0) {
    advisorOptions.value = [];
    return;
  }
  
  console.log('Building advisor options, count:', advisors.length);
  
  // Simple mapping without sorting to improve performance
  const options = advisors.map(advisor => ({
    value: advisor.id, // Keep as original type (string or number) - NO CONVERSION
    label: advisor.name || `Advisor ${advisor.id}`,
  }));
  
  // Add current advisor if not in list
  const currentAdvisorId = props?.quote?.previous_advisor_id;
  if (currentAdvisorId && !options.find(opt => opt.value == currentAdvisorId)) {
    options.unshift({
      value: currentAdvisorId, // Keep original type
      label: `Previous Advisor (ID: ${currentAdvisorId})`,
    });
  }
  
  advisorOptions.value = options;
  console.log('Advisor options built:', options.length, 'Current advisor ID:', currentAdvisorId);
};

// Edit mode state
const isEditMode = ref(false);

const { isRequired, isNumber, isEmail } = useRules();

// Helper function to format date for input
const formatDateForInput = (date) => {
  if (!date || date === '' || date === 'null' || date === 'undefined') return null;
  
  try {
    // If date is already in YYYY-MM-DD format, return as is
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
      return date;
    }
    
    // Handle DD-MM-YYYY format (common in the system)
    if (typeof date === 'string' && /^\d{2}-\d{2}-\d{4}$/.test(date)) {
      const [day, month, year] = date.split('-');
      return `${year}-${month}-${day}`;
    }
    
    // Handle DD/MM/YYYY format
    if (typeof date === 'string' && /^\d{2}\/\d{2}\/\d{4}$/.test(date)) {
      const [day, month, year] = date.split('/');
      return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }
    
    // Handle YYYY/MM/DD format
    if (typeof date === 'string' && /^\d{4}\/\d{2}\/\d{2}$/.test(date)) {
      return date.replace(/\//g, '-');
    }
    
    // Try to parse as Date object for other formats
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) {
      console.warn('Unable to parse date:', date);
      return null;
    }
    
    // Convert to YYYY-MM-DD format
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
  } catch (error) {
    console.error('Error formatting date:', date, error);
    return null;
  }
};

// Initialize form with reactive data
const initializeFormData = () => ({
  model_type: props?.modelType,
  quote_id: props?.quote?.id,
  renewal_batch: props?.quote?.renewal_batch || null,
  previous_policy_expiry_date: formatDateForInput(props?.quote?.previous_policy_expiry_date),
  previous_policy_start_date: formatDateForInput(props?.quote?.previous_policy_start_date),
  previous_quote_policy_number: props?.quote?.previous_quote_policy_number || null,
  previous_quote_policy_premium: props?.quote?.previous_quote_policy_premium || null,
  previous_advisor_id: props?.quote?.previous_advisor_id || null, // Keep original type - NO CONVERSION
});

// Enhanced form with all editable fields
const policyForm = useForm(initializeFormData());

// Debug logging to see what date values we're getting
console.log('Date debugging:', {
  original_expiry: props?.quote?.previous_policy_expiry_date,
  original_start: props?.quote?.previous_policy_start_date,
  formatted_expiry: formatDateForInput(props?.quote?.previous_policy_expiry_date),
  formatted_start: formatDateForInput(props?.quote?.previous_policy_start_date),
});

// Computed property for debugging form values
const debugFormValues = computed(() => ({
  form_expiry: policyForm.previous_policy_expiry_date,
  form_start: policyForm.previous_policy_start_date,
  prop_expiry: props?.quote?.previous_policy_expiry_date,
  prop_start: props?.quote?.previous_policy_start_date,
  advisor_id_form: policyForm.previous_advisor_id,
  advisor_id_prop: props?.quote?.previous_advisor_id,
  advisor_options_count: advisorOptions.value.length,
  selected_advisor: advisorOptions.value.find(opt => opt.value == policyForm.previous_advisor_id), // Use loose equality
  advisor_exists: advisorOptions.value.find(opt => opt.value == props?.quote?.previous_advisor_id),
}));

// Validation rules
const validatePremium = value => {
  if (!value) return true;
  const num = parseFloat(value);
  return !isNaN(num) && num >= 0 || 'Premium must be a valid positive number';
};

const validateDate = value => {
  if (!value) return true;
  const date = new Date(value);
  return !isNaN(date.getTime()) || 'Please enter a valid date';
};

const validateStartDate = value => {
  if (!value) return true;
  if (!policyForm.previous_policy_expiry_date) return validateDate(value);
  
  const startDate = new Date(value);
  const expiryDate = new Date(policyForm.previous_policy_expiry_date);
  
  if (isNaN(startDate.getTime())) return 'Please enter a valid date';
  if (startDate > expiryDate) return 'Start date must be before expiry date';
  
  return true;
};

function toggleEditMode() {
  isEditMode.value = !isEditMode.value;
  if (!isEditMode.value) {
    // Reset form to original values when canceling
    const originalData = initializeFormData();
    Object.keys(originalData).forEach(key => {
      policyForm[key] = originalData[key];
    });
    policyForm.clearErrors();
  }
}

function onSubmit(isValid) {
  if (isValid) {
    policyForm
      .transform(data => ({
        ...data,
        isInertia: true,
      }))
      .post(`/quotes/update-last-year-policy`, {
        preserveScroll: true,
        onSuccess: () => {
          isEditMode.value = false;
        },
        onFinish: () => {},
      });
  }
}
const readOnlyMode = reactive({
  isDisable: true,
});

// Watch for prop changes and update form accordingly
watch(() => props.quote, (newQuote) => {
  if (newQuote && !isEditMode.value) {
    const newData = initializeFormData();
    Object.keys(newData).forEach(key => {
      policyForm[key] = newData[key];
    });
  }
}, { deep: true });

// Watch for advisors prop changes
watch(() => advisors, () => {
  buildAdvisorOptions();
}, { deep: true });

// Watch for quote changes to update advisor selection
watch(() => props.quote?.previous_advisor_id, (newAdvisorId) => {
  if (newAdvisorId && !isEditMode.value) {
    policyForm.previous_advisor_id = newAdvisorId; // Keep original type
    // Rebuild options to include current advisor if not present
    buildAdvisorOptions();
  }
}, { immediate: true });

onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
  // Build advisor options on mount
  buildAdvisorOptions();
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Last Year's Policy Details
          </h3>
          <div v-if="canEditLastYearDetails && readOnlyMode.isDisable" class="flex gap-2" @click.stop>
            <x-button 
              v-if="!isEditMode"
              @click.stop="toggleEditMode"
              size="sm" 
              color="primary" 
              variant="outline"
            >
              Edit Details
            </x-button>
            <div v-if="isEditMode" class="flex gap-2">
              <x-button 
                @click.stop="toggleEditMode"
                size="sm" 
                color="gray" 
                variant="outline"
              >
                Cancel
              </x-button>
            </div>
          </div>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="flex gap-2 mb-4 justify-end">
          <Link
            v-if="inslyId && can(permissionsEnum.VIEW_LEGACY_DETAILS) && !quote.previous_quote_policy_number"
            :href="`/legacy-policy/${inslyId}`"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
          <Link
            v-else-if="
              inslyId &&
              (quote.source == leadSource.RENEWAL_UPLOAD ||
                quote.source == leadSource.INSLY) &&
              quote.previous_quote_policy_number != null &&
              can(permissionsEnum.VIEW_LEGACY_DETAILS)
            "
            :href="
              route(
                'view-legacy-policy.renewal-uploads',
                quote.previous_quote_policy_number,
              )
            "
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
        </div>

        <!-- Display Mode -->
        <div v-if="!isEditMode" class="p-4 rounded shadow mb-6 bg-white">
          <div class="text-sm">
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Renewal Batch</div>
                <div>
                  {{
                    props?.quote?.renewal_batch_model?.name ?? // this is use for Personal Quotes ( bike, yacht, cycle, life, health, home, travel and life etc)
                    props?.quote?.renewal_batch_text ?? // this is use for health, home, travel and business
                    props?.quote?.renewal_batch ?? // this is use for Car
                    'N/A'
                  }}
                </div>
              </div>

              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Previous Policy Number</div>
                <div>{{ props?.quote?.previous_quote_policy_number || 'N/A' }}</div>
              </div>

              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Previous Policy Expiry Date</div>
                <div>
                  {{ dateFormat(props?.quote?.previous_policy_expiry_date) }}
                </div>
              </div>

              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Previous Policy Premium</div>
                <div>{{ props?.quote?.previous_quote_policy_premium || 'N/A' }}</div>
              </div>

              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Previous Policy Start Date</div>
                <div>
                  {{ dateFormat(props?.quote?.previous_policy_start_date) }}
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Previous Advisor</div>
                <div>
                  {{ props?.quote?.previous_advisor_id_text || 'N/A' }}
                </div>
              </div>
              <div class="grid sm:grid-cols-2" v-if="previousQuote?.code">
                <div class="font-medium">Previous Ref-ID</div>
                <div>
                  <Link
                    :href="`/quotes/car/${previousQuote?.uuid}`"
                    class="text-primary-600 hover:underline font-semibold"
                  >
                    {{ previousQuote?.code }}
                  </Link>
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Policy Number</div>
                <div>{{ props?.quote?.policy_number || 'N/A' }}</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div class="font-medium">Lost reason</div>
                <div>
                  {{ props?.quote?.lost_reason || 'N/A' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Debug Info (remove in production) -->
        <div v-if="isEditMode && hasRole(rolesEnum.Engineering)" class="p-2 bg-gray-100 rounded mb-4 text-xs">
          <strong>Debug Info:</strong>
          <pre>{{ JSON.stringify(debugFormValues, null, 2) }}</pre>
        </div>

        <!-- Edit Mode -->
        <x-form v-if="isEditMode" @submit="onSubmit" :auto-focus="false">
          <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <!-- Renewal Batch -->
              <x-input
                label="Renewal Batch"
                v-model="policyForm.renewal_batch"
                type="text"
                :error="policyForm.errors.renewal_batch"
                placeholder="Enter renewal batch"
              />

              <!-- Previous Policy Number -->
              <x-input
                label="Previous Policy Number"
                v-model="policyForm.previous_quote_policy_number"
                type="text"
                :error="policyForm.errors.previous_quote_policy_number"
                placeholder="Enter previous policy number"
              />

              <!-- Previous Policy Expiry Date -->
              <x-input
                label="Previous Policy Expiry Date"
                v-model="policyForm.previous_policy_expiry_date"
                type="date"
                placeholder="Select policy expiry date"
                :error="policyForm.errors.previous_policy_expiry_date"
              />

              <!-- Previous Policy Premium -->
              <x-input
                label="Previous Policy Premium"
                v-model="policyForm.previous_quote_policy_premium"
                type="number"
                step="0.01"
                min="0"
                :rules="[validatePremium]"
                :error="policyForm.errors.previous_quote_policy_premium"
                placeholder="Enter premium amount"
              />

              <!-- Previous Policy Start Date -->
              <x-input
                label="Previous Policy Start Date"
                v-model="policyForm.previous_policy_start_date"
                type="date"
                :rules="[validateStartDate]"
                :error="policyForm.errors.previous_policy_start_date"
              />

              <!-- Previous Advisor -->
              <x-select
                label="Previous Advisor"
                v-model="policyForm.previous_advisor_id"
                :options="advisorOptions"
                :error="policyForm.errors.previous_advisor_id"
                placeholder="Select Previous Advisor"
                filterable
                class="w-full"
              />
            </div>
          </div>
          
          <div class="flex justify-end gap-3">
            <x-button 
              @click="toggleEditMode"
              color="gray" 
              variant="outline"
              type="button"
            >
              Cancel
            </x-button>
            <x-button 
              color="primary" 
              type="submit"
              :disabled="policyForm.processing"
            >
              {{ policyForm.processing ? 'Updating...' : 'Update Details' }}
            </x-button>
          </div>
        </x-form>

        <!-- Legacy renewal batch edit (for backward compatibility) -->
        <x-form v-if="!isEditMode && !canEditLastYearDetails" @submit="onSubmit" :auto-focus="false">
          <div
            class="flex justify-between gap-3 items-center"
            v-if="canAddBatchNumber"
          >
            <x-input
              v-if="allowEdit"
              label="Renewal batch"
              required
              v-model="policyForm.renewal_batch"
              type="tel"
              class="w-full md:w-64"
              :rules="[isRequired]"
              :error="policyForm.errors.renewal_batch"
            />
            <div v-if="readOnlyMode.isDisable === true">
              <x-button v-if="allowEdit" color="primary" type="submit">
                Update
              </x-button>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
