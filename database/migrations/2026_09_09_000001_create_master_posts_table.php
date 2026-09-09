<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('image_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_published', 'published_at'], 'master_posts_feed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_posts');
    }
};
