<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            // Owner — the mailbox is private to this user (Danny never sees it).
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Fastmail", "Outline (Zen)"

            // IMAP (read).
            $table->string('imap_host');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('imap_username');

            // SMTP (send). Username defaults to the IMAP one when blank.
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->default(465);
            $table->string('smtp_username')->nullable();

            // One encrypted secret used for both unless SMTP differs.
            $table->text('password'); // encrypted cast on the model

            // Sending identities on this account (Fastmail wears several hats).
            $table->json('from_addresses')->nullable();

            $table->string('color')->nullable();   // UI tag
            $table->boolean('is_active')->default(true);

            // Incremental-sync bookmark.
            $table->unsignedInteger('last_uid')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
