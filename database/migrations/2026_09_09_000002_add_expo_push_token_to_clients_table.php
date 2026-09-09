<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('expo_push_token')->nullable()->after('client_user_id');
            // Separate from the row's own `updated_at`, which a master editing this
            // client's notes/tags also bumps — this column says only when the push
            // token itself last changed, so staleness can be judged on its own.
            $table->timestampTz('expo_push_token_updated_at')->nullable()->after('expo_push_token');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['expo_push_token', 'expo_push_token_updated_at']);
        });
    }
};
