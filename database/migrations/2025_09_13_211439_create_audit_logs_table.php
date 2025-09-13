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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('ID мастера');
            $table->foreignId('actor_id')->nullable()->constrained('users')->comment('Пользователь, совершивший действие');
            $table->string('action')->comment('Тип действия');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('ID объекта');
            $table->string('subject_type')->nullable()->comment('Класс модели объекта');
            $table->index(['subject_type', 'subject_id']);
            $table->ipAddress('ip_address')->nullable()->comment('IP адрес');
            $table->string('user_agent')->nullable()->comment('User-Agent клиента');
            $table->jsonb('meta')->nullable()->comment('Доп. сведения');
            $table->timestampTz('created_at')->comment('Время события');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
