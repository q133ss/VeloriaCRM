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
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('Уникальный идентификатор заказа');
            $table->foreignId('master_id')
                ->constrained('users')
                ->comment('Идентификатор мастера, закрепленного за заказом');
            $table->foreignId('client_id')
                ->constrained('users')
                ->comment('Идентификатор клиента, оформившего заказ');
            $table->json('services')->comment('Список выбранных услуг и их параметры');
            $table->timestamp('scheduled_at')->comment('Запланированная дата и время визита');
            $table->timestamp('actual_started_at')->nullable()->comment('Фактическое время начала обслуживания');
            $table->text('note')->nullable()->comment('Заметка для мастера');
            $table->unsignedInteger('duration')->nullable()->comment('Фактическая длительность обслуживания в минутах');
            $table->unsignedInteger('duration_forecast')->nullable()->comment('Прогнозируемая длительность обслуживания в минутах');
            $table->timestamp('actual_finished_at')->nullable()->comment('Фактическое время завершения обслуживания');
            $table->decimal('total_price', 10, 2)->comment('Итоговая стоимость заказа');
            $table->enum('status', [
                'new',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'no_show',
            ])->default('new')->comment('Текущий статус заказа');
            $table->timestamp('rescheduled_from')->nullable()->comment('Изначальная дата записи перед переносом');
            $table->unsignedInteger('reschedule_count')->default(0)->comment('Количество переносов записи');
            $table->text('cancellation_reason')->nullable()->comment('Причина отмены заказа');
            $table->integer('client_lateness')->nullable()->comment('Опоздание клиента в минутах');
            $table->timestamp('confirmed_at')->nullable()->comment('Дата и время подтверждения заказа');
            $table->timestamp('cancelled_at')->nullable()->comment('Дата и время отмены заказа');
            $table->timestamp('reminded_at')->nullable()->comment('Дата и время отправки напоминания');
            $table->string('payment_method', 20)->nullable()->comment('Способ оплаты заказа');
            $table->string('payment_status', 20)->nullable()->comment('Статус оплаты заказа');
            $table->unsignedInteger('duration_optimistic')->nullable()->comment('Оптимистичный прогноз длительности в минутах');
            $table->unsignedInteger('duration_pessimistic')->nullable()->comment('Пессимистичный прогноз длительности в минутах');
            $table->decimal('confidence_level', 3, 2)->nullable()->comment('Уверенность прогноза длительности (0-1)');
            $table->string('source', 20)->default('manual')->comment('Источник заказа (manual, vk, telegram и т.д.)');
            $table->decimal('prepaid_amount', 10, 2)->default(0)->comment('Сумма предоплаты по заказу');
            $table->boolean('is_reminder_sent')->default(false)->comment('Флаг отправки напоминания клиенту');
            $table->unsignedTinyInteger('complexity_level')->nullable()->comment('Оценка сложности услуги (1-5)');
            $table->json('recommended_services')->nullable()->comment('Рекомендованные услуги от ИИ');
            $table->timestamp('created_at')->nullable()->comment('Дата и время создания заказа');
            $table->timestamp('updated_at')->nullable()->comment('Дата и время последнего обновления заказа');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
