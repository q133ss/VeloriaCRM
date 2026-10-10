<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The unguessable part of the address a client comes back to after paying.
        Schema::table('payments', function (Blueprint $table) {
            $table->string('return_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['return_token']);
            $table->dropColumn('return_token');
        });
    }
};
