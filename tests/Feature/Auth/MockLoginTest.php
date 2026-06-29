<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The mock-login shortcut is a local-only dev affordance. The suite runs
     * with APP_ENV=testing and MOCK_LOGIN unset — the same gate state as
     * production — so the route must not even be registered here. This is the
     * regression guard that keeps the auth bypass from ever reaching prod.
     */
    public function test_dev_login_route_is_absent_outside_local(): void
    {
        $this->assertFalse(app()->environment('local'));
        $this->assertFalse((bool) config('app.mock_login'));

        User::factory()->create();

        $this->post('/dev/login')->assertNotFound();
    }
}
