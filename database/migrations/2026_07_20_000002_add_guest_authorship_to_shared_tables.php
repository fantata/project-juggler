<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Client-board visitors aren't Users — they're identified only by a random
     * per-browser key (cookie) and a display name they type once. These columns
     * let cards, comments, reactions and files be authored by a guest instead of
     * a user_id, while keeping the existing user-authored rows working.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->string('guest_key', 64)->nullable()->index();
            $table->string('guest_name')->nullable();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->string('guest_key', 64)->nullable()->index();
            $table->string('guest_name')->nullable();
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->string('guest_key', 64)->nullable()->index();
            $table->string('guest_name')->nullable();
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->string('guest_key', 64)->nullable()->index();
            // One of each reaction per guest per item — mirrors the per-user
            // unique index already on this table. NULLs are distinct in MySQL,
            // so user-authored rows (guest_key null) are unaffected.
            $table->unique(['reactable_type', 'reactable_id', 'guest_key', 'emoji'], 'reactions_guest_unique');
        });

        // Guest writes have no user_id — relax the NOT NULL on the two tables
        // that still require it (attachments is already nullable).
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['guest_key', 'guest_name']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['guest_key', 'guest_name']);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['guest_key', 'guest_name']);
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropUnique('reactions_guest_unique');
            $table->dropColumn('guest_key');
        });
    }
};
