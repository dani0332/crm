<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  leadId: {
    type: [String, Number],
    required: true,
  },
  lob: {
    type: String,
    required: true,
  },
  customerData: {
    type: Object,
    default: () => ({}),
  },
  insuranceProviders: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'success']);

const page = usePage();

// Form data using Inertia's useForm with proper field mapping
const form = useForm({
  lead_id: props.leadId,
  lob: props.lob,
  customer_type: '',
  customer_name: '',
  company_name: '',
  insurance_provider_id: '',
  policy_number: '',
  policy_expiry: '',
  chassis_number: '',
});

// Reactive data
const isSubmitting = ref(false);
const showModal = ref(props.visible);
const selectedInsurer = ref(null);

// Computed properties
const isMotorLob = computed(() => {
  return ['car', 'bike', 'Car', 'Bike'].includes(props.lob);
});

const isHealthLob = computed(() => {
  return ['health', 'Health'].includes(props.lob);
});

const isTravelLob = computed(() => {
  return ['travel', 'Travel'].includes(props.lob);
});

const isHomeLob = computed(() => {
  return ['home', 'Home'].includes(props.lob);
});

const isBusinessLob = computed(() => {
  return ['business', 'Business'].includes(props.lob);
});

const isPetLob = computed(() => {
  return ['pet', 'Pet'].includes(props.lob);
});

const isSukoonInsurance = computed(() => {
  if (!selectedInsurer.value) return false;
  return selectedInsurer.value.code?.toLowerCase() === 'oic' || 
         selectedInsurer.value.text?.toLowerCase().includes('sukoon');
});

const modalTitle = computed(() => {
  return `Create BOR Request - ${props.lob} Insurance`;
});

const customerNameLabel = computed(() => {
  return form.customer_type === 'Entity' ? 'COMPANY NAME *' : 'CUSTOMER NAME *';
});

const customerNamePlaceholder = computed(() => {
  return form.customer_type === 'Entity' ? 'Enter company name' : 'Enter customer name';
});

// LOB-specific field requirements
const requiredFields = computed(() => {
  const fields = {
    policy_number: false,
    policy_expiry: false,
    chassis_number: false,
  };

  // Motor LOBs require policy number and expiry
  if (isMotorLob.value) {
    fields.policy_number = true;
    fields.policy_expiry = true;
    
    // Sukoon insurance requires chassis number for motor
    if (isSukoonInsurance.value) {
      fields.chassis_number = true;
    }
  }

  // Health LOB may require policy number
  if (isHealthLob.value) {
    fields.policy_number = true;
  }

  return fields;
});

// Options data
const customerTypeOptions = ref([
  { label: 'Individual', value: 'Individual' },
  { label: 'Entity', value: 'Entity' },
]);

// Insurance providers - use props or fetch from API
const availableInsurers = computed(() => {
  return props.insuranceProviders.map(provider => ({
    label: provider.text,
    value: provider.id,
    code: provider.code,
    text: provider.text,
  }));
});

// Watch for visibility changes
watch(() => props.visible, (newValue) => {
  showModal.value = newValue;
  if (newValue) {
    resetForm();
  }
});

// Watch for customer type changes to reset relevant fields
watch(() => form.customer_type, (newValue) => {
  // Clear the customer/company name when type changes
  form.customer_name = '';
  form.company_name = '';
  
  // Clear policy fields when switching to Entity (since they won't be visible)
  if (newValue === 'Entity') {
    form.policy_number = '';
    form.policy_expiry = '';
    form.chassis_number = '';
  }
  
  // Set default customer type based on LOB
  if (isBusinessLob.value && newValue === '') {
    form.customer_type = 'Entity';
  } else if ((isMotorLob.value || isHealthLob.value) && newValue === '') {
    form.customer_type = 'Individual';
  }
});

// Watch for insurer selection changes
watch(() => form.insurance_provider_id, (newProviderId) => {
  selectedInsurer.value = props.insuranceProviders.find(p => p.id == newProviderId) || null;
});

