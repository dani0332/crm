<script setup>
import { router } from '@inertiajs/vue3';
import NProgress from 'nprogress';

const props = defineProps({
  claim: Object,
  dropdowns: Object,
  requiredFieldsFilled: Boolean,
});

const emit = defineEmits(['update']);

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();
const processing = ref(false);

const claimSubStatusAndCustomerForm = useForm({
  customer_message: '',
  ai_optimized_message: '',
  claim_sub_status_id: props.claim?.claim_sub_status_id || '',
});

const subStatusOptions = computed(() => {
  return (
    props.dropdowns.claimSubStatuses
      ?.filter(
        subStatus => subStatus.quote_type_id === page.props.claim.quote_type_id,
      )
      ?.map(subStatus => ({
        value: subStatus.id,
        label: subStatus.text,
      })) || []
  );
});

const updateClaimSubStatusAndCustomer = async (isValid) => {
  console.log('updateClaimStatus');
  
  try {
    NProgress.start();
    claimSubStatusAndCustomerForm.processing = true;

    const response = await axios.post(
      route('claims.send-notification', props.claim?.uuid),
      claimSubStatusAndCustomerForm.data()
    );
    
    console.log('response', response.data);
    
    // Show success notification
    if (response.data.success) {
      claimSubStatusAndCustomerForm.reset();
      notification.success({
        title: response.data.message || 'Notification sent successfully',
        position: 'top',
      });
    }
    
    // Partial reload of just the claim data while preserving scroll
    router.reload({
      only: ['claim', 'dropdowns'],
      preserveScroll: true,
      preserveState: true,
    });
    
    // Emit update event
    emit('update', response.data);
    
  } catch (error) {
    console.error('Error sending notification:', error);
    
    // Handle validation errors
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors || {};
      Object.keys(errors).forEach(function (key) {
        const errorMessages = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
        errorMessages.forEach(message => {
          notification.error({
            title: message,
            position: 'top',
          });
        });
      });
    } else {
      // Handle other errors
      const errorMessage = error.response?.data?.message || 'Failed to send notification';
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

const enableSendMessageButton = computed(() => {
  return (
    claimSubStatusAndCustomerForm.claim_sub_status_id &&
    claimSubStatusAndCustomerForm.customer_message &&
    claimSubStatusAndCustomerForm.ai_optimized_message
  );
});

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

    const response = await axios.post(
      route(
        'claims.optimize-message',
        claimSubStatusAndCustomerForm.claim_sub_status_id,
      ),
      {
        message: claimSubStatusAndCustomerForm.customer_message,
      },
    );

    if (response.data.status) {
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
    <Collapsible :expanded="true">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Claim Status</h3>
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
                  :disabled="disableClaimSubStatusAndCustomerUpdate || !claimSubStatusAndCustomerForm.ai_optimized_message"
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
