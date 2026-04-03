<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('raw_email');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->text('raw_email')->nullable()->after('urgency');
        });
    }
};
