<script setup>
const props = defineProps({
  claim: { type: Object, default: null },
  dropdowns: { type: Object, default: null },
  isEdit: { type: Boolean, default: false },
});

const page = usePage();
const notification = useToast();
const { isRequired, isEmail } = useRules();

const claimForm = useForm({
  // IMCRM Required Fields
  first_name: props.claim?.first_name || '',
  last_name: props.claim?.last_name || '',
  email: props.claim?.email || '',
  mobile_no: props.claim?.mobile_no || '',
  quote_type_id: props.claim?.quote_type_id || '',
  customer_id: props.claim?.customer_id || '',
  insurance_provider_id: props.claim?.insurance_provider_id || '',

  // Additional Fields
  claim_type_id: props.claim?.claim_type_id || '',
  incident_date: props.claim?.incident_date || '',
  incident_story: props.claim?.incident || '',
  policy_number: props.claim?.policy_number || '',
  claim_number: props.claim?.claim_number || '',

  // Car-specific fields (visible when editing)
  plate_number: props.claim?.plate_number || '',
  car_make: props.claim?.car_make || '',
  car_model: props.claim?.car_model || '',
  car_model_year: props.claim?.car_model_year || '',

  // Financial fields (visible when editing)
  approved_repair_amount: props.claim?.approved_repair_amount || '',
  approved_total_loss_amount: props.claim?.approved_total_loss_amount || '',
  approved_cash_loss_amount: props.claim?.approved_cash_loss_amount || '',

  // Claim denial reason (visible when editing)
  claim_denial_reason: props.claim?.claim_denial_reason || '',

  /* Health-specific fields */
  claim_request_type_id: props.claim?.claim_request_type_id || '',
  service_type_id: props.claim?.service_type_id || '',
  request_reference_number: props.claim?.request_reference_number || '',

  // Policy Selection
  selected_policy_id: null,
  selected_quote_uuid: null,
  policy_not_listed: false,

  // System Fields
  source: props.claim?.source || 'IMCRM',
});

// Policy search state
const policySearch = reactive({
  loading: false,
  searched: false,
  policies: [],
  pagination: {
    current_page: 1,
    has_more_pages: false,
    next_page_url: null,
    prev_page_url: null,
    total: null,
    from: 1,
    to: 0,
  },
  error: null,
  canSave: props.isEdit || false,
  showPolicies: false,
});

// Server options for pagination
const serverOptions = ref({
  page: 1,
});

// DataTable headers for policies
const policyTableHeaders = ref([
  { text: 'REF ID', value: 'ref_id', is_active: true },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  { text: 'CUSTOMER NAME', value: 'customer_name', is_active: true },
  {
    text: 'CURRENTLY INSURED WITH',
    value: 'currently_insured_with',
    is_active: true,
  },
  { text: 'PRODUCT', value: 'product', is_active: true },
  { text: 'POLICY EXPIRY DATE', value: 'policy_expiry_date', is_active: true },
  { text: 'ACTION', value: 'action', is_active: true },
]);

// Function to get row class for selected policy
const getRowClass = item => {
  return claimForm.selected_policy_id === item.id
    ? 'bg-blue-50 ring-2 ring-blue-500'
    : '';
};

