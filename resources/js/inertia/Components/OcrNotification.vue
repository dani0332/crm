<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';

const page = usePage();
const notification = useNotifications('toast');
const channelName = `public.${page.props.appEnv}.ocr.user`;
const eventName = 'ocr.notification';

let worker;

const listen = () => {
  worker = new SharedWorker('/build/workers/pusher.worker.js?v=' + new Date().getTime());
  worker.port.addEventListener('message', e => {
    const currentUrl = page.props.location || '';
    if (
      e.data.uuid === page.props?.quote?.uuid &&
      currentUrl.includes('/quotes/car/') &&
      e.data.userId === page.props.auth.user.id
    ) {
      notification.info({
        title: e.data.message,
        position: 'top',
      });

      // Emit event for PolicyDetail.vue
      window.dispatchEvent(new CustomEvent('ocr-notification', {
        detail: {
          imageUrl: '/image/alfred-theme.png',
          title: 'OCR Notification',
          message: e.data.message,
          status: e.data.status,
          uuid: e.data.uuid,
          error: e.data.error,
          docType: e.data.docType,
        }
      }));

      // If OCR completed successfully and all required fields are filled
      if (e.data.status === 'end' && e.data.message.includes('completed') && !e.data.error) {
        // Check if we need to update lead status to "Policy Issued"
        const allFieldsFilled = checkRequiredPolicyFields();

        // Only show notification if fields are missing, otherwise silently update status
        if (allFieldsFilled) {
          // Update lead status to "Policy Issued"
          updateLeadStatus(e.data.uuid);
        } else {
          notification.info({
            title: 'Some required fields are still missing in Policy details',
            position: 'top',
          });
        }
      }
    }
  });
  worker.onerror = function (error) {
    worker.port.close();
  };
  worker.port.start();
  worker.port.postMessage({
    action: 'subscribe',
    channel: channelName,
    event: eventName,
    pusherKey: page.props.pusherKey,
    pusherCluster: page.props.pusherCluster,
  });
};

const checkRequiredPolicyFields = () => {
  // Check if all required policy fields with FieldLoader overlays are filled
  const quote = page.props?.quote;
  if (!quote) {
    return false;
  }

  // Only check the 4 fields that have FieldLoader overlays
  const requiredFields = [
    { field: 'policy_number', property: 'quote_policy_number' },
    { field: 'policy_start_date', property: 'quote_policy_start_date' },
    { field: 'policy_expiry_date', property: 'quote_policy_expiry_date' },
    { field: 'price_vat_applicable', property: 'price_vat_applicable' }
  ];

  // Make sure all required fields have values
  const result = requiredFields.every(item => {
    // Check both the direct quote property and the form property
    const value = quote[item.field] || quote[item.property];
    return value !== null && value !== undefined && String(value).trim() !== '';
  });

  return result;
};

const updateLeadStatus = (uuid) => {
  // Make sure we have a valid quote object
  if (!page.props.quote || !page.props.quote.id) {
    notification.error({
      title: 'Cannot update lead status: Quote data is missing',
      position: 'top',
    });
    return;
  }

  // Determine the correct model type from the URL
  const currentUrl = page.props.location || '';
  const modelType = currentUrl.includes('/quotes/car/') ? 'Car' : 'Home';

  // Create form data with all required fields
  const leadStatusForm = {
    modelType: modelType,
    leadId: page.props.quote.id,
    quote_uuid: page.props.quote.uuid,
    leadStatus: 33, // PolicyIssued status ID from QuoteStatusEnum
    notes: page.props.quote.notes || null,
    assigned_to_user_id: page.props.quote.advisor_id || page.props.auth.user.id,
  };

  // Update lead status to "Policy Issued"
  router.post(
    route('updateLeadStatus', {
      modelType: modelType,
      QuoteUId: page.props.quote.id,
    }),
    leadStatusForm,
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Lead status has been updated to Policy Issued',
          position: 'top',
        });
      },
      onError: (errors) => {
        notification.error({
          title: errors.value || 'Error updating lead status',
          position: 'top',
        });
      }
    }
  );
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  if (worker) {
    worker.port.postMessage({
      action: 'unsubscribe',
      channel: channelName,
      event: eventName,
    });
  }
});
</script>

<template>
  <!-- Empty template as we're using toast notifications instead -->
</template>