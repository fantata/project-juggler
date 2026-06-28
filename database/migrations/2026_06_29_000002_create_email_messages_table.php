<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();

            // IMAP identity, for dedupe + incremental sync.
            $table->unsignedInteger('uid');
            $table->string('message_id')->nullable();
            $table->string('in_reply_to')->nullable();

            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            // The address it arrived at — used as the reply "from" identity.
            $table->string('to_email')->nullable();
            $table->string('subject')->nullable();

            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->string('folder')->default('INBOX');
            $table->boolean('is_read')->default(false);

            // AI triage output.
            $table->boolean('is_junk')->default(false);
            $table->string('priority')->nullable();       // high | normal | low
            $table->boolean('action_needed')->default(false);
            $table->text('ai_summary')->nullable();
            $table->longText('suggested_reply')->nullable();
            $table->timestamp('triaged_at')->nullable();

            $table->timestamps();

            $table->unique(['email_account_id', 'uid']);
            $table->index(['email_account_id', 'is_junk', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
