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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('channel',['sms','email','telegram']);
            $table->enum('direction',['outbound','inbound'])->comment('Исходящее или входящее сообщение');
            $table->text('content');
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->enum('status',['queued','sent','failed'])->default('queued');
            $table->decimal('cost',10,2)->nullable()->comment('Стоимость у провайдера в RUB');
            $table->timestampTz('scheduled_at')->nullable()->comment('Время плановой отправки');
            $table->jsonb('meta')->nullable()->comment('Данные провайдера/статусы');
            $table->timestamps();
            $table->index(['user_id','channel','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
