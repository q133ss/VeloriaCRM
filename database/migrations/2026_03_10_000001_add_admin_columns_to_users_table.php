<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('admin_role')->nullable()->after('is_admin');
            $table->string('status')->default('active')->after('admin_role');
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->text('admin_notes')->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin',
                'admin_role',
                'status',
                'suspended_at',
                'admin_notes',
            ]);
        });
    }
};
