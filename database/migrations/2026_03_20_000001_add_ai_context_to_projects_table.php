<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->longText('ai_context')->nullable()->after('notes');
            $table->timestamp('ai_context_updated_at')->nullable()->after('ai_context');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['ai_context', 'ai_context_updated_at']);
        });
    }
};
