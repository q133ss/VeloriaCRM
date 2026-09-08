<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The missing half of the cashback rework.
 *
 * `2025_10_01_000002_update_promotions_for_cashback` reshaped the table around
 * cashback — it added `percent`, `service_id` and `service_category_id` and
 * dropped `value` — but left the `type` enum from the original migration in
 * place. Since then the form, the filter, the controller's option list, the
 * marketing screen and both language files all speak the cashback vocabulary,
 * while the column still only accepts the discount one. The two sets do not
 * overlap, so every promotion the form allowed was rejected by the column.
 */
return new class extends Migration
{
    /** What the form, the UI and the translations have been using all along. */
    private const CASHBACK_TYPES = ['order_percent', 'service_percent', 'category_percent', 'free_service'];

    private const DISCOUNT_TYPES = ['percentage', 'fixed', 'gift', 'bogo', 'loyalty'];

    /** Rows written before the rework, mapped onto their nearest cashback kind. */
    private const FORWARD_MAP = [
        'percentage' => 'order_percent',
        'fixed' => 'order_percent',
        'gift' => 'free_service',
        'bogo' => 'free_service',
        'loyalty' => 'order_percent',
    ];

    private const BACKWARD_MAP = [
        'order_percent' => 'percentage',
        'service_percent' => 'percentage',
        'category_percent' => 'percentage',
        'free_service' => 'gift',
    ];

    public function up(): void
    {
        $this->rewrite(self::FORWARD_MAP, self::CASHBACK_TYPES);
    }

    public function down(): void
    {
        $this->rewrite(self::BACKWARD_MAP, self::DISCOUNT_TYPES);
    }

    /**
     * Widen, rewrite, narrow — in that order. The old and the new vocabulary do
     * not overlap, so the column has to stop rejecting both of them before any
     * row can cross over.
     *
     * @param  array<string, string>  $map
     * @param  array<int, string>  $allowed
     */
    private function rewrite(array $map, array $allowed): void
    {
        $driver = DB::getDriverName();
        $list = "'" . implode("','", $allowed) . "'";

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE promotions MODIFY type VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            $this->dropCheckConstraint('promotions', 'type');
        }

        foreach ($map as $from => $to) {
            DB::table('promotions')->where('type', $from)->update(['type' => $to]);
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE promotions MODIFY type ENUM({$list})");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE promotions ADD CONSTRAINT promotions_type_check CHECK (type IN ({$list}))");
        }
    }

    private function dropCheckConstraint(string $table, string $column): void
    {
        $constraints = DB::select(
            <<<'SQL'
SELECT con.conname
FROM pg_constraint AS con
INNER JOIN pg_class AS rel ON rel.oid = con.conrelid
INNER JOIN pg_namespace AS nsp ON nsp.oid = rel.relnamespace
INNER JOIN pg_attribute AS att ON att.attrelid = con.conrelid AND att.attnum = ANY(con.conkey)
WHERE con.contype = 'c'
  AND rel.relname = ?
  AND att.attname = ?
SQL,
            [$table, $column]
        );

        foreach ($constraints as $constraint) {
            DB::statement('ALTER TABLE "' . $table . '" DROP CONSTRAINT IF EXISTS "' . $constraint->conname . '"');
        }
    }
};
