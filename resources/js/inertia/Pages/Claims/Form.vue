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
  email: props.claim?.email  || '',
  mobile_no: props.claim?.mobile_no || '',
  quote_type_id: props.claim?.quote_type_id || '',
  customer_id: props.claim?.customer_id || '',
  insurance_provider_id: props.claim?.insurance_provider_id || '',

  // Additional Fields
  claim_type_id: props.claim?.claim_type_id || '',
  incident_date: props.claim?.incident_date || '',
  incident_story: props.claim?.incident_story || '',
  policy_number: props.claim?.policy_number || '',
  claim_number: props.claim?.claim_number || '',
  
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
  error: null,
});

// Form state
const formState = reactive({
  canSave: false,
  showPolicies: false,
});

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

// Watch for policy selection changes
watch([() => claimForm.selected_policy_id, () => claimForm.policy_not_listed], () => {
  formState.canSave = !!(claimForm.selected_policy_id || claimForm.policy_not_listed);
});

// Policy search function
async function searchPolicies() {
  if (!claimForm.email && !claimForm.policy_number) {
    notification.error({
      title: 'Please enter either email address or policy number to search',
      position: 'top',
    });
    return;
  }
  resetPolicySelection();
  policySearch.loading = true;
  policySearch.error = null;
  policySearch.policies = [];

  try {
    const response = await axios.post('/claim/search-policies', {
      email: claimForm.email,
      policy_number: claimForm.policy_number,
      quote_type_id: claimForm.quote_type_id,
    });

    policySearch.policies = response.data.policies || [];
    policySearch.searched = true;
    formState.showPolicies = true;

    // Don't set error for empty results, just show the table with "-- NO AVAILABLE DATA --"
  } catch (error) {
    policySearch.error = 'Error searching policies. Please try again.';
    console.error('Policy search error:', error);
  } finally {
    policySearch.loading = false;
  }
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
  formState.showPolicies = false;
  policySearch.searched = false;
  claimForm.selected_policy_id = null;
  claimForm.policy_not_listed = false;
  formState.canSave = false;
}

function onSubmit(isValid) {
  if (isValid) {
    // Check if policy selection is required
    if (policySearch.searched && !formState.canSave) {
      notification.error({
        title: 'Please select a policy or click "Policy is not listed" to continue',
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
        claimForm.setError(errors);
      },
      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });

        // Redirect to claims list
       /*  router.visit('/claim'); */
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
      <x-alert color="error" class="mb-5" v-if="claimForm.errors.error">
        {{ claimForm?.errors?.error }}
      </x-alert>

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
        </div>
        
        <!-- Incident Story -->
        <div class="mt-4">
          <x-textarea
            v-model="claimForm.incident_story"
            label="Incident Story"
            placeholder="Please describe what happened..."
            rows="4"
            class="w-full"
            :error="claimForm.errors.incident_story"
          />
        </div>

        
      </div>

      <!-- Policy Search Results -->
      <div v-if="formState.showPolicies" class="bg-white p-6 rounded shadow mb-6">
        <!-- Error Message (only for actual errors, not empty results) -->
        <div v-if="policySearch.error" class="mb-4 text-center">
          <span class="text-red-600 text-lg">{{ policySearch.error }}</span>
        </div>

        <!-- Policies Table -->
        <div v-if="policySearch.policies.length > 0" class="overflow-x-auto mb-4">
          <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">REF ID</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">POLICY NUMBER</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">CUSTOMER NAME</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">CURRENTLY INSURED WITH</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">PRODUCT</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">POLICY EXPIRY DATE</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">ACTION</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr 
                v-for="policy in policySearch.policies" 
                :key="policy.ref_id"
                :class="{
                  'bg-blue-50 ring-2 ring-blue-500': claimForm.selected_policy_id === policy.ref_id
                }"
              >
                <td class="px-4 py-3 text-sm">{{ policy.ref_id }}</td>
                <td class="px-4 py-3 text-sm">{{ policy.policy_number }}</td>
                <td class="px-4 py-3 text-sm">{{ policy.customer_name }}</td>
                <td class="px-4 py-3 text-sm">{{ policy.currently_insured_with }}</td>
                <td class="px-4 py-3 text-sm">{{ policy.product }}</td>
                <td class="px-4 py-3 text-sm">{{ policy.policy_expiry_date }}</td>
                <td class="px-4 py-3 text-sm">
                  <x-button
                    size="sm"
                    :color="claimForm.selected_policy_id === policy.ref_id ? 'success' : 'primary'"
                    @click="selectPolicy(policy)"
                  >
                    {{ claimForm.selected_policy_id === policy.ref_id ? 'Selected' : 'Select' }}
                  </x-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Show "-- NO AVAILABLE DATA --" when no policies found -->
        <div v-if="policySearch.policies.length === 0 && policySearch.searched" class="overflow-x-auto mb-4">
          <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">REF ID</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">POLICY NUMBER</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">CUSTOMER NAME</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">CURRENTLY INSURED WITH</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">PRODUCT</th>
                <th class="px-4 py-3 text-left text-sm font-medium uppercase tracking-wider">POLICY EXPIRY DATE</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                  -- NO AVAILABLE DATA --
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Policy Not Listed Button -->
        <div v-if="policySearch.searched" class="flex justify-center gap-3 mt-4">
          <x-button
            type="button"
            size="md"
            color="gray"
            @click="policyNotListed"
            :class="{ 'bg-gray-500 text-white': claimForm.policy_not_listed }"
          >
            {{ claimForm.policy_not_listed ? 'Policy Not Listed (Selected)' : 'Policy is not listed' }}
          </x-button>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end gap-3 mb-4">
        <!-- Search Button -->
        <x-button
            type="button"
            size="md"
            color="primary"
            @click="searchPolicies"
            :loading="policySearch.loading"
          >
            Search
        </x-button>
        <x-button
          v-if="policySearch.searched"
          size="md"
          color="emerald"
          type="submit"
          :loading="claimForm.processing"
          :disabled="policySearch.searched && !formState.canSave"
          :title="policySearch.searched && !formState.canSave ? 'Please select a policy or click Policy is not listed' : ''"
        >
          {{ isEdit ? 'Update Claim' : 'Save' }}
        </x-button>
      </div>
      
      <!-- Policy Selection Warning -->
      <div v-if="policySearch.searched && !formState.canSave" class="mb-4">
        <x-alert color="info">
          Please select a policy from the search results or click "Policy is not listed" to continue.
        </x-alert>
      </div>
    </x-form>
  </div>
</template>
