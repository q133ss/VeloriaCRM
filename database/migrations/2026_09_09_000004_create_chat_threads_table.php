<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('last_message_at')->nullable();
            $table->string('last_message_sender_type')->nullable();
            $table->timestampTz('master_read_at')->nullable();
            $table->timestampTz('client_read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'client_id']);
            $table->index(['user_id', 'last_message_at'], 'chat_threads_inbox_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
