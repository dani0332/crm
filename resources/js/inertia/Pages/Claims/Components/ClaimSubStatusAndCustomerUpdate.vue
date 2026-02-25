<script setup>
import { router } from '@inertiajs/vue3';
import NProgress from 'nprogress';

const props = defineProps({
  claim: Object,
  dropdowns: Object,
  requiredFieldsFilled: Boolean,
  expanded: {
    type: Boolean,
    default: false,
    required: false,
  },
});

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const claimsEnum = page.props.claimsEnum;
const notification = useToast();
const processing = ref(false);

const claimSubStatusAndCustomerForm = useForm({
  customer_message: '',
  ai_optimized_message: '',
  claim_sub_status_id: props.claim?.claim_sub_status_id || '',
});

// Check if the selected line of business is Car
const isCarLOB = computed(() => {
  return page.props.quoteTypeIds?.Car === page.props.claim.quote_type_id;
});

// Check if the selected line of business is Health
const isHealthLOB = computed(() => {
  let isHealth =
    page.props.quoteTypeIds?.Health === page.props.claim.quote_type_id;
  let isGroupMedical =
    page.props.quoteBusinessTypeIdEnum?.GROUP_MEDICAL ===
    props.claim?.business_type_of_insurance_id;
  return isHealth || isGroupMedical;
});

// Check if selected line of business is car
const isBikeLOB = computed(() => {
  return page.props.quoteTypeIds?.Bike === props.claim.quote_type_id;
});

const subStatusOptions = computed(() => {
  let quoteType = isHealthLOB.value
    ? page.props.quoteTypeIds?.Health
    : isBikeLOB.value
      ? page.props.quoteTypeIds?.Car
      : page.props.claim.quote_type_id;
  return (
    props.dropdowns.claimSubStatuses
      ?.filter(subStatus => {
        // Always filter by quote_type_id
        const matchesQuoteType = subStatus.quote_type_id === quoteType;

        // If claim has claim_request_type_id, also filter by it
        if (page.props.claim.claim_request_type_id != null) {
          const matchesClaimRequestType =
            subStatus.claim_request_type_id === null ||
            subStatus.claim_request_type_id ===
              page.props.claim.claim_request_type_id;
          return matchesQuoteType && matchesClaimRequestType;
        }

        // If claim doesn't have claim_request_type_id, only filter by quote_type_id
        return matchesQuoteType;
      })
      ?.map(subStatus => ({
        value: subStatus.id,
        label: subStatus.text.label,
      })) || []
  );
});

const updateClaimSubStatusAndCustomer = async isValid => {
  // Check if required field is filled before submitting
  let isCarOrBikeLOB = isCarLOB.value || isBikeLOB.value;
  if (
    isCarOrBikeLOB &&
    !isRequiredFieldFilled.value &&
    requiredFieldName.value
  ) {
    notification.error({
      title: `Please fill ${requiredFieldName.value} before updating`,
      position: 'top',
    });
    return;
  }

  try {
    NProgress.start();
    claimSubStatusAndCustomerForm.processing = true;

    const response = await axios.post(
      route('claims.send-notification', props.claim?.uuid),
      claimSubStatusAndCustomerForm.data(),
    );

    // Show success notification
    if (response.data.success) {
      notification.success({
        title: response.data.message || 'Notification sent successfully',
        position: 'top',
      });
    }

    // Partial reload of just the claim data while preserving scroll
    router.visit(route('claims.show', props.claim?.uuid), {
      preserveScroll: true,
    });
  } catch (error) {
    console.error('Error sending notification:', error);

    // Handle validation errors
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors || {};
      Object.keys(errors).forEach(function (key) {
        const errorMessages = Array.isArray(errors[key])
          ? errors[key]
          : [errors[key]];
        errorMessages.forEach(message => {
          notification.error({
            title: message,
            position: 'top',
          });
        });
      });
    } else {
      // Handle other errors
      const errorMessage =
        error.response?.data?.message || 'Failed to send notification';
      notification.error({
        title: errorMessage,
        position: 'top',
      });
    }
  } finally {
    NProgress.done();
    claimSubStatusAndCustomerForm.processing = false;
  }
};

