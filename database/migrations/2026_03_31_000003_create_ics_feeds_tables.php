<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ics_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('url');
            $table->string('color')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('last_synced_at')->nullable();
            $table->string('last_sync_status')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->integer('sync_interval_minutes')->default(60);
            $table->timestamps();
        });

        Schema::create('ics_feed_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ics_feed_id')->constrained()->cascadeOnDelete();
            $table->string('uid');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->text('raw_vevent')->nullable();
            $table->boolean('is_backgrounded')->default(false);
            $table->boolean('is_relevant')->default(false);
            $table->string('relevance_note')->nullable();
            $table->timestamps();

            $table->unique(['ics_feed_id', 'uid']);
            $table->index('starts_at');
        });

        Schema::create('ics_feed_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ics_feed_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->string('operator');
            $table->string('value');
            $table->string('action');
            $table->string('action_value')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ics_feed_rules');
        Schema::dropIfExists('ics_feed_events');
        Schema::dropIfExists('ics_feeds');
    }
};
