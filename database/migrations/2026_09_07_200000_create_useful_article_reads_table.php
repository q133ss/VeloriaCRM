<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Я это уже читала?» — вопрос, на который страница не могла ответить.
 *
 * A feed with no memory asks the master to hold the state herself, and she has
 * better things to remember. One row per person per post, written when she
 * opens it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('useful_article_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_article_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'learning_article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('useful_article_reads');
    }
};
