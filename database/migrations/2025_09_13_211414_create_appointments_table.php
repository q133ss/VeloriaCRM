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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->jsonb('service_ids')->comment('ID услуг');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->enum('status', ['scheduled','confirmed','completed','cancelled'])->default('scheduled');
            $table->decimal('deposit_amount',10,2)->default(0);
            $table->decimal('risk_no_show',3,2)->nullable()->comment('0..1 риск неявки');
            $table->decimal('fit_score',3,2)->nullable()->comment('Индекс сложности/маржи');
            $table->jsonb('meta')->nullable()->comment('Доп. данные');
            $table->timestamps();
            $table->index(['user_id','starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
