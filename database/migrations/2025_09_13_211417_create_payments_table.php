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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('yookassa')->comment('Код платёжного провайдера');
            $table->string('provider_payment_id')->comment('ID платежа в провайдере');
            $table->decimal('amount',10,2);
            $table->enum('status',['pending','succeeded','failed','refunded']);
            $table->jsonb('metadata')->nullable()->comment('Ответ провайдера');
            $table->timestampTz('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
