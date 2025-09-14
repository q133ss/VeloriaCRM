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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('work_days')->nullable()->comment('Рабочие дни недели');
            $table->jsonb('work_hours')->nullable()->comment('Рабочие часы по дням');
            $table->jsonb('cancel_policy')->nullable()->comment('Политика отмен');
            $table->jsonb('deposit_policy')->nullable()->comment('Политика предоплат');
            $table->jsonb('notification_prefs')->nullable()->comment('Предпочтения каналов уведомлений');
            $table->jsonb('branding')->nullable()->comment('Цвета/логотипы витрины');
            $table->string('address')->nullable();
            $table->jsonb('map_point')->nullable()->comment('Данные точки на Яндекс.Картах');
            $table->string('smsaero_email')->nullable();
            $table->string('smsaero_api_key')->nullable();
            $table->string('yookassa_shop_id')->nullable();
            $table->string('yookassa_secret_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
