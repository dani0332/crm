<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { formatDate } from '../../Composables/utilities';
import axios from 'axios';

// Fix: Use proper notification import
const notification = useNotifications('toast');

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
  // Edit mode props
  borLog: {
    type: Object,
    default: null,
  },
  borStatusEnum: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'success']);

const page = usePage();

// Edit mode computed property
const isEditMode = computed(() => {
  return props.borLog && props.borLog.id;
});

// Computed properties - use embedded document data from getBorLogs
const hasSignedDocument = computed(() => {
  return props.borLog.has_signed_pdf && props.borLog.signed_pdf?.length > 0;
});

const signedDocuments = computed(() => {
  return props.borLog.signed_pdf || [];
});

// Form data using Inertia's useForm with proper field mapping
const form = useForm({
  lead_id: props.leadId,
  lob: props.lob,
  customer_type: '',
  insurer_name: props.customerData.firstName + ' ' + props.customerData.lastName,
  company_name: props.customerData.companyName,
  insurance_provider_id: props.customerData.currentlyInsuredWith,
  policy_number: '',
  policy_expiry: '',
  chassis_number: '',
  // Edit mode fields - these will NOT be updated
  additional_notes: '',
  reason: '',
});

// Reactive data
const isSubmitting = ref(false);
const showModal = ref(props.visible);
const selectedInsurer = ref(null);
const uploadedDocuments = ref([]);
const signedPdf = ref(null);
const downloadLoader = ref(false);

// Options data for reason dropdown
const reasonOptions = ref([
  { label: 'Customer request to cancel', value: 'customer_request_to_cancel' },
  { label: 'Reject by insurer', value: 'reject_by_insurer' },
  { label: 'Other reason', value: 'other_reason' },
]);

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
  if (isEditMode.value) {
    return `Edit BOR Request #${props.borLog.bor_reference} - ${props.lob} Insurance`;
  }
  return `Create BOR Request - ${props.lob} Insurance`;
});

const customerNameLabel = computed(() => {
  return form.customer_type === 'Entity' ? 'COMPANY NAME *' : 'CUSTOMER NAME *';
});

const customerNamePlaceholder = computed(() => {
  return form.customer_type === 'Entity' ? 'Enter company name' : 'Enter customer name';
});

// Status badge configuration for display
const getStatusBadge = (status) => {
  const statusConfig = {
    'SIGNATURE_REQUESTED': { class: 'bg-yellow-100 text-yellow-800', text: 'Signature Requested' },
    'SENT_TO_INSURER': { class: 'bg-blue-100 text-blue-800', text: 'Sent to Insurer' },
    'DOCUMENT_SIGNED': { class: 'bg-indigo-100 text-indigo-800', text: 'Document Signed' },
    'DOCUMENT_UPLOADED': { class: 'bg-purple-100 text-purple-800', text: 'Document Uploaded' },
    'CANCELLED': { class: 'bg-gray-100 text-gray-800', text: 'Cancelled' },
    'COMPLETED': { class: 'bg-green-100 text-green-800', text: 'Completed' },
  }
  return statusConfig[status] || { class: 'bg-gray-100 text-gray-800', text: status }
};

// LOB-specific field requirements
const requiredFields = computed(() => {
  const fields = {
    policy_number: false,
    policy_expiry: false,
    chassis_number: false,
  };

  // Motor LOBs require policy number and expiry
  if (isMotorLob.value && form.customer_type == 'Individual') {
    fields.policy_number = true;
    fields.policy_expiry = true;
    
    // Sukoon insurance requires chassis number for motor
    if (isSukoonInsurance.value) {
      fields.chassis_number = true;
    }
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
    label: provider.text ?? provider.label,
    value: provider.id ?? provider.value,
    code: provider.code,
    text: provider.text,
  }));
});

// Watch for visibility changes
watch(() => props.visible, (newValue) => {
  showModal.value = newValue;
  if (newValue) {
    resetForm();
    if (isEditMode.value) {
      prefillFormFromBorLog();
      loadEmbeddedDocuments();
    }
  }
});

// Watch for customer type changes to reset relevant fields
watch(() => form.customer_type, (newValue) => {
  
  // Clear policy fields when switching to Entity (since they won't be visible)
  if (newValue === 'Entity') {
    form.policy_number = '';
    form.policy_expiry = '';
    form.chassis_number = '';
  }
  
  // Set default customer type based on LOB
  if (isBusinessLob.value && newValue === '') {
    form.customer_type = 'Entity';
    form.insurer_name = '';
  } else if ((isMotorLob.value || isHealthLob.value) && newValue === '') {
    form.customer_type = 'Individual';
    form.company_name = '';
  }
});

