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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->date('birthday')->nullable();
            $table->jsonb('tags')->nullable()->comment('Массив тегов');
            $table->jsonb('allergies')->nullable()->comment('Список аллергий');
            $table->jsonb('preferences')->nullable()->comment('Предпочтения клиента');
            $table->text('notes')->nullable();
            $table->timestampTz('last_visit_at')->nullable();
            $table->string('loyalty_level')->nullable()->comment('Уровень лояльности');
            $table->timestamps();
            $table->unique(['user_id','phone']);
            $table->unique(['user_id','email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
