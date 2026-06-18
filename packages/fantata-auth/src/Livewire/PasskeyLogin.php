<?php

namespace Fantata\Auth\Livewire;

use Fantata\Auth\FantataIdClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Drop <livewire:fantata-passkey-login /> into an app's OWN login page. The
 * WebAuthn ceremony runs in this app's browser context (RP id = fantata.com via
 * Related Origin Requests); the server bits round-trip through FantataID.
 */
class PasskeyLogin extends Component
{
    public ?string $ceremonyId = null;

    public ?string $error = null;

    /** Step 1: ask FantataID for a login challenge, hand it to the browser. */
    public function begin(): void
    {
        $this->error = null;
        $data = app(FantataIdClient::class)->loginBegin();
        $this->ceremonyId = $data['ceremony_id'];
        $this->dispatch('fantata-passkey-get', publicKey: $data['publicKey']);
    }

    /** Step 2: browser returns the assertion → verify with FantataID → log in. */
    public function finish(array $credential): void
    {
        try {
            $identity = app(FantataIdClient::class)->loginFinish($this->ceremonyId, $credential);

            $model = config('fantata-auth.user_model');
            $user = $model::fromFantataId($identity);

            session([
                'fantata_token' => $identity['access_token'] ?? null,
                'fantata_refresh' => $identity['refresh_token'] ?? null,
            ]);

            Auth::login($user, remember: true);
            session()->regenerate();

            $this->redirectIntended(config('fantata-auth.redirect_after_login'));
        } catch (\Throwable $e) {
            report($e);
            $this->error = "That passkey didn't work — give it another go.";
        }
    }

    public function render()
    {
        return view('fantata-auth::livewire.passkey-login');
    }
}
