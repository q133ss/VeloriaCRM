<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'status')) {
                $table->dropIndex('promotions_user_id_status_index');
            }
        });

        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'value')) {
                $table->dropColumn('value');
            }
            if (Schema::hasColumn('promotions', 'audience')) {
                $table->dropColumn('audience');
            }
            if (Schema::hasColumn('promotions', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->float('percent')->nullable()->after('type');
            $table->foreignId('service_id')->nullable()->after('percent')->constrained()->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->after('service_id')->constrained()->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['percent', 'service_id', 'service_category_id', 'archived_at']);
            $table->float('value')->nullable()->after('type');
            $table->string('audience')->nullable()->after('promo_code');
            $table->string('status')->default('draft')->after('ends_at');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });
    }
};
