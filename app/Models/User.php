<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Fantata\Auth\Concerns\HasFantataId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasFantataId, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'fantata_id',
        'ics_feed_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'ics_feed_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Project a FantataID identity onto a local user row.
     *
     * This overrides the package default (which matches on fantata_id only).
     * Project Juggler already has an established user whose id anchors every
     * project, its Sanctum API tokens and the ICS feed token. So on first
     * sign-in we link by email and keep that same row, rather than minting a
     * duplicate. That keeps existing API tokens and the calendar feed valid.
     * Access is gated to the allowlist in config/fantata.php.
     *
     * @param  array  $identity  The TokenPair body from loginFinish/registerFinish.
     */
    public static function fromFantataId(array $identity): static
    {
        $email = $identity['email'] ?? null;

        if (! static::fantataEmailAllowed($email)) {
            throw new \RuntimeException('This FantataID is not permitted to sign in to Project Juggler.');
        }

        $name = $identity['display_name'] ?? $identity['handle'] ?? 'Member';

        // Already linked: just refresh the denormalised display fields.
        $user = static::where('fantata_id', $identity['fantata_id'])->first();

        // First sign-in for an existing local account: link by email so the
        // existing id, and its API and ICS tokens, carries over untouched.
        $user ??= static::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'fantata_id' => $identity['fantata_id'],
                'name' => $name,
                'email' => $email,
            ])->save();

            return $user;
        }

        // A genuinely new person, for example the second Juggler user enrolling.
        return static::create([
            'fantata_id' => $identity['fantata_id'],
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * Is this FantataID email allowed to sign in? An empty allowlist allows any
     * verified FantataID, matching the package's default posture.
     */
    protected static function fantataEmailAllowed(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        $allowed = array_filter(array_map(
            'trim',
            explode(',', (string) config('fantata.allowed_emails'))
        ));

        return $allowed === []
            || in_array(strtolower($email), array_map('strtolower', $allowed), true);
    }
}
