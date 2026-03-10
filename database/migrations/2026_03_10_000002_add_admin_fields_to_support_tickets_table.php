<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('priority')->default('normal')->after('status');
            $table->string('category')->nullable()->after('priority');
            $table->string('source')->default('in_app')->after('category');
            $table->timestamp('first_responded_at')->nullable()->after('last_message_at');
            $table->timestamp('closed_at')->nullable()->after('first_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'priority',
                'category',
                'source',
                'first_responded_at',
                'closed_at',
            ]);
        });
    }
};