// Watch for insurer selection changes
watch(() => form.insurance_provider_id, (newProviderId) => {
  selectedInsurer.value = props.insuranceProviders.find(p => p.id == newProviderId) || null;
});

// Methods
const resetForm = () => {
  form.reset();
  form.clearErrors(); // Fix: Clear form errors on reset
  form.lead_id = props.leadId;
  form.lob = props.lob;

  selectedInsurer.value = null;
  uploadedDocuments.value = [];
  signedPdf.value = null;
  
  // Set default customer type based on LOB
  if (isBusinessLob.value) {
    form.customer_type = 'Entity';
  } else if (isMotorLob.value || isHealthLob.value) {
    form.customer_type = 'Individual';
  }
};

// Prefill form with BorLog data for edit mode
const prefillFormFromBorLog = () => {
  if (!props.borLog) return;
  
  const borLog = props.borLog;
  
  // Prefill all the main fields
  form.customer_type = borLog.customer_type || '';
  form.insurer_name = borLog.insurer_name || '';
  form.company_name = borLog.company_name || '';
  form.insurance_provider_id = borLog.insurance_provider_id || '';
  form.policy_number = borLog.policy_number || '';
  form.policy_expiry = borLog.policy_expiry || '';
  form.chassis_number = borLog.chassis_number || '';
  
  // Set the selected insurer
  if (borLog.insurance_provider_id) {
    selectedInsurer.value = props.insuranceProviders.find(p => p.id == borLog.insurance_provider_id) || null;
  }
};

// Load embedded document data from BOR log
const loadEmbeddedDocuments = () => {
  if (!props.borLog?.id) return;
  
  // Use embedded document data from the BOR log object
  uploadedDocuments.value = props.borLog.uploaded_documents || [];
  signedPdf.value = props.borLog.signed_pdf?.[0] || null;
};

const closeModal = () => {
  // Reset form state
  isSubmitting.value = false;
  showModal.value = false;
  uploadedDocuments.value = [];
  emit('close');
};

const validateForm = () => {
  // Fix: Clear previous errors before validation
  form.clearErrors();
  
  const errors = {};
  let isValid = true;

  // Basic validation before submission
  if (!form.customer_type) {
    errors.customer_type = 'Please select a customer type';
    isValid = false;
  }

  // Customer/Company name validation
  if (form.customer_type === 'Individual' && !form.insurer_name) {
    errors.insurer_name = 'Please enter customer name';
    isValid = false;
  }

  if (form.customer_type === 'Entity' && !form.company_name) {
    errors.company_name = 'Please enter company name';
    isValid = false;
  }

  // LOB-specific validation
  if (requiredFields.value.policy_number && !form.policy_number) {
    errors.policy_number = 'Policy number is required for ' + props.lob + ' insurance';
    isValid = false;
  }
  
  if (requiredFields.value.policy_expiry && !form.policy_expiry) {
    errors.policy_expiry = 'Policy expiry is required for ' + props.lob + ' insurance';
    isValid = false;
  }
  
  if (requiredFields.value.chassis_number && !form.chassis_number) {
    errors.chassis_number = 'Chassis number is required for Sukoon insurance';
    isValid = false;
  }

  if (!isValid) {
    form.setError(errors);
  }

  return isValid;
};

