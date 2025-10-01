<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->default('insight');
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('impact_text')->nullable();
            $table->json('action')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_recommendations');
    }
};
