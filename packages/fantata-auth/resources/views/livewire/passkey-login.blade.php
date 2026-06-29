{{-- App-owned login UI. Style this however the host app likes — only the markup
     hooks (wire:click="begin", the dispatched event, $wire.finish) matter. --}}
<div
    x-data
    x-on:fantata-passkey-get.window="(async () => {
        try {
            const credential = await window.fantataPasskey.authenticate($event.detail.publicKey);
            $wire.finish(credential);
        } catch (e) {
            $wire.set('error', 'Passkey sign-in was cancelled.');
        }
    })()"
>
    <button type="button" wire:click="begin" wire:loading.attr="disabled" wire:target="begin,finish">
        <span wire:loading.remove wire:target="begin,finish">Sign in with a passkey</span>
        <span wire:loading wire:target="begin,finish">Waiting for your passkey…</span>
    </button>

    @if ($error)
        <p role="alert" data-fantata-error>{{ $error }}</p>
    @endif
</div>
