// FantataID passkey browser helpers.
// Depends on @simplewebauthn/browser (handles base64url encoding + the
// navigator.credentials calls). Install in the host app:  npm i @simplewebauthn/browser
//
// Import once in the app's bundle:  import 'vendor/fantata-auth/fantata-passkey';
import { startAuthentication, startRegistration } from '@simplewebauthn/browser';

window.fantataPasskey = {
    // options = the `publicKey` object FantataID returned (RP id = fantata.com).
    // The ceremony runs on THIS app's origin; Related Origin Requests
    // (fantata.com/.well-known/webauthn) lets it use the shared RP.
    authenticate: (publicKey) => startAuthentication({ optionsJSON: publicKey }),
    register: (publicKey) => startRegistration({ optionsJSON: publicKey }),
};
