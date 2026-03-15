<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('weekly_useful_digest_enabled')
                ->default(false)
                ->after('daily_post_ideas_preferences');
            $table->string('weekly_useful_digest_channel')
                ->default('platform')
                ->after('weekly_useful_digest_enabled');
            $table->text('weekly_useful_digest_preferences')
                ->nullable()
                ->after('weekly_useful_digest_channel');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'weekly_useful_digest_enabled',
                'weekly_useful_digest_channel',
                'weekly_useful_digest_preferences',
            ]);
        });
    }
};
