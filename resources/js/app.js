import './bootstrap';
import Sortable from 'sortablejs';
import focus from '@alpinejs/focus';

// Alpine is bundled with Livewire 3+, don't import separately — register plugins
// on its init hook instead.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus); // x-trap for accessible modals
});

// Kanban drag-and-drop. Exposed globally so the board's Alpine x-init can reach it.
// Honour reduced-motion by disabling the slide animation.
window.Sortable = Sortable;
window.boardSortAnimation = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 150;

// FantataID passkey browser helpers (window.fantataPasskey).
// Published from the fantata/auth package via vendor:publish --tag=fantata-auth-assets.
import './vendor/fantata-auth/fantata-passkey';
