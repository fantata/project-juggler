<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['client', 'personal', 'speculative']);
            $table->enum('status', ['active', 'paused', 'blocked', 'complete', 'killed'])->default('active');
            $table->enum('money_status', ['paid', 'partial', 'awaiting', 'none', 'speculative'])->default('none');
            $table->decimal('money_value', 10, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->string('next_action')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_touched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
