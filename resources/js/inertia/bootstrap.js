import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Pusher.logToConsole = true;
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: process.env.MIX_PUSHER_APP_KEY,
  cluster: 'ap1',
  forceTLS: true,
});