// Methods
const resetForm = () => {
  form.reset();
  form.lead_id = props.leadId;
  form.lob = props.lob;

  selectedInsurer.value = null;
  
  // Set default customer type based on LOB
  if (isBusinessLob.value) {
    form.customer_type = 'Entity';
  } else if (isMotorLob.value || isHealthLob.value) {
    form.customer_type = 'Individual';
  }
};

const closeModal = () => {
  showModal.value = false;
  emit('close');
};

const validateForm = () => {
  // Basic validation before submission
  if (!form.customer_type) {
    // Use proper toast notification if available
    if (window.toast) {
      window.toast.error('Please select a customer type');
    }
    return false;
  }

  // Customer/Company name validation
  if (form.customer_type === 'Individual' && !form.customer_name) {
    if (window.toast) {
      window.toast.error('Please enter customer name');
    }
    return false;
  }

  if (form.customer_type === 'Entity' && !form.company_name) {
    if (window.toast) {
      window.toast.error('Please enter company name');
    }
    return false;
  }

  if (!form.insurance_provider_id) {
    if (window.toast) {
      window.toast.error('Please select an insurance provider');
    }
    return false;
  }

  // LOB-specific validation
  if (requiredFields.value.policy_number && !form.policy_number) {
    if (window.toast) {
      window.toast.error('Policy number is required for ' + props.lob + ' insurance');
    }
    return false;
  }
  
  if (requiredFields.value.policy_expiry && !form.policy_expiry) {
    if (window.toast) {
      window.toast.error('Policy expiry is required for ' + props.lob + ' insurance');
    }
    return false;
  }
  
  if (requiredFields.value.chassis_number && !form.chassis_number) {
    if (window.toast) {
      window.toast.error('Chassis number is required for Sukoon insurance');
    }
    return false;
  }

  return true;
};

