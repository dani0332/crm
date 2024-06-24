<script setup>
import Pusher from 'pusher-js';
const page = usePage();

const notification = useNotifications('toast');

const options = {
  cluster: 'ap1',
  forceTLS: false,
};

const pusher = new Pusher(page.props.pusherKey, options);
const channel = pusher.subscribe(
  'public.' + page.props.appEnv + '.activity.user',
);

const listen = () => {
  channel.bind('expire.notification', function (e) {
    if (e.advisorId === page.props.auth.user.id) {
      notification.info({
        title: 'Payment',
        iconColor: 'success',
        message: e.message,
        action: {
          label: 'REF#',
          onClick: () => {
            window.open(e.url, '_self');
          },
        },
        timeout: 3500
      });
    }
  });
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  channel.unbind('expire.notification');
  channel.unsubscribe('public.' + page.props.appEnv + '.activity.user');
});
</script>

<template>
  <div></div>
</template>
