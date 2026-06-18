{{-- App-owned first-run enrolment UI. Style to taste — only the markup hooks
     (wire:model="email", wire:click="begin", the dispatched event, $wire.finish)
     matter. --}}
<div
    x-data
    x-on:fantata-passkey-create.window="
        try {
            const credential = await window.fantataPasskey.register($event.detail.publicKey);
            $wire.finish(credential);
        } catch (e) {
            $wire.set('error', 'Passkey registration was cancelled.');
        }
    "
>
    <input
        type="email"
        wire:model="email"
        placeholder="you@example.com"
        autocomplete="email"
        inputmode="email"
    />

    <button type="button" wire:click="begin" wire:loading.attr="disabled" wire:target="begin,finish">
        <span wire:loading.remove wire:target="begin,finish">Register a passkey</span>
        <span wire:loading wire:target="begin,finish">Follow your device's prompt…</span>
    </button>

    @if ($error)
        <p role="alert" data-fantata-error>{{ $error }}</p>
    @endif
</div>
