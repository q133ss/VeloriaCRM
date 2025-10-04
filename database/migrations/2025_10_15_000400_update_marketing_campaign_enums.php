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
            DB::statement("ALTER TYPE marketing_campaigns_segment_enum ADD VALUE IF NOT EXISTS 'selected'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE marketing_campaigns MODIFY channel ENUM('sms','email','telegram','whatsapp')");
            DB::statement("ALTER TABLE marketing_campaigns MODIFY segment ENUM('all','new','loyal','sleeping','by_service','by_master','custom') DEFAULT 'all'");
        }
    }
};
