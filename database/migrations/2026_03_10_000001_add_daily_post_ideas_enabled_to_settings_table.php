<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('daily_post_ideas_enabled')
                ->default(false)
                ->after('reminder_message')
                ->comment('Daily AI ideas for Telegram or platform posts');
            $table->string('daily_post_ideas_channel')
                ->nullable()
                ->after('daily_post_ideas_enabled')
                ->comment('Preferred channel for daily post ideas');
            $table->text('daily_post_ideas_preferences')
                ->nullable()
                ->after('daily_post_ideas_channel')
                ->comment('Topics and tone preferences for daily AI post ideas');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'daily_post_ideas_enabled',
                'daily_post_ideas_channel',
                'daily_post_ideas_preferences',
            ]);
        });
    }
};
