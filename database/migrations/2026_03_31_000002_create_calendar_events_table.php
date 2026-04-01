<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->dateTime('recurrence_until')->nullable();
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->string('color')->nullable();
            $table->string('uid')->unique();
            $table->timestamps();

            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
