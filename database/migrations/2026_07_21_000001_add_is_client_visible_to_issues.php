<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Whether this card is shown on the project's public client board.
            // Fail-closed: default false so nothing reaches a client unless it's
            // deliberately opted in from the internal board.
            $table->boolean('is_client_visible')->default(false)->after('kind');
        });

        // Existing cards stay internal. They can be opted into the client board
        // one at a time from the internal board.
        DB::table('issues')->update(['is_client_visible' => false]);
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('is_client_visible');
        });
    }
};
