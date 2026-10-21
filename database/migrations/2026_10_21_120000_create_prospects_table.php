<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            // Null once an unclaimed prospect expires and its account is deleted.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('landing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 16)->default('vk');
            $table->string('external_id', 128);
            $table->string('url')->nullable();
            $table->string('title')->nullable();
            $table->string('booking_service', 64)->nullable();
            $table->string('status', 16)->default('built');
            $table->string('services_source', 16)->nullable();
            $table->text('password')->nullable();
            $table->string('claim_token', 64)->nullable()->unique();
            $table->json('source')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
