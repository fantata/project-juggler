<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'app:create-user
                            {--name= : The person\'s display name}
                            {--email= : Their login email}
                            {--password= : Their password (you will be prompted if omitted)}';

    protected $description = 'Create a Juggler login. Public registration is closed, so accounts are made here.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with the email {$email} already exists.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password');

        if (blank($password)) {
            $this->error('A password is required.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Created login for {$user->name} ({$user->email}).");

        return self::SUCCESS;
    }
}
