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
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('start_confirmation_notified_at')
                ->nullable()
                ->after('reminded_at')
                ->comment('Дата и время отправки напоминания мастеру подтвердить начало');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('action_url')
                ->nullable()
                ->after('message')
                ->comment('Ссылка, которую нужно открыть при переходе по уведомлению');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('start_confirmation_notified_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('action_url');
        });
    }
};
