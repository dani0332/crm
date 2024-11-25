import Pusher from 'pusher-js';
const broadcastChannel = new BroadcastChannel("pusher-sync");

let pusherInstance = null;
let channels = {}; // Track subscribed channels

const options = {
  cluster: 'ap1',
  forceTLS: false,
};

export function getPusherInstance(key, options) {
    if (!pusherInstance) {
        pusherInstance = new Pusher(key, options); // Create a single Pusher instance
    }
    return pusherInstance;
}

export function subscribeToChannel(pusher, channelName, eventName, callback) {
    // Subscribe only if the channel is not already initialized
    if (!channels[channelName]) {
        const channel = pusher.subscribe(channelName);
        channels[channelName] = channel;

        // Bind Pusher events to listen for updates
        channel.bind(eventName, (event) => {
            // Broadcast event to other tabs
            broadcastChannel.postMessage({
                type: "pusher-event",
                channel: channelName,
                event: eventName,
                data: event,
            });
        });
    }

    // Listen for events broadcasted by other tabs
    broadcastChannel.onmessage = (message) => {
        if (
            message.data.type === "pusher-event" &&
            message.data.channel === channelName &&
            message.data.event === eventName
        ) {
            callback(message.data.data); // Execute the callback with the event data
        }
    };
}

export function unbindEvent(channelName, eventName) {
    if (channels[channelName]) {
        channels[channelName].unbind(eventName);
    }
}

export function unsubscribeChannel(channelName) {
    if (channels[channelName]) {
        channels[channelName].unsubscribe();
        delete channels[channelName];
    }
}
