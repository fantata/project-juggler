<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Tests\TestCase;

class FantataAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A FantataID payload as returned by loginFinish/registerFinish.
     */
    private function identity(array $overrides = []): array
    {
        return array_merge([
            'fantata_id' => 'fid_abc123',
            'display_name' => 'Chris Read',
            'email' => 'chris@fantata.com',
        ], $overrides);
    }

    public function test_existing_user_is_linked_by_email_keeping_the_same_row(): void
    {
        Config::set('fantata.allowed_emails', 'chris@fantata.com');

        $existing = User::factory()->create([
            'email' => 'chris@fantata.com',
            'fantata_id' => null,
        ]);

        $user = User::fromFantataId($this->identity());

        // Same row, now carrying the FantataID, no duplicate created.
        $this->assertSame($existing->id, $user->id);
        $this->assertSame('fid_abc123', $user->fantata_id);
        $this->assertSame(1, User::count());
    }

    public function test_linking_preserves_api_tokens_and_the_ics_feed_token(): void
    {
        Config::set('fantata.allowed_emails', 'chris@fantata.com');

        $existing = User::factory()->create([
            'email' => 'chris@fantata.com',
            'fantata_id' => null,
            'ics_feed_token' => hash('sha256', 'ics-secret'),
        ]);
        $plainToken = $existing->createToken('api')->plainTextToken;

        User::fromFantataId($this->identity());

        // The Sanctum token still resolves to the same user.
        $found = PersonalAccessToken::findToken($plainToken);
        $this->assertNotNull($found);
        $this->assertSame($existing->id, $found->tokenable_id);
        $this->assertSame(1, $existing->fresh()->tokens()->count());

        // The ICS feed token is untouched.
        $this->assertSame(hash('sha256', 'ics-secret'), $existing->fresh()->ics_feed_token);
    }

    public function test_already_linked_user_has_display_fields_refreshed(): void
    {
        Config::set('fantata.allowed_emails', '');

        $existing = User::factory()->create([
            'email' => 'chris@fantata.com',
            'fantata_id' => 'fid_abc123',
            'name' => 'Old Name',
        ]);

        $user = User::fromFantataId($this->identity(['display_name' => 'New Name']));

        $this->assertSame($existing->id, $user->id);
        $this->assertSame('New Name', $user->name);
        $this->assertSame(1, User::count());
    }

    public function test_a_new_allowed_person_creates_a_fresh_row(): void
    {
        Config::set('fantata.allowed_emails', '');

        $user = User::fromFantataId($this->identity([
            'fantata_id' => 'fid_second',
            'email' => 'second@fantata.com',
            'display_name' => 'Second User',
        ]));

        $this->assertSame('fid_second', $user->fantata_id);
        $this->assertSame('second@fantata.com', $user->email);
        $this->assertSame(1, User::count());
    }

    public function test_an_email_outside_the_allowlist_is_rejected(): void
    {
        Config::set('fantata.allowed_emails', 'chris@fantata.com');

        $this->expectException(RuntimeException::class);

        User::fromFantataId($this->identity(['email' => 'stranger@example.com']));
    }

    public function test_login_screen_offers_passkey_when_enabled(): void
    {
        Config::set('fantata.auth', true);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in with a passkey');
    }

    public function test_login_screen_is_password_only_when_disabled(): void
    {
        Config::set('fantata.auth', false);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Sign in with a passkey');
    }
}
