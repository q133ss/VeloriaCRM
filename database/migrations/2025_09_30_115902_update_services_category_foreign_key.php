<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('service_categories')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('service_categories')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
