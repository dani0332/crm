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
  worker = new SharedWorker(
    '/build/workers/pusher.worker.js?v=' + new Date().getTime(),
  );
  worker.port.addEventListener('message', e => {
    const currentUrl = page.props.location || '';
    const isCurrentUser = e.data.userId === page.props.auth.user.id;

    // Only show notifications to the user who uploaded the document
    if (
      e.data.uuid === page.props?.quote?.uuid &&
      currentUrl.includes('/quotes/car/') &&
      isCurrentUser
    ) {
      // Only show toast notification for 'start' status and CERTIFICATE_OF_ISSUANCE document type
      // Still show 'fail' status notifications for all supported document types
      if (
        (e.data.status === 'start' &&
          e.data.docType ===
            page.props.ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value) ||
        e.data.status === 'fail'
      ) {
        notification.info({
          title: e.data.message,
          position: 'top',
        });
      }

      window.dispatchEvent(
        new CustomEvent('ocr-notification', {
          detail: {
            imageUrl: '/image/alfred-theme.png',
            title: 'OCR Notification',
            message: e.data.message,
            status: e.data.status,
            uuid: e.data.uuid,
            error: e.data.error,
            docType: e.data.docType,
            userId: e.data.userId,
          },
        }),
      );

      // The completion notification for 'end' status is removed as per requirements
      // Field checking is still kept for updating UI if needed
      if (e.data.status === 'end' && !e.data.error) {
        // Check if we need to update lead status to "Policy Issued"
        const allFieldsFilled = checkRequiredPolicyFields();

        // Only show notification if fields are missing
        if (!allFieldsFilled) {
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
    { field: 'price_vat_applicable', property: 'price_vat_applicable' },
  ];

  // Make sure all required fields have values
  const result = requiredFields.every(item => {
    // Check both the direct quote property and the form property
    const value = quote[item.field] || quote[item.property];
    return value !== null && value !== undefined && String(value).trim() !== '';
  });

  return result;
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