const submitForm = () => {
  if (!validateForm()) {
    return;
  }

  // Submit using Inertia
  form.post(route('bor.requests.store'), {
    preserveScroll: true,
    onBefore: () => {
      isSubmitting.value = true;
    },
    onSuccess: (page) => {
      isSubmitting.value = false;
      
      // Emit success with the new BOR log data
      if (page.props.newBorLog) {
        emit('success', page.props.newBorLog);
      } else {
        emit('success', {
          id: Date.now(), // Temporary ID
          ...form.data(),
          status: 'pending',
          created_at: new Date().toISOString(),
        });
      }
      
      closeModal();
      
      // Show success message
      if (window.toast) {
        window.toast.success('BOR request created successfully');
      }
    },
    onError: (errors) => {
      isSubmitting.value = false;
      
      // Show validation errors
      const firstError = Object.values(errors)[0];
      if (window.toast && firstError) {
        window.toast.error(firstError);
      }
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

// Initialize form on mount
onMounted(() => {
  resetForm();
});
</script>

<template>
  <x-modal
    v-model="showModal"
    :title="modalTitle"
    size="xl"
    show-close
    backdrop
    @close="closeModal"
  >
    <template #default>
      <x-form @submit="submitForm" :auto-focus="false">
        <div class="space-y-6">
          <!-- BOR Request Information -->
          <div class="bg-gray-50 p-6 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              BOR Request Information
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Customer Type -->
              <x-select
                label="CUSTOMER TYPE *"
                v-model="form.customer_type"
                :options="customerTypeOptions"
                placeholder="Select customer type"
                :error="form.errors.customer_type"
                required
              />

              <!-- Customer Name (Individual) -->
              <x-input
                v-if="form.customer_type === 'Individual'"
                label="CUSTOMER NAME *"
                v-model="form.customer_name"
                placeholder="Enter customer name"
                :error="form.errors.customer_name"
                required
              />

              <!-- Company Name (Entity) -->
              <x-input
                v-if="form.customer_type === 'Entity'"
                label="COMPANY NAME *"
                v-model="form.company_name"
                placeholder="Enter company name"
                :error="form.errors.company_name"
                required
              />

              <!-- Insurance Provider -->
              <x-select
                label="INSURANCE PROVIDER *"
                v-model="form.insurance_provider_id"
                :options="availableInsurers"
                placeholder="Select insurance provider"
                :error="form.errors.insurance_provider_id"
                searchable
                clearable
                required
              />

              <!-- Policy Number (Only for Individual customers) -->
              <x-input
                v-if="form.customer_type === 'Individual'"
                :label="`POLICY NUMBER${requiredFields.policy_number ? ' *' : ''}`"
                v-model="form.policy_number"
                placeholder="Enter policy number"
                :error="form.errors.policy_number"
                :required="requiredFields.policy_number"
              />

              <!-- Policy Expiry (Only for Individual customers) -->
              <x-input
                v-if="form.customer_type === 'Individual'"
                :label="`POLICY EXPIRY${requiredFields.policy_expiry ? ' *' : ''}`"
                v-model="form.policy_expiry"
                type="date"
                placeholder="Select policy expiry date"
                :error="form.errors.policy_expiry"
                :required="requiredFields.policy_expiry"
              />

              <!-- Chassis Number (Motor LOBs with Sukoon Insurance) -->
              <x-input
                v-if="isMotorLob && (isSukoonInsurance || form.chassis_number)"
                :label="`CHASSIS NUMBER${requiredFields.chassis_number ? ' *' : ''}`"
                v-model="form.chassis_number"
                placeholder="Enter chassis number"
                :error="form.errors.chassis_number"
                :required="requiredFields.chassis_number"
              />
            </div>
          </div>

          <!-- LOB-specific Information -->
          <div v-if="isMotorLob" class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              <div>
                <h5 class="text-sm font-medium text-blue-900 mb-1">
                  {{ lob }} Insurance BOR Requirements
                </h5>
                <p class="text-sm text-blue-700 mb-2">
                  For Individual customers with motor insurance, policy number and expiry date are mandatory fields. Entity customers don't require policy details.
                </p>
                <div v-if="isSukoonInsurance" class="bg-yellow-50 border border-yellow-200 p-3 rounded mt-2">
                  <p class="text-sm text-yellow-800">
                    <strong>Sukoon Insurance:</strong> Chassis number is additionally required for Individual customers with motor insurance policies.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="isHealthLob" class="bg-green-50 border border-green-200 p-4 rounded-lg">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              <div>
                <h5 class="text-sm font-medium text-green-900 mb-1">
                  Health Insurance BOR Process
                </h5>
                <p class="text-sm text-green-700">
                  For Individual customers, health insurance BOR requests typically require policy number for verification. Entity customers don't require policy details.
                </p>
              </div>
            </div>
          </div>

          <div v-else class="bg-green-50 border border-green-200 p-4 rounded-lg">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              <div>
                <h5 class="text-sm font-medium text-green-900 mb-1">
                  {{ lob }} Insurance BOR Process
                </h5>
                <p class="text-sm text-green-700">
                  The BOR request will be processed and the customer will be notified via email with the digital signature link.
                </p>
              </div>
            </div>
          </div>
        </div>
      </x-form>
    </template>

    <template #actions>
      <div class="flex justify-end space-x-3">
        <x-button
          @click="closeModal"
          :disabled="isSubmitting"
          color="primary"
          ghost
        >
          Cancel
        </x-button>
        
        <x-button
          @click="submitForm"
          :loading="isSubmitting"
          color="orange"
          type="submit"
        >
          Create BOR Request
        </x-button>
      </div>
    </template>
  </x-modal>
</template>

<style scoped>
/* Custom styles for enhanced form sections */
.bg-gray-50 {
  background-color: #f9fafb;
}

.border-blue-200 {
  border-color: #bfdbfe;
}

.border-green-200 {
  border-color: #bbf7d0;
}

.border-yellow-200 {
  border-color: #fef3c7;
}
</style> 