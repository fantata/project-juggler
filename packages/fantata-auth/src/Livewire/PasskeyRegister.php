<?php

namespace Fantata\Auth\Livewire;

use Fantata\Auth\FantataIdClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * First-time passkey enrolment. Drop <livewire:fantata-passkey-register /> into
 * an app's own sign-up / first-run page. The WebAuthn create() ceremony runs in
 * this app's browser context (RP id = fantata.com via Related Origin Requests);
 * the server bits round-trip through FantataID, which creates the FantataID and
 * returns a token pair, so the new user is logged straight in.
 */
class PasskeyRegister extends Component
{
    public string $email = '';

    public ?string $ceremonyId = null;

    public ?string $error = null;

    /** Step 1: ask FantataID for a creation challenge, hand it to the browser. */
    public function begin(): void
    {
        $this->error = null;
        $this->validate(['email' => 'required|email']);

        $data = app(FantataIdClient::class)->registerBegin($this->email);
        $this->ceremonyId = $data['ceremony_id'];
        $this->dispatch('fantata-passkey-create', publicKey: $data['publicKey']);
    }

    /** Step 2: browser returns the new credential → verify → log in. */
    public function finish(array $credential): void
    {
        try {
            $identity = app(FantataIdClient::class)->registerFinish($this->ceremonyId, $credential);

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
            $this->error = "We couldn't register that passkey — give it another go.";
        }
    }

    public function render()
    {
        return view('fantata-auth::livewire.passkey-register');
    }
}
