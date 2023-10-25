import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher.logToConsole = true;
const options = {
  broadcaster: 'pusher',
  key: process.env.VITE_PUSHER_APP_KEY,
  wsHost: window.location.hostname,
  encrypted: false,
  wsPort: 6001,
  enabledTransports: ['ws', 'wss'],
  cluster: 'ap1',
  forceTLS: false,
  disableStats: false,
};
window.Echo = new Echo({ ...options });
