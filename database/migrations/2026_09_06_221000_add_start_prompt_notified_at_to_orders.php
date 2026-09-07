<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks that the "your next client is due, start the timer?" nudge has gone out,
 * so a rescheduled booking cannot send it twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('start_prompt_notified_at')->nullable()->after('start_confirmation_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('start_prompt_notified_at');
        });
    }
};
