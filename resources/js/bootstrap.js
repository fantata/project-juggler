import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Laravel Echo over Reverb — real-time channel for WebRTC call signalling.
// Importing is cheap (no connection); only instantiate Echo where Reverb is
// actually configured (a key is present). On environments without a Reverb
// server it stays off — no failed-WebSocket spam, and the call widget simply
// doesn't appear.
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
