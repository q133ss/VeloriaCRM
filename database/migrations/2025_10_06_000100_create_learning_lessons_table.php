<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_category_id')->constrained('learning_categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(5);
            $table->string('format')->default('micro');
            $table->json('content')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_lessons');
    }
};
