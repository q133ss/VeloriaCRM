<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            $table->boolean('is_published')
                ->default(true)
                ->after('action');
            $table->boolean('is_featured')
                ->default(false)
                ->after('is_published');
            $table->unsignedInteger('sort_order')
                ->default(0)
                ->after('is_featured');
            $table->string('source_url')
                ->nullable()
                ->after('sort_order');
            $table->timestamp('published_at')
                ->nullable()
                ->after('source_url');
        });

        DB::table('learning_articles')
            ->whereNull('published_at')
            ->update([
                'published_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            $table->dropColumn([
                'is_published',
                'is_featured',
                'sort_order',
                'source_url',
                'published_at',
            ]);
        });
    }
};
