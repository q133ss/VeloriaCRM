<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            // claim: the hand-over link; landing: the booking page built for her.
            $table->string('kind', 16);
            // Hash of ip + user agent: counts distinct visitors without storing the ip.
            $table->string('visitor', 40);
            $table->boolean('is_bot')->default(false);
            $table->string('referer', 255)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['prospect_id', 'kind', 'is_bot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_visits');
    }
};
