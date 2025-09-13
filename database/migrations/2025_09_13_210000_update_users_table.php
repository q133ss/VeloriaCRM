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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('timezone')->default('UTC')->after('password');
            $table->string('locale', 5)->default('en')->after('timezone');

            $table->dropUnique('users_email_unique');
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'phone']);
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_tenant_id_email_unique');
            $table->dropUnique('users_tenant_id_phone_unique');
            $table->dropIndex('users_tenant_id_index');
            $table->dropIndex('users_created_at_index');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'phone', 'timezone', 'locale']);
            $table->unique('email');
        });
    }
};
