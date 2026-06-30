import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Laravel Echo over Reverb — real-time channel for WebRTC call signalling.
// Connection details come from the server at runtime via window.__reverb (the
// layout renders them from the app's REVERB_* config), so the prod host isn't
// baked into the build. Falls back to Vite env vars for local dev. Only
// instantiate Echo where a key is present — otherwise it stays off (no failed
// WebSocket spam, and the call widget simply doesn't appear).
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const reverb = window.__reverb ?? (import.meta.env.VITE_REVERB_APP_KEY ? {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME,
} : null);

if (reverb?.key) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverb.key,
        wsHost: reverb.host,
        wsPort: Number(reverb.port) || 80,
        wssPort: Number(reverb.port) || 443,
        forceTLS: (reverb.scheme ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
