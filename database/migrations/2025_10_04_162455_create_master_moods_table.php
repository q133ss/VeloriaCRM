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
        Schema::create('master_moods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('mood_date');
            $table->string('mood');
            $table->string('mood_label');
            $table->timestamps();

            // В день хранится один ответ мастера — это упрощает аналитику и прогнозы нагрузки.
            $table->unique(['user_id', 'mood_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_moods');
    }
};
