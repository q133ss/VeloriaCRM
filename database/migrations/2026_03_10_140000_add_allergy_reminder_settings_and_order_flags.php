<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('allergy_reminder_enabled')
                ->default(false)
                ->after('reminder_message');
            $table->unsignedSmallInteger('allergy_reminder_minutes')
                ->default(15)
                ->after('allergy_reminder_enabled');
            $table->jsonb('allergy_reminder_exclusions')
                ->nullable()
                ->after('allergy_reminder_minutes');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('allergy_reminder_sent_at')
                ->nullable()
                ->after('start_confirmation_notified_at');
            $table->timestamp('allergy_reminder_sent_for')
                ->nullable()
                ->after('allergy_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'allergy_reminder_sent_at',
                'allergy_reminder_sent_for',
            ]);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'allergy_reminder_enabled',
                'allergy_reminder_minutes',
                'allergy_reminder_exclusions',
            ]);
        });
    }
};
