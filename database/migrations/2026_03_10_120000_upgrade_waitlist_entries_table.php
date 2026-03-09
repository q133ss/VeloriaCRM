<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->foreignId('client_user_id')->nullable()->after('client_id')->constrained('users')->nullOnDelete();
            $table->json('preferred_dates')->nullable()->after('preferred_slots');
            $table->json('preferred_time_windows')->nullable()->after('preferred_dates');
            $table->unsignedTinyInteger('flexibility_days')->default(0)->after('preferred_time_windows');
            $table->unsignedTinyInteger('priority_manual')->default(0)->after('priority');
            $table->string('source', 40)->default('manual')->after('priority_manual');
            $table->text('notes')->nullable()->after('source');
            $table->timestamp('last_offered_at')->nullable()->after('notes');
            $table->timestamp('expires_at')->nullable()->after('last_offered_at');
            $table->timestamp('matched_slot')->nullable()->after('expires_at');
            $table->decimal('match_score', 7, 2)->nullable()->after('matched_slot');
            $table->json('match_reasons')->nullable()->after('match_score');
            $table->json('meta')->nullable()->after('match_reasons');
        });
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
            $table->dropColumn([
                'preferred_dates',
                'preferred_time_windows',
                'flexibility_days',
                'priority_manual',
                'source',
                'notes',
                'last_offered_at',
                'expires_at',
                'matched_slot',
                'match_score',
                'match_reasons',
                'meta',
            ]);
        });
    }
};
