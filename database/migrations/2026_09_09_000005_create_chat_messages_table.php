<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_thread_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
            $table->index(['chat_thread_id', 'created_at'], 'chat_messages_thread_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
