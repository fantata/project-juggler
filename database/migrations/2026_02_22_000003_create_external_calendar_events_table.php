<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('uid')->index();
            $table->string('title');
            $table->datetime('start');
            $table->datetime('end')->nullable();
            $table->string('location')->nullable();
            $table->boolean('all_day')->default(false);
            $table->timestamps();

            $table->unique(['source', 'uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_calendar_events');
    }
};