const disableClaimSubStatusAndCustomerUpdate = computed(() => {
  return (
    !props.requiredFieldsFilled ||
    !canAny([permissionsEnum.CLAIMS_SUB_STATUS_UPDATE])
  );
});

const enableOptimizeButton = computed(() => {
  return (
    claimSubStatusAndCustomerForm.claim_sub_status_id &&
    claimSubStatusAndCustomerForm.customer_message
  );
});

// Get the selected claim sub status text value
const selectedSubStatusText = computed(() => {
  if (!claimSubStatusAndCustomerForm.claim_sub_status_id) {
    return null;
  }
  const selectedStatus = props.dropdowns.claimSubStatuses?.find(
    status => status.id === claimSubStatusAndCustomerForm.claim_sub_status_id,
  );
  return selectedStatus?.text?.value || null;
});

// Helper function to check if a value is filled
const isFieldFilled = value => {
  if (value === null || value === undefined) {
    return false;
  }
  if (typeof value === 'string') {
    return value.trim() !== '';
  }
  if (typeof value === 'number') {
    return value > 0;
  }
  return Boolean(value);
};

// Status field requirements configuration
const statusFieldRequirements = computed(() => {
  if (!claimsEnum) {
    return {};
  }

  return {
    [claimsEnum.CLAIM_SUB_STATUS_REPAIR_APPROVED_AND_WORK_IN_PROGRESS?.toLowerCase()]:
      {
        fieldName: 'approved_repair_amount',
        label: 'Approved repair amount',
      },
    [claimsEnum.CLAIM_SUB_STATUS_TOTAL_LOSS_OFFER_LETTER_SHARED?.toLowerCase()]:
      {
        fieldName: 'approved_total_loss_amount',
        label: 'Total Loss Offered amount',
      },
    [claimsEnum.CLAIM_SUB_STATUS_CASH_LOSS_APPROVED?.toLowerCase()]: {
      fieldName: 'approved_cash_loss_amount',
      label: 'Cash loss offered amount',
    },
    [claimsEnum.CLAIM_SUB_STATUS_CLAIM_DENIED?.toLowerCase()]: {
      fieldName: 'claim_decline_reason',
      label: 'Claim denial reason',
    },
  };
});

// Get the field requirement for the selected status
const getStatusFieldRequirement = computed(() => {
  const statusText = selectedSubStatusText.value;
  if (!statusText) {
    return null;
  }

  const normalizedStatus = statusText.toLowerCase().trim();
  return statusFieldRequirements.value[normalizedStatus] || null;
});

// Get the required field name based on selected status
const requiredFieldName = computed(() => {
  return getStatusFieldRequirement.value?.label || null;
});

// Check if required field is filled based on selected status
const isRequiredFieldFilled = computed(() => {
  let isCarOrBikeLOB = isCarLOB.value || isBikeLOB.value;
  const requirement = getStatusFieldRequirement.value;
  if (!requirement) {
    return true; // No field requirement for this status
  }

  const fieldValue = props.claim?.[requirement.fieldName];
  return isCarOrBikeLOB ? isFieldFilled(fieldValue) : true;
});

const enableSendMessageButton = computed(() => {
  return (
    claimSubStatusAndCustomerForm.claim_sub_status_id &&
    claimSubStatusAndCustomerForm.customer_message &&
    claimSubStatusAndCustomerForm.ai_optimized_message &&
    isRequiredFieldFilled.value
  );
});

