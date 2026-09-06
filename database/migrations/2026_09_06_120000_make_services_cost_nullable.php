<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `services.cost` was created NOT NULL, but the rest of the application has
 * always treated it as optional: the form request validates it as `nullable`,
 * the controller writes `$validated['cost'] ?? null`, and Service::margin()
 * returns null when no cost is recorded. The result was a 500 from
 * POST /api/v1/services whenever a service was created without a cost, which is
 * the normal case for someone adding their first service.
 *
 * Null here means "cost not recorded" and must stay distinct from 0, which would
 * claim the service costs nothing and quietly report a 100% margin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing rows may hold null, so they are zeroed before the column
        // becomes required again.
        DB::table('services')->whereNull('cost')->update(['cost' => 0]);

        Schema::table('services', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->nullable(false)->change();
        });
    }
};
