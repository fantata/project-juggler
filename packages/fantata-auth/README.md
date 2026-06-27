# fantata/auth

FantataID passkey auth for Laravel apps. Your app keeps **its own login + account
UI on its own domain** — this package handles the FantataID round-trip and logs the
user into your normal Laravel session. One passkey works across every Fantata app
(shared relying party `fantata.com` + WebAuthn Related Origin Requests).

> **Status: scaffold.** The server side (the FantataID auth service) is specced in
> `fantata-platform` (`domain/authentication.md`, `openapi/auth.yaml`, brief 011) but
> not built yet. This package is the client and is ready to wire once the service exists.

## Install (in a consumer app, e.g. Fast Five)

```bash
composer require fantata/auth
php artisan vendor:publish --tag=fantata-auth-config
php artisan migrate           # adds users.fantata_id, makes password nullable
npm i @simplewebauthn/browser
```

`.env`:

```
FANTATA_BASE_URL=https://accounts.fantata.com
FANTATA_RP_ID=fantata.com
FANTATA_SITE=fast-five           # this app's site slug in the platform
```

Add the trait to your `User` model:

```php
use Fantata\Auth\Concerns\HasFantataId;
class User extends Authenticatable { use HasFantataId; /* ... */ }
```

Import the browser helper in your JS bundle:

```js
import '../../vendor/fantata-auth/fantata-passkey'; // after vendor:publish --tag=fantata-auth-assets
```

Then drop the component into **your own** login page (style it freely):

```blade
<livewire:fantata-passkey-login />
```

## How it works

1. `begin()` asks FantataID for a WebAuthn challenge (RP `fantata.com`).
2. The browser runs the ceremony **on your app's origin** (allowed via
   `fantata.com/.well-known/webauthn`).
3. `finish()` verifies the assertion with FantataID, projects the FantataID into a
   local `users` row (`HasFantataId::fromFantataId`), and `Auth::login`s the user.

The OS/Bitwarden passkey picker shows the credential as **fantata.com** (the shared
relying party) — your login *page* is fully your app's. There's no redirect; it's
one-tap per app (not silent cross-app SSO — that's a deliberate trade).

## Onboarding a new app

1. Register the app as a Site in the platform (gives it a slug + membership rules).
2. Add its origin to `fantata.com/.well-known/webauthn`.
3. Install this package, build your own login/account screens.

See `fantata-platform/docs/fantata-id-auth.md` for the full architecture.
