<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Yes/no answer for a question card. Null = still pending.
            $table->string('answer')->nullable()->after('is_question');
            $table->timestamp('answered_at')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['answer', 'answered_at']);
        });
    }
};