// Watch for sub-status changes and show alert if required field is not filled
watch(
  () => claimSubStatusAndCustomerForm.claim_sub_status_id,
  (newStatusId, oldStatusId) => {
    let isCarOrBikeLOB = isCarLOB.value || isBikeLOB.value;
    // Only show alert if status actually changed and we have a requirement
    if (
      newStatusId &&
      newStatusId !== oldStatusId &&
      requiredFieldName.value &&
      !isRequiredFieldFilled.value &&
      isCarOrBikeLOB
    ) {
      notification.error({
        title: `Please fill ${requiredFieldName.value} before updating`,
        position: 'top',
      });
    }
  },
);

const optimizeMessage = async () => {
  if (
    !claimSubStatusAndCustomerForm.customer_message ||
    !claimSubStatusAndCustomerForm.claim_sub_status_id
  ) {
    notification.error({
      title: 'Please provide both message and sub status before optimizing',
      position: 'top',
    });
    return;
  }

  try {
    NProgress.start();
    processing.value = true;

    const response = await axios.post(route('claims.optimize-message'), {
      message: claimSubStatusAndCustomerForm.customer_message,
      claim_uuid: props.claim?.uuid,
    });

    if (response.data.success) {
      claimSubStatusAndCustomerForm.ai_optimized_message =
        response.data.optimized_message;
      notification.success({
        title: 'Message optimized successfully',
        position: 'top',
      });
    } else {
      notification.error({
        title: response.data.message || 'Failed to optimize message',
        position: 'top',
      });
    }
  } catch (error) {
    console.error('Error optimizing message:', error);
    notification.error({
      title:
        error.response?.data?.message ||
        'An error occurred while optimizing the message',
      position: 'top',
    });
  } finally {
    NProgress.done();
    processing.value = false;
  }
};
</script>

<template>
  <div
    v-if="canAny([permissionsEnum.CLAIMS_SUB_STATUS_UPDATE])"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">
            Claim Sub Status
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form
          :form="claimSubStatusAndCustomerForm"
          @submit="updateClaimSubStatusAndCustomer"
        >
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-select
                  v-model="claimSubStatusAndCustomerForm.claim_sub_status_id"
                  label="Claim Sub Status"
                  :error="
                    claimSubStatusAndCustomerForm.errors.claim_sub_status_id
                  "
                  :options="subStatusOptions"
                  :disabled="disableClaimSubStatusAndCustomerUpdate"
                  placeholder="Claim Sub Status"
                  class="w-full uppercase"
                  filterable
                  required
                />
              </div>
            </div>
          </div>
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-textarea
                  v-model="claimSubStatusAndCustomerForm.customer_message"
                  label="Message"
                  :error="claimSubStatusAndCustomerForm.errors.customer_message"
                  :disabled="disableClaimSubStatusAndCustomerUpdate"
                  placeholder="Message"
                  class="w-full"
                  rows="5"
                />
              </div>
            </div>
            <div class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-textarea
                  v-model="claimSubStatusAndCustomerForm.ai_optimized_message"
                  label="AI Optimized Message"
                  :error="
                    claimSubStatusAndCustomerForm.errors.ai_optimized_message
                  "
                  :disabled="
                    disableClaimSubStatusAndCustomerUpdate ||
                    !claimSubStatusAndCustomerForm.ai_optimized_message
                  "
                  placeholder="AI Optimized Message"
                  class="w-full"
                  rows="5"
                />
              </div>
            </div>
          </div>

          <x-divider class="mt-4" />
          <div class="flex justify-end">
            <x-button
              :disabled="
                disableClaimSubStatusAndCustomerUpdate || !enableOptimizeButton
              "
              class="mt-4 mr-2"
              color="primary"
              size="sm"
              :loading="processing"
              @click="optimizeMessage"
            >
              Optimize
            </x-button>
            <x-button
              :disabled="
                disableClaimSubStatusAndCustomerUpdate ||
                !enableSendMessageButton
              "
              class="mt-4"
              color="success"
              size="sm"
              :loading="claimSubStatusAndCustomerForm.processing"
              type="submit"
            >
              Update and Notify to Customer
            </x-button>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
