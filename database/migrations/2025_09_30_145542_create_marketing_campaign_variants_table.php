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
        Schema::create('marketing_campaign_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->string('label', 10);
            $table->string('subject')->nullable();
            $table->text('content');
            $table->unsignedInteger('sample_size')->nullable();
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['campaign_id', 'label']);
        });

        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->foreign('winning_variant_id')
                ->references('id')
                ->on('marketing_campaign_variants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->dropForeign(['winning_variant_id']);
        });

        Schema::dropIfExists('marketing_campaign_variants');
    }
};
