<?php

namespace Fantata\Auth;

use GuzzleHttp\Client;

/**
 * Thin client for the FantataID auth API (openapi/auth.yaml in fantata-platform).
 * All the WebAuthn crypto lives server-side; this just relays ceremonies and
 * returns the token pair / identity summary.
 */
class FantataIdClient
{
    private Client $http;

    public function __construct(
        private string $baseUrl,
        private string $site,
        int $timeout = 10,
    ) {
        $this->http = new Client([
            'base_uri' => $this->baseUrl.'/',
            'timeout' => $timeout,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }

    /** @return array{ceremony_id:string, publicKey:array} */
    public function loginBegin(?string $email = null): array
    {
        return $this->post('v1/auth/login/begin', array_filter([
            'site' => $this->site,
            'email' => $email,
        ]));
    }

    /** @return array token pair + identity summary (see TokenPair schema) */
    public function loginFinish(string $ceremonyId, array $credential): array
    {
        return $this->post('v1/auth/login/finish', [
            'ceremony_id' => $ceremonyId,
            'credential' => $credential,
        ]);
    }

    /** @return array{ceremony_id:string, publicKey:array} */
    public function registerBegin(string $email): array
    {
        return $this->post('v1/auth/register/begin', [
            'site' => $this->site,
            'email' => $email,
        ]);
    }

    public function registerFinish(string $ceremonyId, array $credential, ?string $label = null): array
    {
        return $this->post('v1/auth/register/finish', array_filter([
            'ceremony_id' => $ceremonyId,
            'credential' => $credential,
            'label' => $label,
        ]));
    }

    public function refresh(string $refreshToken): array
    {
        return $this->post('v1/auth/token/refresh', ['refresh_token' => $refreshToken]);
    }

    private function post(string $path, array $body): array
    {
        $res = $this->http->post($path, ['json' => $body]);

        return json_decode((string) $res->getBody(), true) ?? [];
    }
}
