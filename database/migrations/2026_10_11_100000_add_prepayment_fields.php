<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // null = follow the master's general rule; none = never ask for this service.
            $table->string('prepay_mode', 10)->nullable();
            $table->decimal('prepay_value', 10, 2)->nullable();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('prepay_override', 10)->default('inherit'); // inherit | always | never
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('prepay_expires_at')->nullable();
            $table->json('prepay_rule')->nullable();
            $table->index(['payment_status', 'prepay_expires_at']);
        });

        Schema::create('prepayment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 10); // period | weekday
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('weekdays')->nullable(); // 0 (Sun) .. 6 (Sat)
            $table->string('mode', 10); // fixed | percent
            $table->decimal('value', 10, 2);
            $table->json('service_ids')->nullable(); // null = every service
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        // payments was created for per-client payments and never written to.
        // A booking prepayment hangs on an order, and ЮKassa has more states
        // than the old enum allowed.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
            $table->foreignId('client_id')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('client_id')->constrained('orders')->nullOnDelete();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->text('confirmation_url')->nullable();

            $table->unique('provider_payment_id');
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_payment_id']);
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn(['refunded_amount', 'confirmation_url']);
        });

        Schema::dropIfExists('prepayment_rules');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'prepay_expires_at']);
            $table->dropColumn(['prepay_expires_at', 'prepay_rule']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('prepay_override');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['prepay_mode', 'prepay_value']);
        });
    }
};
