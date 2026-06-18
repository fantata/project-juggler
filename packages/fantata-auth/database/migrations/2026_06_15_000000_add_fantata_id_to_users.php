<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local projection of the FantataID. Credentials (passkeys) live in FantataID;
 * the app stores only the mapping + denormalised display fields. Password
 * becomes optional (passwordless), mirroring the Pulsinator/Keycloak pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fantata_id')->nullable()->unique()->after('id');
            if (Schema::hasColumn('users', 'password')) {
                $table->string('password')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fantata_id');
        });
    }
};
