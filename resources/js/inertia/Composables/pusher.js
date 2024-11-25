import Pusher from "pusher-js";

const broadcastChannel = new BroadcastChannel("pusher-sync");

let pusherInstance = null;
let channels = {}; // Track subscribed channels

const options = {
  cluster: 'ap1',
  forceTLS: false,
};

export function getPusherInstance(key, options) {
  // Pusher instance is managed in the "master tab"
  if (!pusherInstance) {
    pusherInstance = new Pusher(key, options);
    pusherInstance.connection.bind("connected", () => {
      // Notify other tabs that this tab has the Pusher connection
      broadcastChannel.postMessage({ type: "pusher-connected" });
    });

    // Listen for requests from other tabs to subscribe to channels
    broadcastChannel.onmessage = (message) => {
      if (message.data.type === "pusher-subscribe") {
        const { channelName, eventName } = message.data;
        subscribeToChannelInternally(channelName, eventName);
      }
    };
  }
  return pusherInstance;
}

function subscribeToChannelInternally(channelName, eventName) {
  if (!channels[channelName]) {
    const channel = pusherInstance.subscribe(channelName);
    channels[channelName] = channel;

    channel.bind(eventName, (data) => {
      // Broadcast received events to all tabs
      broadcastChannel.postMessage({
        type: "pusher-event",
        channel: channelName,
        event: eventName,
        data,
      });
    });
  }
}

export function subscribeToChannel(pusherKey, channelName, eventName, callback) {
  getPusherInstance(pusherKey, options);

  // Broadcast subscription request to the "master tab"
  broadcastChannel.postMessage({
    type: "pusher-subscribe",
    channelName,
    eventName,
  });

  // Listen for events broadcast by the "master tab"
  broadcastChannel.onmessage = (message) => {
    if (
      message.data.type === "pusher-event" &&
      message.data.channel === channelName &&
      message.data.event === eventName
    ) {
      callback(message.data.data);
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
