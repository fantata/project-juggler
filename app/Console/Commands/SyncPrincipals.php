<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPrincipals extends Command
{
    protected $signature = 'caldav:sync-principals';
    protected $description = 'Sync existing Laravel users to CalDAV principals table';

    public function handle(): int
    {
        $users = User::all();

        foreach ($users as $user) {
            $principalUri = 'principals/' . $user->email;

            $existing = DB::table('principals')->where('uri', $principalUri)->first();

            if (!$existing) {
                DB::table('principals')->insert([
                    'uri' => $principalUri,
                    'email' => $user->email,
                    'displayname' => $user->name,
                ]);

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

                $this->info("Created principal for {$user->email}");
            } else {
                $this->info("Principal already exists for {$user->email}");
            }
        }

        $this->info('Done.');
        return 0;
    }
}