const lineOfBusinessOptions = computed(() => {
  return (
    props.dropdowns?.lineOfBusiness?.map(lob => ({
      value: lob.id,
      label: lob.text,
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

// Check if the selected line of business is Car
const isCarLOB = computed(() => {
  const page = usePage();
  return page.props.quoteTypeIds?.Car === claimForm.quote_type_id;
});

// Check if there are validation errors (excluding general error messages)
const hasValidationErrors = computed(() => {
  const errors = claimForm.errors;
  if (!errors || typeof errors !== 'object') return false;

  const validationFields = Object.keys(errors).filter(
    key => key !== 'error' && key !== 'message' && errors[key],
  );
  return validationFields.length > 0;
});

// Watch for policy selection changes
watch(
  [() => claimForm.selected_policy_id, () => claimForm.policy_not_listed],
  () => {
    policySearch.canSave = !!(
      claimForm.selected_policy_id || claimForm.policy_not_listed
    );
  },
);

// Watch for server options changes (pagination)
watch(
  () => serverOptions.value,
  (newValue, oldValue) => {
    if (oldValue && newValue.page !== oldValue.page) {
      // Ensure we're working with integers for comparison
      const newPage = parseInt(newValue.page, 10) || 1;
      const oldPage = parseInt(oldValue.page, 10) || 1;
      if (newPage !== oldPage) {
        handlePageChange(newPage);
      }
    }
  },
  { deep: true },
);

// Policy search function
async function searchPolicies(pageNumber = 1) {
  if (!claimForm.email && !claimForm.policy_number) {
    notification.error({
      title: 'Please enter either email address or policy number to search',
      position: 'top',
    });
    return;
  }

  // Ensure page number is always an integer
  let page = parseInt(pageNumber, 10) || 1;

  // Only reset policy selection on new search (page 1)
  if (page === 1) {
    policySearch.policies = [];
    resetPolicySelection();
  }

  policySearch.loading = true;
  policySearch.error = null;

  try {
    const response = await axios.post('/claim/search-policies', {
      email: claimForm.email,
      policy_number: claimForm.policy_number,
      quote_type_id: claimForm.quote_type_id,
      page: page,
    });

    const policiesData = response.data.policies;
    policySearch.policies = policiesData.data || [];
    policySearch.pagination = {
      current_page: policiesData.current_page || 1,
      has_more_pages: policiesData.has_more_pages || false,
      next_page_url: policiesData.next_page_url || null,
      prev_page_url: policiesData.prev_page_url || null,
      total: policiesData.total || null,
      from: policiesData.from || 1,
      to: policiesData.to || 0,
    };
    policySearch.searched = true;
    policySearch.showPolicies = true;
    serverOptions.value.page = page;

    // Don't set error for empty results, just show the table with "-- NO AVAILABLE DATA --"
  } catch (error) {
    console.error('Policy search error:', error);

    // Handle specific error types with detailed messages
    if (error.response) {
      const status = error.response.status;
      const data = error.response.data;

      if (status === 422) {
        // Validation errors
        if (data.errors) {
          const validationErrors = [];
          Object.keys(data.errors).forEach(field => {
            if (Array.isArray(data.errors[field])) {
              validationErrors.push(...data.errors[field]);
            } else {
              validationErrors.push(data.errors[field]);
            }
          });
          policySearch.error = validationErrors.join('. ');
        } else {
          policySearch.error =
            data.message || 'Validation failed. Please check your input.';
        }
      } else {
        policySearch.error =
          data.message || `Error ${status}: Unable to search policies.`;
      }

      // Show notification for validation errors
      if (status === 422) {
        notification.error({
          title: 'Validation Error',
          message: policySearch.error,
          position: 'top',
        });
      }
    } else {
      policySearch.error = 'An unexpected error occurred. Please try again.';
      notification.error({
        title: 'Error',
        message: policySearch.error,
        position: 'top',
      });
    }
  } finally {
    policySearch.loading = false;
  }
}

// Handle pagination changes
function handlePageChange(newPage) {
  // Ensure page is always an integer
  const pageNumber = parseInt(newPage, 10) || 1;
  searchPolicies(pageNumber);
}

// Select policy function
function selectPolicy(policy) {
  claimForm.policy_not_listed = false;
  claimForm.selected_policy_id = policy.id;
  claimForm.selected_quote_uuid = policy.uuid;
  claimForm.policy_number = policy.policy_number;
  claimForm.customer_id = policy.customer_id;
  claimForm.insurance_provider_id = policy.insurance_provider_id;
}

// Policy not listed function
function policyNotListed() {
  claimForm.policy_not_listed = true;
  claimForm.selected_policy_id = null;
}

// Reset policy selection
function resetPolicySelection() {
  policySearch.showPolicies = false;
  policySearch.searched = false;
  claimForm.selected_policy_id = null;
  claimForm.policy_not_listed = false;
  policySearch.canSave = false;
}

function onSubmit(isValid) {
  if (isValid) {
    // Check if policy selection is required
    if (policySearch.searched && !policySearch.canSave) {
      notification.error({
        title:
          'Please select a policy or click "Policy is not listed" to continue',
        position: 'top',
      });
      return;
    }

    let method = 'post';
    let url = `/claim`;
    let title = 'Claim created successfully';

    if (props.isEdit && props.claim) {
      method = 'put';
      url = `/claim/${props.claim.code}`;
      title = 'Claim updated successfully';
    }

    claimForm.submit(method, url, {
      onError: errors => {
        console.log('Form errors:', errors);

        // Handle different types of errors
        if (typeof errors === 'object' && errors !== null) {
          // Field-specific validation errors
          claimForm.setError(errors);

          // Show summary notification for validation errors
          const errorMessages = [];
          Object.keys(errors).forEach(field => {
            if (Array.isArray(errors[field])) {
              errorMessages.push(...errors[field]);
            } else if (typeof errors[field] === 'string') {
              errorMessages.push(errors[field]);
            }
          });

          if (errorMessages.length > 0) {
            notification.error({
              title: 'Please correct the following errors:',
              message:
                errorMessages.slice(0, 3).join('. ') +
                (errorMessages.length > 3 ? '...' : ''),
              position: 'top',
              duration: 6000,
            });
          }
        } else if (typeof errors === 'string') {
          // Single error message
          notification.error({
            title: 'Error',
            message: errors,
            position: 'top',
          });
        }
      },
      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });

        // Redirect to claims list
        /*  router.visit('/claim'); */
      },
      onFinish: () => {
        // Always called after success or error
        console.log('Form submission finished');
      },
    });
  } else {
    notification.error({
      title:
        'Error while submitting claim. Please check the form and try again',
      position: 'top',
    });
  }
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Claim' : 'Create Claim Lead'" />
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold">
          {{ isEdit ? 'Edit Claim' : 'Create New Claim Lead' }}
        </h2>
        <p class="text-sm text-gray-600 mt-1" v-if="!isEdit">
          Create a new claim lead in IMCRM. Fields marked with * are mandatory.
        </p>
      </div>
      <div>
        <Link href="/claim">
          <x-button size="sm" color="#ff5e00">Claims List</x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :autofocus="false">
      <!-- General Error Alert -->
      <div
        v-if="claimForm.errors.error || claimForm.errors.message"
        class="mb-5"
      >
        <x-alert color="error">
          <div class="flex items-start">
            <Icon
              name="exclamation-triangle"
              class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0"
            />
            <div>
              <div class="font-medium">Error</div>
              <div class="text-sm mt-1">
                {{ claimForm.errors.error || claimForm.errors.message }}
              </div>
            </div>
          </div>
        </x-alert>
      </div>

      <!-- Claim Lead Information -->
      <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Claim Lead Information</h3>
        <p class="text-sm text-gray-600 mb-4">
          Fields marked with * are mandatory
        </p>
        <div class="grid sm:grid-cols-2 gap-4">
          <x-input
            v-model="claimForm.first_name"
            :rules="[isRequired]"
            class="w-full"
            type="text"
            label="First Name"
            placeholder="Enter First Name"
            required
            :error="claimForm.errors.first_name"
          />
          <x-input
            v-model="claimForm.last_name"
            :rules="[isRequired]"
            class="w-full"
            type="text"
            label="Last Name"
            placeholder="Enter Last Name"
            required
            :error="claimForm.errors.last_name"
          />
          <x-input
            v-model="claimForm.mobile_no"
            :rules="[isRequired]"
            class="w-full"
            type="tel"
            label="Phone Number"
            placeholder="Enter Phone Number"
            required
            :error="claimForm.errors.mobile_no"
          />
          <x-input
            v-model="claimForm.email"
            :rules="[isRequired, isEmail]"
            class="w-full"
            :disabled="isEdit"
            :class="{ 'readonly': isEdit }"
            type="email"
            label="Email Address"
            placeholder="Enter Email Address"
            required
            :error="claimForm.errors.email"
          />
          <x-select
            v-model="claimForm.quote_type_id"
            :rules="[isRequired]"
            label="Line of Business"
            placeholder="Select Line of Business"
            :options="lineOfBusinessOptions"
            filterable
            filterPlaceholder="Filter Line of Business...."
            required
            :error="claimForm.errors.quote_type_id"
          />
          <x-input
            v-model="claimForm.claim_number"
            type="text"
            label="Insurer Claim Number"
            placeholder="Enter Insurer Claim Number"
            class="w-full"
            :error="claimForm.errors.claim_number"
          />
          <x-select
            v-model="claimForm.claim_type_id"
            label="Claim Type"
            placeholder="Select Claim Type"
            :options="claimTypeOptions"
            :rules="[isRequired]"
            filterable
            filterPlaceholder="Filter Claim Type...."
            :error="claimForm.errors.claim_type_id"
          />
          <DatePicker
            v-model="claimForm.incident_date"
            name="incident_date"
            label="Incident Date"
            placeholder="Select Incident Date"
            :hasError="claimForm.errors.incident_date"
          />
          <x-input
            v-model="claimForm.policy_number"
            type="text"
            label="Policy Number"
            placeholder="Enter Policy Number"
            class="w-full"
            :error="claimForm.errors.policy_number"
            @input="resetPolicySelection"
          />

          <!-- Additional fields for Car LOB when editing -->
          <x-input
            v-if="isCarLOB"
            v-model="claimForm.plate_number"
            type="text"
            label="Plate Number"
            placeholder="Enter Plate Number"
            class="w-full"
            :error="claimForm.errors.plate_number"
          />
          <x-input
            v-if="isCarLOB"
            v-model="claimForm.car_model_year"
            type="number"
            label="Car Model Year"
            placeholder="Enter Model Year"
            class="w-full"
            :error="claimForm.errors.car_model_year"
          />
          <x-input
            v-if="isCarLOB"
            v-model="claimForm.car_make"
            type="text"
            label="Car Make"
            placeholder="Enter Car Make"
            class="w-full"
            :error="claimForm.errors.car_make"
          />
          <x-input
            v-if="isCarLOB"
            v-model="claimForm.car_model"
            type="text"
            label="Car Model"
            placeholder="Enter Car Model"
            class="w-full"
            :error="claimForm.errors.car_model"
          />

          <x-input
            v-if="isCarLOB"
            v-model="claimForm.approved_repair_amount"
            type="number"
            step="0.01"
            label="Approved Repair Amount"
            placeholder="Enter Amount"
            class="w-full"
            :error="claimForm.errors.approved_repair_amount"
          />
          <x-input
            v-if="isCarLOB"
            v-model="claimForm.approved_total_loss_amount"
            type="number"
            step="0.01"
            label="Approved Total Loss Amount"
            placeholder="Enter Amount"
            class="w-full"
            :error="claimForm.errors.approved_total_loss_amount"
          />
          <x-input
            v-if="isCarLOB"
            v-model="claimForm.approved_cash_loss_amount"
            type="number"
            step="0.01"
            label="Approved Cash Loss Amount"
            placeholder="Enter Amount"
            class="w-full"
            :error="claimForm.errors.approved_cash_loss_amount"
          />
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Incident Story -->
          <x-textarea
            v-model="claimForm.incident_story"
            label="Incident Story"
            placeholder="Please describe what happened..."
            rows="4"
            class="w-full"
            :error="claimForm.errors.incident_story"
          />

        <!-- Claim Denial Reason (when editing) -->
          <x-textarea
            v-model="claimForm.claim_denial_reason"
            label="Claim Denial Reason"
            placeholder="Note..."
            rows="4"
            class="w-full"
            :error="claimForm.errors.claim_denial_reason"
          />
        </div>



      </div>

      <!-- Policy Search Results -->
      <div
        v-if="policySearch.showPolicies"
        class="bg-white p-6 rounded shadow mb-6"
      >
        <!-- Error Message (only for actual errors, not empty results) -->
        <div v-if="policySearch.error" class="mb-4">
          <x-alert color="error" class="mb-4">
            <div class="flex items-start">
              <Icon
                name="exclamation-triangle"
                class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0"
              />
              <div>
                <div class="font-medium">Search Error</div>
                <div class="text-sm mt-1">{{ policySearch.error }}</div>
              </div>
            </div>
          </x-alert>
        </div>

        <!-- Policies Table -->
        <div v-if="policySearch.searched" class="mb-4">
          <DataTable
            v-model:server-options="serverOptions"
            :headers="policyTableHeaders"
            :items="policySearch.policies"
            :loading="policySearch.loading"
            border-cell
            hide-rows-per-page
            hide-footer
            table-class-name="policy-table"
            :body-row-class-name="getRowClass"
            server-side-pagination
          >
            <template
              #item-action="{
                ref_id,
                id,
                uuid,
                policy_number,
                customer_id,
                insurance_provider_id,
              }"
            >
              <x-button
                size="sm"
                :color="
                  claimForm.selected_policy_id === id ? 'success' : 'primary'
                "
                @click="
                  selectPolicy({
                    id,
                    uuid,
                    policy_number,
                    customer_id,
                    insurance_provider_id,
                    ref_id,
                  })
                "
              >
                {{
                  claimForm.selected_policy_id === id ? 'Selected' : 'Select'
                }}
              </x-button>
            </template>
          </DataTable>

          <!-- Pagination -->
          <div
            v-if="
              policySearch.pagination.has_more_pages ||
              policySearch.pagination.current_page > 1
            "
          >
            <PaginateClient
              :links="{
                next: policySearch.pagination.next_page_url,
                prev: policySearch.pagination.prev_page_url,
                current: policySearch.pagination.current_page,
                from: policySearch.pagination.from,
                to: policySearch.pagination.to,
                total: policySearch.pagination.total,
              }"
              :loading="policySearch.loading"
              @update="handlePageChange"
            />
          </div>
        </div>

        <!-- Policy Not Listed Button -->
        <div v-if="policySearch.searched" class="flex justify-end gap-3 mt-4">
          <x-button
            type="button"
            size="md"
            color="gray"
            @click="policyNotListed"
            :class="{ 'bg-gray-500 text-white': claimForm.policy_not_listed }"
          >
            {{
              claimForm.policy_not_listed
                ? 'Policy Not Listed (Selected)'
                : 'Policy is not listed'
            }}
          </x-button>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end gap-3 mb-4">
        <!-- Search Button  -->
        <x-button
          v-if="!isEdit"
            type="button"
            size="md"
            color="primary"
            @click="searchPolicies"
            :disabled="!claimForm.email && !claimForm.policy_number"
            :loading="policySearch.loading"
          >
            Search
        </x-button>
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="claimForm.processing"
          :disabled="!policySearch.canSave"
          :title="
            policySearch.searched && !policySearch.canSave
              ? 'Please select a policy or click Policy is not listed'
              : ''
          "
        >
          {{ isEdit ? 'Update Claim' : 'Save' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
