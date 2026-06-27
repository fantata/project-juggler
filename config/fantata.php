<?php

return [

    /*
    | Master switch for FantataID passkey auth. When true the login screen
    | offers passkey sign-in; when false it falls back to the built-in
    | email and password form. Lets us ship behind a flag and flip it on
    | once the juggler site is registered with the platform.
    */
    'auth' => env('FANTATA_AUTH', false),

    /*
    | Comma separated list of emails permitted to sign in via FantataID.
    | Empty allows any verified FantataID. Project Juggler is a two person
    | app, so we keep this tight.
    */
    'allowed_emails' => env('JUGGLER_ALLOWED_EMAILS', ''),

];
