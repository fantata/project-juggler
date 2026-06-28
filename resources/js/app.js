import './bootstrap';
import Sortable from 'sortablejs';

// Alpine is bundled with Livewire 3+, don't import separately

// Kanban drag-and-drop. Exposed globally so the board's Alpine x-init can reach it.
window.Sortable = Sortable;

// FantataID passkey browser helpers (window.fantataPasskey).
// Published from the fantata/auth package via vendor:publish --tag=fantata-auth-assets.
import './vendor/fantata-auth/fantata-passkey';
