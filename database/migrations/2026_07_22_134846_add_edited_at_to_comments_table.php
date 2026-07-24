<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Mirrors issues.edited_at — set only when the author rewrites the
            // comment, so "edited" doesn't appear for unrelated row updates.
            $table->timestamp('edited_at')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });
    }
};
