<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('priority')->nullable()->default(null)->change();
        });

        // Set existing 0 priorities to NULL
        DB::table('projects')->where('priority', 0)->update(['priority' => null]);
    }

    public function down(): void
    {
        // Set NULL back to 0
        DB::table('projects')->whereNull('priority')->update(['priority' => 0]);

        Schema::table('projects', function (Blueprint $table) {
            $table->integer('priority')->default(0)->change();
        });
    }
};
