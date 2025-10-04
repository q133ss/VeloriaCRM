<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE marketing_campaigns MODIFY channel ENUM('sms','email','whatsapp')");
            DB::statement("ALTER TABLE marketing_campaigns MODIFY segment ENUM('all','new','loyal','sleeping','by_service','by_master','custom','selected') DEFAULT 'all'");
        } elseif ($driver === 'pgsql') {
            $this->dropCheckConstraint('marketing_campaigns', 'channel');
            $this->dropCheckConstraint('marketing_campaigns', 'segment');

            DB::statement("ALTER TABLE marketing_campaigns ADD CONSTRAINT marketing_campaigns_channel_check CHECK (channel IN ('sms','email','whatsapp'))");
            DB::statement("ALTER TABLE marketing_campaigns ADD CONSTRAINT marketing_campaigns_segment_check CHECK (segment IN ('all','new','loyal','sleeping','by_service','by_master','custom','selected'))");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE marketing_campaigns MODIFY channel ENUM('sms','email','telegram','whatsapp')");
            DB::statement("ALTER TABLE marketing_campaigns MODIFY segment ENUM('all','new','loyal','sleeping','by_service','by_master','custom') DEFAULT 'all'");
        } elseif ($driver === 'pgsql') {
            $this->dropCheckConstraint('marketing_campaigns', 'channel');
            $this->dropCheckConstraint('marketing_campaigns', 'segment');

            DB::statement("ALTER TABLE marketing_campaigns ADD CONSTRAINT marketing_campaigns_channel_check CHECK (channel IN ('sms','email','telegram','whatsapp'))");
            DB::statement("ALTER TABLE marketing_campaigns ADD CONSTRAINT marketing_campaigns_segment_check CHECK (segment IN ('all','new','loyal','sleeping','by_service','by_master','custom'))");
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
