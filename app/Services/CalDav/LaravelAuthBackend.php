<?php

namespace App\Services\CalDav;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class LaravelAuthBackend extends AbstractBasic
{
    protected function validateUserPass($username, $password)
    {
        $user = User::where('email', $username)->first();

        if ($user && Hash::check($password, $user->password)) {
            return true;
        }

        return false;
    }
}
