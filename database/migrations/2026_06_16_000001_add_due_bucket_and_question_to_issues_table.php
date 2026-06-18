<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Loose deadline grouping for Mission Control. Nullable and additive
            // so the MCP server and existing issue flows are unaffected.
            $table->string('due_bucket')->nullable()->after('urgency');
            // Lets an item stand out as a question on the shared screen.
            $table->boolean('is_question')->default(false)->after('due_bucket');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['due_bucket', 'is_question']);
        });
    }
};
