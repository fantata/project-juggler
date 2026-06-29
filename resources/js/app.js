import './bootstrap';
import './calendar'; // registers the FullCalendar Alpine component (bundled, not inline)

// Alpine is bundled with Livewire 3+, don't import separately

// FantataID passkey browser helpers (window.fantataPasskey).
// Published from the fantata/auth package via vendor:publish --tag=fantata-auth-assets.
import './vendor/fantata-auth/fantata-passkey';
