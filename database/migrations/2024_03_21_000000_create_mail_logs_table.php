<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->nullable()->index(); // Message-ID header
            $table->string('type')->nullable()->index(); // marketing, support, etc.
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->text('to_email'); // JSON array for multiple recipients
            $table->text('cc')->nullable(); // JSON array
            $table->text('bcc')->nullable(); // JSON array
            $table->string('subject');
            $table->string('smtp_host');
            $table->string('smtp_username');
            $table->string('connection_name')->index();
            $table->string('status')->default('queued')->index(); // queued, sent, failed
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional data like template name, campaign ID, etc.
            $table->longText('mailable_data')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
    }
}; 