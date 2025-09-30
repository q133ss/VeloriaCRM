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
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('name');
            $table->enum('channel', ['sms', 'email', 'telegram', 'whatsapp']);
            $table->enum('segment', [
                'all',
                'new',
                'loyal',
                'sleeping',
                'by_service',
                'by_master',
                'custom',
            ])->default('all');
            $table->json('segment_filters')->nullable();
            $table->boolean('is_ab_test')->default(false);
            $table->string('status')->default('draft');
            $table->timestampTz('scheduled_at')->nullable();
            $table->string('subject')->nullable();
            $table->text('content');
            $table->unsignedInteger('test_group_size')->nullable();
            $table->unsignedBigInteger('winning_variant_id')->nullable();
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'segment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
