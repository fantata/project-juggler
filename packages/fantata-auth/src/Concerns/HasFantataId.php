<?php

namespace Fantata\Auth\Concerns;

/**
 * Add to the app's User model. The local row is a projection of the FantataID
 * (like Pulsinator's `keycloak_sub`) — credentials live in FantataID, the app
 * just keeps the mapping + denormalised display fields.
 */
trait HasFantataId
{
    /**
     * Upsert the local user from a FantataID token-pair payload and return it.
     *
     * @param  array  $identity  The TokenPair body from loginFinish/registerFinish.
     */
    public static function fromFantataId(array $identity): static
    {
        return static::updateOrCreate(
            ['fantata_id' => $identity['fantata_id']],
            [
                'name' => $identity['display_name'] ?? $identity['handle'] ?? 'Member',
                'email' => $identity['email'] ?? null,
            ],
        );
    }
}
