<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function created(User $user): void
    {
        $this->ensurePrincipal($user);
    }

    public function updated(User $user): void
    {
        $this->ensurePrincipal($user);
    }

    private function ensurePrincipal(User $user): void
    {
        $principalUri = 'principals/' . $user->email;

        $existing = DB::table('principals')->where('uri', $principalUri)->first();

        if ($existing) {
            DB::table('principals')->where('uri', $principalUri)->update([
                'email' => $user->email,
                'displayname' => $user->name,
            ]);
        } else {
            DB::table('principals')->insert([
                'uri' => $principalUri,
                'email' => $user->email,
                'displayname' => $user->name,
            ]);

            // Also create calendar-proxy principals for delegation support
            DB::table('principals')->insert([
                'uri' => $principalUri . '/calendar-proxy-read',
                'email' => null,
                'displayname' => null,
            ]);
            DB::table('principals')->insert([
                'uri' => $principalUri . '/calendar-proxy-write',
                'email' => null,
                'displayname' => null,
            ]);
        }
    }
}
