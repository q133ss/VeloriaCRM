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
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('revenue',10,2);
            $table->integer('hours_booked');
            $table->decimal('repeat_rate',5,2)->comment('Доля повторных визитов');
            $table->decimal('margin_per_hour',10,2);
            $table->integer('no_show_count');
            $table->timestamps();
            $table->unique(['user_id','date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
    }
};
