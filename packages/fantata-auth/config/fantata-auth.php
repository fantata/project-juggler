<?php

return [

    /*
    | The FantataID service base URL (the identity service auth surface).
    | e.g. https://accounts.fantata.com — or the local gateway in dev.
    */
    'base_url' => env('FANTATA_BASE_URL', 'https://accounts.fantata.com'),

    /*
    | The WebAuthn relying-party ID — the FantataID home domain. Every app uses
    | the SAME value so one passkey works across all of them (Related Origin
    | Requests). This app's own origin must be listed in
    | https://fantata.com/.well-known/webauthn.
    */
    'rp_id' => env('FANTATA_RP_ID', 'fantata.com'),

    /*
    | This app's site slug, as registered in the FantataID platform (drives
    | which membership/role the token carries).
    */
    'site' => env('FANTATA_SITE'),

    /*
    | Where to send the user after a successful login.
    */
    'redirect_after_login' => env('FANTATA_REDIRECT', '/'),

    /*
    | The local Eloquent user model that carries the `fantata_id` projection.
    */
    'user_model' => env('FANTATA_USER_MODEL', \App\Models\User::class),

    'http_timeout' => 10,
];
