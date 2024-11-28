import Pusher from 'pusher-js/worker';

var clients = [];
var subscriptions = {}; // Store subscriptions (channel -> event -> client)
var pusher;

function subscribeToChannel(channelName, eventName, port, pusherKey, pusherCluster) {
  console.log(`Subscribing to channel: ${channelName}, event: ${eventName}`);

  if (!subscriptions[channelName]) {
    subscriptions[channelName] = {};
  }

  if (!subscriptions[channelName][eventName]) {
    subscriptions[channelName][eventName] = [];
  }

  // Add the client's port to the list of subscribers for this event on this channel
  subscriptions[channelName][eventName].push(port);
  console.log(`Current subscriptions for ${channelName}.${eventName}:`, subscriptions[channelName][eventName]);

  if (!pusher) {
    console.log('Initializing Pusher connection...');
    pusher = new Pusher(pusherKey, {
      cluster: pusherCluster,
      forceTLS: false,
    });

    pusher.connection.bind('connected', () => {
      console.log('Pusher connected successfully.');
    });

    pusher.connection.bind('error', (err) => {
      console.error('Pusher connection error:', err);
    });
  }

  const channel = pusher.subscribe(channelName);
  console.log(`Subscribed to Pusher channel: ${channelName}`);

  // Bind to the event only once per event on this channel
  if (!channel._boundEvents || !channel._boundEvents.includes(eventName)) {
    console.log(`Binding to event: ${eventName} on channel: ${channelName}`);
    channel.bind(eventName, function (data) {
      console.log(`Event triggered: ${eventName} on channel: ${channelName}`, data);

      // Relay data to all clients subscribed to this event
      (subscriptions[channelName][eventName] || []).forEach(function (client) {
        console.log(`Sending data to client:`, data);
        client.postMessage(data);
      });
    });

    if (!channel._boundEvents) {
      channel._boundEvents = [];
    }
    channel._boundEvents.push(eventName);
    console.log(`Event binding complete for: ${eventName} on channel: ${channelName}`);
  }
}

function unsubscribeFromChannel(channelName, eventName, port) {
  console.log(`Unsubscribing from channel: ${channelName}, event: ${eventName}`);
  if (!subscriptions[channelName] || !subscriptions[channelName][eventName]) {
    console.log(`No subscription found for channel: ${channelName}, event: ${eventName}`);
    return; // Nothing to unsubscribe
  }

  // Remove the client's port from the list of subscribers
  subscriptions[channelName][eventName] = subscriptions[channelName][eventName].filter(
    (client) => client !== port
  );

  console.log(`Remaining subscriptions for ${channelName}.${eventName}:`, subscriptions[channelName][eventName]);

  // If no clients are subscribed to this event, unbind it
  if (subscriptions[channelName][eventName].length === 0) {
    const channel = pusher.channel(channelName);
    if (channel) {
      console.log(`Unbinding event: ${eventName} from channel: ${channelName}`);
      channel.unbind(eventName);
      // Remove the event from the list of bound events
      channel._boundEvents = channel._boundEvents.filter((e) => e !== eventName);
    }
    delete subscriptions[channelName][eventName];
    console.log(`Event: ${eventName} removed from subscriptions for channel: ${channelName}`);
  }

  // If no events remain for this channel, unsubscribe
  if (Object.keys(subscriptions[channelName]).length === 0) {
    console.log(`Unsubscribing from channel: ${channelName}`);
    pusher.unsubscribe(channelName);
    delete subscriptions[channelName];
  }
}

self.addEventListener("connect", function(event) {
  var port = event.ports[0];
  clients.push(port);

  console.log('New client connected. Total clients:', clients.length);
  port.postMessage('Hello, new client connected!');

  port.addEventListener("message", (e) => {
    console.log('Received message from main thread:', e.data);
    const { channel, event, action, pusherKey, pusherCluster } = e.data;

    if (action === 'subscribe') {
      subscribeToChannel(channel, event, port, pusherKey, pusherCluster);
    } else if (action === 'unsubscribe') {
      unsubscribeFromChannel(channel, event, port);
    }
  });

  port.start();
  console.log('Port communication started.');
});
