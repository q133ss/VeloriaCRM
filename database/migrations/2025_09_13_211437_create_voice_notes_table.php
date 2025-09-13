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
        Schema::create('voice_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorder_id')->constrained('users');
            $table->string('audio_path');
            $table->text('transcript')->nullable();
            $table->jsonb('notes')->nullable()->comment('Структурированные сущности/задачи');
            $table->enum('status',['processing','done','failed'])->default('processing');
            $table->string('language',5)->nullable()->comment('ISO код языка');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_notes');
    }
};
