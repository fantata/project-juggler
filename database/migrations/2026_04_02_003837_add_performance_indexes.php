<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index('status');
            $table->index('deadline');
            $table->index('last_touched_at');
            $table->index('money_status');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->index('status');
            $table->index('urgency');
            $table->index(['project_id', 'status']);
        });

        Schema::table('ics_feeds', function (Blueprint $table) {
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['deadline']);
            $table->dropIndex(['last_touched_at']);
            $table->dropIndex(['money_status']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['urgency']);
            $table->dropIndex(['project_id', 'status']);
        });

        Schema::table('ics_feeds', function (Blueprint $table) {
            $table->dropIndex(['is_enabled']);
        });
    }
};
