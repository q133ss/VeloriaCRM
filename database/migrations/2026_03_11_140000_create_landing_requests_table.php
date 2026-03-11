<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_id')->constrained('landings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('client_name');
            $table->string('client_phone', 32);
            $table->string('client_email')->nullable();
            $table->date('preferred_date')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 40)->default('new');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['landing_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_requests');
    }
};
