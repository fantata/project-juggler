<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_logs', function (Blueprint $table) {
            $table->decimal('hours', 5, 2)->nullable()->after('entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_logs', function (Blueprint $table) {
            $table->dropColumn('hours');
        });
    }
};
