<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `orders` shipped with nothing but a primary key, and Postgres does not index
 * foreign keys on its own. Every calendar request scans the table twice: once
 * by master and date range, once per client for history.
 */
return new class extends Migration
{
    /** CREATE INDEX CONCURRENTLY cannot run inside a transaction. */
    public $withinTransaction = false;

    private const INDEXES = [
        'orders_master_scheduled_idx' => ['master_id', 'scheduled_at'],
        'orders_master_client_status_idx' => ['master_id', 'client_id', 'status'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $name => $columns) {
            $this->createIndex($name, $columns);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::INDEXES) as $name) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");

                continue;
            }

            Schema::table('orders', function ($table) use ($name) {
                $table->dropIndex($name);
            });
        }
    }

    private function createIndex(string $name, array $columns): void
    {
        $columnList = implode(', ', $columns);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$name} ON orders ({$columnList})");

            return;
        }

        // SQLite (tests) and MySQL have no CONCURRENTLY and no IF NOT EXISTS here.
        Schema::table('orders', function ($table) use ($name, $columns) {
            $table->index($columns, $name);
        });
    }
};
