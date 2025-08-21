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

    const allowedPages = ['/quotes/car/', '/personal-quotes/home/', '/medical/amt/'];
    const isAllowedPage = allowedPages.some(p => currentUrl.includes(p));

    // Only show notifications to the user who uploaded the document, same quote, and only on allowed pages
    if (
      e.data.uuid === page.props?.quote?.uuid &&
      currentUrl.includes('/quotes/car/') &&
      isCurrentUser &&
      isAllowedPage
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
      // Field checking is moved to page-level handlers to avoid duplicate notifications
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

// checkRequiredPolicyFields function moved to individual LOB pages to avoid duplicate notifications

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
