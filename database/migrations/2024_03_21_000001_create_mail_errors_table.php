<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_log_id')->constrained('mail_logs')->onDelete('cascade');
            $table->text('error_message');
            $table->string('error_code')->nullable();
            $table->text('stack_trace')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_errors');
    }
}; 