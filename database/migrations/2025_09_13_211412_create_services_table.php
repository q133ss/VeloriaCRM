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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('service_categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('base_price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->integer('duration_min');
            $table->jsonb('upsell_suggestions')->nullable()->comment('ID сопутствующих услуг');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