const submitForm = () => {
  // Fix: Prevent multiple submissions
  if (isSubmitting.value) {
    return;
  }

  if (!validateForm()) {
    return;
  }

  // Prepare form data (exclude additional_notes and reason for main update)
  const submitData = {
    lead_id: form.lead_id,
    lob: form.lob,
    customer_type: form.customer_type,
    insurer_name: form.insurer_name,
    company_name: form.company_name,
    insurance_provider_id: form.insurance_provider_id,
    policy_number: form.policy_number,
    policy_expiry: form.policy_expiry,
    chassis_number: form.chassis_number,
  };

  // Determine route and method based on mode
  const routeName = isEditMode.value ? 'bor.requests.update' : 'bor.requests.store';
  const routeParams = isEditMode.value ? [props.borLog.id] : [];

  // Submit using Inertia with improved error handling
  const method = isEditMode.value ? 'put' : 'post';
  
  form[method](route(routeName, ...routeParams), {
    preserveScroll: true,
    data: submitData,
    onBefore: () => {
      isSubmitting.value = true;
      form.clearErrors();
    },
    onSuccess: (page) => {
      isSubmitting.value = false;
      
      // Emit success with the updated/new BOR log data
      if (page.props.updatedBorLog || page.props.newBorLog) {
        emit('success', page.props.updatedBorLog || page.props.newBorLog);
      } else {
        emit('success', {
          id: props.borLog?.id || Date.now(),
          ...submitData,
          status: props.borLog?.status || 'pending',
          created_at: props.borLog?.created_at || new Date().toISOString(),
        });
      }
      
      closeModal();
    },
    onError: (errors) => {
      isSubmitting.value = false;
      
      // Show validation errors
      const firstError = Object.values(errors)[0];
      if (firstError) {
        notification.error({
          title: 'Submission Error',
          message: Array.isArray(firstError) ? firstError[0] : firstError,
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Submission Error',
          message: `Failed to ${isEditMode.value ? 'update' : 'create'} BOR request. Please try again.`,
          position: 'top',
        });
      }
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

const downloadFile = download => {
  const save = document.createElement('a');
  if (typeof save.download !== 'undefined') {
    // if the download attribute is supported, save.download will return empty string, if not supported, it will return undefined
    // if you are using helper method, such as isNone in ember, you can also do isNone(save.download)
    save.href =
      window.location.protocol +
      '//' +
      window.location.host +
      '/bor/logs/' + props.borLog.id + '/download?path=' +
      download.doc_url;
    save.target = '_blank';
    save.download = download.doc_name;
    save.dispatchEvent(new MouseEvent('click'));
  } else {
    window.location.href =
      window.location.protocol +
      '//' +
      window.location.host +
      '/bor/logs/' + props.borLog.id + '/download?path=' +
      download.doc_url; // so that it opens new tab for IE11
  }

  downloadLoader.value = true;
  setTimeout(() => {
    downloadLoader.value = false;
  }, 1300);
};

// View BOR PDF document (generates PDF on-the-fly - base64 preview only)
const viewSignedPdf = async () => {
  try {
    downloadLoader.value = true;
    
    const response = await axios.get(route('bor.logs.view-signed-pdf', {
      borLogId: props.borLog.id
    }));
    if (response.data.success) {
      // Open PDF in new window for viewing only
      const newWindow = window.open();
      newWindow.document.write(`
        <html>
          <head>
            <title>View: ${response.data.name}</title>
            <style>
              body { margin: 0; padding: 0; }
              iframe { width: 100%; height: 100vh; border: none; }
            </style>
          </head>
          <body>
            <iframe src="${response.data.data}" type="application/pdf"></iframe>
          </body>
        </html>
      `);
      newWindow.document.close();
    } else {
      throw new Error(response.data.message || 'Failed to load document');
    }
  } catch (error) {
    console.error('Error viewing signed PDF:', error);
    notification.error({
      title: 'View Error',
      message: 'Failed to view signed document. Please try again.',
      position: 'top',
    });
  } finally {
    downloadLoader.value = false;
  }
};

// Initialize form on mount
onMounted(() => {
  resetForm();
  if (isEditMode.value && props.visible) {
    prefillFormFromBorLog();
    loadEmbeddedDocuments();
  }
});
</script>

<template>
  <x-modal
    v-model="showModal"
    :title="modalTitle"
    size="xl"
    backdrop
  >
    <template #default>
      <x-form :auto-focus="false">
        <div class="space-y-6">
          <!-- Status Display (Edit Mode Only) -->
          <div v-if="isEditMode" class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-lg font-semibold text-blue-900 mb-2">
                  BOR Request Status
                </h4>
                <div class="flex items-center space-x-3">
                  <span class="text-sm text-blue-700">Current Status:</span>
                  <span 
                    :class="getStatusBadge(borLog.status).class"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getStatusBadge(borLog.status).text }}
                  </span>
                </div>
                <div class="mt-2 text-sm text-blue-600">
                  <strong>BOR Reference:</strong> {{ borLog.bor_reference }}
                </div>
                <div class="text-sm text-blue-600">
                  <strong>Created:</strong> {{ borLog.created_at }}
                </div>
              </div>
            </div>
          </div>

          <!-- Uploaded Documents (Edit Mode Only) -->
          <div v-if="isEditMode && ( hasSignedDocument || uploadedDocuments.length > 0 )" class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Documents
            </h4>
            <div class="space-y-2">
              <div 
                v-for="document in uploadedDocuments" 
                :key="document.id"
                class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md"
              >
                <div class="flex items-center space-x-3">
                  <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ document.doc_name }}</p>
                    <p class="text-xs text-gray-500">{{ document.doc_mime_type }}</p>
                    <p class="text-xs text-gray-400">Uploaded: {{ formatDate(document.updated_at) }}</p>
                  </div>
                </div>
                <x-button
                  size="xs"
                  color="primary"
                  :loading="downloadLoader"
                  @click.prevent="downloadFile(document)"
                >
                  View
                </x-button>
              </div>
              <!-- Signed PDF Documents -->
              <div v-if="hasSignedDocument" class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Signed BOR Documents
                  <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Signed
                  </span>
                </h4>
                <div class="space-y-2">
                  <div 
                    v-for="document in signedDocuments" 
                    :key="document.id"
                    class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md"
                  >
                    <div class="flex items-center space-x-3">
                      <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ document.doc_name }}</p>
                        <p class="text-xs text-gray-500">{{ document.doc_mime_type }}</p>
                        <p class="text-xs text-gray-400">Uploaded: {{ formatDate(document.updated_at) }}</p>
                        <p class="text-xs text-gray-400">Type: {{ document.document_type_text }}</p>
                      </div>
                    </div>
                    <x-button
                      size="xs"
                      color="primary"
                      :loading="downloadLoader"
                      @click.prevent="viewSignedPdf()"
                    >
                      View PDF
                    </x-button>
                  </div>
                </div>
              </div>
            </div>
          </div>

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
                label="CUSTOMER TYPE"
                v-model="form.customer_type"
                :options="customerTypeOptions"
                placeholder="Select customer type"
                :error="form.errors.customer_type"
                :disabled="isSubmitting"
                required
              />

              <!-- Customer Name (Individual) -->
              <x-input
                v-if="form.customer_type === 'Individual'"
                label="CUSTOMER NAME"
                v-model="form.insurer_name"
                placeholder="Enter customer name"
                :error="form.errors.insurer_name"
                :disabled="isSubmitting"
                required
              />

              <!-- Company Name (Entity) -->
              <x-input
                v-if="form.customer_type === 'Entity'"
                label="COMPANY NAME"
                v-model="form.company_name"
                placeholder="Enter company name"
                :error="form.errors.company_name"
                :disabled="isSubmitting"
                required
              />

              <!-- Insurance Provider -->
              <x-select
                label="INSURANCE PROVIDER"
                v-model="form.insurance_provider_id"
                :options="availableInsurers"
                placeholder="Select insurance provider"
                :error="form.errors.insurance_provider_id"
                :disabled="isSubmitting"
                filterable
                clearable
              />

              <!-- Policy Number (Only for Individual customers) -->
              <x-input
                v-if="form.customer_type === 'Individual'"
                label="POLICY NUMBER"
                v-model="form.policy_number"
                placeholder="Enter policy number"
                :error="form.errors.policy_number"
                :disabled="isSubmitting"
                :required="requiredFields.policy_number"
              />

              <!-- Policy Expiry (Only for Individual customers) -->
              <x-input
                v-if="form.customer_type === 'Individual'"
                label="POLICY EXPIRY"
                v-model="form.policy_expiry"
                type="date"
                placeholder="Select policy expiry date"
                :error="form.errors.policy_expiry"
                :disabled="isSubmitting"
                :required="requiredFields.policy_expiry"
              />

              <!-- Chassis Number (Motor LOBs with Sukoon Insurance) -->
              <x-input
                v-if="isMotorLob"
                label="CHASSIS NUMBER"
                v-model="form.chassis_number"
                placeholder="Enter chassis number"
                :error="form.errors.chassis_number"
                :disabled="isSubmitting"
                :required="requiredFields.chassis_number"
              />
            </div>
          </div>

          <!-- Edit Mode Additional Fields -->
          <div v-if="isEditMode" class="bg-orange-50 border border-orange-200 p-6 rounded-lg">
            <h4 class="text-lg font-semibold text-orange-900 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Additional Information
              <span class="ml-2 text-sm font-normal text-orange-700">(For reference only - not updated)</span>
            </h4>
            <div class="grid grid-cols-1 gap-4">
              <!-- Additional Notes -->
              <div>
                <label class="block text-sm font-medium text-orange-900 mb-2">
                  Additional Notes
                </label>
                <textarea
                  v-model="form.additional_notes"
                  rows="3"
                  class="w-full border border-orange-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="Enter any additional notes or comments about this BOR request..."
                  :disabled="isSubmitting"
                ></textarea>
              </div>

              <!-- Reason -->
              <x-select
                label="REASON"
                v-model="form.reason"
                :options="reasonOptions"
                placeholder="Select a reason"
                :disabled="isSubmitting"
                clearable
              />
            </div>
            <div class="mt-3 p-3 bg-orange-100 border border-orange-200 rounded">
              <p class="text-sm text-orange-800">
                <strong>Note:</strong> The fields in this section are for informational purposes only and will not be saved to the BOR request. 
                To update the status, additional notes, or reason, please use the appropriate action buttons in the BOR logs list.
              </p>
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
          :disabled="isSubmitting"
          color="orange"
          type="submit"
        >
          {{ isSubmitting ? (isEditMode ? 'Updating...' : 'Creating...') : (isEditMode ? 'Update and Send' : 'Save and Send') }}
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

.border-orange-200 {
  border-color: #fed7aa;
}
</style> 