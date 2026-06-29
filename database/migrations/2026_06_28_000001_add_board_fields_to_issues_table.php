<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Who's it for — the collaboration spine (Chris / Danny / nobody).
            $table->foreignId('assignee_id')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            // What flavour of "thing" this card is.
            $table->string('kind')->default('task')->after('assignee_id');
            // Kanban placement: which column + order within it.
            $table->string('board_column')->nullable()->after('kind');
            $table->unsignedInteger('position')->default(0)->after('board_column');
            $table->index(['project_id', 'board_column', 'position']);
        });

        // Seed existing issues into columns from their status so the board
        // isn't empty on first load. New idea/request cards start in Ideas
        // via the app, not here.
        DB::table('issues')->where('status', 'done')->update(['board_column' => 'done']);
        DB::table('issues')->where('status', 'in_progress')->update(['board_column' => 'doing']);
        DB::table('issues')->whereNull('board_column')->update(['board_column' => 'todo']);
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropIndex(['project_id', 'board_column', 'position']);
            $table->dropColumn(['kind', 'board_column', 'position']);
        });
    }
};
