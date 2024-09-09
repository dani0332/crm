<script setup>
import Pusher from 'pusher-js';
const page = usePage();
import CallbackNotification from './CallBackNotification.vue';
const showNotification = ref(false);
const notificationData = ref({});
const options = {
    cluster: 'ap1',
    forceTLS: false,
};
const pusher = new Pusher(page.props.pusherKey, options);
const channel = pusher.subscribe(
    'public.' + page.props.appEnv + '.activity.user',
);
const listen = () => {
    channel.bind('callback.notification', function (e) {
        if (e.advisorId === page.props.auth.user.id) {
            notificationData.value = {
                imageUrl: '/image/alfred-theme.png',
                url: e.url,
                quoteUuid: e.quoteUuid,
                title: e.title,
                message:e.message,
                timeout: 30000,
            };
            showNotification.value = true;
        }
    });
};
const hideNotification = () => {
    showNotification.value = false;
};
onMounted(() => {
    listen();
});
onUnmounted(() => {
    channel.unbind('callback.notification');
    channel.unsubscribe('public.' + page.props.appEnv + '.activity.user');
});
</script>

<template>
    <div>
        <CallbackNotification
            v-if="showNotification"
            :imageUrl="notificationData.imageUrl"
            :url="notificationData.url"
            :quoteUuid="notificationData.quoteUuid"
            :title="notificationData.title"
            :message="notificationData.message"
            :timeout="notificationData.timeout"
            :callHideFunction="hideNotification"
            @close="showNotification = false"
        />
    </div>
</template>
