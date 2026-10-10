<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Set on every authenticated client-portal request (see
            // EnsureSanctumTokenIsClient), not on card creation — unlike
            // `client_user_id`, which is just the booking identity link and can be
            // non-null for someone who has never opened the app. Null here means
            // the client has never actually logged in.
            $table->timestampTz('last_seen_at')->nullable()->after('expo_push_token_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
