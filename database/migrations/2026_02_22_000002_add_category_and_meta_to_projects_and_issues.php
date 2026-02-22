<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('category')->default('consultancy');
            $table->json('meta')->nullable();
            $table->string('calendar_id')->nullable();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->json('meta')->nullable();
            $table->string('calendar_event_uri')->nullable();
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('due_at')->nullable();
        });

        // Migrate existing projects to consultancy category
        \DB::table('projects')->update(['category' => 'consultancy']);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['category', 'meta', 'calendar_id']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['meta', 'calendar_event_uri', 'scheduled_at', 'due_at']);
        });
    }
};
