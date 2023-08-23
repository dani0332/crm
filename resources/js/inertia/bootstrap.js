import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Pusher.logToConsole = true;
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: '55209d647ceac319ce26',
  cluster: 'ap1',
  forceTLS: true,
});
