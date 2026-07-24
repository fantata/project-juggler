<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Set when the author rewrites a card, so the board can show an
            // "edited" marker. Deliberately NOT updated_at: that moves whenever
            // anyone touches status, column or position, which isn't an edit.
            $table->timestamp('edited_at')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });
    }
};
