<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Unguessable capability URL for the client-facing board. Stored in
            // plaintext (unlike the hashed API/ICS tokens) on purpose: this is a
            // "anyone with the link" share URL Chris re-copies into emails, not a
            // secret credential — it needs to be re-readable, and it's revocable.
            $table->string('share_token', 64)->nullable()->unique();
            $table->boolean('share_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['share_token', 'share_enabled']);
        });
    }
};
