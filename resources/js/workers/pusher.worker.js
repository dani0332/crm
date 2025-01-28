import Pusher from 'pusher-js/worker';

var clients = [];
var subscriptions = {}; // Store subscriptions (channel -> event -> client)
var pusher;

function subscribeToChannel(
  channelName,
  eventName,
  port,
  pusherKey,
  pusherCluster,
) {
  if (!subscriptions[channelName]) {
    subscriptions[channelName] = {};
  }

  if (!subscriptions[channelName][eventName]) {
    subscriptions[channelName][eventName] = [];
  }

  // Add the client's port to the list of subscribers for this event on this channel
  subscriptions[channelName][eventName].push(port);

  if (!pusher) {
    pusher = new Pusher(pusherKey, {
      cluster: pusherCluster,
      forceTLS: false,
    });

    pusher.connection.bind('connected', () => {
      console.log('Pusher connected successfully.');
    });

    pusher.connection.bind('error', err => {
      console.error('Pusher connection error:', err);
    });
  }

  const channel = pusher.subscribe(channelName);

  // Bind to the event only once per event on this channel
  if (!channel._boundEvents || !channel._boundEvents.includes(eventName)) {
    channel.bind(eventName, function (data) {
      // Relay data to all clients subscribed to this event
      (subscriptions[channelName][eventName] || []).forEach(function (client) {
        client.postMessage(data);
      });
    });

    if (!channel._boundEvents) {
      channel._boundEvents = [];
    }
    channel._boundEvents.push(eventName);
  }
}

function unsubscribeFromChannel(channelName, eventName, port) {
  if (!subscriptions[channelName] || !subscriptions[channelName][eventName]) {
    return; // Nothing to unsubscribe
  }

  // Remove the client's port from the list of subscribers
  subscriptions[channelName][eventName] = subscriptions[channelName][
    eventName
  ].filter(client => client !== port);

  // If no clients are subscribed to this event, unbind it
  if (subscriptions[channelName][eventName].length === 0) {
    const channel = pusher.channel(channelName);
    if (channel) {
      channel.unbind(eventName);
      // Remove the event from the list of bound events
      channel._boundEvents = channel._boundEvents.filter(e => e !== eventName);
    }
    delete subscriptions[channelName][eventName];
  }

  // If no events remain for this channel, unsubscribe
  if (Object.keys(subscriptions[channelName]).length === 0) {
    pusher.unsubscribe(channelName);
    delete subscriptions[channelName];
  }
}

self.addEventListener('connect', function (event) {
  var port = event.ports[0];
  clients.push(port);

  port.postMessage('Hello, new client connected!');

  port.addEventListener('message', e => {
    const { channel, event, action, pusherKey, pusherCluster } = e.data;

    if (action === 'subscribe') {
      subscribeToChannel(channel, event, port, pusherKey, pusherCluster);
    } else if (action === 'unsubscribe') {
      unsubscribeFromChannel(channel, event, port);
    }
  });

  port.start();
});
